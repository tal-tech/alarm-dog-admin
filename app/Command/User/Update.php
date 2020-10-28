<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Exception\AppException;
use App\Model\User;
use App\Service\Pinyin;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class Update extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ValidatorFactoryInterface
     */
    protected $validatorFactory;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Pinyin
     */
    protected $pinyin;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->validatorFactory = $container->get(ValidatorFactoryInterface::class);
        $this->user = $container->get(User::class);
        $this->pinyin = $container->get(Pinyin::class);

        parent::__construct('user:update');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update the user');
        $this->addOption('uid', 'U', InputOption::VALUE_REQUIRED, '工号');
        $this->addOption('account', 'A', InputOption::VALUE_REQUIRED, '帐号');
        $this->addOption('username', 'u', InputOption::VALUE_REQUIRED, '用户姓名');
        $this->addOption('email', 'E', InputOption::VALUE_REQUIRED, '邮箱');
        $this->addOption('password', 'P', InputOption::VALUE_REQUIRED, '密码');
        $this->addOption('role', 'R', InputOption::VALUE_OPTIONAL, '角色 user|admin', 'user');
        $this->addOption('phone', 'p', InputOption::VALUE_OPTIONAL, '手机号', '');
        $this->addOption('department', 'D', InputOption::VALUE_OPTIONAL, '部门', '');
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

        $update = [];
        if ($username = $this->input->getOption('username')) {
            $user['username'] = $username;
            $user['pinyin'] = $this->pinyin->name($username);
        }
        if ($email = $this->input->getOption('email')) {
            $user['email'] = $email;
            $user['user'] = explode('@', $email)[0];
        }
        if ($password = $this->input->getOption('password')) {
            $user['role'] = $this->user->passwordHash($password);
        }
        if ($phone = $this->input->getOption('phone')) {
            $user['phone'] = $phone;
        }
        if ($department = $this->input->getOption('department')) {
            $user['department'] = $department;
        }
        $user['updated_at'] = time();
        $user->save();

        $userInfo = $user->toArray();

        $header = array_keys($userInfo);
        $rows = [
            array_values($userInfo),
        ];
        $this->table($header, $rows);
    }
}
