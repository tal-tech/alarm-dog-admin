<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\User;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class UserController extends AbstractController
{
    /**
     * @Inject
     * @var User
     */
    protected $user;

    public function search()
    {
        $param = $this->validate([
            'search' => 'required|string',
            'pageSize' => 'integer|max:100|min:1',
        ]);
        $param['pageSize'] = (int) $this->request->input('pageSize', 20);

        $users = $this->user->searchUser($param['search'], $param['pageSize']);

        return $this->success(compact('users'));
    }

    public function profile()
    {
        $user = Context::get(Auth::class)->user()->toArray();
        $profile = array_only_keys($user, [
            'uid', 'user', 'username', 'email', 'department', 'wechatid', 'phone', 'created_at',
        ]);
        $permission = $this->user->permission();

        return $this->success([
            'user' => $profile,
            'permission' => $permission,
        ]);
    }

    public function permission()
    {
        $permission = $this->user->permission();

        return $this->success([
            'permission' => $permission,
        ]);
    }

    public function updatePhone()
    {
        $param = $this->validate([
            'phone' => 'required|integer',
        ]);
        if (! preg_match('/^1[3-9]\d{9}$/', $param['phone'])) {
            return $this->failed('手机号必须为有效格式');
        }

        $user = $this->user->updateThisPhone($param['phone']);

        return $this->success([
            'phone' => $user['phone'],
        ]);
    }
}
