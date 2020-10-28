<?php

declare(strict_types=1);

namespace App\Service;

use Gregwar\Captcha\CaptchaBuilder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;

class ImageCaptcha
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var array
     */
    protected $config = [
        'pool' => '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM',
        'length' => 5,
        'width' => 150,
        'height' => 40,
        'cache_prefix' => 'dog.secure.captcha.',
        'expire' => 300,
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->redis = $container->get(Redis::class);
        $this->config = array_replace($this->config, $container->get(ConfigInterface::class)->get('captcha', []));
    }

    /**
     * 构建验证码
     */
    public function build(?string $cid = null): array
    {
        if (is_null($cid)) {
            $cid = $this->genCid();
        }

        $code = $this->genCode();
        $captcha = $this->genGraph($code);

        $this->redis->set($this->cacheKey($cid), sha1(strtolower($code)), ['ex' => $this->config['expire']]);

        return [
            'cid' => $cid,
            'captcha' => $captcha,
        ];
    }

    /**
     * 验证验证码是否合法.
     */
    public function validate(string $cid, string $code): bool
    {
        $cacheKey = $this->cacheKey($cid);

        if ($this->redis->get($cacheKey) !== sha1(strtolower($code))) {
            return false;
        }

        $this->redis->del($cacheKey);

        return true;
    }

    public function getPool(): string
    {
        return $this->config['pool'];
    }

    public function getLength(): int
    {
        return (int) $this->config['length'];
    }

    /**
     * 根据字符串生成图片验证码
     */
    protected function genGraph(string $code): string
    {
        return CaptchaBuilder::create($code)
            ->build($this->config['width'], $this->config['height'])
            ->inline();
    }

    /**
     * 生成验证码字符串.
     */
    protected function genCode(): string
    {
        $length = $this->getLength();
        $pool = $this->getPool();

        $bag = [];
        for ($i = 0; $i < $length; $i++) {
            $char = $pool[rand(0, strlen($pool) - 1)];
            $bag[] = $char;
        }

        return implode('', $bag);
    }

    protected function genCid(): string
    {
        return (string) Uuid::uuid4();
    }

    protected function cacheKey(string $key): string
    {
        return $this->config['cache_prefix'] . sha1($key);
    }
}
