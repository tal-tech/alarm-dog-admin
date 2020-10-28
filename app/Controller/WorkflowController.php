<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\Workflow;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use stdClass;

class WorkflowController extends AbstractController
{
    /**
     * @Inject
     * @var Workflow
     */
    protected $workflow;

    /**
     * 列表.
     */
    public function list()
    {
        $param = $this->validate([
            'departmentId' => 'nullable|integer|min:1',
            'taskId' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|in:' . implode(',', array_keys(Workflow::$availableStatuses)),
            'timerange' => 'nullable|array',
            'order' => 'nullable',
            'tagId' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'departmentId' => null,
            'taskId' => null,
            'search' => null,
            'page' => 1,
            'pageSize' => 20,
            'status' => null,
            'order' => [],
            'timerange' => [],
            'tagId' => null,
        ]);
        $user = Context::get(Auth::class)->user();
        $data = $this->workflow->list(
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order'],
            $param['timerange'],
            $param['status'],
            $param['departmentId'],
            $param['taskId'],
            $param['tagId'],
            $user
        );

        return $this->success($data);
    }

    /**
     * 详情.
     */
    public function show()
    {
        $workflowId = (int) $this->request->input('id');
        $user = Context::get(Auth::class)->user();
        $workflow = $this->workflow->showWorkflow($workflowId, $user);

        return $this->success(['workflow' => $workflow]);
    }

    /**
     * 统计各状态数量.
     */
    public function statsByStatus()
    {
        $param = $this->validate([
            'departmentId' => 'nullable|integer|min:1',
            'taskId' => 'nullable|integer|min:1',
            'timerange' => 'nullable|array',
            'tagId' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'departmentId' => null,
            'taskId' => null,
            'timerange' => [],
            'tagId' => null,
        ]);
        $user = Context::get(Auth::class)->user();
        $stats = $this->workflow->statsByStatus(
            $param['departmentId'],
            $param['taskId'],
            $param['timerange'],
            $param['tagId'],
            $user
        );

        return $this->success([
            'statistics' => $stats,
        ]);
    }

    /**
     * 认领.
     */
    public function claim()
    {
        $param = $this->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
            'remark' => 'nullable|string|max:200',
        ]);
        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = $this->request->getAttribute('user');

        [$workflows, $errors] = $this->workflow->claim($param['ids'], $param['remark'], $user);

        return $this->success([
            'workflows' => $workflows,
            'errors' => $errors ?: new stdClass(),
        ]);
    }

    /**
     * 分派.
     */
    public function assign()
    {
        $param = $this->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
            'remark' => 'required|string|max:200',
            'assignto' => 'required|array',
            'assignto.*' => 'required|integer|distinct',
        ]);

        $user = $this->request->getAttribute('user');

        [$workflows, $errors] = $this->workflow->assign($param['ids'], $param['remark'], $param['assignto'], $user);

        return $this->success([
            'workflows' => $workflows,
            'errors' => $errors ?: new stdClass(),
        ]);
    }

    /**
     * 处理完成.
     */
    public function processed()
    {
        $param = $this->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
            'remark' => 'required|string|max:200',
        ]);

        $user = $this->request->getAttribute('user');

        [$workflows, $errors] = $this->workflow->processed($param['ids'], $param['remark'], $user);

        return $this->success([
            'workflows' => $workflows,
            'errors' => $errors ?: new stdClass(),
        ]);
    }

    /**
     * 重新激活任务
     */
    public function reactive()
    {
        $param = $this->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
            'remark' => 'required|string|max:200',
        ]);

        $user = $this->request->getAttribute('user');

        [$workflows, $errors] = $this->workflow->reactive($param['ids'], $param['remark'], $user);

        return $this->success([
            'workflows' => $workflows,
            'errors' => $errors ?: new stdClass(),
        ]);
    }

    /**
     * 关闭.
     */
    public function close()
    {
        $param = $this->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|distinct',
            'remark' => 'required|string|max:200',
        ]);

        $user = $this->request->getAttribute('user');

        [$workflows, $errors] = $this->workflow->close($param['ids'], $param['remark'], $user);

        return $this->success([
            'workflows' => $workflows,
            'errors' => $errors ?: new stdClass(),
        ]);
    }
}
