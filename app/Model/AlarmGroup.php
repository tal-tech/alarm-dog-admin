<?php

declare(strict_types=1);

namespace App\Model;

use App\Context\Auth;
use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Context;
use stdClass;
use Throwable;

class AlarmGroup extends Model
{
    // 短信通知
    public const CHANNEL_SMS = 'sms';

    // 电话通知
    public const CHANNEL_PHONE = 'phone';

    // 邮件通知
    public const CHANNEL_EMAIL = 'email';

    // 钉钉工作通知
    public const CHANNEL_DINGWORKER = 'dingworker';

    // 微信通知
    public const CHANNEL_WECHAT = 'wechat';

    // 钉钉群通知
    public const CHANNEL_DINGGROUP = 'dinggroup';

    // Yach群通知
    public const CHANNEL_YACHGROUP = 'yachgroup';

    // Yach工作通知
    public const CHANNEL_YACHWORKER = 'yachworker';

    // WebHook通知
    public const CHANNEL_WEBHOOK = 'webhook';

    public $timestamps = false;

    /**
     * 可用的通知渠道且与用户相关.
     */
    public static $availableChannelsUser = [
        self::CHANNEL_SMS, self::CHANNEL_EMAIL, self::CHANNEL_PHONE, self::CHANNEL_DINGWORKER, self::CHANNEL_YACHWORKER,
    ];

    /**
     * 可用的机器人通知渠道.
     */
    public static $availableChannelsRobot = [
        self::CHANNEL_DINGGROUP, self::CHANNEL_YACHGROUP,
    ];

    /**
     * 可用的通知渠道.
     */
    public static $availableChannels = [
        self::CHANNEL_SMS, self::CHANNEL_EMAIL, self::CHANNEL_PHONE, self::CHANNEL_DINGWORKER, self::CHANNEL_DINGGROUP,
        self::CHANNEL_YACHGROUP, self::CHANNEL_YACHWORKER, self::CHANNEL_WEBHOOK,
    ];

    protected $table = 'alarm_group';

    protected $fillable = ['name', 'pinyin', 'remark', 'receiver', 'created_by', 'created_at', 'updated_at'];

    protected $casts = [
        'receiver' => 'array',
    ];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * @Inject
     * @var AlarmGroupPermission
     */
    protected $permission;

    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 是否有权限.
     *
     * @param User $user
     * @param int $groupId
     * @return bool
     */
    public function hasPermisson($user, $groupId)
    {
        // 超管直接允许通过
        if ($user->role == User::ROLE_ADMIN) {
            return true;
        }

        return AlarmGroupPermission::where('group_id', $groupId)
            ->where('uid', $user['uid'])
            ->exists();
    }

    /**
     * 查询对应告警组权限.
     *
     * @param int $groupId
     * @return array|Collection
     */
    public function permission($groupId)
    {
        $uids = AlarmGroupPermission::where('group_id', $groupId)
            ->pluck('uid')
            ->toArray();

        return User::whereIn('uid', $uids)
            ->select('uid', 'user', 'email', 'username', 'department')
            ->get();
    }

    /**
     * 获取各渠道的验证规则和默认值
     *
     * @return array
     */
    public static function getChannelsValid()
    {
        $channelsValidate = [
            'receiver.channels.dinggroupfocus' => 'nullable|string',
        ];
        foreach (static::$availableChannels as $channel) {
            if ($channel === self::CHANNEL_WEBHOOK) {
                'receiver.channels.' . $channelsValidate[$channel] = 'array';
            } else {
                'receiver.channels.' . $channelsValidate[$channel] = 'array';
            }
        }

        return $channelsValidate;
    }

