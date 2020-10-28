<?php

declare(strict_types=1);

namespace App\Support\Process;

use Swoole\Process;
use Throwable;

trait SingleProcessTask
{
    // protected function getPidFile()
    // {
    //     return BASE_PATH . '/runtime/process-name.pid';
    // }

    /**
     * 判断某个PID文件是否在运行中.
     *
     * @return bool|int
     */
    protected function isRunning()
    {
        $pidFile = $this->getPidFile();

        if (! file_exists($pidFile)) {
            return false;
        }
        $pid = (int) file_get_contents($pidFile);
        if (! $pid) {
            return false;
        }
        try {
            if (! Process::kill($pid, 0)) {
                return false;
            }
            return $pid;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * 保存pid文件.
     */
    protected function savePidFile()
    {
        $pidFile = $this->getPidFile();

        $dir = dirname($pidFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($pidFile, getmypid());
    }

    /**
     * 移除pid文件.
     */
    protected function removePidFile()
    {
        $pidFile = $this->getPidFile();
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }
}
