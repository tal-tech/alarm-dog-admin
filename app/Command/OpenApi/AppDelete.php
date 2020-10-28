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
class AppDelete extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('openapi:app:delete');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Delete OpenAPI App');
        $this->addOption('appid', 'A', InputOption::VALUE_REQUIRED, '应用ID');
    }

    public function handle()
    {
        $appid = $this->input->getOption('appid');
        if (! $appid) {
            $this->error('应用ID --appid 不能为空');
            return;
        }
        $app = make(OpenapiApp::class)->deleteApp($appid);

        $this->line('Delete successfully');

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
