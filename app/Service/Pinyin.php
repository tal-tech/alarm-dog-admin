<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Di\Annotation\Inject;
use Overtrue\Pinyin\Pinyin as BasePinyin;

class Pinyin
{
    /**
     * @Inject
     * @var BasePinyin
     */
    protected $pinyin;

    /**
     * 语句转拼音选项.
     *
     * @var int
     */
    protected $pinyinOptConvert;

    /**
     * 姓名转拼音选项.
     *
     * @var int
     */
    protected $pinyinOptName;

    public function __construct()
    {
        $this->pinyinOptConvert = (int) config('app.pinyin.optConvert', PINYIN_DEFAULT);
        $this->pinyinOptName = (int) config('app.pinyin.optName', PINYIN_NAME);
    }

    /**
     * 转换一般名称.
     *
     * @param string $name
     * @return string
     */
    public function convert($name)
    {
        $pinyins = $this->pinyin->convert($name, $this->pinyinOptConvert);

        return implode('', $pinyins);
    }

    /**
     * 转换姓名.
     *
     * @param string $name
     * @return string
     */
    public function name($name)
    {
        $pinyins = $this->pinyin->name($name, $this->pinyinOptName);

        return implode('', $pinyins);
    }
}
