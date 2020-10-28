<?php

declare(strict_types=1);

namespace App\Command\Upgrade;

use App\Model\User;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class FillUser extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('upgrade:fill-user');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Fill user field into user table for upgrade');
    }

    public function handle()
    {
        $users = User::select('id', 'uid', 'username', 'email', 'user')->get();
        foreach ($users as $user) {
            $user['user'] = explode('@', $user['email'])[0];
            $user->save();

            $this->info("Updated template [{$user['uid']}:{$user['username']}]");
        }

        $this->info('Done!');
    }
}
