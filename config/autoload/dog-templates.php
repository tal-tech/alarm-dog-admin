<?php

declare(strict_types=1);

use App\Model\AlarmGroup;
use App\Model\AlarmTemplate;

return [
    // 告警任务直接使用的模板
    'tasks' => [
        AlarmTemplate::SCENE_NOT_COMPRESS => [
            AlarmGroup::CHANNEL_YACHGROUP => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个新告警
    
任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
            AlarmGroup::CHANNEL_YACHWORKER => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个新告警
    
任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
            AlarmGroup::CHANNEL_DINGGROUP => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个新告警
    
任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
            AlarmGroup::CHANNEL_DINGWORKER => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个新告警
    
任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
            AlarmGroup::CHANNEL_SMS => [
                'title' => '您有一个新告警',
                'template' => '{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
            AlarmGroup::CHANNEL_EMAIL => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个新告警
    
任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
                'subject' => '您有一个新告警',
            ],
            AlarmGroup::CHANNEL_PHONE => [
                'title' => '您有一个新告警',
                'template' => '{task.name}发生了告警，
级别：{history.level}，
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
        ],
        AlarmTemplate::SCENE_COMPRESSED => [
            AlarmGroup::CHANNEL_YACHGROUP => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个[{task.compress_method}-{task.compress_type}收敛]告警

任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
            ],
            AlarmGroup::CHANNEL_YACHWORKER => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个[{task.compress_method}-{task.compress_type}收敛]告警

任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
            ],
            AlarmGroup::CHANNEL_DINGGROUP => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个[{task.compress_method}-{task.compress_type}收敛]告警

任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
            ],
            AlarmGroup::CHANNEL_DINGWORKER => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个[{task.compress_method}-{task.compress_type}收敛]告警

任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
            ],
            AlarmGroup::CHANNEL_SMS => [
                'title' => '您有一个新告警',
                'template' => '{task.name}发生了一个[{task.compress_method}-{task.compress_type}收敛]告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
            ],
            AlarmGroup::CHANNEL_EMAIL => [
                'title' => '您有一个新告警',
                'template' => '{common.env}您有一个[{task.compress_method}-{task.compress_type}收敛]告警

任务：{task.name}发生了告警
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.level',
                    'history.ctn',
                    'task.compress_type',
                    'task.compress_method',
                ],
                'subject' => '您有一个新告警',
            ],
            AlarmGroup::CHANNEL_PHONE => [
                'title' => '您有一个新告警',
                'template' => '{task.name}发生了收敛告警，
级别：{history.level}，
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.level',
                    'history.ctn',
                ],
            ],
        ],
        AlarmTemplate::SCENE_UPGRADE => [
            AlarmGroup::CHANNEL_YACHGROUP => [
                'title' => '您有一个新告警升级',
                'template' => '{common.env}您有一个告警升级

任务：{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，告警次数为：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_YACHWORKER => [
                'title' => '您有一个新告警升级',
                'template' => '{common.env}您有一个告警升级

任务：{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，告警次数为：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_DINGGROUP => [
                'title' => '您有一个新告警升级',
                'template' => '{common.env}您有一个告警升级

任务：{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，告警次数为：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_DINGWORKER => [
                'title' => '您有一个新告警升级',
                'template' => '{common.env}您有一个告警升级

任务：{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，告警次数为：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_SMS => [
                'title' => '您有一个新告警升级',
                'template' => '{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级
次数：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_EMAIL => [
                'title' => '您有一个新告警升级',
                'template' => '{common.env}您有一个告警升级

任务：{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，告警次数为：{context.zcount}
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
                'subject' => '您有一个告警升级',
            ],
            AlarmGroup::CHANNEL_PHONE => [
                'title' => '您有一个新告警升级',
                'template' => '{task.name}触发了{context.rule.interval}分钟{context.rule.count}条的告警升级，
次数：{context.zcount}，
级别：{history.level}，
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'context.zcount',
                    'context.rule.interval',
                    'context.rule.count',
                    'history.ctn',
                    'history.level',
                ],
            ],
        ],
        AlarmTemplate::SCENE_RECOVERY => [
            AlarmGroup::CHANNEL_YACHGROUP => [
                'title' => '您有一个新告警恢复',
                'template' => '{common.env}您有一个告警恢复

任务：{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_YACHWORKER => [
                'title' => '您有一个新告警恢复',
                'template' => '{common.env}您有一个告警恢复

任务：{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_DINGGROUP => [
                'title' => '您有一个新告警恢复',
                'template' => '{common.env}您有一个告警恢复

任务：{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_DINGWORKER => [
                'title' => '您有一个新告警恢复',
                'template' => '{common.env}您有一个告警恢复

任务：{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_SMS => [
                'title' => '您有一个新告警恢复',
                'template' => '{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
            AlarmGroup::CHANNEL_EMAIL => [
                'title' => '您有一个新告警恢复',
                'template' => '{common.env}您有一个告警恢复

任务：{task.name}触发了告警恢复
级别：{history.level}
内容：{history.ctn}',
                'vars' => [
                    'common.env',
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
                'subject' => '您有一个告警恢复',
            ],
            AlarmGroup::CHANNEL_PHONE => [
                'title' => '您有一个新告警恢复',
                'template' => '{task.name}触发了告警恢复，
级别：{history.level}，
内容：{history.ctn}',
                'vars' => [
                    'task.name',
                    'history.ctn',
                    'history.level',
                ],
            ],
        ],
    ],
];
