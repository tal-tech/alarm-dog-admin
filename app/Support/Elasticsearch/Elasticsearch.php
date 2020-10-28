<?php

declare(strict_types=1);

namespace App\Support\Elasticsearch;

use App\Exception\AppException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Elasticsearch\ClientBuilderFactory;

class Elasticsearch
{
    /**
     * 配置.
     *
     * @var array
     */
    private $configs = [];

    public function __construct(ConfigInterface $confInterface)
    {
        try {
            $this->loadConf($confInterface);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 获取实例
     * 如果在协程环境下创建，则会自动使用协程版的 Handler，非协程环境下无改变.
     *
     * @return \Elasticsearch\Client
     */
    public function getInstance()
    {
        try {
            $builder = make(ClientBuilderFactory::class)->create();

            return $builder->setHosts($this->configs)->build();
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 加载配置.
     * @param mixed $confInterface
     */
    private function loadConf($confInterface)
    {
        $config = $confInterface->get('elasticsearch.default', []);

        // 如果没配置，直接抛异常
        if (! isset($config['cluster']) || empty($config['cluster'])) {
            throw new AppException('elasticsearch config null');
        }

        // 多个用逗号分隔
        $this->configs = explode(',', $config['cluster']);
    }
}
