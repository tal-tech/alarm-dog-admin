<?php

declare(strict_types=1);

namespace App\Service\Monitor\ProtocolDetect;

use Psr\Container\ContainerInterface;

abstract class ProtocolDetectAbstract
{
    /**
     * @var int
     */
    public static $protocol = 0;

    /**
     * @var string
     */
    public static $name = 'ProtocolDetectAbstract';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 配置信息.
     *
     * @var array
     */
    protected $config = [];

    public function __construct(ContainerInterface $container, array $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * 验证连接配置格式化.
     */
    abstract public function validConfig(): array;

    /**
     * 验证连接是否可用.
     */
    abstract public function validConnect(): array;
}
