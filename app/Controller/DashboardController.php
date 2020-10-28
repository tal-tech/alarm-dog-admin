<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Dashboard;
use Hyperf\Di\Annotation\Inject;

class DashboardController extends AbstractController
{
    /**
     * @Inject
     * @var Dashboard
     */
    protected $dashboard;

    /**
     * 状态概览.
     */
    public function stat()
    {
        $params = $this->getReqParams();
        $stats = $this->dashboard->getStats(
            $params['department_id'],
            $params['task_id'],
            $params['tag_id'],
            $params['date']
        );
        return $this->success($stats);
    }

    /**
     * 平均请求qps.
     */
    public function avgReqQps()
    {
        $params = $this->getReqParams();
        $data = $this->dashboard->getAvgReqQps(
            $params['department_id'],
            $params['task_id'],
            $params['tag_id'],
            $params['date']
        );
        return $this->success($data);
    }

    /**
     * 最大请求qps.
     */
    public function maxReqQps()
    {
        $params = $this->getReqParams();
        $data = $this->dashboard->getMaxReqQps(
            $params['department_id'],
            $params['task_id'],
            $params['tag_id'],
            $params['date']
        );
        return $this->success($data);
    }

    /**
     * 平均生产qps.
     */
    public function avgProdQps()
    {
        $params = $this->getReqParams();
        $data = $this->dashboard->getAvgProdQps(
            $params['department_id'],
            $params['task_id'],
            $params['tag_id'],
            $params['date']
        );
        return $this->success($data);
    }

    /**
     * 最大生产qps.
     */
    public function maxProdQps()
    {
        $params = $this->getReqParams();
        $data = $this->dashboard->getMaxProdQps(
            $params['department_id'],
            $params['task_id'],
            $params['tag_id'],
            $params['date']
        );
        return $this->success($data);
    }

    /**
     * 获取请求参数.
     * @return array
     */
    private function getReqParams()
    {
        $params = $this->validate([
            'department_id' => 'nullable|integer|min:1', //部门ID
            'tag_id' => 'nullable|integer|min:1', //标签ID
            'task_id' => 'nullable|integer|min:1', //任务ID
            'date' => 'nullable|date', //日期时间戳
        ]);

        $params = array_null2default($params, [
            'department_id' => 0,
            'tag_id' => 0,
            'task_id' => 0,
            'date' => date('Y-m-d'),
        ]);

        $params['date'] = strtotime($params['date']);
        return $params;
    }
}
