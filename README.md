# 哮天犬后台接口

## 环境要求

该模块是基于 [hyperf 1.1](https://hyperf.wiki/1.1/#/zh-cn/quick-start/install) 的框架开发，环境要求同该框架要求：

- PHP >= 7.2
- Swoole PHP 扩展 >= 4.4，并关闭了 `Short Name`
- OpenSSL PHP 扩展
- JSON PHP 扩展
- GD PHP 扩展，生成验证码需要

## 安装

```shell
composer install
```

## 配置

```shell
cp .env.example .env
```

然后根据实际情况修改 `.env` 里面的配置

## 启动

```shell
php bin/hyperf.php start`
```
