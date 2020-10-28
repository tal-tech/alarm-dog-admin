<?php

declare(strict_types=1);

namespace App\Command\Permission;

use App\Model\User;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class RoleListAdmin extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('permission:role:list-admin');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List Admin Users');
    }

    public function handle()
    {
        $users = User::where('role', User::ROLE_ADMIN)->get();

        $header = [
            'UID', 'Username', 'Email', 'Role', 'Department',
        ];
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user['uid'], $user['username'], $user['email'], User::$roles[$user['role']], $user['department'],
            ];
        }

        $this->table($header, $rows);
    }
}