    public function validAndFormatChannels($params, $sceneDesc = '', $withGroupFocus = false)
    {
        // 判断自定义通知渠道
        $channels = [];
        if (! empty($params['channels']) && is_array($params['channels'])) {
            // 用户类型
            $users = [];
            foreach (self::$availableChannelsUser as $channel) {
                if (empty($params['channels'][$channel])) {
                    continue;
                }
                foreach ($params['channels'][$channel] as $uid) {
                    if (! is_numeric($uid)) {
                        throw new AppException('用户UID必须为数字');
                    }
                    $uesrs[] = $uid;
                }
                $channels[$channel] = $params['channels'][$channel];
            }

            // 机器人类型
            foreach (self::$availableChannelsRobot as $channel) {
                if (empty($params['channels'][$channel])) {
                    continue;
                }
                $robots = [];
                foreach ($params['channels'][$channel] as $robotGroup) {
                    if (empty($robotGroup['webhook']) || ! is_string($robotGroup['webhook'])) {
                        throw new AppException('receiver ' . $channel . ' require webhook' . $sceneDesc);
                    }
                    if (empty($robotGroup['secret']) || ! is_string($robotGroup['secret'])) {
                        throw new AppException('receiver ' . $channel . ' require secret' . $sceneDesc);
                    }
                    // 避免webhook、secret填写错误
                    if (! preg_match('/^[a-zA-Z0-9]+$/', $robotGroup['webhook'])) {
                        throw new AppException('receiver ' . $channel . ' webhook invalid' . $sceneDesc);
                    }
                    if (! preg_match('/^[a-zA-Z0-9]+$/', $robotGroup['secret'])) {
                        throw new AppException('receiver ' . $channel . ' secret invalid' . $sceneDesc);
                    }
                    $robots[] = [
                        'webhook' => $robotGroup['webhook'],
                        'secret' => $robotGroup['secret'],
                    ];
                }
                $channels[$channel] = $robots;
            }

            // webhook类型
            if (
                ! empty($params['channels'][self::CHANNEL_WEBHOOK]) &&
                ! empty($params['channels'][self::CHANNEL_WEBHOOK]['url'])
            ) {
                $channel = self::CHANNEL_WEBHOOK;
                if (! filter_var($params['channels'][$channel]['url'], FILTER_VALIDATE_URL)) {
                    throw new AppException("receiver {$channel} url must be a active url {$sceneDesc}", [
                        'channel' => $channel,
                        'url' => $params['channels'][$channel]['url'],
                    ]);
                }
                $channels[$channel] = [
                    'url' => $params['channels'][$channel]['url'],
                ];
            }

            // groupfocus
            if ($withGroupFocus) {
                if (! empty($params['channels']['dinggroupfocus'])) {
                    $channels['dinggroupfocus'] = $params['channels']['dinggroupfocus'];
                }
            }
        }

        return $channels;
    }

    /**
     * 格式化输出channels结构.
     *
     * @param array $receiver
     * @param array $users
     * @return array
     */
    public function formatChannels($receiver, $users)
    {
        $channels = [];
        if (empty($receiver['channels'])) {
            return $channels;
        }

        foreach (AlarmGroup::$availableChannels as $channel) {
            // 设置默认值
            $channels[$channel] = $channel == AlarmGroup::CHANNEL_WEBHOOK ? ['url' => ''] : [];
            // 为空退出
            if (empty($receiver['channels'][$channel])) {
                continue;
            }

            // 钉钉群不用转换和webhook
            if (! in_array($channel, AlarmGroup::$availableChannelsUser)) {
                $channels[$channel] = $receiver['channels'][$channel];
                continue;
            }
            // 其他用户类渠道需要转换
            foreach ($receiver['channels'][$channel] as $uid) {
                if (isset($users[$uid])) {
                    $channels[$channel][] = $users[$uid];
                }
            }
        }

        return $channels;
    }

