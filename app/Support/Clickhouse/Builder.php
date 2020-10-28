<?php

declare(strict_types=1);

namespace App\Support\Clickhouse;

use App\Exception\AppException;

class Builder
{
    /**
     * 表名.
     */
    private $table;

    /**
     * clickhouse实例.
     */
    private $instance;

    /**
     * 递增序号值
     */
    private $sequence = 0;

    /**
     * 需查询字段.
     */
    private $fields = ['*'];

    /**
     * 查询where条件.
     */
    private $whereOrm = [];

    /**
     * 查询条件中，具体的值
     */
    private $replaceOrms = [];

    /**
     * 排序.
     */
    private $orderByOrm = [];

    /**
     * 分组.
     */
    private $groupByOrm = [];

    /**
     * 限制查询个数.
     */
    private $limitOrm = '';

    public function __construct(string $table)
    {
        try {
            if (empty($table)) {
                throw new AppException('clickhouse表名错误！', ['clickhouse表名错误！'], null, 1);
            }
            $this->table = $table;
            $this->instance = make(Clickhouse::class)->getDb();
            $this->sequence = 0;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 设置需查询的字段.
     *
     * @return $this
     */
    public function select(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * 设置查询where条件.
     *
     * @param string $op 查询表达试，如:>,<,= ...
     * @param string $val
     * @return $this
     */
    public function where(string $field, string $op, $val)
    {
        // 递增加1，避免重复
        $this->sequence++;
        $seqField = $field . 'K' . $this->sequence;
        $seqVal = $field . 'V' . $this->sequence;
        $this->whereOrm[] = sprintf('{%s} %s :%s', $seqField, $op, $seqVal);
        $this->replaceOrms[$seqField] = $field;
        $this->replaceOrms[$seqVal] = $val;

        return $this;
    }

    /**
     * 设置查询whereIn条件.
     *
     * @return $this
     */
    public function whereIn(string $field, array $vals)
    {
        $this->sequence++;
        $seqField = $field . 'K' . $this->sequence;
        $seqVal = $field . 'V' . $this->sequence;
        $this->whereOrm[] = sprintf('{%s} IN (:%s)', $seqField, $seqVal);
        $this->replaceOrms[$seqField] = $field;
        $this->replaceOrms[$seqVal] = $vals;

        return $this;
    }

    /**
     * 排序.
     *
     * @return $this
     */
    public function orderBy(string $field, string $sort)
    {
        $this->orderByOrm[] = "{$field} " . strtoupper($sort);

        return $this;
    }

    /**
     * 分组.
     *
     * @return $this
     */
    public function groupBy(string $field)
    {
        $this->groupByOrm[] = $field;

        return $this;
    }

    /**
     * 指定查询数量.
     *
     * @return $this
     */
    public function limit(int $limit = 20)
    {
        if (! empty($this->limitOrm)) {
            return $this;
        }
        $this->limitOrm = '0, ' . $limit;

        return $this;
    }

    /**
     * 批量返回结果.
     */
    public function rows(): array
    {
        $statement = $this->instance->select($this->toSql(), $this->replaceOrms);
        return $statement->rows();
    }

    /**
     * 单条返回结果.
     */
    public function first(): array
    {
        $statement = $this->instance->select($this->toSql(), $this->replaceOrms);
        return $statement->fetchOne();
    }

    /**
     * 设置sql语句
     * 单独拉出来，为了好扩展，降低偶合度.
     */
    private function toSql(): string
    {
        return sprintf(
            'SELECT %s FROM %s%s%s%s%s',
            ! empty($this->fields) ? implode(',', $this->fields) : '',
            $this->table,
            ! empty($this->whereOrm) ? ' WHERE ' . implode(' AND ', $this->whereOrm) : '',
            ! empty($this->groupByOrm) ? ' GROUP BY ' . implode(',', $this->groupByOrm) : '',
            ! empty($this->orderByOrm) ? ' ORDER BY ' . implode(',', $this->orderByOrm) : '',
            ! empty($this->limitOrm) ? ' LIMIT ' . $this->limitOrm : ''
        );
    }
}
