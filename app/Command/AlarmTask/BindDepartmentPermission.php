<?php

declare(strict_types=1);

namespace App\Command\AlarmTask;

use App\Exception\AppException;
use App\Model\AlarmTask;
use App\Model\AlarmTaskPermission;
use App\Model\Department;
use App\Model\User;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class BindDepartmentPermission extends HyperfCommand
{
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
        parent::__construct('alarmTask:bindDepartmentPermission');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('bind deparment permission');
        $this->addOption('uid', 'U', InputOption::VALUE_REQUIRED, '用户ID');
        $this->addOption('departmentId', 'D', InputOption::VALUE_REQUIRED, '部门ID');
        $this->addOption('type', 'T', InputOption::VALUE_OPTIONAL, '权限类型', AlarmTaskPermission::TYPE_RW);
    }

    public function handle()
    {
        $departmentId = $this->input->getOption('departmentId');
        // 判断部门是否合法
        $department = $this->container->get(Department::class)->getByIdAndThrow($departmentId);

        $uid = $this->input->getOption('uid');
        // 判断用户是否合法
        $user = $this->container->get(User::class)->findUser($uid);
        if (empty($user)) {
            throw new AppException(sprintf('user not find: %s', $uid));
        }

        $type = $this->input->getOption('type');
        if (! in_array($type, [AlarmTaskPermission::TYPE_RO, AlarmTaskPermission::TYPE_RW])) {
            throw new AppException('invalid type');
        }

        $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
        $bindTaskIds = AlarmTaskPermission::whereIn('task_id', $taskIds)
            ->where('uid', $uid)
            ->where('type', $type)
            ->pluck('task_id')
            ->toArray();
        $notBindTaskIds = array_values(array_diff($taskIds, $bindTaskIds));

        $inserts = [];
        foreach ($notBindTaskIds as $taskId) {
            $inserts[] = [
                'task_id' => $taskId,
                'type' => $type,
                'uid' => $uid,
            ];
        }
        if (! empty($inserts)) {
            Db::table('alarm_task_permission')->insert($inserts);
            $this->info(sprintf('bind department: %s', implode(', ', $notBindTaskIds)));
        } else {
            $this->info('no department need to bind');
        }
    }
}
