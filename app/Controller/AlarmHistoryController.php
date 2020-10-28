<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\AlarmHistory;
use App\Service\AlarmHistoryAll;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class AlarmHistoryController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmHistory
     */
    protected $alarmHistory;

    /**
     * @Inject
     * @var AlarmHistoryAll
     */
    protected $alarmHistoryAll;

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
            'timerange' => 'nullable|array',
            'order' => 'nullable',
            'sourceType' => 'nullable',
            'actionPage' => 'nullable',
            'firstId' => 'nullable',
            'lastId' => 'nullable',
            'tagId' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'departmentId' => null,
            'taskId' => null,
            'search' => null,
            'page' => 1,
            'pageSize' => 20,
            'order' => [],
            'timerange' => [],
            'sourceType' => 1,
            'actionPage' => 1,
            'firstId' => -1,
            'lastId' => -1,
            'tagId' => null,
        ]);
        $user = Context::get(Auth::class)->user();
        $data = $this->alarmHistory->list(
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order'],
            $param['timerange'],
            $param['departmentId'],
            $param['taskId'],
            $param['sourceType'],
            $param['actionPage'],
            $param['firstId'],
            $param['lastId'],
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
        $historyId = (int) $this->request->input('historyId');

        $history = $this->alarmHistoryAll->showHistory($historyId);

        return $this->success(['history' => $history]);
    }
}
