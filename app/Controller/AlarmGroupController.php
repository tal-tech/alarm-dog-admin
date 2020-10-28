<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\AlarmGroup;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class AlarmGroupController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmGroup
     */
    protected $alarmGroup;

    /**
     * 告警组列表.
     */
    public function get()
    {
        $param = $this->validate([
            'search' => 'string',
            'page' => 'numeric|min:1',
            'pageSize' => 'numeric|min:1|max:100',
        ]);
        $search = $this->request->input('search', '');
        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('pageSize', 20);

        $data = $this->alarmGroup->list($page, $pageSize, $search);

        return $this->success($data);
    }

    /**
     * 创建告警组.
     */
    public function store()
    {
        $channelsValidate = AlarmGroup::getChannelsValid();
        $param = $this->validate(array_merge($channelsValidate, [
            'name' => 'required|string|max:100',
            'receiver' => 'required|array',
            'receiver.channels' => 'required|array',
            'remark' => 'nullable|string|max:200',
            'permission' => 'array',
            'permission.*' => 'integer|distinct',
        ]));
        $param = array_null2default($param, [
            'remark' => '',
            'permission' => [],
        ]);

        $user = Context::get(Auth::class)->user();
        [$alarmGroup, $nullPhoneUsers] = $this->alarmGroup->storeGroup($param, $user);

        return $this->success([
            'alarmgroup' => $alarmGroup,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    /**
     * 告警组详情.
     */
    public function show()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $user = Context::get(Auth::class)->user();

        $alarmGroup = $this->alarmGroup->showGroup($param['id'], $user);

        return $this->success([
            'alarmgroup' => $alarmGroup,
        ]);
    }

    /**
     * 更新告警组.
     */
    public function update()
    {
        $channelsValidate = AlarmGroup::getChannelsValid();
        $param = $this->validate(array_merge($channelsValidate, [
            'id' => 'required|integer',
            'name' => 'required|string|max:100',
            'receiver' => 'required|array',
            'receiver.channels' => 'required|array',
            'remark' => 'nullable|string|max:200',
            'permission' => 'required|array',
            'permission.*' => 'integer|distinct',
        ]));
        $param = array_null2default($param, [
            'remark' => '',
            'permission' => [],
        ]);

        $user = Context::get(Auth::class)->user();
        [$alarmGroup, $nullPhoneUsers] = $this->alarmGroup->updateGroup($param['id'], $param, $user);

        return $this->success([
            'alarmgroup' => $alarmGroup,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    /**
     * 删除告警组.
     */
    public function delete()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $user = Context::get(Auth::class)->user();

        $this->alarmGroup->deleteGroup($param['id'], $user);

        return $this->success([
            'id' => (int) $param['id'],
        ]);
    }

    /**
     * 简单列表.
     */
    public function search()
    {
        $param = $this->validate([
            'search' => 'required|string',
            'pageSize' => 'numeric|max:100',
        ]);
        $param['pageSize'] = (int) $this->request->input('pageSize', 20);

        $alarmGroups = $this->alarmGroup->simpleList($param['search'], $param['pageSize']);

        return $this->success([
            'alarmgroups' => $alarmGroups,
        ]);
    }
}
