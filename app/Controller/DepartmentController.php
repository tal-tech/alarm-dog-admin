<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\Department;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class DepartmentController extends AbstractController
{
    /**
     * @Inject
     * @var Department
     */
    protected $department;

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
            'bu_id' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'search' => null,
            'page' => 1,
            'pageSize' => 20,
            'order' => [],
            'bu_id' => [],
        ]);

        $data = $this->department->list(
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order'],
            $param['bu_id']
        );

        return $this->success($data);
    }

    /**
     * 详情.
     */
    public function show()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $department = $this->department->showDepartment($param['id']);

        return $this->success([
            'department' => $department,
        ]);
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $user = Context::get(Auth::class)->user();

        $this->department->deleteDepartment($param['id'], $user);

        return $this->success([
            'id' => $param['id'],
        ]);
    }

    /**
     * 简单列表.
     */
    public function simpleList()
    {
        $search = $this->request->input('search');
        $pageSize = $this->request->input('pageSize', null);
        $buId = $this->request->input('bu_id', null);

        $departments = $this->department->simpleList($search, $pageSize, $buId);

        return $this->success([
            'departments' => $departments,
        ]);
    }

    /**
     * 创建.
     */
    public function store()
    {
        $param = $this->validate([
            'bu_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $department = $this->department->storeDepartment($param, $user);

        return $this->success([
            'department' => $department,
        ]);
    }

    /**
     * 更新.
     */
    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'bu_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $department = $this->department->updateDepartment($param['id'], $param, $user);

        return $this->success([
            'department' => $department,
        ]);
    }
}
