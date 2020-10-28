<?php

declare(strict_types=1);

namespace App\Controller\Monitor;

use App\Context\Auth;
use App\Controller\AbstractController;
use App\Model\MonitorDatasource;
use App\Service\Monitor\DateTime;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class DatasourceController extends AbstractController
{
    /**
     * @Inject
     * @var MonitorDatasource
     */
    protected $dataSource;

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

        $data = $this->dataSource->list(
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

        $datasource = $this->dataSource->showDatasource($param['id']);

        return $this->success([
            'datasource' => $datasource,
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

        $this->dataSource->deleteDatasource($param['id'], $user);

        return $this->success([
            'id' => (int) $param['id'],
        ]);
    }

    /**
     * 简单列表.
     */
    public function simpleList()
    {
        $search = $this->request->input('search');
        $pageSize = $this->request->input('pageSize', null);

        $datasources = $this->dataSource->simpleList($search, $pageSize);

        return $this->success([
            'datasources' => $datasources,
        ]);
    }

    /**
     * 创建.
     */
    public function store()
    {
        $param = $this->validate([
            'type' => 'required|in:' . implode(',', array_keys(MonitorDatasource::$types)),
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'config' => 'required|array',
            'fields' => 'required|array',
            'timestamp_field' => 'required|string',
            'timestamp_unit' => 'required|in:' . implode(',', array_keys(DateTime::$units)),
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $datasource = $this->dataSource->storeDatasource($param, $user);

        return $this->success([
            'datasource' => $datasource,
        ]);
    }

    /**
     * 更新.
     */
    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'type' => 'required|in:' . implode(',', array_keys(MonitorDatasource::$types)),
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'config' => 'required|array',
            'fields' => 'required|array',
            'timestamp_field' => 'required|string',
            'timestamp_unit' => 'required|in:' . implode(',', array_keys(DateTime::$units)),
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $datasource = $this->dataSource->updateDatasource($param['id'], $param, $user);

        return $this->success([
            'datasource' => $datasource,
        ]);
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect()
    {
        $param = $this->validate([
            'type' => 'required|in:' . implode(',', array_keys(MonitorDatasource::$types)),
            'name' => 'required|string|max:100',
            'config' => 'required|array',
            'fields' => 'required|array',
            'timestamp_field' => 'required|string',
            'timestamp_unit' => 'required|in:' . implode(',', array_keys(DateTime::$units)),
        ]);

        $this->dataSource->validConnect($param);

        return $this->success();
    }

    /**
     * 获取数据源字段.
     */
    public function fields()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $resp = $this->dataSource->getFields($param['id']);

        return $this->success($resp);
    }
}
