<?php

declare(strict_types=1);

namespace App\Support\Clickhouse;

use ClickHouseDB\Client as ClickHouseClient;
use Hyperf\Contract\ConfigInterface;

class Clickhouse
{
    /**
     * @var ClickHouseClient
     */
    protected $db;

    /**
     * 配置.
     *
     * @var array
     */
    protected $config = [];

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config->get('clickhouse.default', []);
        $this->connection();
    }

    /**
     * @return ClickHouseClient
     */
    public function getDb()
    {
        if (is_null($this->db)) {
            $this->connection();
        }

        return $this->db;
    }

    protected function connection()
    {
        $config = [
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ];
        $db = new ClickHouseClient($config);
        $db->database($this->config['database']);
        $db->setTimeout($this->config['timeout']);
        $db->setConnectTimeOut($this->config['connect_timeout']);

        $this->db = $db;
    }
}
