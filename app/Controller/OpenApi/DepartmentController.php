<?php

declare(strict_types=1);

namespace App\Controller\OpenApi;

use App\Model\Department;
use Hyperf\Di\Annotation\Inject;

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
}
