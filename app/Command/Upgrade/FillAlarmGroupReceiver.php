<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmGroup;
use App\Model\AlarmGroupWebhook;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * @Command
 */
class FillAlarmGroupReceiver extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:fill-alarm-group-receiver');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Fill receiver field into `alarm_group` table for upgrade');
    }

    public function handle()
    {
        $groups = AlarmGroup::select('id', 'name', 'receiver')->get();
        $groupChannels = [];

        // 用户类型
        foreach (AlarmGroup::$availableChannelsUser as $channel) {
            $table = 'alarm_group_' . $channel;
            $groupedChannel = Db::table($table)->select('group_id', 'uid')->get()->groupBy('group_id');
            foreach ($groupedChannel as $groupId => $groupChannel) {
                $uids = [];
                foreach ($groupChannel as $item) {
                    $uids[] = (int) $item->uid;
                }
                $groupChannels[$groupId][$channel] = array_unique($uids);
            }
        }

        // 机器人类型
        foreach (AlarmGroup::$availableChannelsRobot as $channel) {
            $table = 'alarm_group_' . $channel;
            $groupedChannel = Db::table($table)->select('group_id', 'webhook', 'secret')->get()->groupBy('group_id');
            foreach ($groupedChannel as $groupId => $groupChannel) {
                $robots = [];
                foreach ($groupChannel as $item) {
                    $robots[$item->webhook] = [
                        'webhook' => $item->webhook,
                        'secret' => $item->secret,
                    ];
                }
                $groupChannels[$groupId][$channel] = array_values($robots);
            }
        }

        // webhook类型
        $groupedChannel = AlarmGroupWebhook::select('group_id', 'url', 'config')->get()->keyBy('group_id');
        foreach ($groupedChannel as $groupId => $groupChannel) {
            $groupChannels[$groupId][AlarmGroup::CHANNEL_WEBHOOK] = [
                'url' => $groupChannel->url,
            ];
        }

        // 遍历groups更新
        foreach ($groups as $group) {
            if (isset($groupChannels[$group['id']])) {
                $channels = $groupChannels[$group['id']];
            } else {
                $channels = new stdClass();
            }
            $group['receiver'] = [
                'channels' => $channels,
            ];
            $group->save();

            $this->info("Updated group [{$group['id']}:{$group['name']}]");
        }

        $this->info('Done!');
    }
}
