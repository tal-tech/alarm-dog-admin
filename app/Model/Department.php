<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;

class Department extends Model
{
    public $timestamps = false;

    protected $table = 'department';

    protected $fillable = ['bu_id', 'name', 'pinyin', 'remark', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * 是否存在该名称的记录.
     *
     * @param string $name
     * @param int $excludeId
     * @return int
     */
    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 判断是否存在，不存在则报错.
     *
     * @param int $depId
     * @return self
     */
    public function getByIdAndThrow($depId)
    {
        $department = $this->where('id', $depId)->first();
        if (empty($department)) {
            throw new AppException(sprintf('department [%s] not found', $depId), [
                'departmentid' => $depId,
            ], null, 404);
        }

        return $department;
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')
            ->select('uid', 'username', 'user', 'email', 'department');
    }

    public function updator()
    {
        return $this->hasOne(User::class, 'uid', 'updated_by')
            ->select('uid', 'username', 'user', 'email', 'department');
    }

    public function businessUnit()
    {
        return $this->hasOne(BusinessUnit::class, 'id', 'bu_id')
            ->select('id', 'name', 'remark', 'pinyin');
    }

    /**
     * 列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     * @param null|mixed $buId
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [], $buId = null)
    {
        $builder = $this->with('creator')->with('updator')->with('businessUnit');

        if ($buId) {
            $builder->where('bu_id', $buId);
        }

        if ($search) {
            $builder->where(function ($query) use ($search) {
                if (is_numeric($search)) {
                    $query->orWhere('id', $search);
                }
                $query->orWhere('pinyin', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     * @param null|mixed $buId
     */
    public function simpleList($search = null, $pageSize = null, $buId = null)
    {
        $builder = $this->select('id', 'pinyin', 'name', 'remark');

        if ($buId) {
            $builder->where('bu_id', $buId);
        }

        if ($search) {
            $builder->where(function ($query) use ($search) {
                if (is_numeric($search)) {
                    $query->where('id', $search);
                }
                $query->orWhere('pinyin', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }

        return $builder->get();
    }

    /**
     * 详情.
     * @param mixed $depId
     */
    public function showDepartment($depId)
    {
        $department = $this->getByIdAndThrow($depId);
        $department->load('creator');
        $department->load('updator');
        $department->load('businessUnit');

        return $department;
    }

    /**
     * 删除.
     * @param mixed $depId
     */
    public function deleteDepartment($depId, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以删除部门');
        }

        $department = $this->getByIdAndThrow($depId);

        // 查询有无关联的告警任务
        $relateTasks = AlarmTask::where('department_id', $depId)->count();
        if ($relateTasks > 0) {
            throw new AppException('删除失败，该部门有关联的告警任务，请取消关联之后再删除');
        }

        $department->delete();
    }

    /**
     * 创建.
     * @param mixed $param
     */
    public function storeDepartment($param, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以创建部门');
        }

        // 判断事业部是否存在
        $businessUnit = $this->getContainer()->get(BusinessUnit::class)->getByIdAndThrow($param['bu_id']);

        // 重名判断
        if ($this->hasByName($param['name'])) {
            throw new AppException(sprintf('department [%s] exists, please use other name', $param['name']), [
                'name' => $param['name'],
            ]);
        }
        $data = [
            'bu_id' => $param['bu_id'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'name' => $param['name'],
            'remark' => $param['remark'],
            'created_by' => $user['uid'],
            'updated_by' => $user['uid'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $department = self::create($data);
        $department->load('creator');
        $department->load('updator');
        $department->load('businessUnit');

        return $department;
    }

    /**
     * 更新.
     * @param mixed $depId
     * @param mixed $param
     */
    public function updateDepartment($depId, $param, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以更新部门');
        }

        // 判断事业部是否存在
        $businessUnit = $this->getContainer()->get(BusinessUnit::class)->getByIdAndThrow($param['bu_id']);

        // 重名判断
        if ($this->hasByName($param['name'], $depId)) {
            throw new AppException(sprintf('department [%s] exists, please use other name', $param['name']), [
                'name' => $param['name'],
                'exclude_id' => $depId,
            ]);
        }

        $department = $this->getByIdAndThrow($depId);

        $department['bu_id'] = $param['bu_id'];
        $department['pinyin'] = $this->pinyin->convert($param['name']);
        $department['name'] = $param['name'];
        $department['remark'] = $param['remark'];
        $department['updated_by'] = $user['uid'];
        $department['updated_at'] = time();
        $department->save();

        $department->load('creator');
        $department->load('updator');
        $department->load('businessUnit');

        return $department;
    }
}
