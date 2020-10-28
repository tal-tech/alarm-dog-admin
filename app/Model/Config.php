<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;

class Config extends Model
{
    public $timestamps = false;

    protected $table = 'config';

    protected $fillable = ['key', 'remark', 'value', 'created_at', 'updated_at'];

    /**
     * 创建一条配置项.
     * @param mixed $key
     * @param mixed $value
     * @param null|mixed $remark
     */
    public static function createConfig($key, $value, $remark = null)
    {
        $now = time();
        $data = [
            'key' => $key,
            'remark' => $remark ?: '',
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return self::create($data);
    }

    /**
     * 更新配置.
     * @param mixed $key
     * @param mixed $value
     * @param null|mixed $remark
     */
    public static function updateConfig($key, $value, $remark = null)
    {
        $config = self::where('key', $key)->first();
        if (empty($config)) {
            throw new AppException(sprintf('配置项 [%s] 不存在', $key));
        }

        $config['value'] = $value;
        $config['updated_at'] = time();
        if (! is_null($remark)) {
            $config['remark'] = $remark;
        }
        $config->save();

        return $config;
    }

    /**
     * 获取配置.
     * @param mixed $key
     * @param null|mixed $default
     */
    public static function getRaw($key, $default = null)
    {
        $value = self::where('key', $key)->value('value');

        return $value ?: $default;
    }

    /**
     * 获取配置-JSON.
     * @param mixed $key
     * @param mixed $default
     */
    public static function getJson($key, $default = [])
    {
        $value = self::where('key', $key)->value('value');
        if (empty($value)) {
            return $default;
        }

        $json = json_decode($value, true);
        if (empty($json) || json_last_error() != JSON_ERROR_NONE) {
            return $default;
        }

        return $json;
    }

    /**
     * 获取配置-Items.
     * @param mixed $key
     * @param mixed $default
     */
    public static function getItems($key, $default = [])
    {
        $value = self::where('key', $key)->value('value');
        if (empty($value)) {
            return $default;
        }

        return explode(',', $value);
    }
}
