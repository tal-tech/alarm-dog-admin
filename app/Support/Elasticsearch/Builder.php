<?php

declare(strict_types=1);

namespace App\Support\Elasticsearch;

use App\Exception\AppException;

class Builder
{
    /**
     * 索引名称，后续扩展，一个表对应一个索引操作文件.
     *
     * @var string
     */
    protected $indexName = '';

    /**
     * 索引别名.
     *
     * @var string
     */
    protected $indexAlias = '';

    /**
     * 类型.
     *
     * @var string
     */
    protected $indexType = '';

    /**
     * 客户端实例.
     *
     * @var
     */
    private $client;

    public function __construct(string $index, string $alias, string $type)
    {
        try {
            if (empty($index) || empty($type) || empty($alias)) {
                throw new AppException('elasticsearch index/alias/type null');
            }

            $this->client = make(Elasticsearch::class)->getInstance();

            $this->indexName = $index;
            $this->indexAlias = $alias;
            $this->indexType = $type;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 初始化索引参数.
     */
    public function initParams(): array
    {
        return [
            'index' => $this->indexName,
            'type' => $this->indexType,
        ];
    }

    /**
     * 创建一个索引.
     *
     * @return array
     */
    public function createIndex(array $settings = [])
    {
        try {
            $initParams['index'] = $this->indexName;
            ! empty($settings) && $initParams['body']['settings'] = $settings;

            return $this->client->indices()->create($initParams);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 索引是否存在.
     *
     * @return bool
     */
    public function existsIndex()
    {
        $params['index'] = $this->indexName;
        return $this->client->indices()->exists($params);
    }

    /**
     * 删除索引.
     *
     * @return array
     */
    public function deleteIndex()
    {
        try {
            $params = [
                'index' => $this->indexName,
            ];

            return $this->client->indices()->delete($params);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 索引设置别名.
     *
     * @return array
     */
    public function setAlias()
    {
        try {
            $aliasParams = [
                'index' => $this->indexName,
                'name' => $this->indexAlias,
            ];

            return $this->client->indices()->putAlias($aliasParams);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 向索引中插入数据.
     */
    public function add(array $data): bool
    {
        try {
            $params = $this->initParams();
            isset($data['id']) && $params['id'] = $data['id'];
            $params['body'] = $data['body'];

            $ret = $this->client->index($params);

            return (! isset($ret['_shards']['successful']) || ! $ret['_shards']['successful']) ? false : true;
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 批量创建索引
     * 说明：索引没有被创建时会自动创建索引.
     */
    public function bulk(array $body)
    {
        try {
            if (empty($body)) {
                return false;
            }

            foreach ($body as $bod) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $this->indexName,
                        '_type' => $this->indexType,
                        '_id' => $bod['id'],
                    ],
                ];

                $params['body'][] = $bod;
            }

            return $this->client->bulk($params);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 更新索引数据.
     *
     * @param $id
     * @param $doc
     */
    public function updateDoc(int $id, array $doc)
    {
        try {
            if (empty($id) || empty($doc)) {
                return [];
            }

            $params = $this->initParams();
            $params['id'] = $id;
            $params['body']['doc'] = $doc;

            return $this->client->update($params);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 删除索引.
     */
    public function deleteDoc(int $id)
    {
        try {
            if (empty($id)) {
                return false;
            }

            $params = $this->initParams();
            $params['id'] = $id;

            return $this->client->delete($params);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 根据关键字查询数据.
     */
    public function searchMulti(array $data = []): array
    {
        try {
            if (! is_array($data) || empty($data)) {
                return [];
            }

            $params = [
                'index' => $this->indexAlias,
                'type' => $this->indexType,
            ];

            // 查询字段
            if (array_key_exists('fields', $data)) {
                $params['_source'] = $data['fields'];
            }

            // 分页
            if (array_key_exists('pageSize', $data)) {
                $params['size'] = ! empty($data['pageSize']) ? $data['pageSize'] : 1;
                //前端页码默认传1
                $params['from'] = ! empty($data['page']) ? ($data['page'] - 1) * $params['size'] : 0;
            }

            // 排序
            if (array_key_exists('sortField', $data)) {
                $sortFile = ! empty($data['sortField']) ? $data['sortField'] : 'id';
                $sortRule = ! empty($data['sortRule']) ? $data['sortRule'] : 'DESC';
                $params['body']['sort'][] = [
                    '' . $sortFile . '' => [
                        'order' => '' . $sortRule . '',
                    ],
                ];
            }

            //条件组合
            if (array_key_exists('condition', $data)) {
                $queryObj = new Query();
                $query = $queryObj->setQuery($data['condition']);
                ! empty($query) && $params['body']['query'] = $query;
            }

            $ret = $this->client->search($params);

            return $this->formatSearch($ret);
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getConText(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 统一格式化搜索结果.
     */
    public function formatSearch(array $data): array
    {
        $ret = [
            'total' => 0,
            'list' => [],
        ];

        if (empty($data)) {
            return $ret;
        }

        if (! isset($data['hits']['hits']) || empty($data['hits']['hits'])) {
            return $ret;
        }

        $hits = $data['hits']['hits'];
        $ret['total'] = ! empty($data['hits']['total']) ? $data['hits']['total'] : 0;

        foreach ($hits as $k => $hit) {
            if (! isset($hit['_source']) || empty($hit['_source'])) {
                continue;
            }
            $ret['list'][$k] = $hit['_source'];
        }

        return $ret;
    }
}
