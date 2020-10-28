<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class AlarmTag extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_tag';

    protected $fillable = ['name', 'pinyin', 'remark', 'created_by', 'created_at', 'updated_at'];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * 判断是否存在，不存在则报错.
     *
     * @param int $tagId
     * @return self
     */
    public function getByIdAndThrow($tagId)
    {
        $tag = $this->where('id', $tagId)->first();
        if (empty($tag)) {
            throw new AppException(sprintf('tag [%s] not found', $tag), [
                'tag_id' => $tag,
            ], null, 404);
        }

        return $tag;
    }

    /**
     * 标签关联的任务信息
     * 任务关联的创建者信息.
     */
    public function tasks()
    {
        return $this->belongsToMany(AlarmTask::class, 'alarm_task_tag', 'tag_id', 'task_id', 'id')
            ->select('name', 'created_by')
            ->with('creator');
    }

    /**
     * 标签关联的创建者信息.
     */
    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')
            ->select('uid', 'username', 'user', 'email', 'department');
    }

    /**
     * 标签列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function getTag($page = 1, $pageSize = 20, $search = null, $order = [])
    {
        $builder = $this->with('creator')->orderBy('created_at', 'desc');
        if ($search) {
            $builder->where(function ($query) use ($search) {
                if (is_numeric($search)) {
                    $query->where('id', $search);
                }
                $query->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 更新标签.
     * @param mixed $tagId
     * @param mixed $param
     */
    public function updateTag($tagId, $param)
    {
        $time = time();

        if ($this->where('id', '!=', $tagId)->where('name', $param['name'])->exists()) {
            throw new AppException('标签名称已存在，请重新填写');
        }

        $tag = $this->where('id', $tagId)->first();
        if (empty($tag)) {
            throw new AppException('此Tag不存在，更新失败');
        }

        $tag['name'] = $param['name'];
        $tag['pinyin'] = $this->pinyin->convert($param['name']);
        $tag['remark'] = $param['remark'];
        $tag['updated_at'] = $time;
        $tag->save();

        $tag->load('creator');

        return $tag;
    }

    /**
     * 删除标签.
     * @param mixed $tagId
     */
    public function deleteTag($tagId)
    {
        $tag = $this->where('id', $tagId)->first();
        if (empty($tag)) {
            throw new AppException('此Tag不存在，删除失败');
        }

        Db::beginTransaction();
        try {
            AlarmTaskTag::where('tag_id', $tagId)->delete();
            $tag->delete();
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 新增标签.
     * @param mixed $param
     * @param mixed $user
     */
    public function storeTag($param, $user)
    {
        $time = time();

        if ($this->where('name', $param['name'])->exists()) {
            throw new AppException('标签名称已存在，请重新填写');
        }

        $data = [
            'name' => $param['name'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'remark' => $param['remark'],
            'created_by' => $user['uid'],
            'created_at' => $time,
            'updated_at' => $time,
        ];

        $tag = AlarmTag::create($data);

        $tag->load('creator');

        return $tag;
    }

    /**
     * 搜索标签.
     *
     * @param string $keyword
     * @param int $pageSize
     * @param null|mixed $search
     * @return array
     */
    public function searchTags($search = null, $pageSize = null)
    {
        $builder = $this->with('creator')
            ->select('id', 'pinyin', 'name', 'remark', 'created_by');

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
}
