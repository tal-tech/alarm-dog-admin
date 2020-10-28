<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 条件数组工具类.
 */
class ConditionArr
{
    // 等于自身
    public const OP_EQ_SELF = 'eq-self';

    // 等于
    public const OP_EQ = 'eq';

    // 不等于
    public const OP_NEQ = 'neq';

    // 字段存在
    public const OP_ISSET = 'isset';

    // 字段不存在
    public const OP_NOT_ISSET = 'not-isset';

    // 小于
    public const OP_LT = 'lt';

    // 大于
    public const OP_GT = 'gt';

    // 小于等于
    public const OP_LTE = 'lte';

    // 大于等于
    public const OP_GTE = 'gte';

    // 在范围内
    public const OP_IN = 'in';

    // 不在范围内
    public const OP_NOT_IN = 'not-in';

    // 包含
    public const OP_CONTAIN = 'contain';

    // 不包含
    public const OP_NOT_CONTAIN = 'not-contain';

    /**
     * 条件字段拆分
     * 只负责拆分，不负责字段中可能包含的非法字符串检测.
     */
    public static function fieldSplit(string $field): array
    {
        // 拆分/占位规则正则
        $regex = '/(?:^|(?<=\.))(\[(.*?)\])(?:(?=\.)|$)/';
        // 正则占位符
        $placeholder = '$#$';

        // 匹配规则及offset，预定义避免后面报错
        $matches = [];
        $matchOffset = 0;
        if (preg_match_all($regex, $field, $matches, PREG_SET_ORDER)) {
            $field = preg_replace($regex, $placeholder, $field);
        }

        $parts = [];
        foreach (explode('.', $field) as $part) {
            // 当前字符串为占位符且存在匹配的match，则从matches中取值
            // 此处可能存在极端情况占位符被原先就在字段中存在，此处忽略这种极端情况
            if ($part == $placeholder && isset($matches[$matchOffset])) {
                $parts[] = $matches[$matchOffset++][2];
            } else {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    /**
     * 从数组中获取数据.
     *
     * @param array $array 原始数组
     * @param array $keys key
     * @param mixed $default 默认值
     * @return array [bool $exists, mixed $value]
     */
    public static function getValue(array $array, array $keys, $default = null): array
    {
        if (empty($keys)) {
            return [false, $default];
        }

        $subKeyArray = $array;
        foreach ($keys as $segment) {
            if (is_array($subKeyArray) && array_key_exists($segment, $subKeyArray)) {
                $subKeyArray = $subKeyArray[$segment];
            } else {
                return [false, $default];
            }
        }

        return [true, $subKeyArray];
    }

    /**
     * @param array $keys 将.拆分成不同parts组合，不是多个key
     */
    public static function hasKey(array $array, array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }

        $subKeyArray = $array;
        foreach ($keys as $segment) {
            if (is_array($subKeyArray) && array_key_exists($segment, $subKeyArray)) {
                $subKeyArray = $subKeyArray[$segment];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 是否满足条件.
     *
     * @param array $conditions
     * @param array $entry
     * @return array 满足时返回满足的条件，不满足时返回空数组
     */
    public static function match($conditions, $entry)
    {
        foreach ($conditions as $condition) {
            // 或条件判断，满足条件则立马结束
            if (static::matchRule($condition['rule'], $entry)) {
                return $condition;
            }
        }

        return [];
    }

    /**
     * 是否满足rule.
     *
     * @param array $rule
     * @param array $entry
     * @return bool
     */
    public static function matchRule($rule, $entry)
    {
        foreach ($rule as $ruleItem) {
            if (! static::matchRuleAssert($ruleItem, $entry)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 规则断言
     *
     * @param array $ruleItem
     * @param array $entry
     * @return bool
     */
    public static function matchRuleAssert($ruleItem, $entry)
    {
        [$exist, $value] = static::getValue($entry, $ruleItem['field_split']);

        switch ($ruleItem['operator']) {
            case static::OP_EQ_SELF:
                return true;
            case static::OP_EQ:
                return $value == $ruleItem['threshold'];
            case static::OP_NEQ:
                return $value != $ruleItem['threshold'];
            case static::OP_ISSET:
                return $exist;
            case static::OP_NOT_ISSET:
                return ! $exist;
            case static::OP_LT:
                return $value < $ruleItem['threshold'];
            case static::OP_GT:
                return $value > $ruleItem['threshold'];
            case static::OP_LTE:
                return $value <= $ruleItem['threshold'];
            case static::OP_GTE:
                return $value >= $ruleItem['threshold'];
            case static::OP_IN:
                return in_array($value, $ruleItem['threshold']);
            case static::OP_NOT_IN:
                return ! in_array($value, $ruleItem['threshold']);
            case static::OP_CONTAIN:
                return mb_strpos($value, $ruleItem['threshold']) !== false;
            case static::OP_NOT_CONTAIN:
                return mb_strpos($value, $ruleItem['threshold']) === false;
            default:
                // 如果条件操作符不合法，返回false
                // TODO 记录日志
                return false;
        }
    }
}
