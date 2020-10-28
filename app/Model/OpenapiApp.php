<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use Throwable;

class OpenapiApp extends Model
{
    public $timestamps = false;

    protected $table = 'openapi_app';

    protected $fillable = ['appid', 'token', 'name', 'remark', 'created_at', 'updated_at'];

    protected $hiddens = [];

    /**
     * 创建应用.
     *
     * @param string $name
     * @param string $remark
     * @return self
     */
    public function createApp($name, $remark = '')
    {
        if ($this->hasByName($name)) {
            throw new AppException("App [{$name}] exists, please use other name");
        }

        // appid可能会冲突，采用死循环直到成功能有效创建应用
        while (true) {
            $data = [
                'appid' => $this->genAppid(),
                'name' => $name,
                'remark' => $remark,
                'token' => sha1(uniqid()),
                'created_at' => time(),
                'updated_at' => time(),
            ];
            try {
                return self::create($data);
            } catch (Throwable $e) {
                // appid冲突报错则继续
                if ($e->getCode() == 23000) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * 生成APPID.
     */
    public function genAppid()
    {
        return (int) substr((string) sprintf('%u', crc32(uniqid())), 0, 6);
    }

    public function hasByName($name, $excludeAppid = 0)
    {
        if ($excludeAppid) {
            return $this->where('name', $name)->where('appid', '<>', $excludeAppid)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 查询APP详情.
     * @param mixed $keywords
     * @param mixed $pageSize
     */
    public function searchApps($keywords, $pageSize = 20)
    {
        $apps = $this->where(function ($query) use ($keywords) {
            $query->where('name', 'like', "%{$keywords}%")
                ->orWhere('remark', 'like', '%{$keywords}%')
                ->orWhere('appid', $keywords);
        })->limit($pageSize)->get();

        return $apps;
    }

    public function getByIdAndThrow($appid, $throwable = false)
    {
        $app = $this->where('appid', $appid)->first();
        if ($throwable && empty($app)) {
            throw new AppException("openapi app [{$appid}] not found");
        }

        return $app;
    }

    /**
     * 更新APP信息.
     *
     * @param int $appid
     * @param string $name
     * @param string $remark
     * @param bool $resetToken
     * @return self
     */
    public function updateApp($appid, $name = null, $remark = null, $resetToken = false)
    {
        $app = $this->getByIdAndThrow($appid, true);
        if ($name !== null) {
            if ($this->hasByName($name, $appid)) {
                throw new AppException("App [{$name}] exists, please use other name");
            }
            $app->name = $name;
        }
        if ($remark !== null) {
            $app->remark = $remark;
        }
        if ($resetToken) {
            $app->token = sha1(uniqid());
        }
        $app->updated_at = time();

        $app->save();

        return $app;
    }

    /**
     * 删除APP.
     *
     * @param int $appid
     * @return self
     */
    public function deleteApp($appid)
    {
        $app = $this->getByIdAndThrow($appid, true);
        $app->delete();

        return $app;
    }
}
