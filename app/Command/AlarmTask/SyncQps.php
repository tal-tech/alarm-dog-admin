<?php

declare(strict_types=1);

namespace App\Command\AlarmTask;

use App\Exception\AppException;
use App\Model\AlarmTaskQps;
use App\Model\Config;
use App\Support\Process\SingleProcessTask;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @Command
 */
class SyncQps extends HyperfCommand
{
    use SingleProcessTask;

    public const REDIS_KEY_PREFIX_REQUEST = 'dog-req.';

    public const REDIS_KEY_PREFIX_RATE_LIMIT = 'dog-prod.';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var Config
     */
    protected $taskConfig;

    /**
     * @Inject
     * @var Redis
     */
    protected $redis;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(LoggerFactory::class)->get('sync-qps');
        parent::__construct('alarmTask:sync-qps');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('alarm task sync QPS from Redis into MySQL');
    }

    public function handle()
    {
        if ($pid = $this->isRunning()) {
            throw new AppException(sprintf('Another process is running at %s', $pid));
        }
        $this->savePidFile();

        $configKey = 'task-qps-statistics';
        $curTime = time();
        $lastExecuteTime = (int) $this->taskConfig->getRaw($configKey);

        if ($lastExecuteTime >= $curTime) {
            $lastExecuteTime = $curTime - 60;
        }

        if (! $lastExecuteTime) {
            $lastExecuteTime = $curTime - 60;
            $this->taskConfig->createConfig($configKey, $lastExecuteTime);
        }

        $lastExecuteTime = $lastExecuteTime - $lastExecuteTime % 60;

        for ($time = $lastExecuteTime; $time <= time() - 60; $time += 60) {
            $data = $this->statisticsQps($time, true, 0);
            if (! empty($data)) {
                AlarmTaskQps::Insert($data);
                $this->logger->info(sprintf('statisticsQps success, whitch qps time is %s', $time));
            } else {
                $this->logger->warning(sprintf('statisticsQps failed, current sync data in redis is empty, whitch qps time is %s', $time));
            }
            $this->taskConfig->updateConfig($configKey, $time);
        }
        $this->removePidFile();
    }

    public function statisticsQps($time, $needDeleteField = true, $second = 0)
    {
        $data = [];
        $reqKey = static::REDIS_KEY_PREFIX_REQUEST . date('Y-m-d-H-i', $time);
        $prodKey = static::REDIS_KEY_PREFIX_RATE_LIMIT . date('Y-m-d-H-i', $time);

        $req = $this->getAllField($reqKey);
        $prod = $this->getAllField($prodKey);

        if (! empty($req)) {
            $reqQps = [];
            foreach ($req as $field => $value) {
                $fieldArr = explode('-', $field);
                $taskId = $fieldArr[0];
                if (! isset($reqQps[$taskId])) {
                    $reqQps[$taskId]['req_total_qps'] = 0;
                    $reqQps[$taskId]['req_max_qps'] = 0;
                }
                $reqQps[$taskId]['req_total_qps'] += $value;
                $reqQps[$taskId]['req_max_qps'] = max($reqQps[$taskId]['req_max_qps'], $value);
                if ($needDeleteField) {
                    $this->delField($reqKey, $field);
                }
            }
            foreach ($reqQps as $taskId => $qps) {
                $data[$taskId] = [
                    'task_id' => $taskId,
                    'req_avg_qps' => round($qps['req_total_qps'] / ($second ? $second : 60), 2),
                    'req_max_qps' => $qps['req_max_qps'],
                    'prod_avg_qps' => 0.00,
                    'prod_max_qps' => 0,
                    'created_at' => $time,
                ];
            }
        }

        if (! empty($prod)) {
            $prodQps = [];
            foreach ($prod as $field => $value) {
                $fieldArr = explode('-', $field);
                $taskId = $fieldArr[0];
                if (! isset($prodQps[$taskId])) {
                    $prodQps[$taskId]['prod_total_qps'] = 0;
                    $prodQps[$taskId]['prod_max_qps'] = 0;
                }
                $prodQps[$taskId]['prod_total_qps'] += $value;
                $prodQps[$taskId]['prod_max_qps'] = max($prodQps[$taskId]['prod_max_qps'], $value);
                if ($needDeleteField) {
                    $this->delField($reqKey, $field);
                }
            }

            foreach ($prodQps as $taskId => $qps) {
                if (isset($data[$taskId])) {
                    $data[$taskId]['prod_avg_qps'] = round($qps['prod_total_qps'] / ($second ? $second : 60), 2);
                    $data[$taskId]['prod_max_qps'] = $qps['prod_max_qps'];
                } else {
                    $data[$taskId] = [
                        'task_id' => $taskId,
                        'req_avg_qps' => 0.00,
                        'req_max_qps' => 0,
                        'prod_avg_qps' => round($qps['prod_total_qps'] / ($second ? $second : 60), 2),
                        'prod_max_qps' => $qps['prod_max_qps'],
                        'created_at' => $time,
                    ];
                }
            }
        }
        return array_values($data);
    }

    protected function getAllField($key)
    {
        try {
            return $this->redis->hGetAll($key);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
        return [];
    }

    protected function delField($key, $field)
    {
        try {
            $this->redis->hDel($key, $field);
        } catch (Throwable $e) {
            $this->logger->error($e);
        }
    }

    /**
     * @return string
     */
    protected function getPidFile()
    {
        return BASE_PATH . '/runtime/alarmtask-sync-qps.pid';
    }
}
