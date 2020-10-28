<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Service\Pinyin;
use App\Support\ConditionArr;
use App\Support\MySQL;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Collection;
use Throwable;

class AlarmTemplate extends Model
{
    /**
     * 模板类型.
     */
    // 默认模板
    public const TYPE_DEFAULT = 0;

    // 预定义模板
    public const TYPE_PREDEFINED = 1;

    // 自定义模板
    public const TYPE_CUSTOM = 2;

    /**
     * 模板场景.
     */
    // 告警被收敛
    public const SCENE_COMPRESSED = 'compressed';

    // 告警未收敛
    public const SCENE_NOT_COMPRESS = 'not_compress';

    // 告警升级
    public const SCENE_UPGRADE = 'upgrade';

    // 告警自动恢复
    public const SCENE_RECOVERY = 'recovery';

    /**
     * 模板格式类型.
     */
    public const FORMAT_TEXT = 1;

    public const FORMAT_MARKDOWN = 2;

    public const FORMAT_HTML = 3;

    public const FORMAT_ACTIONCARD = 4;

    public $timestamps = false;

    public $casts = [
        'template' => 'array',
    ];

    /**
     * 告警模板场景.
     */
    public static $scenes = [
        self::SCENE_COMPRESSED => '告警被收敛',
        self::SCENE_NOT_COMPRESS => '告警未收敛',
        self::SCENE_UPGRADE => '告警升级',
        self::SCENE_RECOVERY => '告警自动恢复',
    ];

    /**
     * 告警模板可用渠道.
     */
    public static $channels = [
        AlarmGroup::CHANNEL_SMS,
        AlarmGroup::CHANNEL_EMAIL,
        AlarmGroup::CHANNEL_PHONE,
        // AlarmGroup::CHANNEL_WECHAT,
        AlarmGroup::CHANNEL_DINGWORKER,
        AlarmGroup::CHANNEL_DINGGROUP,
        AlarmGroup::CHANNEL_YACHWORKER,
        AlarmGroup::CHANNEL_YACHGROUP,
    ];

    protected $table = 'alarm_template';

    protected $fillable = ['name', 'pinyin', 'remark', 'template', 'created_by', 'created_at', 'updated_at'];

    /**
     * @Inject
     * @var pinyin
     */
    protected $pinyin;

    /**
     * @Inject
     * @var AlarmTemplateFilter
     */
    protected $alarmTemplateFilter;

    /**
     * @Inject
     * @var AlarmTemplatePermission
     */
    protected $permission;

    /**
     * 是否存在场景.
     *
     * @param string $scene
     * @return bool
     */
    public static function hasScene($scene)
    {
        return isset(static::$scenes[$scene]);
    }

    /**
     * 是否有权限.
     *
     * @param User $user
     * @param int $templateId
     * @return bool
     */
    public function hasPermisson($user, $templateId)
    {
        // 超管直接允许通过
        if ($user->role == User::ROLE_ADMIN) {
            return true;
        }

        return AlarmTemplatePermission::where('template_id', $templateId)
            ->where('uid', $user['uid'])
            ->exists();
    }

    /**
     * 查询对应告警组权限.
     *
     * @param int $templateId
     * @return array|Collection
     */
    public function permission($templateId)
    {
        $uids = AlarmTemplatePermission::where('template_id', $templateId)
            ->pluck('uid')
            ->toArray();

        return User::whereIn('uid', $uids)
            ->select('uid', 'user', 'email', 'username', 'department')
            ->get();
    }

