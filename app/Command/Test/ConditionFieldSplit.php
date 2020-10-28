<?php

declare(strict_types=1);

namespace App\Command\Test;

use App\Support\ConditionArr;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class ConditionFieldSplit extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('test:conditionFieldSplit');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('条件字符串拆分测试，此处为临时测试，正规测试应写单元测试');
    }

    public function handle()
    {
        // 待测试字符串
        $fields = [
            'ctn.host.name',
            'ctn.[host.name',
            'ctn.[host.name]',
            'ctn.host.[name',
            '[ctn.host.name',
            '[ctn.host].name',
            'ctn[a.host.name',
            'ctn.host[.name',
            'ctn.hos]t.name',
            'ctn.[hos]t.name]',
            'ctn.\[hos]t.name\]',
            'ctn.host.[name].[[name]].[name]',
            'ctn.host.[name].a.[world]',
            'ctn.host.[name].world]',
            'ctn.[host.[name].[world]',
            '[ctn.host].name.[a.]b].c',
        ];

        foreach ($fields as $field) {
            $fieldSplit = ConditionArr::fieldSplit($field);
            $jsonSplit = json_encode($fieldSplit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $this->info(sprintf("testing field '%s', output '%s'", $field, $jsonSplit));
        }
    }
}
