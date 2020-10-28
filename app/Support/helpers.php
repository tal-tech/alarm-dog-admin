<?php

declare(strict_types=1);

if (! function_exists('list_to_tree')) {
    /**
     * 列表转Tree.
     *
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int $root
     *
     * @return array
     */
    function list_to_tree(array $list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
    {
        // 创建Tree
        $tree = [];
        // 创建基于主键的数组引用
        $refer = [];
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }

        return $tree;
    }
}

if (! function_exists('array_null2default')) {
    /**
     * 将null的数组转为配置的默认值
     *
     * @param array $data 原始数据，关联数组
     * @param array $defaults 默认值配置，['param1', 'param2']
     *                        或['param1' => 'default1', 'param2' => 'default2']
     * @return array
     */
    function array_null2default($data, $defaults)
    {
        foreach ($defaults as $idx => $val) {
            if (is_numeric($idx)) {
                // 索引数组，默认值为空字符串，字段名为$val
                if (! isset($data[$val]) || is_null($data[$val])) {
                    $data[$val] = '';
                }
            } else {
                // 关联数组，默认值为$val，字段名为$idx
                if (! isset($data[$idx]) || is_null($data[$idx])) {
                    $data[$idx] = $val;
                }
            }
        }

        return $data;
    }
}

if (! function_exists('array_only_keys')) {
    /**
     * 将null的数组转为配置的默认值
     *
     * @param array $array 原始数据，关联数组
     * @param array $onlyKeys 需要导出的keys，请保证keys务必存在，否则会报语法错误
     * @return array
     */
    function array_only_keys($array, $onlyKeys = [])
    {
        $export = [];
        foreach ($onlyKeys as $key) {
            isset($array[$key]) && $export[$key] = $array[$key];
        }

        return $export;
    }
}

if (! function_exists('str_trim')) {
    /**
     * trim 字符串
     * 类似 php 函数 trim.
     *
     * @param string $str 源字符
     * @param string $list 待清除字符
     * @return string
     */
    function str_trim($str, $list = '')
    {
        $list = (string) $list;
        if (! isset($list[0])) {
            return trim($str);
        }

        $len1 = strlen($str);
        $len2 = strlen($list);
        if ($len2 > $len1) {
            return trim($str);
        }

        $str = str_ltrim($str, $list);
        return str_rtrim($str, $list);
    }
}

if (! function_exists('str_ltrim')) {
    /**
     * ltrim 字符串
     * 类似 php 函数 ltrim.
     *
     * @param string $str 源字符
     * @param string $list 待清除字符
     * @return string
     */
    function str_ltrim($str, $list = '')
    {
        $list = (string) $list;
        if (! isset($list[0])) {
            return ltrim($str);
        }

        $len1 = strlen($str);
        $len2 = strlen($list);
        if ($len2 > $len1) {
            return ltrim($str);
        }

        $s = '';
        do {
            $s = substr($str, 0, $len2);
            if ($s == $list) {
                $str = substr($str, $len2);
            }
        } while ($s == $list);

        return $str;
    }
}

if (! function_exists('str_rtrim')) {
    /**
     * rtrim 字符串
     * 类似 php 函数 rtrim.
     *
     * @param string $str 源字符
     * @param string $list 待清除字符
     * @return string
     */
    function str_rtrim($str, $list = '')
    {
        $list = (string) $list;
        if (! isset($list[0])) {
            return rtrim($str);
        }

        $len1 = strlen($str);
        $len2 = strlen($list);
        if ($len2 > $len1) {
            return rtrim($str);
        }

        $s = '';
        do {
            $s = substr($str, -$len2);
            if ($s == $list) {
                $str = substr($str, 0, -$len2);
            }
        } while ($s == $list);

        return $str;
    }
}
