<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\AlarmTag;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class AlarmTagController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTag
     */
    protected $alarmTag;

    /**
     * 标签列表.
     */
    public function list()
    {
        $param = $this->validate([
            'search' => 'string',
            'page' => 'numeric|min:1',
            'pageSize' => 'numeric|min:1|max:100',
        ]);
        $search = $this->request->input('search', '');
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('pageSize', 20);

        $data = $this->alarmTag->getTag($page, $pageSize, $search);

        return $this->success($data);
    }

    /**
     * 新增标签.
     */
    public function store()
    {
        $param = $this->validate([
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $tag = $this->alarmTag->storeTag($param, $user);

        return $this->success([
            'tag' => $tag,
        ]);
    }

    /**
     * 更新标签.
     */
    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $tag = $this->alarmTag->updateTag($param['id'], $param);

        return $this->success([
            'tag' => $tag,
        ]);
    }

    /**
     * 删除标签.
     */
    public function delete()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $this->alarmTag->deleteTag($param['id']);

        return $this->success([
            'id' => (int) $param['id'],
        ]);
    }

    /**
     * 搜索标签.
     */
    public function search()
    {
        $param = $this->validate([
            'search' => 'nullable|string',
            'pageSize' => 'integer|max:100|min:1',
        ]);
        $param = array_null2default($param, [
            'search' => '',
        ]);

        $param['pageSize'] = (int) $this->request->input('pageSize', 20);

        $task_tags = $this->alarmTag->searchTags($param['search'], $param['pageSize']);

        return $this->success(compact('task_tags'));
    }
}
