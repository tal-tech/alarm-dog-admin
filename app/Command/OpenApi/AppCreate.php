<?php

declare(strict_types=1);

namespace App\Command\OpenApi;

use App\Model\OpenapiApp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class AppCreate extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('openapi:app:create');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create OpenAPI App');
        $this->addOption('name', 'N', InputOption::VALUE_REQUIRED, '应用名称');
        $this->addOption('remark', 'R', InputOption::VALUE_OPTIONAL, '备注', '');
    }

    public function handle()
    {
        $name = $this->input->getOption('name');
        $remark = $this->input->getOption('remark');
        if (! $name) {
            $this->error('应用名称 --name 不能为空');
            return;
        }
        $app = make(OpenapiApp::class)->createApp($name, $remark);

        $this->line('Crate successfully');

        $header = [
            'APPID', 'Token', 'Name', 'Remark', 'CreatedAt', 'UpdatedAt',
        ];
        $rows = [
            [
                $app->appid, $app->token, $app->name, $app->remark,
                date('Y-m-d H:i:s', $app->created_at), date('Y-m-d H:i:s', $app->updated_at),
            ],
        ];
        $this->table($header, $rows);
    }
}
