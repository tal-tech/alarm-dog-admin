<?php

declare(strict_types=1);

namespace App\Context;

use App\Exception\UnauthorizedException;
use App\Model\User;

class Auth
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * 设置登录用户信息.
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * 退出登录.
     */
    public function logout()
    {
        $this->user = null;
    }

    /**
     * 获取用户ID.
     *
     * @return int
     */
    public function id()
    {
        if (is_null($this->user)) {
            throw new UnauthorizedException();
        }

        return (int) $this->user['uid'];
    }

    /**
     * 获取用户ID.
     *
     * @return int
     */
    public function uid()
    {
        return $this->id();
    }

    /**
     * 获取用户信息.
     *
     * @param string $key
     * @return null|mixed|User
     */
    public function user($key = '')
    {
        if (is_null($this->user)) {
            throw new UnauthorizedException();
        }

        return $key ? ($this->user[$key] ?? null) : $this->user;
    }

    /**
     * 是否是超管
     *
     * @return bool
     */
    public function isAdmin()
    {
        if (is_null($this->user)) {
            throw new UnauthorizedException();
        }

        return $this->user->isAdmin();
    }
}
