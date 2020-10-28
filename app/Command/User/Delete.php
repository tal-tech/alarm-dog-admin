<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Exception\AppException;
use App\Model\User;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class Delete extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var User
     */
    protected $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->user = $container->get(User::class);

        parent::__construct('user:delete');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Delete the user');
        $this->addOption('uid', 'U', InputOption::VALUE_OPTIONAL, '工号');
        $this->addOption('account', 'A', InputOption::VALUE_OPTIONAL, '帐号');
    }

    public function handle()
    {
        $uid = (int) $this->input->getOption('uid');
        $account = $this->input->getOption('account');

        $where = [];
        if (! empty($uid)) {
            $where['uid'] = $uid;
        }
        if (! empty($account)) {
            $where['account'] = $account;
        }
        if (empty($where)) {
            throw new AppException('contidion field `uid` and `account` cannot all be empty');
        }

        $user = $this->user->where($where)->first();
        if (empty($user)) {
            throw new AppException('user not found');
        }

        $userInfo = $user->toArray();
        $user->delete();

        $header = array_keys($userInfo);
        $rows = [
            array_values($userInfo),
        ];
        $this->table($header, $rows);
    }
}
