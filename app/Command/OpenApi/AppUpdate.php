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
class AppUpdate extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('openapi:app:update');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update OpenAPI App');
        $this->addOption('appid', 'A', InputOption::VALUE_REQUIRED, '应用ID');
        $this->addOption('name', 'N', InputOption::VALUE_OPTIONAL, '应用名称');
        $this->addOption('remark', 'R', InputOption::VALUE_OPTIONAL, '备注');
        $this->addOption('token', 'T', InputOption::VALUE_NONE, '是否重置token');
    }

    public function handle()
    {
        $appid = $this->input->getOption('appid');
        $name = $this->input->getOption('name');
        $remark = $this->input->getOption('remark');
        $resetToken = $this->input->getOption('token');
        if (! $appid) {
            $this->error('应用ID --appid 不能为空');
            return;
        }
        if (! $name && ! $remark && ! $resetToken) {
            $this->error('至少需修改一项数据');
            return;
        }
        $app = make(OpenapiApp::class)->updateApp($appid, $name, $remark, $resetToken);

        $this->line('Update successfully');

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