    /**
     * 模板验证及解析.
     *
     * @param array $param
     * @param mixed $require
     * @return array
     */
    public function validAndFormat($param, $require = false)
    {
        $template = [];
        foreach (static::$scenes as $scene => $sceneTitle) {
            if (empty($param[$scene]) || ! is_array($param[$scene])) {
                continue;
            }
            $sceneTemplate = [];
            foreach (static::$channels as $channel) {
                if (empty($param[$scene][$channel])) {
                    continue;
                }
                if (empty($param[$scene][$channel]['template'])) {
                    continue;
                }

                // 电话通知模板感词过滤，并提示用户模板中存在的敏感词
                if ($channel === AlarmGroup::CHANNEL_PHONE) {
                    $sourceContent = preg_replace('/\{([^\{\}]+)\}/', '_', $param[$scene][$channel]['template']);
                    $senstiveWordsArr = $this->alarmTemplateFilter->senstiveFilte($sourceContent);
                    $senstiveWordsString = implode(',', $senstiveWordsArr);
                    if (! empty($senstiveWordsString)) {
                        throw new AppException("{$sceneTitle} -> 电话: 模板中有敏感词（  {$senstiveWordsString} ）请重新编辑模板。");
                    }
                }

                if ($channel === AlarmGroup::CHANNEL_SMS) {
                    $symbol = ['【', '】'];
                    $senstiveWordsArr = $this->alarmTemplateFilter->symbolFilter($param[$scene][$channel]['template'], $symbol);
                    $senstiveWordsString = implode(' ', $senstiveWordsArr);
                    if (! empty($senstiveWordsString)) {
                        throw new AppException("{$sceneTitle} -> 短信: 模板中不能有 {$senstiveWordsString} 符号请重新编辑模板。");
                    }
                }

                // 解析模板变量
                $vars = [];
                if (preg_match_all('/\{([^\{\}]+)\}/', $param[$scene][$channel]['template'], $matches)) {
                    $vars = $matches[1];
                }

                $sceneTemplate[$channel] = [
                    'format' => (int) ($param[$scene][$channel]['format'] ?? self::FORMAT_TEXT),
                    'template' => $param[$scene][$channel]['template'],
                    'vars' => $vars,
                    'vars_split' => $this->splitVars($vars),
                ];
            }
            if (! empty($sceneTemplate)) {
                $template[$scene] = $sceneTemplate;
            }
        }

        if ($require && empty($template)) {
            throw new AppException('fill in at least one template');
        }

        return $template;
    }

    /**
     * 模板格式化，变量替换.
     *
     * @param array $template
     * @param array $vars
     * @return string
     */
    public static function formatTemplate($template, $vars)
    {
        $template['vars'] = array_merge(config('dog.tpl.vars', []), $template['vars']);
        $vars = array_merge(config('dog.tpl.values', []), $vars);

        $replaceSearch = [];
        $replaceVars = [];

        foreach ($template['vars'] as $varName) {
            if (array_key_exists($varName, $template['vars_split'])) {
                [$exist, $val] = ConditionArr::getValue($vars, $template['vars_split']);
            } else {
                $val = data_get($vars, $varName);
            }
            if ($val !== null) {
                if (is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                }
                $replaceSearch[] = '{' . $varName . '}';
                $replaceVars[] = $val;
            }
        }

        return str_replace($replaceSearch, $replaceVars, $template['template']);
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')->select('uid', 'username', 'email', 'department');
    }

    /**
     * 列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [])
    {
        $builder = $this->with('creator')->select('id', 'name', 'remark', 'created_at', 'created_by');
        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    public function getByIdAndThrow($templateId, $throwable = false)
    {
        $template = $this->where('id', $templateId)->first();
        if ($throwable && empty($template)) {
            throw new AppException("template [{$templateId}] not found");
        }

        return $template;
    }

    /**
     * 详情.
     * @param mixed $templateId
     */
    public function showTemplate($templateId)
    {
        $template = $this->getByIdAndThrow($templateId, true);
        $template->load('creator');

        $template['template'] = $this->fmtAttrTemplate($template['template']);

        // 权限
        $template['permission'] = $this->permission($templateId);

        return $template;
    }

    /**
     * 格式化属性template.
     *
     * @param array $sceneTemplates 场景模板信息
     * @param int $matchType 匹配到的模板类型，可选预定义与自定义
     * @return array
     */
    public function fmtAttrTemplate($sceneTemplates, $matchType = self::TYPE_PREDEFINED)
    {
        $template = [];
        $defaults = $this->defaultTemplates();
        foreach (static::$scenes as $scene => $sceneTitle) {
            foreach (static::$channels as $channel) {
                if (isset($sceneTemplates[$scene], $sceneTemplates[$scene][$channel])) {
                    $template[$scene][$channel] = [
                        'type' => $matchType,
                        'format' => $sceneTemplates[$scene][$channel]['format'] ?? self::FORMAT_TEXT,
                        'template' => $sceneTemplates[$scene][$channel]['template'],
                    ];
                } else {
                    $template[$scene][$channel] = [
                        'type' => self::TYPE_DEFAULT,
                        'format' => $defaults[$scene][$channel]['format'] ?? self::FORMAT_TEXT,
                        'template' => $defaults[$scene][$channel]['template'],
                    ];
                }
            }
        }

        return $template;
    }

