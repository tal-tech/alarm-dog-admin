<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Service\Monitor\DataSource\DataSourceFactory;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;

class MonitorDatasource extends Model
{
    /**
     * 数据源类型.
     */
    public const TYPE_ES = 1;

    public const TYPE_MYSQL = 2;

    public const TYPE_KAFKA = 3;

    public const TYPE_WEBHOOK = 4;

    /**
     * Webhook配置-请求方式.
     */
    public const CONF_WEBHOOK_METHOD_GET = 'GET';

    public const CONF_WEBHOOK_METHOD_POST = 'POST';

    /**
     * Webhook配置-body类型.
     */
    public const CONF_WEBHOOK_BODY_TYPE_JSON = 'application/json';

    public const CONF_WEBHOOK_BODY_TYPE_TEXT = 'text/plain';

    public const CONF_WEBHOOK_BODY_TYPE_X_WWW_FORM = 'application/x-www-form-urlencoded';

    public const CONF_WEBHOOK_BODY_TYPE_FORM_DATA = 'multipart/form-data';

    public const CONF_WEBHOOK_BODY_TYPE_NONE = 'none';

    /**
     * 字段定义-字段类型.
     */
    public const CONF_FIELDS_TYPE_FLOAT = 'float';

    public const CONF_FIELDS_TYPE_INTEGER = 'integer';

    public $timestamps = false;

    public static $types = [
        self::TYPE_ES => 'ElasticSearch',
        self::TYPE_MYSQL => 'MySQL',
        self::TYPE_KAFKA => 'Kafka',
        self::TYPE_WEBHOOK => 'Webhook',
    ];

    public static $confWebhookMethods = [
        self::CONF_WEBHOOK_METHOD_GET => 'GET',
        self::CONF_WEBHOOK_METHOD_POST => 'POST',
    ];

    public static $confWebhookBodyTypes = [
        self::CONF_WEBHOOK_BODY_TYPE_JSON => 'JSON',
        self::CONF_WEBHOOK_BODY_TYPE_TEXT => 'text/plain',
        self::CONF_WEBHOOK_BODY_TYPE_X_WWW_FORM => 'x-www-form-urlencoded',
        self::CONF_WEBHOOK_BODY_TYPE_FORM_DATA => 'form-data',
        self::CONF_WEBHOOK_BODY_TYPE_NONE => 'none',
    ];

    public static $confFieldsTypes = [
        self::CONF_FIELDS_TYPE_FLOAT => 'Float',
        self::CONF_FIELDS_TYPE_INTEGER => 'Integer',
    ];

    // 字段类型验证函数
    public static $fieldsTypeValidators = [
        self::CONF_FIELDS_TYPE_FLOAT => 'is_float', // 只要可以被 call_user_func 调用的格式都行
        self::CONF_FIELDS_TYPE_INTEGER => 'is_numeric',
    ];

    // 字段类型格式化函数
    public static $fieldsTypeFormatters = [
        self::CONF_FIELDS_TYPE_FLOAT => 'floatval',
        self::CONF_FIELDS_TYPE_INTEGER => 'intval',
    ];

    protected $table = 'monitor_datasource';

    protected $fillable = [
        'type', 'name', 'pinyin', 'remark', 'config', 'fields', 'timestamp_field', 'timestamp_unit',
        'created_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'config' => 'array',
        'fields' => 'array',
    ];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * 字段类型验证
     *
     * @param string $type
     * @param string $field
     * @param mixed $value
     * @throws AppException
     */
    public static function fieldTypeValidate($type, $field, $value)
    {
        if (! isset(self::$fieldsTypeValidators[$type])) {
            throw new AppException("not support field type [{$type}] at field [{$field}]", [
                'field' => $field,
                'type' => $type,
            ]);
        }
        if (! is_scalar($value)) {
            throw new AppException("field [{$field}]`s value must be scalar", [
                'field' => $field,
                'value' => $value,
            ]);
        }
        if (! call_user_func(self::$fieldsTypeValidators[$type], $value)) {
            throw new AppException("invalid field type [{$type}] at field [{$field}] using value [{$value}]", [
                'field' => $field,
                'type' => $type,
                'value' => $value,
            ]);
        }
    }

    /**
     * 是否存在该名称的数据源.
     *
     * @param string $name
     * @param int $excludeId
     * @return int
     */
    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 判断是否存在，不存在则报错.
     *
     * @param int $datasourceId
     * @return self
     */
    public function getByIdAndThrow($datasourceId)
    {
        $dataSource = $this->where('id', $datasourceId)->first();
        if (empty($dataSource)) {
            throw new AppException("data source [{$datasourceId}] not found", [
                'datasource_id' => $datasourceId,
            ], null, 404);
        }

        return $dataSource;
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')
            ->select('uid', 'username', 'user', 'email', 'department');
    }

    /**
     * 列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [])
    {
        $builder = $this->with('creator')
            ->select('id', 'type', 'name', 'remark', 'config', 'created_at', 'updated_at', 'created_by');
        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 详情.
     * @param mixed $datasourceId
     */
    public function showDatasource($datasourceId)
    {
        $datasource = $this->getByIdAndThrow($datasourceId, true);
        $datasource->load('creator');

        return $datasource;
    }

