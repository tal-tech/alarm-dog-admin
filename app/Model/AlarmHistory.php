<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Service\AlarmHistoryElastic;
use App\Support\Clickhouse\Builder;
use App\Support\Clickhouse\Clickhouse;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;
use stdClass;

class AlarmHistory extends Model
{
    /**
     * 告警级别常量定义.
     */

    // 通知
    public const LEVEL_NOTICE = 0;

    // 警告
    public const LEVEL_WARN = 1;

    // 错误
    public const LEVEL_ERROR = 2;

    // 紧急
    public const LEVEL_CRITI = 3;

    // 继承
    public const LEVEL_EXTEND = 9;

    // 数据源 1:mysql;2:clickhouse;
    public const SOURCE_TYPE_MYSQL = 1;

    public const SOURCE_TYPE_CH = 2;

    public $timestamps = false;

    // 列表（不包含继承类型）
    public static $levelsNoExtend = [
        self::LEVEL_NOTICE => '通知',
        self::LEVEL_WARN => '警告',
        self::LEVEL_ERROR => '错误',
        self::LEVEL_CRITI => '紧急',
    ];

    protected $table = 'alarm_history';

    protected $fillable = ['task_id', 'uuid', 'batch', 'metric', 'notice_time', 'level', 'ctn', 'receiver', 'type', 'created_at'];

    /**
     * @Inject
     * @var AlarmTaskPermission
     */
    protected $alarmTaskPermission;

    /**
     * 告警任务列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     * @param mixed $timerange
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     * @param mixed $sourceType
     * @param mixed $actionPage
     * @param mixed $firstId
     * @param mixed $lastId
     * @param null|mixed $tagId
     * @param null|mixed $user
     */
    public function list(
        $page = 1,
        $pageSize = 20,
        $search = null,
        $order = [],
        $timerange = [],
        $departmentId = null,
        $taskId = null,
        $sourceType = 1,
        $actionPage = 1,
        $firstId = -1,
        $lastId = -1,
        $tagId = null,
        $user = null
    ) {
        $params = [
            'page' => $page,                 // 页码
            'pageSize' => $pageSize,         // 每页数量
            'search' => $search,             // 关键词
            'order' => $order,               // 排序规则
            'timerange' => $timerange,       // 时间范围
            'departmentId' => $departmentId, // 部门
            'taskId' => $taskId,             // 任务
            'firstId' => $firstId,           // 上一页查询id范围
            'lastId' => $lastId,             // 下一页查询id范围
            'actionPage' => $actionPage,     // 分页点击操作事件 上一页 或 下一页
            'sourceType' => $sourceType,     // 数据源 1:mysql;2:clickhouse;
            'curTime' => time(),
            'tagId' => $tagId,
        ];
        if (! $user->isAdmin()) {
            $params['taskIds'] = $this->alarmTaskPermission->getTaskIdByUid($user['uid']);
        }

        if ($search) {
            // 从ES搜索
            return $this->getHistorysByEs($params);
        }
        // 从MySQL获取列表
        return $this->getHistorys($params);
    }

