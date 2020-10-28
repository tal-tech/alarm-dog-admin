<?php

declare(strict_types=1);

namespace App\Controller;

use App\Context\Auth;
use App\Model\User;
use App\Service\ImageCaptcha;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;

class AuthController extends AbstractController
{
    /**
     * @Inject
     * @var User
     */
    protected $user;

    /**
     * @Inject
     * @var ImageCaptcha
     */
    protected $imageCaptcha;

    /**
     * 发送邮件验证码
     */
    public function mail()
    {
        $param = $this->validate([
            'email' => 'required|string',
            'cid' => 'required|string',
            'captcha' => 'required|string',
        ]);

        if (! $this->imageCaptcha->validate($param['cid'], $param['captcha'])) {
            return $this->failed('验证码错误', [], 1001);
        }

        if (! filter_var($param['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->failed('邮箱格式错误');
        }

        // 邮件发送验证码
        $resp = $this->user->sendEmailCode($param['email']);

        return $this->success([
            'expired_at' => $resp['expired_at'],
        ]);
    }

    /**
     * 邮箱验证码验证
     */
    public function verifyMail()
    {
        $param = $this->validate([
            'email' => 'required|string',
            'code' => 'required|string',
        ]);

        if (! filter_var($param['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->failed('邮箱格式错误');
        }

        $resp = $this->user->loginByEmail($param['email'], $param['code']);

        $response = Context::get(ResponseInterface::class);
        $response = $response->withHeader('Authorization', 'Bearer ' . $resp['token']);
        Context::set(ResponseInterface::class, $response);

        return $this->success($resp);
    }

    /**
     * 获取用户基础信息.
     * @return array
     */
    public function getUserInfo()
    {
        $user = Context::get(Auth::class)->user()->toArray();
        $profile = array_only_keys($user, ['uid', 'user', 'username', 'email', 'department']);
        $permission = $this->user->permission();

        return $this->success([
            'user' => $profile,
            'permission' => $permission,
        ]);
    }

    /**
     * 退出登录.
     * @return array
     */
    public function logout()
    {
        $this->user->logout();

        return $this->success();
    }

    /**
     * 生成验证码
     */
    public function captcha()
    {
        $cid = $this->request->input('cid', null);

        $resp = $this->imageCaptcha->build($cid);

        return $this->success($resp);
    }

    /**
     * 帐号密码校验.
     */
    public function verifyAccount()
    {
        $param = $this->validate([
            'account' => 'required|string',
            'password' => 'required|string',
            'cid' => 'required|string',
            'captcha' => 'required|string',
        ]);

        if (! $this->imageCaptcha->validate($param['cid'], $param['captcha'])) {
            return $this->failed('验证码错误', [], 1001);
        }

        $resp = $this->user->loginByAccount($param['account'], $param['password']);

        $response = Context::get(ResponseInterface::class);
        $response = $response->withHeader('Authorization', 'Bearer ' . $resp['token']);
        Context::set(ResponseInterface::class, $response);

        return $this->success($resp);
    }
}