    /**
     * 删除.
     * @param mixed $templateId
     * @param mixed $user
     */
    public function deleteTemplate($templateId, $user)
    {
        $template = $this->getByIdAndThrow($templateId, true);

        // 判断是否有权限
        if (! $this->hasPermisson($user, $templateId)) {
            throw new ForbiddenException('您没有权限删除');
        }

        // 查询有无关联
        $taskIds = AlarmTaskConfig::where('alarm_template_id', $templateId)->pluck('task_id')->toArray();
        if (! empty($taskIds)) {
            $tasks = AlarmTask::whereIn('id', $taskIds)->pluck('name')->toArray();
            throw new AppException('该模板被关联到告警任务 ' . implode(', ', $tasks) . '，请解除关联后再删除');
        }

        Db::beginTransaction();
        try {
            // 无关联，直接删除
            $template->delete();
            // 删除权限
            AlarmTemplatePermission::where('template_id', $templateId)->delete();

            Db::commit();
            return $template;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'name');

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }

        return $builder->get();
    }

    /**
     * 默认模板
     */
    public static function defaultTemplates()
    {
        $defaults = config('dog-templates.tasks', []);
        $templates = [];
        foreach (static::$scenes as $scene => $sceneTitle) {
            foreach (static::$channels as $channel) {
                $templates[$scene][$channel] = [
                    'format' => self::FORMAT_TEXT,
                    'template' => $defaults[$scene][$channel]['template'],
                ];
            }
        }
        return $templates;
    }

    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 创建.
     * @param mixed $param
     * @param mixed $user
     */
    public function storeTemplate($param, $user)
    {
        // 重名判断
        if ($this->hasByName($param['name'])) {
            throw new AppException("template [{$param['name']}] exists, please use other name");
        }

        $data = [
            'name' => $param['name'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'remark' => $param['remark'],
            'template' => $this->validAndFormat($param['template'], true),
            'created_by' => $user['uid'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        // 开始入库
        Db::beginTransaction();
        try {
            $template = AlarmTemplate::create($data);

            // 保存权限
            $param['permission'][] = $user['uid'];
            $this->permission->savePermission($template['id'], $param['permission'], false);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        $template->load('creator');

        $template->setVisible(['id', 'name', 'remark', 'created_at', 'created_by', 'creator']);

        return $template;
    }

    /**
     * 更新.
     * @param mixed $templateId
     * @param mixed $param
     * @param mixed $user
     */
    public function updateTemplate($templateId, $param, $user)
    {
        // 判断是否有权限
        if (! $this->hasPermisson($user, $templateId)) {
            throw new ForbiddenException('您没有权限更新');
        }

        // 重名判断
        if ($this->hasByName($param['name'], $templateId)) {
            throw new AppException("template [{$param['name']}] exists, please use other name");
        }

        $template = $this->getByIdAndThrow($templateId, true);

        $template->name = $param['name'];
        // 莫名其妙，通过属性赋值居然不行
        $template['pinyin'] = $this->pinyin->convert($param['name']);
        $template->remark = $param['remark'];
        $template->template = $this->validAndFormat($param['template'], true);
        $template->updated_at = time();

        // 开始入库
        Db::beginTransaction();
        try {
            $template->save();

            // 保存权限
            $this->permission->savePermission($template['id'], $param['permission'], true);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        $template->load('creator');

        $template->setVisible(['id', 'name', 'remark', 'created_at', 'created_by', 'creator']);

        return $template;
    }

    /**
     * 拆分模板变量.
     *
     * @return array
     */
    protected function splitVars(array $vars)
    {
        $splitVars = [];
        foreach ($vars as $var) {
            $splitVars[$var] = ConditionArr::fieldSplit($var);
        }

        return $splitVars;
    }
}
