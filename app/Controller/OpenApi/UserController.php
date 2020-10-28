<?php

declare(strict_types=1);

namespace App\Controller\OpenApi;

use App\Model\User;
use Hyperf\Di\Annotation\Inject;

class UserController extends AbstractController
{
    /**
     * @Inject
     * @var User
     */
    protected $user;

    /**
     * 获取用户信息.
     */
    public function profile()
    {
        $param = $this->validate([
            'uid' => 'required|integer',
        ]);

        $profile = $this->user->getProfileByUid($param['uid']);

        return $this->success($profile);
    }

    /**
     * 更新手机号.
     */
    public function updatePhone()
    {
        $param = $this->validate([
            'uid' => 'required|integer',
            'phone' => 'required|integer',
        ]);
        if (! preg_match('/^1[3-9]\d{9}$/', $param['phone'])) {
            return $this->failed('手机号必须为有效格式');
        }

        $user = $this->user->updatePhoneByUid($param['uid'], $param['phone']);

        return $this->success([
            'user' => $user,
        ]);
    }
}
