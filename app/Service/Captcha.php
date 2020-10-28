<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\AppException;
use Dog\Noticer\Channel\Email;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Throwable;

class Captcha
{
    /**
     * @Inject
     * @var Redis
     */
    protected $redis;

    /**
     * @Inject
     * @var LoggerFactory
     */
    protected $loggerFactory;

    /**
     * @var string
     */
    protected $indexUrl = 'http://127.0.0.1:9501/';

    public function __construct()
    {
        $this->indexUrl = config('app.index_url');
    }

    /**
     * 发送验证码通知.
     *
     * @return array $errors
     */
    public function send(string $receiver)
    {
        $ttl = (int) config('app.login_captcha_ttl', 300);
        $cacheKey = config('app.login_captcha_cache_prefix', 'alarm-dog-captcha.') . $receiver;
        $resp = [
            'captcha' => $this->genCaptcha(),
            'expired_at' => date('Y-m-d H:i:s', time() + $ttl),
        ];

        try {
            $this->noticeByEmail($receiver, $resp);
        } catch (Throwable $e) {
            throw new AppException('验证码发送失败', [], null, 500);
        }

        // 写入redis
        try {
            $this->redis->set($cacheKey, $resp['captcha']);
            $this->redis->expire($cacheKey, $ttl);
        } catch (Throwable $e) {
            $logger = $this->loggerFactory->get();
            $logger->warning($e->getMessage(), [
                'key' => $cacheKey,
                'captcha' => $resp['captcha'],
            ]);
            throw new AppException('验证码缓存写入失败', [], null, 500);
        }

        return $resp;
    }

    /**
     * 邮箱验证码验证
     *
     * @return string
     */
    public function verify(string $receiver, string $captcha)
    {
        $cacheKey = config('app.login_captcha_cache_prefix', 'alarm-dog-captcha.') . $receiver;
        if ($this->redis->get($cacheKey) != $captcha) {
            throw new AppException('验证码错误', [], null, 400);
        }

        // 验证通过之后删除
        $this->redis->del($cacheKey);

        return $receiver;
    }

    /**
     * 邮件通知.
     *
     * @throws \Dog\Noticer\Exception\NoticeException
     */
    protected function noticeByEmail(string $receiver, array $param)
    {
        $html = <<<EOF
<p>
    尊敬的 {$receiver}：
</p>

<p>
  您正在登录哮天犬（<a href="{$this->indexUrl}/" target="_blank">{$this->indexUrl}/</a>），本次登录的验证码为 <span style="color: red; font-weight: 600">{$param['captcha']}</span>，有效期至 <span style="color: red">{$param['expired_at']}</span>，请在有效期前使用验证码。
</p>

<p>
  如果不是您本人操作，请忽略本邮件。
</p>

<p><br></p>

<p style="font-size: 10px; color: #aaa">
  本邮件由系统发出，请勿回复。
</p>
EOF;
        // 发送html
        make(Email::class)->init()
            ->to($receiver)
            ->subject('哮天犬登录验证码')
            ->html($html)
            ->send();
    }

    /**
     * 生成验证码
     *
     * @return string
     */
    protected function genCaptcha()
    {
        return sprintf('%06d', mt_rand(0, 999999));
    }
}