    public function storeGroup($param, $user)
    {
        $time = time();

        if ($this->hasByName($param['name'])) {
            throw new AppException('告警组名称已存在，请重新填写');
        }
        $channels = $this->validAndFormatChannels($param['receiver'], '', true);
        if (empty($channels)) {
            throw new AppException('至少需要完善一个通知渠道');
        }

        $nullPhoneUsers = $this->validPhoneByUid($param);

        // 开始入库
        Db::beginTransaction();
        try {
            $data = [
                'name' => $param['name'],
                'pinyin' => $this->pinyin->convert($param['name']),
                'remark' => $param['remark'],
                'receiver' => ['channels' => $channels],
                'created_by' => $user['uid'],
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $group = AlarmGroup::create($data);

            // 写入到通知渠道表中
            $this->saveChannelsForUser($group, $channels);
            $this->saveChannelsForRobot($group, $channels, $user);
            $this->saveChannelsForWebhook($group, $channels);

            // 保存权限
            $param['permission'][] = $user['uid'];
            $this->permission->savePermission($group['id'], $param['permission'], false);

            Db::commit();

            $group->load('creator');
            return [$group, $nullPhoneUsers];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function updateGroup($groupID, $param, $user)
    {
        // 判断是否有权限
        if (! $this->hasPermisson($user, $groupID)) {
            throw new ForbiddenException('您没有权限更新');
        }

        $time = time();
        if ($this->hasByName($param['name'], $groupID)) {
            throw new AppException('告警组名称已存在，请重新填写');
        }
        $channels = $this->validAndFormatChannels($param['receiver'], '', true);
        if (empty($channels)) {
            throw new AppException('至少需要完善一个通知渠道');
        }

        $nullPhoneUsers = $this->validPhoneByUid($param);

        $group = $this->getByIdAndThrow($groupID, true);

        // 开始入库
        Db::beginTransaction();
        try {
            $group['name'] = $param['name'];
            $group['pinyin'] = $this->pinyin->convert($param['name']);
            $group['remark'] = $param['remark'];
            $group['receiver'] = ['channels' => $channels];
            $group['updated_at'] = $time;
            $group->save();

            // 写入到通知渠道表中
            $this->saveChannelsForUser($group, $channels);
            $this->saveChannelsForRobot($group, $channels, $user);
            $this->saveChannelsForWebhook($group, $channels);

            // 保存权限
            $this->permission->savePermission($group['id'], $param['permission'], true);

            Db::commit();

            $group->load('creator');
            return [$group, $nullPhoneUsers];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 保存用户渠道信息.
     * @param mixed $group
     * @param mixed $channels
     */
    public function saveChannelsForUser($group, $channels)
    {
        foreach (AlarmGroup::$availableChannelsUser as $channel) {
            $table = 'alarm_group_' . $channel;
            // 先删除，避免提前退出逻辑未删除
            Db::table($table)->where('group_id', $group['id'])->delete();

            // 为空不保存
            if (empty($channels[$channel])) {
                continue;
            }

            $insertsChannel = [];
            foreach ($channels[$channel] as $uid) {
                $insertsChannel[] = [
                    'group_id' => $group['id'],
                    'uid' => $uid,
                ];
            }
            Db::table($table)->insert($insertsChannel);
        }
    }

    /**
     * 保存Robot渠道信息.
     * @param mixed $group
     * @param mixed $channels
     * @param mixed $user
     */
    public function saveChannelsForRobot($group, $channels, $user)
    {
        foreach (AlarmGroup::$availableChannelsRobot as $channel) {
            $table = 'alarm_group_' . $channel;
            // 先删除，避免提前退出逻辑未删除
            Db::table($table)->where('group_id', $group['id'])->delete();

            // 为空不保存
            if (empty($channels[$channel])) {
                continue;
            }

            $insertsChannel = [];
            foreach ($channels[$channel] as $channelGroup) {
                $insertsChannel[] = [
                    'group_id' => $group['id'],
                    'webhook' => $channelGroup['webhook'],
                    'secret' => $channelGroup['secret'],
                ];
            }
            Db::table($table)->insert($insertsChannel);
        }

        // 写入AlarmGroupDingGroupFocus表
        if (! empty($user['uid'])) {
            if (! empty($channels['dinggroupfocus'])) {
                Db::table('alarm_group_dinggroupfocus')->updateOrInsert(
                    ['group_id' => $group['id'], 'uid' => $user['uid']],
                    ['keywords' => $channels['dinggroupfocus']]
                );
            } else {
                AlarmGroupDingGroupFocus::where('group_id', $group['id'])->where('uid', $user['uid'])->delete();
            }
        }
    }

    /**
     * 保存Webhook渠道信息.
     * @param mixed $group
     * @param mixed $channels
     */
    public function saveChannelsForWebhook($group, $channels)
    {
        AlarmGroupWebhook::where('group_id', $group['id'])->delete();

        // 为空不保存
        if (empty($channels[self::CHANNEL_WEBHOOK])) {
            return;
        }
        AlarmGroupWebhook::create([
            'group_id' => $group['id'],
            'url' => $channels[self::CHANNEL_WEBHOOK]['url'],
            'config' => new stdClass(),
        ]);
    }

    /**
     * 列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [])
    {
        $builder = $this->with('creator');

        // 权限验证
        $user = Context::get(Auth::class)->user();
        if (! $user->isAdmin()) {
            $groupIds = $this->permission->getGroupsByUid($user['uid']);
            $builder->whereIn('id', $groupIds);
        }

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'name');

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }

        return $builder->get();
    }

    public function deleteGroup($groupId, $user)
    {
        $group = $this->getByIdAndThrow($groupId, true);

        // 判断是否有权限
        if (! $this->hasPermisson($user, $groupId)) {
            throw new ForbiddenException('您没有权限删除');
        }

        Db::beginTransaction();
        try {
            $group->delete();

            AlarmGroupDingGroupFocus::where('group_id', $groupId)->where('uid', $user['uid'])->delete();

            // 删除关联渠道表
            foreach (AlarmGroup::$availableChannels as $channel) {
                $table = 'alarm_group_' . $channel;
                Db::table($table)->where('group_id', $groupId)->delete();
            }
            // 删除权限
            AlarmGroupPermission::where('group_id', $groupId)->delete();

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')->select('uid', 'username', 'email', 'department');
    }

    public function getByIdAndThrow($groupId, $throwable = false)
    {
        $alarmGroup = $this->where('id', $groupId)->first();
        if ($throwable && empty($alarmGroup)) {
            throw new AppException("AlarmGroup [{$groupId}] not found");
        }

        return $alarmGroup;
    }

    /**
     * 告警组详情.
     * @param mixed $id
     * @param mixed $user
     */
    public function showGroup($id, $user)
    {
        $alarmGroup = $this->getByIdAndThrow($id, true)->toArray();
        $uids = [$alarmGroup['created_by']];

        // 取出用户ID
        if (! empty($alarmGroup['receiver']['channels'])) {
            foreach ($alarmGroup['receiver']['channels'] as $channel => $noticer) {
                if (in_array($channel, AlarmGroup::$availableChannelsUser)) {
                    $uids = array_merge($uids, $noticer);
                }
            }
        }

        // 查询关联用户信息
        $users = User::whereIn('uid', array_unique($uids))->select('uid', 'username', 'email', 'department')
            ->get()
            ->keyBy('uid')
            ->toArray();

        // 格式化输出
        $channels = $this->formatChannels($alarmGroup['receiver'], $users) ?: new stdClass();
        $alarmGroup['receiver']['channels'] = $channels;

        $alarmGroup['creator'] = isset($users[$alarmGroup['created_by']]) ? $users[$alarmGroup['created_by']] : null;

        // 获取dinggroup focus
        $dinggroupFocus = AlarmGroupDingGroupFocus::where('group_id', $id)
            ->where('uid', $user['uid'])
            ->value('keywords');
        $alarmGroup['receiver']['channels']['dinggroupfocus'] = $dinggroupFocus;

        // 权限
        $alarmGroup['permission'] = $this->permission($id);

        return $alarmGroup;
    }

    /**
     * 告警组：校验电话或短信通知渠道通知人有无手机号.
     * @param mixed $param
     */
    public function validPhoneByUid($param)
    {
        $phoneUid = [];
        $smsUid = [];
        if (isset($param['receiver']['channels']['sms'])) {
            $smsUid = $param['receiver']['channels']['sms'];
        }
        if (isset($param['receiver']['channels']['phone'])) {
            $phoneUid = $param['receiver']['channels']['phone'];
        }

        $channelsPhoneSmsUid = array_unique(array_merge($smsUid, $phoneUid));

        return empty($channelsPhoneSmsUid) ? [] : User::whereIn('uid', $channelsPhoneSmsUid)->where('phone', '')->get();
    }
}