    /**
     * 删除.
     * @param mixed $datasourceId
     */
    public function deleteDatasource($datasourceId, User $user)
    {
        // 权限判断，仅允许超管创建数据源
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以删除数据源');
        }

        $datasource = $this->getByIdAndThrow($datasourceId, true);

        // 查询有无关联监控任务
        if (MonitorUniversal::where('datasource_id', $datasourceId)->count()) {
            throw new AppException('有关联的通用监控任务，请先删除监控任务之后再删除数据源');
        }
        if (MonitorCycleCompare::where('datasource_id', $datasourceId)->count()) {
            throw new AppException('有关联的同比环比监控任务，请先删除监控任务之后再删除数据源');
        }
        if (MonitorUprushDownrush::where('datasource_id', $datasourceId)->count()) {
            throw new AppException('有关联的突增突降监控任务，请先删除监控任务之后再删除数据源');
        }

        // 判断有无关联的influxDB，如果有，先删除
        // TODO

        $datasource->delete();
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'name', 'type', 'config', 'remark');

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }

        return $builder->get();
    }

    /**
     * 创建.
     * @param mixed $param
     */
    public function storeDatasource($param, User $user)
    {
        // 权限判断，仅允许超管创建数据源
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以创建数据源');
        }

        // 重名判断
        if ($this->hasByName($param['name'])) {
            throw new AppException("data source [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
            ]);
        }

        // 验证数据源有效性
        $this->validDataSource($param);

        $data = [
            'type' => $param['type'],
            'name' => $param['name'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'remark' => $param['remark'],
            'config' => $param['config'],
            'fields' => $param['fields'],
            'timestamp_field' => $param['timestamp_field'],
            'timestamp_unit' => $param['timestamp_unit'],
            'created_by' => $user['uid'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $datasource = self::create($data);
        $datasource->load('creator');

        return $datasource;
    }

    /**
     * 更新.
     * @param mixed $datasourceId
     * @param mixed $param
     * @param mixed $user
     */
    public function updateDatasource($datasourceId, $param, $user)
    {
        // 权限判断，仅允许超管创建数据源
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以更新数据源');
        }

        // 重名判断
        if ($this->hasByName($param['name'], $datasourceId)) {
            throw new AppException("data source [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
                'exclude_id' => $datasourceId,
            ]);
        }

        $datasource = $this->getByIdAndThrow($datasourceId, true);

        // 验证数据源有效性
        $this->validDataSource($param);

        $datasource['type'] = $param['type'];
        $datasource['name'] = $param['name'];
        $datasource['pinyin'] = $this->pinyin->convert($param['name']);
        $datasource['remark'] = $param['remark'];
        $datasource['config'] = $param['config'];
        $datasource['fields'] = $param['fields'];
        $datasource['timestamp_field'] = $param['timestamp_field'];
        $datasource['timestamp_unit'] = $param['timestamp_unit'];
        $datasource['updated_at'] = time();
        $datasource->save();

        $datasource->load('creator');

        return $datasource;
    }

    /**
     * 验证连接是否可用.
     * @param mixed $param
     */
    public function validConnect($param)
    {
        $this->validDataSource($param);
    }

    /**
     * 获取数据源字段.
     * @param mixed $datasourceId
     */
    public function getFields($datasourceId)
    {
        $datasource = $this->getByIdAndThrow($datasourceId);

        return [
            'timestamp' => [
                'field' => $datasource['timestamp_field'],
                'unit' => $datasource['timestamp_unit'],
            ],
            'fields' => $datasource['fields']['fields'],
        ];
    }

    /**
     * 数据源字段验证、格式化.
     *
     * @param array $param
     * @return array
     */
    protected function validAndFmtFields($param)
    {
        $fields = [];
        foreach ($param['fields'] ?? [] as $field) {
            // 字段
            if (empty($field['field'])) {
                throw new AppException('datasource field connot be empty');
            }
            if (! is_string($field['field'])) {
                throw new AppException('datasource field must be string', [
                    'field' => $field['field'],
                ]);
            }

            // 类型
            if (empty($field['type'])) {
                throw new AppException('datasource field type connot be empty');
            }
            if (! array_key_exists($field['type'], MonitorDatasource::$confFieldsTypes)) {
                throw new AppException('datasource field type invalid', [
                    'field' => $field['field'],
                    'type' => $field['type'],
                ]);
            }

            // label，可以为空，但必须为字符串
            if (! empty($field['label']) && ! is_string($field['label'])) {
                throw new AppException('datasource field label must be string', [
                    'field' => $field['field'],
                    'label' => $field['label'],
                ]);
            }

            $fields[] = [
                'field' => $field['field'],
                'type' => $field['type'],
                'label' => $field['label'] ?? '',
            ];
        }

        // 字段不允许为空
        if (empty($fields)) {
            throw new AppException('datasource fields cannot be empty');
        }

        return [
            'fields' => $fields,
        ];
    }

    /**
     * 验证数据源.
     *
     * @param array $param
     */
    protected function validDataSource(&$param)
    {
        // 字段格式化
        $param['fields'] = $this->validAndFmtFields($param['fields']);

        $datasource = DataSourceFactory::create($param['type'], $param['config'], $param['timestamp_field'], $param['timestamp_unit']);
        $param['config'] = $datasource->validConfig();
        $datasource->validConnect();
        $datasource->validFields($param['fields']['fields']);
        $datasource->validTimestamp();
    }
}
