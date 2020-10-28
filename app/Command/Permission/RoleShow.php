<?php

declare(strict_types=1);

namespace App\Command\Permission;

use App\Model\User;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class RoleShow extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('permission:role:show');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Show User Permission Role');
        $this->addOption('uid', 'U', InputOption::VALUE_OPTIONAL, '工号', '');
        $this->addOption('email', 'E', InputOption::VALUE_OPTIONAL, '邮箱', '');
    }

    public function handle()
    {
        $uid = (int) $this->input->getOption('uid');
        $email = $this->input->getOption('email');
        if (empty($uid) && empty($email)) {
            $this->error('工号 --uid 和邮箱 --email 不能同时为空，请任意填写一个');
            return;
        }

        if (! empty($uid)) {
            $user = User::where('uid', $uid)->first();
        } else {
            $user = User::where('email', $email)->first();
        }
        if (empty($user)) {
            $this->error('用户不存在');
            return;
        }

        $header = [
            'UID', 'Username', 'Email', 'Role', 'Department',
        ];
        $rows = [
            [
                $user['uid'], $user['username'], $user['email'], User::$roles[$user['role']], $user['department'],
            ],
        ];
        $this->table($header, $rows);
    }
}
