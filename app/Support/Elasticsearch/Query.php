<?php

declare(strict_types=1);

namespace App\Support\Elasticsearch;

use App\Exception\AppException;

class Query
{
    /**
     * 查询条件符.
     */
    public const TYPE_BETWEEN = 'between';

    public const TYPE_GTE = '>=';

    public const TYPE_LTE = '<=';

    public const TYPE_GT = '>';

    public const TYPE_LT = '<';

    public const TYPE_EQ = '=';

    public const TYPE_IN = 'in';

    public const TYPE_LIKE = 'like';

    /**
     * 允许查询条件类型.
     *
     * @var array
     */
    protected $allowTypes = [
        self::TYPE_BETWEEN => 1,
        self::TYPE_GTE => 1,
        self::TYPE_LTE => 1,
        self::TYPE_GT => 1,
        self::TYPE_LT => 1,
        self::TYPE_EQ => 1,
        self::TYPE_IN => 1,
        self::TYPE_LIKE => 1,
    ];

    public function __construct()
    {
    }

    /**
     * 设置query.
     */
    public function setQuery(array $conditions): array
    {
        try {
            // 构造请求参数
            $params = [
                'bool' => [
                    'must' => [],
                ],
            ];

            if (empty($conditions)) {
                return [];
            }

            foreach ($conditions as $condition) {
                $type = strtolower($condition['type']);

                if (! isset($this->allowTypes[$type])) {
                    throw new AppException('执行条件不允许，请检查或者扩展条件！');
                }

                $params = $this->setType($params, $type, $condition);
            }

            return $params;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 设置条件.
     */
    public function setType(array $params, string $type, array $condition): array
    {
        switch ($type) {
            case self::TYPE_BETWEEN:
                $params['bool']['filter']['range'][$condition['field']]['gte'] = (int) $condition['value'][0];
                $params['bool']['filter']['range'][$condition['field']]['lte'] = (int) $condition['value'][1];
                break;
            case self::TYPE_GTE:
                $params['bool']['filter']['range'][$condition['field']]['gte'] = (int) $condition['value'];
                break;
            case self::TYPE_LTE:
                $params['bool']['filter']['range'][$condition['field']]['lte'] = $condition['value'];
                break;
            case self::TYPE_GT:
                $params['bool']['filter']['range'][$condition['field']]['gt'] = $condition['value'];
                break;
            case self::TYPE_LT:
                $params['bool']['filter']['range'][$condition['field']]['lt'] = $condition['value'];
                break;
            case self::TYPE_EQ:
                $params['bool']['must'][]['match_phrase'][$condition['field']] = ['query' => $condition['value']];
                break;
            case self::TYPE_IN:
                if (! empty($condition['value']) || is_array($condition['value'])) {
                    $ins = [];
                    foreach ($condition['value'] as $val) {
                        $ins['bool']['should'][] = [
                            'match_phrase' => [
                                $condition['field'] => $val,
                            ],
                        ];
                    }
                    ! empty($ins) && $params['bool']['must'][] = $ins;
                }
                break;
            case self::TYPE_LIKE:
                $params['bool']['must'][]['query_string'] = [
                    'query' => $condition['value'],
                    'analyze_wildcard' => true,
                    'default_field' => $condition['field'],
                ];
                break;
        }

        return $params;
    }
}
