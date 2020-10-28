<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmTemplate;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * @Command
 */
class FillAlarmTemplateFormat extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:fill-alarm-template-format');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Fill format field into `alarm_template` table for upgrade');
    }

    public function handle()
    {
        $templates = AlarmTemplate::select('id', 'name', 'template')->get();
        foreach ($templates as $template) {
            $tpl = [];
            foreach ($template['template'] as $scene => $sceneTpl) {
                foreach ($sceneTpl as $channel => $tplConf) {
                    if (! isset($tplConf['format'])) {
                        $tplConf['format'] = AlarmTemplate::FORMAT_TEXT;
                    }
                    $tpl[$scene][$channel] = $tplConf;
                }
            }
            $template['template'] = $tpl ?: new stdClass();
            $template->save();

            $this->info("Updated template [{$template['id']}:{$template['name']}]");
        }

        $this->info('Done!');
    }
}
