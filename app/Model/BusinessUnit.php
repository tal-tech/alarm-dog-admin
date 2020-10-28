<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;

class BusinessUnit extends Model
{
    public $timestamps = false;

    protected $table = 'business_unit';

    protected $fillable = ['name', 'pinyin', 'remark', 'created_by', 'updated_by', 'created_at', 'updated_at'];

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
     * @param int $buId
     * @return self
     */
    public function getByIdAndThrow($buId)
    {
        $businessUnit = $this->where('id', $buId)->first();
        if (empty($businessUnit)) {
            throw new AppException(sprintf('business unit [%s] not found', $buId), [
                'bu_id' => $buId,
            ], null, 404);
        }

        return $businessUnit;
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

    /**
     * 列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [])
    {
        $builder = $this->with('creator')->with('updator');
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
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'pinyin', 'name', 'remark');

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
     * @param mixed $buId
     */
    public function showBusinessUnit($buId)
    {
        $businessUnit = $this->getByIdAndThrow($buId);
        $businessUnit->load('creator');
        $businessUnit->load('updator');

        return $businessUnit;
    }

    /**
     * 删除.
     * @param mixed $buId
     */
    public function deleteBusinessUnit($buId, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以删除事业部');
        }

        $businessUnit = $this->getByIdAndThrow($buId);

        // 查询有无关联的agent
        $relateDepartment = Department::where('bu_id', $buId)->count();
        if ($relateDepartment > 0) {
            throw new AppException('删除失败，该事业部有关联的部门，请取消关联之后再删除');
        }

        $businessUnit->delete();
    }

    /**
     * 创建.
     * @param mixed $param
     */
    public function storeBusinessUnit($param, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以创建事业部');
        }

        // 重名判断
        if ($this->hasByName($param['name'])) {
            throw new AppException(sprintf('business unit [%s] exists, please use other name', $param['name']), [
                'name' => $param['name'],
            ]);
        }

        $data = [
            'pinyin' => $this->pinyin->convert($param['name']),
            'name' => $param['name'],
            'remark' => $param['remark'],
            'created_by' => $user['uid'],
            'updated_by' => $user['uid'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $businessUnit = self::create($data);
        $businessUnit->load('creator');
        $businessUnit->load('updator');

        return $businessUnit;
    }

    /**
     * 更新.
     * @param mixed $buId
     * @param mixed $param
     */
    public function updateBusinessUnit($buId, $param, User $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以更新事业部');
        }

        // 重名判断
        if ($this->hasByName($param['name'], $buId)) {
            throw new AppException(sprintf('business unit [%s] exists, please use other name', $param['name']), [
                'name' => $param['name'],
                'exclude_id' => $buId,
            ]);
        }

        $businessUnit = $this->getByIdAndThrow($buId);

        $businessUnit['pinyin'] = $this->pinyin->convert($param['name']);
        $businessUnit['name'] = $param['name'];
        $businessUnit['remark'] = $param['remark'];
        $businessUnit['updated_by'] = $user['uid'];
        $businessUnit['updated_at'] = time();
        $businessUnit->save();

        $businessUnit->load('creator');
        $businessUnit->load('updator');

        return $businessUnit;
    }
}
