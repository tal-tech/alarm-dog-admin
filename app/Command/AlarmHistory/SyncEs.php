<?php

declare(strict_types=1);

namespace App\Command\AlarmHistory;

use App\Exception\AppException;
use App\Support\MQProxy\Consumer;
use App\Support\Process\SingleProcessTask;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * @Command
 */
class SyncEs extends HyperfCommand
{
    use SingleProcessTask;

    /**
     * @Inject
     * @var Consumer
     */
    protected $consumer;

    protected $operateMap = [
        'alarm_platform.xes_alarm_alarm_history' => \App\Service\AlarmHistoryElastic::class,
        'alarm_platform_test.xes_alarm_alarm_history' => \App\Service\AlarmHistoryElastic::class,
    ];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('elasticsearch:sync-alarmhistory');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Sync alarm history from MySQL into elasticsearch');
    }

    public function handle()
    {
        try {
            if ($pid = $this->isRunning()) {
                throw new AppException(sprintf('Another process is running at %s', $pid));
            }
            $this->savePidFile();

            $config = make(ConfigInterface::class)->get('kafkaproxy.kafka.consumer_host');
            $topic = make(ConfigInterface::class)->get('kafkaproxy.topic.alarmhistory_to_es');
            $perPullNum = make(ConfigInterface::class)->get('kafkaproxy.per_pull_num.alarmhistory');
            $loopCount = 5;
            $loopTime = 1;
            $curNum = 0;

            while ($curNum < $loopCount) {
                $curNum++;

                // 监听kafka消息队列
                $ret = $this->consumer->fetchKafka('http://' . $config, $topic, ['maxMsgs' => $perPullNum]);
                if (empty($ret['data'])) {
                    throw new AppException('nothing fetching successfully in Kafka data null', [
                        'response' => $ret,
                    ]);
                }
                $data = $ret['data'];

                foreach ($data as $k => $item) {
                    if (! isset($item['payload'])) {
                        continue;
                    }

                    $payload = json_decode($item['payload'], true);
                    if (empty($payload)) {
                        continue;
                    }

                    $database = $payload['database'];
                    $table = $payload['table'];

                    $key = $database . '.' . $table;

                    if (! isset($this->operateMap[$key])) {
                        continue;
                    }

                    $ret = make($this->operateMap[$key])->setEsDocAlarmHistorys($payload);

                    $this->info(sprintf(
                        '[SyncEs-AlarmHistory-handle][%s][%s]',
                        json_encode($ret),
                        json_encode($payload)
                    ));
                }

                Coroutine::sleep($loopTime);
            }

            $this->removePidFile();
        } catch (AppException $e) {
            $this->info(sprintf(
                '[SyncEs-AlarmHistory-AppException][%s][%s][%s][%s]',
                $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine(),
                json_encode($e->getContext()),
                json_encode($e->getPrevious()),
                $e->getCode()
            ));
        }
    }

    /**
     * @return string
     */
    protected function getPidFile()
    {
        return BASE_PATH . '/runtime/elasticsearch-sync-alarmhistory.pid';
    }
}
