<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/admin', 'App\Controller\IndexController@admin');

Router::addGroup('/api', function () {
    // 获取验证码
    Router::addRoute(['GET'], '/auth/captcha', 'App\Controller\AuthController@captcha');
    // 帐号密码登录
    Router::addRoute(['POST'], '/auth/verifyaccount', 'App\Controller\AuthController@verifyAccount');
    // 发送邮箱登录验证码
    Router::addRoute(['POST'], '/auth/mail', 'App\Controller\AuthController@mail');
    // 登录验证码验证
    Router::addRoute(['POST'], '/auth/verifymail', 'App\Controller\AuthController@verifyMail');
    // 退出登录清除token
    Router::addRoute(['GET'], '/logout', 'App\Controller\AuthController@logout', ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/dashboard', function () {
        Router::addRoute(['GET'], '/stat', 'App\Controller\DashboardController@stat');
        Router::addRoute(['GET'], '/avgreqqps', 'App\Controller\DashboardController@avgReqQps');
        Router::addRoute(['GET'], '/maxreqqps', 'App\Controller\DashboardController@maxReqQps');
        Router::addRoute(['GET'], '/avgprodqps', 'App\Controller\DashboardController@avgProdQps');
        Router::addRoute(['GET'], '/maxprodqps', 'App\Controller\DashboardController@maxProdQps');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/user', function () {
        Router::addRoute(['GET'], '/search', 'App\Controller\UserController@search');
        Router::addRoute(['GET'], '/profile', 'App\Controller\UserController@profile');
        Router::addRoute(['GET'], '/permission', 'App\Controller\UserController@permission');
        Router::addRoute(['PUT'], '/updatephone', 'App\Controller\UserController@updatePhone');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/department', function () {
        Router::addRoute(['GET'], '', 'App\Controller\DepartmentController@list');
        Router::addRoute(['POST'], '/store', 'App\Controller\DepartmentController@store');
        Router::addRoute(['PUT'], '/update', 'App\Controller\DepartmentController@update');
        Router::addRoute(['DELETE'], '', 'App\Controller\DepartmentController@delete');
        Router::addRoute(['GET'], '/simple', 'App\Controller\DepartmentController@simpleList');
        Router::addRoute(['GET'], '/show', 'App\Controller\DepartmentController@show');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/businessunit', function () {
        Router::addRoute(['GET'], '', 'App\Controller\BusinessUnitController@list');
        Router::addRoute(['GET'], '/show', 'App\Controller\BusinessUnitController@show');
        Router::addRoute(['DELETE'], '', 'App\Controller\BusinessUnitController@delete');
        Router::addRoute(['GET'], '/simple', 'App\Controller\BusinessUnitController@simpleList');
        Router::addRoute(['POST'], '/store', 'App\Controller\BusinessUnitController@store');
        Router::addRoute(['PUT'], '/update', 'App\Controller\BusinessUnitController@update');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmgroup', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmGroupController@get');
        Router::addRoute(['POST'], '/store', 'App\Controller\AlarmGroupController@store');
        Router::addRoute(['GET'], '/show', 'App\Controller\AlarmGroupController@show');
        Router::addRoute(['PUT'], '/update', 'App\Controller\AlarmGroupController@update');
        Router::addRoute(['DELETE'], '/delete', 'App\Controller\AlarmGroupController@delete');
        Router::addRoute(['GET'], '/search', 'App\Controller\AlarmGroupController@search');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmtask', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmTaskController@list');
        Router::addRoute(['GET'], '/simple', 'App\Controller\AlarmTaskController@simpleList');
        Router::addRoute(['GET'], '/simpleall', 'App\Controller\AlarmTaskController@simpleAll');
        Router::addRoute(['POST'], '/store', 'App\Controller\AlarmTaskController@store');
        Router::addRoute(['PUT'], '/update', 'App\Controller\AlarmTaskController@update');
        Router::addRoute(['DELETE'], '', 'App\Controller\AlarmTaskController@delete');
        Router::addRoute(['PUT'], '/stop', 'App\Controller\AlarmTaskController@stop');
        Router::addRoute(['PUT'], '/start', 'App\Controller\AlarmTaskController@start');
        Router::addRoute(['PUT'], '/pause', 'App\Controller\AlarmTaskController@pause');
        Router::addRoute(['PUT'], '/resettoken', 'App\Controller\AlarmTaskController@resetToken');
        Router::addRoute(['PUT'], '/resetsecret', 'App\Controller\AlarmTaskController@resetSecret');
        Router::addRoute(['POST'], '/reportalarm', 'App\Controller\AlarmTaskController@reportAlarm');
        Router::addRoute(['GET'], '/show', 'App\Controller\AlarmTaskController@show');
        Router::addRoute(['POST'], '/validrobotparam', 'App\Controller\AlarmTaskController@validRobotParam');
        Router::addRoute(['POST'], '/validwebhookaddress', 'App\Controller\AlarmTaskController@validWebHookAddress');
        Router::addRoute(['GET'], '/ratelimit', 'App\Controller\AlarmTaskController@getRateLimit');
        Router::addRoute(['PUT'], '/ratelimit', 'App\Controller\AlarmTaskController@updateRateLimit');
        Router::addRoute(['GET'], '/simplebytag', 'App\Controller\AlarmTaskController@simpleByTag');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmtaskqps', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmTaskQpsController@getOffLineQps');
        Router::addRoute(['GET'], '/prodnum', 'App\Controller\AlarmTaskQpsController@getProdNumber');
        Router::addRoute(['GET'], '/tasksnum', 'App\Controller\AlarmTaskQpsController@getTasksNumber');
        Router::addRoute(['GET'], '/activetasks', 'App\Controller\AlarmTaskQpsController@getActiveTasks');
        Router::addRoute(['GET'], '/dynamic', 'App\Controller\AlarmTaskQpsController@getDynamicQps');
        Router::addRoute(['GET'], '/workflowstatus', 'App\Controller\AlarmTaskQpsController@getWorkFlowStatus');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmhistory', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmHistoryController@list');
        Router::addRoute(['GET'], '/show', 'App\Controller\AlarmHistoryController@show');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmtemplate', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmTemplateController@list');
        Router::addRoute(['GET'], '/show', 'App\Controller\AlarmTemplateController@show');
        Router::addRoute(['DELETE'], '', 'App\Controller\AlarmTemplateController@delete');
        Router::addRoute(['GET'], '/simple', 'App\Controller\AlarmTemplateController@simpleList');
        Router::addRoute(['GET'], '/defaults', 'App\Controller\AlarmTemplateController@defaultTemplates');
        Router::addRoute(['POST'], '/store', 'App\Controller\AlarmTemplateController@store');
        Router::addRoute(['PUT'], '/update', 'App\Controller\AlarmTemplateController@update');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/alarmtag', function () {
        Router::addRoute(['GET'], '', 'App\Controller\AlarmTagController@list');
        Router::addRoute(['GET'], '/search', 'App\Controller\AlarmTagController@search');
        Router::addRoute(['DELETE'], '', 'App\Controller\AlarmTagController@delete');
        Router::addRoute(['POST'], '', 'App\Controller\AlarmTagController@store');
        Router::addRoute(['PUT'], '', 'App\Controller\AlarmTagController@update');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/workflow', function () {
        Router::addRoute(['GET'], '', 'App\Controller\WorkflowController@list');
        Router::addRoute(['GET'], '/show', 'App\Controller\WorkflowController@show');
        Router::addRoute(['GET'], '/statisticsbystatus', 'App\Controller\WorkflowController@statsByStatus');
        Router::addRoute(['PUT'], '/claim', 'App\Controller\WorkflowController@claim');
        Router::addRoute(['PUT'], '/assign', 'App\Controller\WorkflowController@assign');
        Router::addRoute(['PUT'], '/processed', 'App\Controller\WorkflowController@processed');
        Router::addRoute(['PUT'], '/reactive', 'App\Controller\WorkflowController@reactive');
        Router::addRoute(['PUT'], '/close', 'App\Controller\WorkflowController@close');
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    Router::addGroup('/monitor', function () {
        Router::addGroup('/datasource', function () {
            Router::addRoute(['GET'], '', 'App\Controller\Monitor\DatasourceController@list');
            Router::addRoute(['GET'], '/show', 'App\Controller\Monitor\DatasourceController@show');
            Router::addRoute(['DELETE'], '', 'App\Controller\Monitor\DatasourceController@delete');
            Router::addRoute(['GET'], '/simple', 'App\Controller\Monitor\DatasourceController@simpleList');
            Router::addRoute(['POST'], '/store', 'App\Controller\Monitor\DatasourceController@store');
            Router::addRoute(['PUT'], '/update', 'App\Controller\Monitor\DatasourceController@update');
            Router::addRoute(['POST'], '/validconnect', 'App\Controller\Monitor\DatasourceController@validConnect');
            Router::addRoute(['GET'], '/fields', 'App\Controller\Monitor\DatasourceController@fields');
        });
        Router::addGroup('/universal', function () {
            Router::addRoute(['GET'], '', 'App\Controller\Monitor\UniversalController@list');
            Router::addRoute(['GET'], '/show', 'App\Controller\Monitor\UniversalController@show');
            Router::addRoute(['DELETE'], '', 'App\Controller\Monitor\UniversalController@delete');
            Router::addRoute(['GET'], '/simple', 'App\Controller\Monitor\UniversalController@simpleList');
            Router::addRoute(['POST'], '/store', 'App\Controller\Monitor\UniversalController@store');
            Router::addRoute(['PUT'], '/update', 'App\Controller\Monitor\UniversalController@update');
            Router::addRoute(['PUT'], '/stop', 'App\Controller\Monitor\UniversalController@stop');
            Router::addRoute(['PUT'], '/start', 'App\Controller\Monitor\UniversalController@start');
            Router::addRoute(['PUT'], '/resettoken', 'App\Controller\Monitor\UniversalController@resetToken');
            // Router::addRoute(['POST'], '/validconnect', 'App\Controller\Monitor\UniversalController@validConnect');
        });
        Router::addGroup('/protocoldetect', function () {
            Router::addRoute(['GET'], '', 'App\Controller\Monitor\ProtocolDetectController@list');
            Router::addRoute(['GET'], '/show', 'App\Controller\Monitor\ProtocolDetectController@show');
            Router::addRoute(['DELETE'], '', 'App\Controller\Monitor\ProtocolDetectController@delete');
            Router::addRoute(['GET'], '/simple', 'App\Controller\Monitor\ProtocolDetectController@simpleList');
            Router::addRoute(['POST'], '/store', 'App\Controller\Monitor\ProtocolDetectController@store');
            Router::addRoute(['PUT'], '/update', 'App\Controller\Monitor\ProtocolDetectController@update');
            Router::addRoute(['PUT'], '/stop', 'App\Controller\Monitor\ProtocolDetectController@stop');
            Router::addRoute(['PUT'], '/start', 'App\Controller\Monitor\ProtocolDetectController@start');
            Router::addRoute(['PUT'], '/resettoken', 'App\Controller\Monitor\ProtocolDetectController@resetToken');
            Router::addRoute(['POST'], '/validconnect', 'App\Controller\Monitor\ProtocolDetectController@validConnect');
        });
        Router::addGroup('/cyclecompare', function () {
            Router::addRoute(['GET'], '', 'App\Controller\Monitor\CycleCompareController@list');
            Router::addRoute(['GET'], '/show', 'App\Controller\Monitor\CycleCompareController@show');
            Router::addRoute(['DELETE'], '', 'App\Controller\Monitor\CycleCompareController@delete');
            Router::addRoute(['GET'], '/simple', 'App\Controller\Monitor\CycleCompareController@simpleList');
            Router::addRoute(['POST'], '/store', 'App\Controller\Monitor\CycleCompareController@store');
            Router::addRoute(['PUT'], '/update', 'App\Controller\Monitor\CycleCompareController@update');
            Router::addRoute(['PUT'], '/stop', 'App\Controller\Monitor\CycleCompareController@stop');
            Router::addRoute(['PUT'], '/start', 'App\Controller\Monitor\CycleCompareController@start');
            Router::addRoute(['PUT'], '/resettoken', 'App\Controller\Monitor\CycleCompareController@resetToken');
            Router::addRoute(
                ['POST'],
                '/datainitbydatasource',
                'App\Controller\Monitor\CycleCompareController@dataInitByDatasource'
            );

            Router::addRoute(
                ['POST'],
                '/datainitbywebhook',
                'App\Controller\Monitor\CycleCompareController@dataInitByWebhook'
            );
            // Router::addRoute(['POST'], '/validconnect', 'App\Controller\Monitor\CycleCompareController@validConnect');
        });
    }, ['middleware' => [\App\Middleware\JwtAuthMiddleware::class]]);

    // 同比环比监控数据源初始化
    Router::addRoute(
        ['POST'],
        '/monitor/cyclecompare/datainitbypush',
        'App\Controller\Monitor\CycleCompareController@dataInitByPush'
    );
});

