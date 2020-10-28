<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model as BaseModel;

abstract class Model extends BaseModel
{
    /**
     * 默认禁用自动时间戳管理，如需开启，请在父类中设置其值为true.
     *
     * @var bool
     */
    public $timestamps = false;
}
