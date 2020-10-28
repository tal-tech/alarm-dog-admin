<?php

declare(strict_types=1);

namespace App\Model;

use App\Context\Auth;
use App\Exception\AppException;
use App\Service\Captcha;
use App\Service\Pinyin;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Phper666\JwtAuth\Jwt;
use Throwable;

class User extends Model
{
    // 普通用户角色
    public const ROLE_DEFAULT = 0;

    // 超管角色
    public const ROLE_ADMIN = 9;

    public $timestamps = false;

    /**
     * 用户角色.
     *
     * @var array
     */
    public static $roles = [
        self::ROLE_DEFAULT => '普通用户',
        self::ROLE_ADMIN => '超级管理员',
    ];

    protected $table = 'user';

    protected $fillable = [
        'uid', 'account', 'username', 'pinyin', 'email', 'user', 'phone', 'department', 'password', 'role',
        'created_at', 'updated_at',
    ];

    protected $hidden = ['id', 'password'];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * @Inject
     * @var Captcha
     */
    protected $captcha;

    /**
     * 查询用户.
     *
     * @param int $uid
     * @return null|array 如果查询不到用户，则返回null
     */
    public function findUser($uid)
    {
        $user = $this->where('uid', $uid)->first();
        if (! empty($user)) {
            return $user->toArray();
        }
        return null;
    }

    /**
     * 搜索用户.
     *
     * @param string $keyword
     * @param int $pageSize
     * @return array
     */
    public function searchUser($keyword, $pageSize = 20)
    {
        return $this->where('username', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('uid', $keyword)
            ->orWhere('pinyin', "%{$keyword}%")
            ->select('uid', 'username', 'email', 'department')
            ->limit($pageSize)
            ->get()
            ->toArray();
    }

    /**
     * 用户权限.
     */
    public function permission()
    {
        $user = Context::get(Auth::class)->user();

        return $this->getPermissionByUser($user);
    }

    /**
     * 根据用户信息获取权限.
     * @param mixed $user
     */
    public function getPermissionByUser($user)
    {
        $permission = [
            'role' => (int) $user['role'],
            'read' => [],
            'write' => [],
        ];

        // 如果不是超管，需要计算有权限的任务ID
        if ($permission['role'] != self::ROLE_ADMIN) {
            $taskPers = AlarmTaskPermission::where('uid', $user['uid'])->select('task_id', 'type')->get();
            foreach ($taskPers as $taskPer) {
                if ($taskPer['type'] == AlarmTaskPermission::TYPE_RO) {
                    $permission['read'][] = (int) $taskPer['task_id'];
                } elseif ($taskPer['type'] == AlarmTaskPermission::TYPE_RW) {
                    $permission['write'][] = (int) $taskPer['task_id'];
                }
            }
        }

        return $permission;
    }

    /**
     * 更新手机号.
     */
    public function updateThisPhone(string $phone)
    {
        $auth = Context::get(Auth::class);
        $user = $auth->user();

        $oldPhone = $user->phone;
        if ($phone == $oldPhone) {
            throw new AppException('与上次手机号一样，无需更新，更新失败');
        }

        Db::beginTransaction();
        try {
            $user->phone = $phone;
            $user->updated_at = time();
            $user->save();

            // 增加审计记录
            UserAuditPhone::create([
                'uid' => $user['uid'],
                'old_phone' => $oldPhone,
                'new_phone' => $user->phone,
                'created_at' => time(),
            ]);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        $auth->setUser($user);
        Context::set(Auth::class, $auth);

        return $user;
    }

    /**
     * 根据用户ID获取用户信息.
     * @param mixed $uid
     */
    public function getProfileByUid($uid)
    {
        $user = $this->findUser($uid);
        if (empty($user)) {
            throw new AppException("user [{$uid}] not found", [], null, 404);
        }

        return [
            'user' => $user,
            'permission' => $this->getPermissionByUser($user),
        ];
    }

    /**
     * 根据用户ID更新手机号.
     * @param mixed $uid
     */
    public function updatePhoneByUid($uid, string $phone)
    {
        $user = $this->findUser($uid);
        if (empty($user)) {
            throw new AppException("user [{$uid}] not found", [], null, 404);
        }

        $oldPhone = $user['phone'];
        if ($phone == $oldPhone) {
            throw new AppException('与上次手机号一样，无需更新，更新失败');
        }

        Db::beginTransaction();
        $now = time();
        try {
            $this->where('uid', $user['uid'])->update([
                'updated_at' => $now,
                'phone' => $phone,
            ]);

            // 增加审计记录
            UserAuditPhone::create([
                'uid' => $user['uid'],
                'old_phone' => $oldPhone,
                'new_phone' => $phone,
                'created_at' => $now,
            ]);

            $user['phone'] = $phone;
            $user['updated_at'] = $now;
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $user;
    }

    /**
     * 判断当前用户是否是超管
     *
     * @param null|int $uid
     * @return bool
     */
    public function isAdmin($uid = null)
    {
        if (is_null($uid)) {
            $user = Context::get(Auth::class)->user();
            return $user['role'] == self::ROLE_ADMIN;
        }

        $isAdmin = $this->where('uid', $uid)
            ->where('role', self::ROLE_ADMIN)
            ->count();
        return (bool) $isAdmin;
    }

    /**
     * 发送邮件验证码
     */
    public function sendEmailCode(string $email)
    {
        if (! $this->where('email', $email)->exists()) {
            throw new AppException('用户不存在');
        }

        // 邮件发送验证码
        return $this->captcha->send($email);
    }

    /**
     * 使用邮箱登录.
     */
    public function loginByEmail(string $email, string $captcha)
    {
        $this->captcha->verify($email, $captcha);

        $user = $this->where('email', $email)->first();
        if (empty($user)) {
            throw new AppException('该用户不存在', [], null, 404);
        }

        return $this->respLoginUser($user);
    }

    /**
     * 使用帐号密码登录.
     */
    public function loginByAccount(string $account, string $password)
    {
        $user = $this->where('account', $account)->first();
        if (empty($user)) {
            throw new AppException('密码错误');
        }

        if (! $this->passwordVerify($password, $user['password'])) {
            throw new AppException('密码错误');
        }

        return $this->respLoginUser($user);
    }

    /**
     * 密码hash.
     */
    public function passwordHash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if (! $hash) {
            throw new AppException('密码hash失败');
        }

        return $hash;
    }

    /**
     * 密码验证
     */
    public function passwordVerify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 退出登录.
     */
    public function logout()
    {
        make(Jwt::class)->logout();

        $auth = Context::get(Auth::class);
        $auth->logout();
        Context::set(Auth::class, $auth);
    }

    /**
     * 响应登录用户.
     *
     * @return array
     */
    protected function respLoginUser(User $user)
    {
        // jwt token获取
        $playload = [
            'uid' => $user['uid'],
        ];
        $token = (string) make(Jwt::class)->getToken($playload);

        // 设置用户登录状态
        $auth = new Auth($user);
        Context::set(Auth::class, $auth);

        // 响应信息
        return [
            'token' => $token,
            'user' => [
                'uid' => (int) $user['uid'],
                'user' => $user['user'],
                'username' => $user['username'],
                'email' => $user['email'],
                'department' => $user['department'],
            ],
            'permission' => $this->permission(),
        ];
    }
}
