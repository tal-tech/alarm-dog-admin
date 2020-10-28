<?php

declare(strict_types=1);

namespace App\Command\Clickhouse;

use App\Exception\AppException;
use App\Model\Workflow;
use App\Support\Clickhouse\Clickhouse;
use App\Support\Process\SingleProcessTask;
use ClickHouseDB\Client as ClickhouseClient;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * @Command
 */
class SyncWorkflow extends HyperfCommand
{
    use SingleProcessTask;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ClickhouseClient
     */
    protected $db;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $container->get(Clickhouse::class)->getDb();

        parent::__construct('clickhouse:sync-workflow');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Sync alarm workflow from MySQL into clickhouse');
    }

    public function handle()
    {
        if ($pid = $this->isRunning()) {
            throw new AppException(sprintf('Another process is running at %s', $pid));
        }
        $this->savePidFile();

        // 多久时间以前的数据入clickhouse
        $unitTime = time() - config('clickhouse.sync.workflow.until_time');
        $unitTime = strtotime(date('Y-m-d 00:00:00', $unitTime));
        $batchSize = config('clickhouse.sync.workflow.batch_size');
        $sleepTime = config('clickhouse.sync.workflow.sleep_time');

        $header = [
            'id', 'task_id', 'metric', 'history_id', 'status', 'created_at', 'updated_at',
        ];

        while (true) {
            $startTime = microtime(true);
            $list = Workflow::where('created_at', '<', $unitTime)
                ->orderBy('id', 'asc')
                ->limit($batchSize)
                ->get();

            // 为空退出循环
            if ($list->isEmpty()) {
                $this->info('mysql data is empty, end the sync');
                break;
            }
            $firstId = $list->first()['id'];
            $lastId = $list->last()['id'];
            $count = $list->count();
            $this->info(sprintf(
                '[%sms]start sync data from %s to %s count %s',
                (microtime(true) - $startTime) * 1000,
                $firstId,
                $lastId,
                $count
            ));

            // 数据写入clickhouse
            $startTime = microtime(true);
            $rows = [];
            foreach ($list as $item) {
                $rows[] = [
                    $item['id'],
                    $item['task_id'],
                    $item['metric'],
                    $item['history_id'],
                    $item['status'],
                    $item['created_at'],
                    $item['updated_at'],
                ];
            }

            $statement = $this->db->insert('xes_alarm_workflow', $rows, $header);

            // 删除mysql数据
            Workflow::where('id', '>=', $firstId)
                ->where('id', '<=', $lastId)
                ->delete();

            $this->info(sprintf(
                '[%sms]successfully synced data from %s to %s count %s',
                (microtime(true) - $startTime) * 1000,
                $firstId,
                $lastId,
                $count
            ));

            // 查询出来的数据小于batchSize说明已到终点，退出循环
            if ($count < $batchSize) {
                $this->info('the count of sync data less than the batch size, end the sync');
                break;
            }

            Coroutine::sleep($sleepTime);
        }

        $this->removePidFile();
    }

    /**
     * @return string
     */
    protected function getPidFile()
    {
        return BASE_PATH . '/runtime/clickhouse-sync-workflow.pid';
    }
}
