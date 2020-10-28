<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\DbConnection\Db;

class HelpersForMigration
{
    /**
     * 获取表前缀
     *
     * @param string $connection 连接
     * @return string
     */
    public static function getPrefix($connection = null)
    {
        if (is_null($connection)) {
            return config('databases.default.prefix');
        }

        return config("databases.{$connection}.prefix");
    }

    /**
     * 备注表名.
     *
     * @param string $table 表名
     * @param string $comment 备注
     * @param string $connection 连接
     */
    public static function commentTable($table, $comment, $connection = null)
    {
        $prefix = static::getPrefix($connection);

        Db::statement("ALTER TABLE `{$prefix}{$table}` comment'{$comment}'");
    }
}
