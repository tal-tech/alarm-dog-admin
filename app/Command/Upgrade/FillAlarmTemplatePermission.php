<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmTemplate;
use App\Model\AlarmTemplatePermission;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class FillAlarmTemplatePermission extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:fill-alarm-template-permission');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Fill permissions into `alarm_template` module for upgrade');
    }

    public function handle()
    {
        // 已存在的权限映射为map，方便对比
        $existsPermission = AlarmTemplatePermission::select('template_id', 'uid')->get();
        $existsMap = [];
        foreach ($existsPermission as $item) {
            $existsMap[$item['template_id'] . '_' . $item['uid']] = 1;
        }

        // 去重，取出权限
        $templates = AlarmTemplate::select('id', 'created_by')->get();
        $data = [];
        foreach ($templates as $template) {
            if (isset($existsMap[$template['id'] . '_' . $template['created_by']])) {
                continue;
            }
            $data[] = [
                'template_id' => $template['id'],
                'uid' => $template['created_by'],
            ];
        }

        if (! empty($data)) {
            Db::table('alarm_template_permission')->insert($data);
        }

        $this->info('Done!');
    }
}
