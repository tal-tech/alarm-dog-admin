<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmGroup;
use App\Model\AlarmGroupPermission;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class FillAlarmGroupPermission extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:fill-alarm-group-permission');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Fill permissions into `alarm_group` module for upgrade');
    }

    public function handle()
    {
        // 已存在的权限映射为map，方便对比
        $existsPermission = AlarmGroupPermission::select('group_id', 'uid')->get();
        $existsMap = [];
        foreach ($existsPermission as $item) {
            $existsMap[$item['group_id'] . '_' . $item['uid']] = 1;
        }

        // 去重，取出权限
        $groups = AlarmGroup::select('id', 'created_by')->get();
        $data = [];
        foreach ($groups as $group) {
            if (isset($existsMap[$group['id'] . '_' . $group['created_by']])) {
                continue;
            }
            $data[] = [
                'group_id' => $group['id'],
                'uid' => $group['created_by'],
            ];
        }

        if (! empty($data)) {
            Db::table('alarm_group_permission')->insert($data);
        }

        $this->info('Done!');
    }
}
