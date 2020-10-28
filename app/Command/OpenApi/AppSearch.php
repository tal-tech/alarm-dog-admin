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
class AppSearch extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('openapi:app:search');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Search OpenAPI Apps');
        $this->addOption('keyword', 'K', InputOption::VALUE_REQUIRED, '关键词');
    }

    public function handle()
    {
        $keyword = $this->input->getOption('keyword');
        if (! $keyword) {
            $this->error('搜索关键词 --keyword 不能为空');
            return;
        }
        $apps = make(OpenapiApp::class)->searchApps($keyword);

        $header = [
            'APPID', 'Token', 'Name', 'Remark', 'CreatedAt', 'UpdatedAt',
        ];
        $rows = [];
        foreach ($apps as $app) {
            $rows[] = [
                $app->appid, $app->token, $app->name, $app->remark,
                date('Y-m-d H:i:s', $app->created_at), date('Y-m-d H:i:s', $app->updated_at),
            ];
        }
        $this->table($header, $rows);
    }
}
