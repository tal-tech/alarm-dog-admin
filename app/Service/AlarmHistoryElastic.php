<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\AppException;
use App\Model\AlarmTask;
use App\Model\AlarmTaskTag;
use App\Support\Elasticsearch\Builder;

class AlarmHistoryElastic
{
    /**
     * 索引名称，后续扩展，一个表对应一个索引操作文件
     * 带有当天日期格式，方便管理(清除).
     *
     * @var string
     */
    protected $indexName = 'xes_alarm_alarm_history';

    /**
     * 索引别名，查询使用.
     *
     * @var string
     */
    protected $indexAlias = 'index_xes_alarm_alarm_history';

    /**
     * 索引类型.
     *
     * @var string
     */
    protected $type = 'log';

    /**
     * 客户端实例.
     *
     * @var
     */
    private $client;

    public function __construct()
    {
        $curTime = date('Y-m-d');
        $this->client = new Builder($this->indexName . '_' . $curTime, $this->indexAlias, $this->type);
    }

    /**
     * 通过ES查询历史告警记录.
     */
    public function getHistorysByEs(array $params): array
    {
        try {
            $conditions = [];
            $firstId = (int) $params['firstId'];
            $lastId = (int) $params['lastId'];

            $query = [
                'page' => 1,
                'pageSize' => (int) $params['pageSize'] + 1,
                'fields' => ['*'],
                'sortField' => 'id',
                'sortRule' => 'DESC',
            ];

            // 关键词
            if (! empty($params['search'])) {
                $conditions[] = ['type' => 'like', 'field' => 'ctn', 'value' => $params['search']];
            }

            if (! empty($params['taskId'])) {
                $conditions[] = ['type' => '=', 'field' => 'task_id', 'value' => $params['taskId']];
            }

            // 查询出所有taskId，然后where in
            if (! empty($params['departmentId'])) {
                $taskIds = AlarmTask::where('department_id', $params['departmentId'])->pluck('id')->toArray();
                if (isset($params['taskIds'])) {
                    $taskIds = array_intersect($params['taskIds'], $taskIds);
                }
                $conditions[] = ['type' => 'in', 'field' => 'task_id', 'value' => $taskIds];
            }

            // tagId, 查询出所有taskId，然后where in
            if (! empty($params['tagId'])) {
                $taskIds = AlarmTaskTag::where('tag_id', $params['tagId'])->pluck('task_id')->toArray();
                if (isset($params['taskIds'])) {
                    $taskIds = array_intersect($params['taskIds'], $taskIds);
                }
                $conditions[] = ['type' => 'in', 'field' => 'task_id', 'value' => $taskIds];
            }

            // 权限判断
            if (isset($params['taskIds'])
                && empty($params['taskId'])
                && empty($params['departmentId'])
                && empty($params['tagId'])) {
                $conditions[] = ['type' => 'in', 'field' => 'task_id', 'value' => $params['taskIds']];
            }

            if (! empty($params['timerange'])) {
                $conditions[] = ['type' => 'between', 'field' => 'created_at', 'value' => [$params['timerange']['begin'], $params['timerange']['end']]];
            }

            switch ($params['actionPage']) {
                // 上一页
                case 'prev':
                    if ($firstId > 0) {
                        $conditions[] = ['type' => '>', 'field' => 'id', 'value' => $firstId];
                    }
                    $query['sortRule'] = 'ASC';
                    break;
                // 下一页
                case 'next':
                    if ($lastId > 0) {
                        $conditions[] = ['type' => '<', 'field' => 'id', 'value' => $lastId];
                    }
                    $query['sortRule'] = 'DESC';
                    break;
            }

            if (! empty($conditions)) {
                $query['condition'] = $conditions;
            }

            return $this->client->searchMulti($query);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 解析kafka消息，并执行对应的索引操作.
     *
     * @param array $msgs
     */
    public function setEsDocAlarmHistorys(array $payload)
    {
        if (! isset($payload['type']) || ! isset($payload['data'])) {
            return false;
        }

        $type = $payload['type'];
        $data = $payload['data'];

        // 格式化告警信息
        if (! empty($data['ctn'])) {
            $ctn = json_decode($data['ctn'], true);
            $data['ctn'] = urldecode(http_build_query($ctn));
        }

        switch ($type) {
            case 'insert':
            case 'update':
                $body = [
                    'id' => $data['id'],
                    'body' => $data,
                ];
                // 验证索引是否存在
                $existsIndex = $this->client->existsIndex();
                if (! $existsIndex) {
                    // 不存在则创建上索引
                    $this->client->createIndex();
                }
                // 设置别名
                $this->client->setAlias();
                // 执行添加
                return $this->client->add($body);
                break;
            case 'delete':
                return $this->client->deleteDoc($data['id']);
                break;
        }
    }
}
