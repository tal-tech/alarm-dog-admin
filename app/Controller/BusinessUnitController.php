<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\BusinessUnit;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class BusinessUnitController extends AbstractController
{
    /**
     * @Inject
     * @var BusinessUnit
     */
    protected $businessUnit;

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

        $data = $this->businessUnit->list(
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
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $businessUnit = $this->businessUnit->showBusinessUnit($param['id']);

        return $this->success([
            'business_unit' => $businessUnit,
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

        $this->businessUnit->deleteBusinessUnit($param['id'], $user);

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

        $businessUnits = $this->businessUnit->simpleList($search, $pageSize);

        return $this->success([
            'business_units' => $businessUnits,
        ]);
    }

    /**
     * 创建.
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

        $businessUnit = $this->businessUnit->storeBusinessUnit($param, $user);

        return $this->success([
            'business_unit' => $businessUnit,
        ]);
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
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $businessUnit = $this->businessUnit->updateBusinessUnit($param['id'], $param, $user);

        return $this->success([
            'business_unit' => $businessUnit,
        ]);
    }
}