    /**
     * 从MySQL获取列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param mixed $order
     * @param mixed $timerange
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     */
    public function listFromMysql($page = 1, $pageSize = 20, $order = [], $timerange = [], $departmentId = null, $taskId = null)
    {
        $builder = $this->with('task', 'task.department');
        if ($taskId) {
            $builder->where('task_id', $taskId);
        }
        if ($departmentId) {
            // 查询出所有taskId，然后where in
            $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        }
        if ($timerange) {
            MySQL::whereTime($builder, $timerange, 'created_at');
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 通过ES查询历史数据
     * 注意：只查询当天的数据。
     * ES要求只存当天索引，过期会被删除.
     */
    public function getHistorysByEs(array $params): array
    {
        try {
            $page = (int) $params['page'];
            $pageSize = (int) $params['pageSize'];

            $elasticObj = new AlarmHistoryElastic();
            $ret = $elasticObj->getHistorysByEs($params);

            $data = ! empty($ret['list']) ? $ret['list'] : [];
            if (empty($data)) {
                throw new AppException('<<告警记录已经没有数据了!>>');
            }

            if ($params['actionPage'] == 'prev') {
                $data = array_reverse($data);
            }

            // 验证是否有下一页
            $more = 0;
            if (count($data) > $pageSize) {
                $more = 1;
                // 多查询一条，删除数组最后一条
                array_pop($data);
            }

            // 转换ctn
            foreach ($data as $k => $item) {
                if (! isset($item['ctn']) || empty($item['ctn'])) {
                    continue;
                }
                parse_str($item['ctn'], $ctn);
                $ctn = json_encode($ctn);
                $item['ctn'] = $ctn;
                $data[$k] = $item;
            }

            // 查询所有部门名称和任务名称
            $tasks = $this->getTasksDepartments($data);

            return [
                'data' => $tasks,
                'current_page' => $page,
                'per_page' => $pageSize,
                'source_type' => static::SOURCE_TYPE_MYSQL,
                'more' => $more,
            ];
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 查询历史数据.
     *
     * @return mixed
     */
    public function getHistorys(array $params)
    {
        try {
            $useTime = $params['curTime'] - config('clickhouse.sync.history.until_time');
            $useTime = strtotime(date('Y-m-d 00:00:00', $useTime));
            $params['useTime'] = $useTime;

            // 查询时间范围包含了mysql和clickhouse，则抛出异常
            $this->checkRangetime($params['timerange'], $useTime);

            // 查询clickhouse库中的数据
            if (isset($params['timerange']['end']) && $params['timerange']['end'] < $useTime) {
                return $this->getClickhouseData($params);
            }

            // 查询mysql中的数据
            if (isset($params['timerange']['begin']) && $params['timerange']['begin'] > $useTime) {
                return $this->getMysqlData($params);
            }

            // 没有时间范围，先查询mysql，在查询clickhouse
            return $this->getMysqlAndClickhouseData($params);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 查询mysql数据.
     *
     * @param int $selectMethod 查询方式 1:单独查询clickhouse;2:mysql和clichhouse混合查询;
     * @return mixed
     */
    public function getMysqlData(array $params, int $selectMethod = 1)
    {
        $ret = $this->getMysqlTaskHistory($params);
        if ($selectMethod == 1 && empty($ret)) {
            throw new AppException('<<告警记录已经没有数据了!>>');
        }
        $sourceType = static::SOURCE_TYPE_MYSQL;

        // mysql和clickhouse混合查询场景下
        if ($selectMethod == 2 && empty($ret)) {
            // clickhouse库，从头开始查询
            $params['lastId'] = -1;
            $ret = $this->getCkTaskHistory($params);
            if (empty($ret)) {
                throw new AppException('<<告警记录已经没有数据了!>>');
            }
            $sourceType = static::SOURCE_TYPE_CH;
        }

        return [
            'data' => $ret,
            'current_page' => $params['page'],
            'per_page' => $params['pageSize'],
            'source_type' => $sourceType,
        ];
    }

    /**
     * 查询clickhouse数据.
     *
     * @param int $selectMethod 查询方式 1:单独查询clickhouse;2:mysql和clichhouse混合查询;
     * @return array
     */
    public function getClickhouseData(array $params, int $selectMethod = 1)
    {
        // 查询clickhouse
        $ret = $this->getCkTaskHistory($params);
        if ($selectMethod == 1 && empty($ret)) {
            throw new AppException('<<告警记录已经没有数据了!>>');
        }
        $sourceType = static::SOURCE_TYPE_CH;

        // 上一页操作，已无数据时，查询mysql库
        if ($selectMethod == 2 && $params['actionPage'] == 'prev' && empty($ret)) {
            $params['firstId'] = -1;
            $ret = $this->getMysqlTaskHistory($params);
            if (empty($ret)) {
                throw new AppException('<<告警记录已经没有数据了!>>');
            }
            $sourceType = static::SOURCE_TYPE_MYSQL;
        }

        return [
            'data' => $ret,
            'current_page' => $params['page'],
            'per_page' => $params['pageSize'],
            'source_type' => $sourceType,
        ];
    }

    /**
     * 查询时间不在规定的范围内，直接抛出异常.
     */
    public function checkRangetime(array $timerange, int $useTime)
    {
        if (
            isset($timerange['begin']) && $timerange['begin'] < $useTime
            && isset($timerange['end']) && $timerange['end'] > $useTime
        ) {
            $utilTime = date('Y-m-d H:i:s', $useTime);
            throw new AppException("查询时间范围只能在{$utilTime}之前或者之后！");
        }
    }

    /**
     * 查询mysql和clickhouse数据.
     *
     * @return array $params
     */
    public function getMysqlAndClickhouseData(array $params)
    {
        try {
            $ret = [];

            switch ((int) $params['sourceType']) {
                case static::SOURCE_TYPE_MYSQL:
                    // 查询mysql数据
                    $ret = $this->getMysqlData($params, 2);
                    break;
                case static::SOURCE_TYPE_CH:
                    // 查询clickhouse
                    $ret = $this->getClickhouseData($params, 2);
                    break;
            }

            return $ret;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 查询clickhouse任务数据.
     *
     * @return array
     */
    public function getCkTaskHistory(array $params)
    {
        try {
            $firstId = (int) $params['firstId'];
            $lastId = (int) $params['lastId'];

            // 实例化ck
            $obj = make(Clickhouse::class)->getDb();

            $whereOrm = [];
            $replaceOrms = [];
            $orderBy = '';

            if (! empty($params['timerange'])) {
                $whereOrm[] = '{key1} >= :value1';
                $whereOrm[] = '{key2} <= :value2';
                $replaceOrms['key1'] = 'created_at';
                $replaceOrms['value1'] = (int) $params['timerange']['begin'];
                $replaceOrms['key2'] = 'created_at';
                $replaceOrms['value2'] = (int) $params['timerange']['end'];
            }

            if (! empty($params['taskId'])) {
                $whereOrm[] = '{key3} = :value3';
                $replaceOrms['key3'] = 'task_id';
                $replaceOrms['value3'] = (int) $params['taskId'];
            }

            if (! empty($params['departmentId'])) {
                // 查询出所有taskId，然后where in
                $taskIds = AlarmTask::where('department_id', (int) $params['departmentId'])->pluck('id')->toArray();
                if (isset($params['taskIds'])) {
                    $taskIds = array_intersect($params['taskIds'], $taskIds);
                }
                if (empty($taskIds)) {
                    $whereOrm[] = '1 = 0';
                } else {
                    $whereOrm[] = '{key4} IN (:value4)';
                    $replaceOrms['key4'] = 'task_id';
                    $replaceOrms['value4'] = $taskIds;
                }
            }

            if (! empty($params['tagId'])) {
                $taskIds = AlarmTaskTag::where('tag_id', $params['tagId'])->pluck('task_id')->toArray();
                if (isset($params['taskIds'])) {
                    $taskIds = array_intersect($params['taskIds'], $taskIds);
                }
                if (empty($taskIds)) {
                    $whereOrm[] = '1 = 0';
                } else {
                    $whereOrm[] = '{key7} IN (:value7)';
                    $replaceOrms['key7'] = 'task_id';
                    $replaceOrms['value7'] = $taskIds;
                }
            }
            // 权限判断
            if (isset($params['taskIds'])
                && empty($params['taskId'])
                && empty($params['departmentId'])
                && empty($params['tagId'])) {
                if (empty($params['taskIds'])) {
                    $whereOrm[] = '1 = 0';
                } else {
                    $whereOrm[] = '{key8} IN (:value8)';
                    $replaceOrms['key8'] = 'task_id';
                    $replaceOrms['value8'] = $params['taskIds'];
                }
            }

            if (! empty($params['order'])) {
                $orderByTmp = '';
                $order = json_decode($params['order'], true);
                foreach ($order as $oColumn => $oVal) {
                    if ($oColumn == 'id') {
                        continue;
                    }
                    $orderByTmp .= sprintf('%s %s,', $oColumn, $oVal);
                }
                $orderBy = ! empty($orderByTmp) ? ' ORDER BY ' . rtrim($orderByTmp, ',') : '';
            }

            // 分页操作
            switch ($params['actionPage']) {
                // 上一页
                case 'prev':
                    if ($firstId > 0) {
                        $whereOrm[] = '{key5} > :value5';
                        $replaceOrms['key5'] = 'id';
                        $replaceOrms['value5'] = (int) $firstId;
                    }
                    $orderBy = empty($orderBy) ? ' ORDER BY id ASC' : $orderBy . ',id ASC';
                    break;
                // 下一页
                case 'next':
                    if ($lastId > 0) {
                        $whereOrm[] = '{key6} < :value6';
                        $replaceOrms['key6'] = 'id';
                        $replaceOrms['value6'] = (int) $lastId;
                    }
                    $orderBy = empty($orderBy) ? ' ORDER BY id DESC' : $orderBy . ',id DESC';
                    break;
            }

            $sql = sprintf(
                'SELECT * FROM xes_alarm_alarm_history_all%s' . $orderBy . ' LIMIT 0,' . (int) $params['pageSize'],
                ! empty($whereOrm) ? ' WHERE ' . implode(' AND ', $whereOrm) : ''
            );
            $statement = $obj->select($sql, $replaceOrms);
            $tasks = $statement->rows();

            // 上一页操作，数据顺序倒序
            if ($params['actionPage'] == 'prev') {
                $tasks = array_reverse($tasks);
            }

            // 查询所有部门名称和任务名称
            return $this->getTasksDepartments($tasks);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 查询mysql作璁数据.
     *
     * @return array
     */
    public function getMysqlTaskHistory(array $params)
    {
        try {
            $firstId = (int) $params['firstId'];
            $lastId = (int) $params['lastId'];

            $builder = $this->with('task', 'task.department');

            if (! empty($params['taskId'])) {
                $builder->where('task_id', $params['taskId']);
            }

            if (! empty($params['tagId'])) {
                $tasksIdBytagId = AlarmTaskTag::where('tag_id', $params['tagId'])->pluck('task_id')->toArray();
                if (isset($params['taskIds'])) {
                    $tasksIdBytagId = array_intersect($params['taskIds'], $tasksIdBytagId);
                }
                $builder->whereIn('task_id', $tasksIdBytagId);
            }

            // 查询出所有taskId，然后where in
            if (! empty($params['departmentId'])) {
                $taskIds = AlarmTask::where('department_id', $params['departmentId'])->pluck('id')->toArray();
                if (isset($params['taskIds'])) {
                    $taskIds = array_intersect($params['taskIds'], $taskIds);
                }
                $builder->whereIn('task_id', $taskIds);
            }

            // 权限判断
            if (isset($params['taskIds'])
                && empty($params['taskId'])
                && empty($params['departmentId'])
                && empty($params['tagId'])) {
                $builder->whereIn('task_id', $params['taskIds']);
            }

            if (! empty($params['timerange'])) {
                MySQL::whereTime($builder, $params['timerange'], 'created_at');
            }

            if (! empty($params['order'])) {
                $order = json_decode($params['order'], true);
                foreach ($order as $oColumn => $oVal) {
                    // id单独分页排序使用
                    if ($oColumn == 'id') {
                        continue;
                    }
                    $builder->orderBy($oColumn, $oVal);
                }
            }

            switch ($params['actionPage']) {
                // 上一页
                case 'prev':
                    if ($firstId > 0) {
                        $builder->where('id', '>', $firstId);
                    }
                    $builder->orderBy('id', 'ASC');
                    break;
                // 下一页
                case 'next':
                    if ($lastId > 0) {
                        $builder->where('id', '<', $lastId);
                    }
                    $builder->orderBy('id', 'DESC');
                    break;
            }

            $data = $builder->limit((int) $params['pageSize'])->get();
            $data = ! $data ? [] : $data->toArray();

            if ($params['actionPage'] == 'prev') {
                $data = array_reverse($data);
            }

            return $data;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    public function getByIdAndThrow($historyId, $throwable = false)
    {
        $history = $this->where('id', $historyId)->first();
        if ($throwable && empty($history)) {
            throw new AppException("history [{$historyId}] not found");
        }

        return $history;
    }

    /**
     * 详情.
     * @param mixed $historyId
     */
    public function showHistory($historyId)
    {
        $history = $this->getByIdAndThrow($historyId, true);
        $history->load('task');
        $history->load('task.department');

        return $history;
    }

    public function task()
    {
        return $this->hasOne(AlarmTask::class, 'id', 'task_id')->select('id', 'name', 'department_id');
    }

    /**
     * 任务列表数据.
     *
     * @param array $tasks
     */
    public function getTasksDepartments($tasks): array
    {
        if (empty($tasks)) {
            return [];
        }

        $taskIds = array_unique(array_column($tasks, 'task_id'));
        if (empty($taskIds)) {
            return $tasks;
        }

        // 查询部门数据
        $departments = make(AlarmTask::class)->getDepartmentByTaskIds($taskIds);
        if (! empty($departments)) {
            $departments = array_column($departments, null, 'id');
        }

        // 拼装部门数据，输出
        return $this->formatHistoryDepartments($tasks, $departments);
    }

    /**
     * 将部门数据拼装到任务列表中，输出.
     *
     * @param array $tasks
     * @param array $departments
     */
    public function formatHistoryDepartments($tasks, $departments): array
    {
        foreach ($tasks as $key => $task) {
            $taskId = $task['task_id'];
            if (! isset($departments[$taskId])) {
                continue;
            }

            // 拼装部门数据
            $task['task'] = $departments[$taskId];

            $tasks[$key] = $task;
        }

        return $tasks;
    }

    /**
     * 查询clickhouse任务历史数据.
     */
    public function getChAlarmHistorys(array $historyIds): array
    {
        try {
            // 查询clickhouse数据
            $obj = new Builder('xes_alarm_alarm_history_all');
            $ret = $obj->select(['*'])
                ->whereIn('id', $historyIds)
                ->rows();

            return ! empty($ret) ? array_column($ret, null, 'id') : [];
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 查询mysql任务历史数据.
     */
    public function getAlarmHistorys(array $historyIds): array
    {
        try {
            $ret = $this->select('*')
                ->whereIn('id', $historyIds)
                ->get();

            if (! $ret) {
                return [];
            }
            $ret = $ret->toArray();

            return array_column($ret, null, 'id');
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 格式化一行数据.
     * @param mixed $row
     */
    protected function fmtRow($row)
    {
        $ctn = json_decode($row['ctn'], true);
        $row['ctn'] = $ctn ?: new stdClass();

        $receiver = json_decode($row['receiver'], true);
        $row['receiver'] = $receiver ?: new stdClass();

        return $row;
    }
}