Router::addGroup('/openapi', function () {
    Router::addGroup('/department', function () {
        Router::addRoute(['GET'], '', 'App\Controller\OpenApi\DepartmentController@list');
        Router::addRoute(['GET'], '/simple', 'App\Controller\OpenApi\DepartmentController@simpleList');
        Router::addRoute(['GET'], '/show', 'App\Controller\OpenApi\DepartmentController@show');
    });
    Router::addGroup('/alarmtask', function () {
//        Router::addRoute(['GET'], '', 'App\Controller\OpenApi\AlarmTaskController@list');
        Router::addRoute(['GET'], '/simple', 'App\Controller\OpenApi\AlarmTaskController@simpleList');
        Router::addRoute(['POST'], '/store', 'App\Controller\OpenApi\AlarmTaskController@store');
        Router::addRoute(['PUT'], '/update', 'App\Controller\OpenApi\AlarmTaskController@update');
        Router::addRoute(['PUT'], '/updatefields', 'App\Controller\OpenApi\AlarmTaskController@updateFields');
        Router::addRoute(['DELETE'], '', 'App\Controller\OpenApi\AlarmTaskController@delete');
        Router::addRoute(['PUT'], '/stop', 'App\Controller\OpenApi\AlarmTaskController@stop');
        Router::addRoute(['PUT'], '/start', 'App\Controller\OpenApi\AlarmTaskController@start');
        Router::addRoute(['PUT'], '/pause', 'App\Controller\OpenApi\AlarmTaskController@pause');
        Router::addRoute(['GET'], '/show', 'App\Controller\OpenApi\AlarmTaskController@show');
    });
    Router::addGroup('/user', function () {
        Router::addRoute(['GET'], '/profile', 'App\Controller\OpenApi\UserController@profile');
        Router::addRoute(['PUT'], '/updatephone', 'App\Controller\OpenApi\UserController@updatePhone');
    });
    Router::addGroup('/workflow', function () {
        Router::addRoute(['GET'], '', 'App\Controller\OpenApi\WorkflowController@list');
        Router::addRoute(['GET'], '/show', 'App\Controller\OpenApi\WorkflowController@show');
        Router::addRoute(['GET'], '/statsByStatus', 'App\Controller\OpenApi\WorkflowController@statsByStatus');
        Router::addRoute(['PUT'], '/claim', 'App\Controller\OpenApi\WorkflowController@claim');
        Router::addRoute(['PUT'], '/assign', 'App\Controller\OpenApi\WorkflowController@assign');
        Router::addRoute(['PUT'], '/processed', 'App\Controller\OpenApi\WorkflowController@processed');
        Router::addRoute(['PUT'], '/reactive', 'App\Controller\OpenApi\WorkflowController@reactive');
        Router::addRoute(['PUT'], '/close', 'App\Controller\OpenApi\WorkflowController@close');
    });
}, ['middleware' => [\App\Middleware\OpenapiGatewayMiddleware::class]]);
