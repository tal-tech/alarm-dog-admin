<?php

declare(strict_types=1);

namespace App\Command\Seed;

use App\Model\AlarmTask;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class AlarmTaskQps extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('seed:alarm-task-qps');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Seed for Model AlarmTaskQps');
    }

    public function handle()
    {
        $taskIds = AlarmTask::pluck('id')->toArray();
        $tomorrow = strtotime('tomorrow');

        $this->line('seed的数据较多，速度比较慢，请耐心等待...', 'info');

        $data = [];
        $percent = 5;
        for ($time = strtotime('today'); $time += 60; $time < $tomorrow) {
            $this->line(sprintf('seed at %s', date('Y-m-d H:i:s', $time)), 'info');

            foreach ($taskIds as $taskId) {
                if (mt_rand(1, 10) > $percent) {
                    continue;
                }
                $data[] = [
                    'task_id' => $taskId,
                    'req_avg_qps' => mt_rand(1, 100000) / 100,
                    'req_max_qps' => mt_rand(1, 100000) / 100,
                    'prod_avg_qps' => mt_rand(1, 100000) / 100,
                    'prod_max_qps' => mt_rand(1, 100000) / 100,
                    'created_at' => $time,
                ];

                if (count($data) > 10000) {
                    Db::table('alarm_task_qps')->insert($data);
                    $data = [];
                }
            }
        }

        if (! empty($data)) {
            Db::table('alarm_task_qps')->insert($data);
        }
    }
}
