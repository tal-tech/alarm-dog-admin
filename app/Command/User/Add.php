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
class Add extends HyperfCommand
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

        parent::__construct('user:add');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Add a user');
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
        $param = $this->input->getOptions();
        $validator = $this->validatorFactory->make(
            $param,
            [
                'uid' => 'required|integer',
                'account' => 'required|string',
                'username' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'role' => 'in:user,admin',
            ]
        );

        if ($validator->fails()) {
            throw new AppException(
                sprintf(
                    'invalid parameters: ' . PHP_EOL . '%s',
                    implode(PHP_EOL, $validator->errors()->all())
                )
            );
        }

        $param['role'] = $param['role'] == 'admin' ? User::ROLE_ADMIN : User::ROLE_DEFAULT;
        $param['pinyin'] = $this->pinyin->name($param['username']);
        $param['user'] = explode('@', $param['email'])[0];
        $param['password'] = $this->user->passwordHash($param['password']);
        $param['created_at'] = time();
        $param['updated_at'] = time();

        $user = User::create($param)->toArray();

        $header = array_keys($user);
        $rows = [
            array_values($user),
        ];
        $this->table($header, $rows);
    }
}
