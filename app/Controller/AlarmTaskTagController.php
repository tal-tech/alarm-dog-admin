<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\AlarmTaskTag;
use Hyperf\Di\Annotation\Inject;

class AlarmTaskTagController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTaskTag
     */
    protected $alarmTaskTag;
}
