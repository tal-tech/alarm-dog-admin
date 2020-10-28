<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmTaskConfig;
use App\Model\AlarmTemplate;
use App\Support\ConditionArr;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class TransformConditionFieldSplit extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:transform-condition-field-split');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Transform condition field split for upgrade');
    }

    public function handle()
    {
        AlarmTaskConfig::chunk(10, function ($items) {
            foreach ($items as $item) {
                $compress = $this->jsonDecode($item['compress']);
                if (! empty($compress) && ! empty($compress['conditions'])) {
                    $compress['conditions'] = $this->splitConditions($compress['conditions']);
                    $item['compress'] = $this->jsonEncode($compress);
                }

                $filter = $this->jsonDecode($item['filter']);
                if (! empty($filter) && ! empty($filter['conditions'])) {
                    $filter['conditions'] = $this->splitConditions($filter['conditions']);
                    $item['filter'] = $this->jsonEncode($filter);
                }

                $recovery = $this->jsonDecode($item['recovery']);
                if (! empty($recovery) && ! empty($recovery['conditions'])) {
                    $recovery['conditions'] = $this->splitConditions($recovery['conditions']);
                    $item['recovery'] = $this->jsonEncode($recovery);
                }

                $receiver = $this->jsonDecode($item['receiver']);
                if (! empty($receiver) && ! empty($receiver['dispatch'])) {
                    $receiver['dispatch'] = $this->splitReceiverDispatch($receiver['dispatch']);
                    $item['receiver'] = $this->jsonEncode($receiver);
                }

                $template = $this->jsonDecode($item['alarm_template']);
                if (! empty($template)) {
                    $template = $this->splitTemplate($template);
                    $item['alarm_template'] = $this->jsonEncode($template);
                }
                $item->save();
                $this->info(sprintf('transform taskid \'%s\' successfully', $item['task_id']));
            }
        });

        AlarmTemplate::chunk(10, function ($items) {
            foreach ($items as $item) {
                $item['template'] = $this->splitTemplate($item['template']);
                $item->save();

                $this->info(sprintf('transform template named \'%s\' successfully', $item['name']));
            }
        });

        $this->info('Done!');
    }

    /**
     * 拆分条件类结构体.
     */
    protected function splitConditions(array $conditions): array
    {
        foreach ($conditions as &$rules) {
            foreach ($rules['rule'] as &$rule) {
                $rule['field_split'] = ConditionArr::fieldSplit($rule['field']);
            }
        }
        unset($rules, $rule);

        return $conditions;
    }

    /**
     * 拆分分级告警通知人结构体.
     */
    protected function splitReceiverDispatch(array $dispatches): array
    {
        foreach ($dispatches as &$dispatch) {
            $dispatch['conditions'] = $this->splitConditions($dispatch['conditions']);
        }
        unset($dispatch);

        return $dispatches;
    }

    /**
     * 拆分模板结构体.
     */
    protected function splitTemplate(array $template): array
    {
        foreach ($template as $scene => &$channels) {
            foreach ($channels as $channel => &$tplConfig) {
                $tplConfig['vars_split'] = $this->splitTemplateVars($tplConfig['vars']);
            }
        }
        unset($channels, $tplConfig);

        return $template;
    }

    /**
     * 拆分模板变量.
     */
    protected function splitTemplateVars(array $vars): array
    {
        $splitVars = [];
        foreach ($vars as $var) {
            $splitVars[$var] = ConditionArr::fieldSplit($var);
        }

        return $splitVars;
    }

    /**
     * 解析json.
     */
    protected function jsonDecode(?string $str): ?array
    {
        if (empty($str)) {
            return null;
        }
        $json = json_decode($str, true);
        return $json ?: null;
    }

    /**
     * encode json.
     */
    protected function jsonEncode(?array $json): string
    {
        if (empty($json)) {
            return '';
        }
        $str = json_encode($json);
        return $str ?: '';
    }
}
