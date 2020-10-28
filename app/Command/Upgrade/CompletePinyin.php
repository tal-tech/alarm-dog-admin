<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\AlarmGroup;
use App\Model\AlarmTask;
use App\Model\AlarmTemplate;
use App\Model\Department;
use App\Model\User;
use App\Service\Pinyin;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class CompletePinyin extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:complete-pinyin');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Complete pinyin for upgrade to supportting search using pinyin');
    }

    public function handle()
    {
        $this->updateUserTable();
        $this->info('Complately update `user` table');

        $this->updateAlarmTaskTable();
        $this->info('Complately update `alarm_task` table');

        $this->updateAlarmGroupTable();
        $this->info('Complately update `alarm_group` table');

        $this->updateAlarmTemplateTable();
        $this->info('Complately update `alarm_template` table');

        $this->updateDepartmentTable();
        $this->info('Complately update `department` table');

        $this->info('All convert done!');
    }

    /**
     * 更新用户姓名.
     */
    protected function updateUserTable()
    {
        $users = User::select('id', 'username', 'pinyin')->get();
        foreach ($users as $user) {
            // 无论是否有拼音，都进行强制覆盖，避免有出错的
            $user['pinyin'] = $this->pinyin->name($user['username']);
            $user->save();
        }
    }

    /**
     * 更新告警任务名称.
     */
    protected function updateAlarmTaskTable()
    {
        $tasks = AlarmTask::select('id', 'name', 'pinyin')->get();
        foreach ($tasks as $task) {
            // 无论是否有拼音，都进行强制覆盖，避免有出错的
            $task['pinyin'] = $this->pinyin->convert($task['name']);
            $task->save();
        }
    }

    /**
     * 更新告警通知组名称.
     */
    protected function updateAlarmGroupTable()
    {
        $groups = AlarmGroup::select('id', 'name', 'pinyin')->get();
        foreach ($groups as $group) {
            // 无论是否有拼音，都进行强制覆盖，避免有出错的
            $group['pinyin'] = $this->pinyin->convert($group['name']);
            $group->save();
        }
    }

    /**
     * 更新告警模板名称.
     */
    protected function updateAlarmTemplateTable()
    {
        $templates = AlarmTemplate::select('id', 'name', 'pinyin')->get();
        foreach ($templates as $template) {
            // 无论是否有拼音，都进行强制覆盖，避免有出错的
            $template['pinyin'] = $this->pinyin->convert($template['name']);
            $template->save();
        }
    }

    /**
     * 更新部门名称.
     */
    protected function updateDepartmentTable()
    {
        $departments = Department::select('id', 'name', 'pinyin')->get();
        foreach ($departments as $department) {
            // 无论是否有拼音，都进行强制覆盖，避免有出错的
            $department['pinyin'] = $this->pinyin->convert($department['name']);
            $department->save();
        }
    }
}
