<?php

declare(strict_types=1);

namespace App\Command\AlarmTask;

use App\Exception\AppException;
use App\Model\AlarmTask;
use App\Model\DelayQueueAlarmTaskPause;
use App\Support\Process\SingleProcessTask;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * @Command
 */
class PauseRecovery extends HyperfCommand
{
    use SingleProcessTask;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 最大键值
     */
    protected $maxId = 0;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        // 脚本执行命令
        parent::__construct('alarmTask:pause-recovery');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('alarm task pause recovery run');
    }

    public function handle()
    {
        try {
            if ($pid = $this->isRunning()) {
                throw new AppException('Another process is running at ' . $pid);
            }
            $this->savePidFile();

            // 分页查询数据，每页10条，50页。每页间隔1秒(单位)，避免数据库造不必要的压力
            $pageSize = 10;
            $loopCount = 50;
            $loopTime = 1;
            $curNum = 0;
            // 求最大主键ID，分页使用
            $this->maxId = 0;

            while ($curNum < $loopCount) {
                $curNum++;
                $curTime = time();

                // 查询出要恢复的数据
                $pauses = $this->getPauses($pageSize, $curTime);
                if (empty($pauses)) {
                    $this->info('[PauseRecovery:getPauses:null]');
                    break;
                }

                // 暂停状态恢复为执行状态，检测过虑掉已在执行状态的数据
                $succ = $this->recoveryRun($pauses, $curTime);

                $this->info(sprintf(
                    '[PauseRecovery:%s][%s][%s]',
                    ! $succ ? 'fail' : 'succ',
                    json_encode($pauses),
                    json_encode($succ)
                ));

                Coroutine::sleep($loopTime);
            }
        } catch (AppException $e) {
            $this->info(sprintf(
                '[PauseRecovery][fail][msg:%s][code:%s][line:%s][file:%s]',
                $e->getMessage(),
                $e->getCode(),
                $e->getLine(),
                $e->getFile()
            ));
        }
    }

    /**
     * 执行暂停恢复操作，事物
     * 1.恢复暂停状态
     * 2.物理删除暂停表数据.
     *
     * @param $pauses 暂停任务
     * @param $time 当前时间
     * @return bool
     */
    public function recoveryRun(array $pauses, int $time)
    {
        Db::beginTransaction();

        try {
            // 更新暂停状态
            $taskIds = array_column($pauses, 'task_id');
            $succ = make(AlarmTask::class)->updatePauseToRunning($time, $taskIds);
            if (! $succ) {
                throw new AppException(sprintf(
                    '[recoveryRun-AlarmTask:fail][%s][%s]',
                    json_encode($taskIds),
                    json_encode($succ)
                ));
            }

            // 物理删除暂停表数据
            $ids = array_column($pauses, 'id');
            $succ = make(DelayQueueAlarmTaskPause::class)->deleteByIds($ids);
            if (! $succ) {
                throw new AppException(sprintf(
                    '[recoveryRun-DelayQueueAlarmTaskPause:fail][%s][%s]',
                    json_encode($ids),
                    json_encode($succ)
                ));
            }

            Db::commit();

            return true;
        } catch (AppException $e) {
            Db::rollback();
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 获取已暂停的数据.
     *
     * @param $pageSize
     * @param $time
     * @return array
     */
    public function getPauses($pageSize, $time)
    {
        $ret = [];

        $queues = make(DelayQueueAlarmTaskPause::class)->getPauseRecoverys($this->maxId, $time, $pageSize);
        if (empty($queues)) {
            return [];
        }

        // 校验数据是否暂停状态
        $taskIds = array_column($queues, 'task_id');
        $pauses = make(AlarmTask::class)->getStatusPauses($taskIds);
        if (empty($pauses)) {
            return $queues;
        }

        // 过虑掉不是暂停状态
        $pauses = array_column($pauses, null, 'id');
        foreach ($queues as $queue) {
            $taskId = $queue['task_id'];
            if (! isset($pauses[$taskId])) {
                // 状态不对的数据，记录
                $this->info('[PauseRecovery:taskStatus:error][' . json_encode($queue) . ']');
                continue;
            }
            $ret[] = $queue;
        }

        return $ret;
    }

    protected function getPidFile()
    {
        return BASE_PATH . '/runtime/alarm-task-pause-recovery.pid';
    }
}
