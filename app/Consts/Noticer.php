<?php

declare(strict_types=1);

namespace App\Consts;

class Noticer
{
    /**
     * 事件类型.
     */
    public const EVENT_PING = 'PING';

    public const EVENT_ALARM = 'ALARM';

    public const EVENT_UPGRADE = 'UPGRADE';

    public const EVENT_RECOVERY = 'RECOVERY';

    public const EVENT_WORKFLOW = 'WORKFLOW';

    /**
     * 场景.
     */
    public const SCENE_PING_PING = 'ping';

    public const SCENE_ALARM_NOT_SAVE_DB = 'not_save_db';

    public const SCENE_ALARM_COMPRESSED = 'compressed';

    public const SCENE_ALARM_COMPRESS_NOT_MATCH = 'compress_not_match';

    public const SCENE_ALARM_COMPRESS_DISABLE = 'compress_disable';

    public const SCENE_UPGRADE_UPGRADE = 'upgrade';

    public const SCENE_RECOVERY_NOT_SAVE_DB = 'not_save_db';

    public const SCENE_RECOVERY_RECOVERY = 'recovery';

    public const SCENE_WORKFLOW_REMIND_PENDING = 'remind_pending';

    public const SCENE_WORKFLOW_REMIND_PROCESSING = 'remind_processing';

    public const SCENE_WORKFLOW_GENERATED = 'generated';

    public const SCENE_WORKFLOW_CLAIM = 'claim';

    public const SCENE_WORKFLOW_ASSIGN = 'assign';

    public const SCENE_WORKFLOW_PROCESSED = 'processed';

    public const SCENE_WORKFLOW_REACTIVE = 'reactive';

    public const SCENE_WORKFLOW_CLOSE = 'close';
}
