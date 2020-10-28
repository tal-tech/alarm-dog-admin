<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\AlarmTemplate;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class AlarmTemplateController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTemplate
     */
    protected $alarmTemplate;

    /**
     * 列表.
     */
    public function list()
    {
        $param = $this->validate([
            'search' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'order' => 'nullable',
        ]);
        $param = array_null2default($param, [
            'search' => null,
            'page' => 1,
            'pageSize' => 20,
            'order' => [],
        ]);

        $data = $this->alarmTemplate->list(
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order']
        );

        return $this->success($data);
    }

    /**
     * 详情.
     */
    public function show()
    {
        $templateId = (int) $this->request->input('id');

        $template = $this->alarmTemplate->showTemplate($templateId);

        return $this->success(['template' => $template]);
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $templateId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $this->alarmTemplate->deleteTemplate($templateId, $user);

        return $this->success(['id' => $templateId]);
    }

    /**
     * 简单列表.
     */
    public function simpleList()
    {
        $search = $this->request->input('search');
        $pageSize = $this->request->input('pageSize', null);

        $templates = $this->alarmTemplate->simpleList($search, $pageSize);

        return $this->success(['templates' => $templates]);
    }

    /**
     * 默认模板
     */
    public function defaultTemplates()
    {
        $templates = $this->alarmTemplate->defaultTemplates();

        return $this->success(['templates' => $templates]);
    }

    /**
     * 创建.
     */
    public function store()
    {
        $param = $this->validate([
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'template' => 'required|array',
            'permission' => 'array',
            'permission.*' => 'integer|distinct',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
            'permission' => [],
        ]);

        $user = Context::get(Auth::class)->user();

        $template = $this->alarmTemplate->storeTemplate($param, $user);

        return $this->success(['template' => $template]);
    }

    /**
     * 更新.
     */
    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'template' => 'required|array',
            'permission' => 'required|array',
            'permission.*' => 'integer|distinct',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $template = $this->alarmTemplate->updateTemplate($param['id'], $param, $user);

        return $this->success(['template' => $template]);
    }
}
