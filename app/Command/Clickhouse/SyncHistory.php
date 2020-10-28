<?php

declare(strict_types=1);

namespace App\Command\Clickhouse;

use App\Exception\AppException;
use App\Model\AlarmHistory;
use App\Support\Clickhouse\Clickhouse;
use App\Support\Process\SingleProcessTask;
use ClickHouseDB\Client as ClickhouseClient;
use Dog\Alarm\Alarm;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * @Command
 */
class SyncHistory extends HyperfCommand
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

    /**
     * 告警SDK.
     *
     * @var Alarm
     */
    protected $alarm;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $container->get(Clickhouse::class)->getDb();
        $this->alarm = $container->get(Alarm::class);

        parent::__construct('clickhouse:sync-history');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Sync alarm history from MySQL into clickhouse');
    }

    public function handle()
    {
        if ($pid = $this->isRunning()) {
            throw new AppException(sprintf('Another process is running at %s', $pid));
        }
        $this->savePidFile();

        // 全局耗时统计
        $globalStartTime = microtime(true);
        // 全局计数器
        $globalCounter = 0;

        // 多久时间以前的数据入clickhouse
        $unitTime = time() - config('clickhouse.sync.history.until_time');
        $unitTime = strtotime(date('Y-m-d 00:00:00', $unitTime));
        $batchSize = config('clickhouse.sync.history.batch_size');
        $sleepTime = config('clickhouse.sync.history.sleep_time');

        $header = [
            'id', 'task_id', 'uuid', 'batch', 'metric', 'notice_time', 'level', 'ctn', 'receiver', 'type',
            'created_at',
        ];
        while (true) {
            $startTime = microtime(true);
            $list = AlarmHistory::where('created_at', '<', $unitTime)
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
                    $item['uuid'],
                    $item['batch'],
                    $item['metric'],
                    $item['notice_time'],
                    $item['level'],
                    $item['ctn'],
                    $item['receiver'],
                    $item['type'],
                    $item['created_at'],
                ];
            }
            $statement = $this->db->insert('xes_alarm_alarm_history_all', $rows, $header);

            // 删除mysql数据
            AlarmHistory::where('id', '>=', $firstId)
                ->where('id', '<=', $lastId)
                ->delete();

            $this->info(sprintf(
                '[%sms]successfully synced data from %s to %s count %s',
                (microtime(true) - $startTime) * 1000,
                $firstId,
                $lastId,
                $count
            ));

            $globalCounter += $count;

            // 查询出来的数据小于batchSize说明已到终点，退出循环
            if ($count < $batchSize) {
                $this->info('the count of sync data less than the batch size, end the sync');
                break;
            }

            Coroutine::sleep($sleepTime);
        }

        $this->removePidFile();

        $duration = microtime(true) - $globalStartTime;

        // 发送告警通知
        $this->alarm->report([
            'msg' => '同步history数据到clickhouse成功',
            'env' => env('DOG_VARS_COMMON_ENV', 'UNKNOWN'),
            'until_time' => date('Y-m-d H:i:s', $unitTime),
            'batch_size' => $batchSize,
            'sleep_time' => $sleepTime,
            'duration' => number_format($duration, 3) . 's',
            'rows_count' => $globalCounter,
        ]);
    }

    /**
     * @return string
     */
    protected function getPidFile()
    {
        return BASE_PATH . '/runtime/clickhouse-sync-history.pid';
    }
}
