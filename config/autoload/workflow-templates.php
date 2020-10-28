<?php

declare(strict_types=1);

use App\Model\AlarmGroup;
use App\Model\Workflow;

return [
    Workflow::SCENE_GENERATED => [
        AlarmGroup::CHANNEL_YACHGROUP => [
            'title' => '您有一个新工作流生成',
            'template' => '{common.env}您有一个新的告警工作流生成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'title' => '您有一个新工作流生成',
            'template' => '{common.env}您有一个新的告警工作流生成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGGROUP => [
            'title' => '您有一个新工作流生成',
            'template' => '{common.env}您有一个新的告警工作流生成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'title' => '您有一个新工作流生成',
            'template' => '{common.env}您有一个新的告警工作流生成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'title' => '您有一个新工作流生成',
            'template' => '{task.name}生成了新的告警工作流
工作流ID：{workflow.id}
时间：{workflow.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'title' => '您有一个新工作流生成',
            'template' => '{common.env}您有一个新的告警工作流生成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
            'subject' => '您有一个新工作流生成',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'title' => '您有一个新工作流生成',
            'template' => '{task.name}生成了新的告警工作流，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
    ],
    Workflow::SCENE_CLAIM => [
        // 认领
        AlarmGroup::CHANNEL_DINGGROUP => [
            'template' => '{common.env}您有一个告警工作流被认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'template' => '{common.env}您有一个告警工作流被认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHGROUP => [
            'template' => '{common.env}您有一个告警工作流被认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'template' => '{common.env}您有一个告警工作流被认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'template' => '{task.name}有一个告警工作流被认领
工作流ID：{workflow.id}
时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'template' => '{common.env}您有一个告警工作流被认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
            'subject' => '您有一个告警工作流被认领',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'template' => '{task.name}有一个告警工作流被认领，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_WECHAT => [
            'template' => 'workflow pending for dinggroup',
            'vars' => [], // TODO
        ],
    ],
    Workflow::SCENE_PROCESSED => [
        // 处理完成
        AlarmGroup::CHANNEL_DINGGROUP => [
            'template' => '{common.env}您有一个告警工作流已处理完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'template' => '{common.env}您有一个告警工作流已处理完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHGROUP => [
            'template' => '{common.env}您有一个告警工作流已处理完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'template' => '{common.env}您有一个告警工作流已处理完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'template' => '{task.name}有一个告警工作流已处理完成
工作流ID：{workflow.id}
时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'template' => '{common.env}您有一个告警工作流已处理完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
            'subject' => '您有一个告警工作流已处理完成',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'template' => '{task.name}有一个告警工作流已处理完成，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_WECHAT => [
            'template' => 'workflow pending for dinggroup',
            'vars' => [], // TODO
        ],
    ],
    Workflow::SCENE_ASSIGN => [
        // 指派
        AlarmGroup::CHANNEL_DINGGROUP => [
            'template' => '{common.env}您有一个告警工作流被指派

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'template' => '{common.env}您有一个告警工作流被指派

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_YACHGROUP => [
            'template' => '{common.env}您有一个告警工作流被指派

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'template' => '{common.env}您有一个告警工作流被指派

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'template' => '{task.name}有一个告警工作流被指派
工作流ID：{workflow.id}
时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'template' => '{common.env}您有一个告警工作流被指派

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
被指派给：{pipeline.props.assigntoUsers}
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
            'subject' => '您有一个告警工作流被指派',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'template' => '{task.name}有一个告警工作流被指派，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
被指派给：{pipeline.props.assigntoUsers}，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
                'pipeline.props.assigntoUsers',
            ],
        ],
        AlarmGroup::CHANNEL_WECHAT => [
            'template' => 'workflow pending for dinggroup',
            'vars' => [], // TODO
        ],
    ],
    Workflow::SCENE_REACTIVE => [
        // 重新激活
        AlarmGroup::CHANNEL_DINGGROUP => [
            'template' => '{common.env}您有一个告警工作流被重新激活

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'template' => '{common.env}您有一个告警工作流被重新激活

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHGROUP => [
            'template' => '{common.env}您有一个告警工作流被重新激活

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'template' => '{common.env}您有一个告警工作流被重新激活

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'template' => '{task.name}有一个告警工作流被重新激活
工作流ID：{workflow.id}
时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'template' => '{task.name}有一个告警工作流被重新激活，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
            'subject' => '您有一个告警工作流被重新激活',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'template' => '{task.name}有一个告警工作流被重新激活，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_WECHAT => [
            'template' => 'workflow pending for dinggroup',
            'vars' => [], // TODO
        ],
    ],
    Workflow::SCENE_CLOSE => [
        // 关闭
        AlarmGroup::CHANNEL_DINGGROUP => [
            'template' => '{common.env}您有一个告警工作流被关闭

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'template' => '{common.env}您有一个告警工作流被关闭

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHGROUP => [
            'template' => '{common.env}您有一个告警工作流被关闭

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'template' => '{common.env}您有一个告警工作流被关闭

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'template' => '{task.name}有一个告警工作流被关闭
工作流ID：{workflow.id}
时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'template' => '{common.env}您有一个告警工作流被关闭

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
操作人：{pipeline.user.username}({pipeline.user.user})
留言：{pipeline.remark}
认领时间：{pipeline.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
            'subject' => '您有一个告警工作流被关闭',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'template' => '{task.name}有一个告警工作流被关闭，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
操作人：{pipeline.user.username}({pipeline.user.user})，
留言：{pipeline.remark}，
认领时间：{pipeline.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
                'pipeline.remark',
                'pipeline.user.username',
                'pipeline.user.user',
                'pipeline.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_WECHAT => [
            'template' => 'workflow pending for dinggroup',
            'vars' => [], // TODO
        ],
    ],
    Workflow::SCENE_REMIND_PENDING => [
        AlarmGroup::CHANNEL_YACHGROUP => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{common.env}您有一个告警工作流待认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{common.env}您有一个告警工作流待认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGGROUP => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{common.env}您有一个告警工作流待认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{common.env}您有一个告警工作流待认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{task.name}有一个告警工作流待认领
工作流ID：{workflow.id}
时间：{workflow.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{common.env}您有一个告警工作流待认领

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
            'subject' => '您有一个告警工作流待认领',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'title' => '您有一个告警工作流待认领',
            'template' => '{task.name}有一个告警工作流待认领，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
    ],
    Workflow::SCENE_REMIND_PROCESSING => [
        AlarmGroup::CHANNEL_YACHGROUP => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{common.env}您有一个告警工作流待完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_YACHWORKER => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{common.env}您有一个告警工作流待完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGGROUP => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{common.env}您有一个告警工作流待完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_DINGWORKER => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{common.env}您有一个告警工作流待完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_SMS => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{task.name}有一个告警工作流待完成
工作流ID：{workflow.id}
时间：{workflow.created_at}
级别：{history.level}
告警ID：{history.uuid}',
            'vars' => [
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
        AlarmGroup::CHANNEL_EMAIL => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{common.env}您有一个告警工作流待完成

告警任务：{task.name}
工作流ID：{workflow.id}
生成时间：{workflow.created_at}
告警级别：{history.level}
告警ID：{history.uuid}
',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'history.ctn',
                'workflow.id',
                'workflow.created_at',
            ],
            'subject' => '您有一个告警工作流待完成',
        ],
        AlarmGroup::CHANNEL_PHONE => [
            'title' => '您有一个告警工作流待完成',
            'template' => '{task.name}有一个告警工作流待完成，
工作流ID：{workflow.id}，
时间：{workflow.created_at}，
级别：{history.level}，
告警ID：{history.uuid}',
            'vars' => [
                'common.env',
                'task.name',
                'history.uuid',
                'history.level',
                'workflow.id',
                'workflow.created_at',
            ],
        ],
    ],
];
