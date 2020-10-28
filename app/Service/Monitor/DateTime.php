<?php

declare(strict_types=1);

namespace App\Service\Monitor;

use App\Exception\AppException;

class DateTime
{
    /**
     * 时间字段单位.
     */
    public const UNIT_SECOND = 1;

    public const UNIT_MS = 2;

    public const UNIT_US = 3;

    public const UNIT_ISOSTR = 4;

    public static $units = [
        self::UNIT_SECOND => '秒(s)',
        self::UNIT_MS => '毫秒(ms)',
        self::UNIT_US => '微秒(μs)',
        self::UNIT_ISOSTR => '满足ISO格式规范的字符串时间',
    ];

    /**
     * 时间戳字段验证
     */
    public static $unitValidators = [
        self::UNIT_SECOND => [DateTime::class, 'isUnitSecond'],
        self::UNIT_MS => [DateTime::class, 'isUnitMS'],
        self::UNIT_US => [DateTime::class, 'isUnitNS'],
        self::UNIT_ISOSTR => [DateTime::class, 'isUnitISOStr'],
    ];

    /**
     * 时间戳格式校验.
     *
     * @param int $unit
     * @param string $field
     * @param mixed $value
     * @throws AppException
     */
    public static function isUnit($unit, $field, $value)
    {
        if (! isset(self::$unitValidators[$unit])) {
            throw new AppException("not support timestamp field unit [{$unit}] at field [{$field}]", [
                'field' => $field,
                'unit' => $unit,
            ]);
        }
        if (! is_scalar($value)) {
            throw new AppException("timestamp field [{$field}]`s value must be scalar", [
                'field' => $field,
                'value' => $value,
            ]);
        }

        call_user_func(self::$unitValidators[$unit], $field, $value);
    }

    /**
     * 时间戳格式校验-秒.
     * @param mixed $field
     * @param mixed $value
     * @param mixed $unitText
     */
    public static function isUnitSecond($field, $value, $unitText = 'second')
    {
        if (strtotime(date('Y-m-d H:i:s', $value)) !== (int) $value) {
            throw new AppException("invalid timestamp {$unitText} unit at field [{$field}] using value [{$value}]");
        }
    }

    /**
     * 时间戳格式校验-毫秒.
     * @param mixed $field
     * @param mixed $value
     */
    public static function isUnitMS($field, $value)
    {
        return self::isUnitSecond($field, $value / 1000, 'microsecond');
    }

    /**
     * 时间戳格式校验-纳秒.
     * @param mixed $field
     * @param mixed $value
     */
    public static function isUnitNS($field, $value)
    {
        return self::isUnitSecond($field, $value / 1000000, 'nanosecond');
    }

    /**
     * 时间戳格式校验-ISO字符串.
     * @param mixed $field
     * @param mixed $value
     */
    public static function isUnitISOStr($field, $value)
    {
        if (strtotime($value) === false) {
            throw new AppException("invalid timestamp ISO string unit at field [{$field}] using value [{$value}]");
        }
    }

    /**
     * 时间点定位，将时间定位到固定的打点时间点.
     *
     * @param int $timestamp 时间戳，秒
     * @param int $interval 周期，间隔，秒
     * @return int 定位到的时间点时间戳
     */
    public static function timePointLocation($timestamp, $interval)
    {
        $remainder = $timestamp % $interval;
        // 余数大于等于周期一半，定位到下一个周期的点
        if ($remainder >= ($interval / 2)) {
            return $timestamp - $remainder + $interval;
        }
        return $timestamp - $remainder;
    }

    /**
     * 指定时间单位时间转为时间戳.
     *
     * @param int|string $time 时间戳或者ISO字符串
     * @param int $unit 时间单位
     * @throws AppException
     * @return int
     */
    public static function timeToTimestamp($time, $unit)
    {
        switch (intval($unit)) {
            case self::UNIT_SECOND:
                return (int) $time;
            case self::UNIT_MS:
                return (int) $time / 1000;
            case self::UNIT_US:
                return (int) $time / 1000000;
            case self::UNIT_ISOSTR:
                $timestamp = strtotime($time);
                if ($timestamp === false) {
                    throw new AppException('invalid ISO string datetime converts to timestamp', [
                        'time' => $time,
                    ]);
                }
                return $timestamp;
            default:
                throw new AppException('not support unit to convert to timestamp', [
                    'unit' => $unit,
                    'time' => $time,
                ]);
        }
    }

    /**
     * 时间戳转为指定时间单位时间.
     *
     * @param int $timestamp 时间戳
     * @param int $unit 时间单位
     * @return int|string
     */
    public static function timestampToTime($timestamp, $unit)
    {
        switch (intval($unit)) {
            case self::UNIT_SECOND:
                return (int) $timestamp;
            case self::UNIT_MS:
                return (int) $timestamp * 1000;
            case self::UNIT_US:
                return (int) $timestamp * 1000000;
            case self::UNIT_ISOSTR:
                return date('c', $timestamp);
            default:
                throw new AppException('not support unit to convert to time', [
                    'unit' => $unit,
                    'timestamp' => $timestamp,
                ]);
        }
    }
}
