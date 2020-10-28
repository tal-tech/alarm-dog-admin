
SET NAMES utf8mb4;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group`;
CREATE TABLE `xes_alarm_alarm_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警组名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `receiver` text COLLATE utf8mb4_unicode_ci COMMENT '自定义通知人配置冗余存储',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组表';

-- ----------------------------
-- Records of xes_alarm_alarm_group
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_group` VALUES (1, '测试通知组1', 'ceshitongzhizu1', '', '{\"channels\":{\"email\":[1],\"dinggroup\":[{\"webhook\":\"eb1f93923a7803e261880974e5a152a5b55a9465af3cdbae0b2992f362c99298\",\"secret\":\"SEC433f3ff24968552cba71a11eba06a70e5bcfd851ef1dcc17664e43bfbafd35e8\"}]}}', 1, 1603789885, 1603789885);
INSERT INTO `xes_alarm_alarm_group` VALUES (2, '测试通知组2', 'ceshitongzhizu2', '', '{\"channels\":{\"dinggroup\":[{\"webhook\":\"eb1f93923a7803e261880974e5a152a5b55a9465af3cdbae0b2992f362c99298\",\"secret\":\"SEC433f3ff24968552cba71a11eba06a70e5bcfd851ef1dcc17664e43bfbafd35e8\"}]}}', 1, 1603789996, 1603789996);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_dinggroup
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_dinggroup`;
CREATE TABLE `xes_alarm_alarm_group_dinggroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `webhook` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '钉钉机器人的WebHook地址',
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '钉钉机器人安全签名secret',
  `webhook_crc32` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'webhook的crc32，注意无符号处理',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_webhookcrc32` (`webhook_crc32`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组钉钉群关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_dinggroup
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_group_dinggroup` VALUES (1, 1, 'eb1f93923a7803e261880974e5a152a5b55a9465af3cdbae0b2992f362c99298', 'SEC433f3ff24968552cba71a11eba06a70e5bcfd851ef1dcc17664e43bfbafd35e8', 0);
INSERT INTO `xes_alarm_alarm_group_dinggroup` VALUES (2, 2, 'eb1f93923a7803e261880974e5a152a5b55a9465af3cdbae0b2992f362c99298', 'SEC433f3ff24968552cba71a11eba06a70e5bcfd851ef1dcc17664e43bfbafd35e8', 0);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_dinggroupfocus
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_dinggroupfocus`;
CREATE TABLE `xes_alarm_alarm_group_dinggroupfocus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  `keywords` text COLLATE utf8mb4_unicode_ci COMMENT '关注关键词',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组钉钉群关键词关注关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_dinggroupfocus
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_dingworker
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_dingworker`;
CREATE TABLE `xes_alarm_alarm_group_dingworker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组钉钉工作通知关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_dingworker
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_email
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_email`;
CREATE TABLE `xes_alarm_alarm_group_email` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组邮箱关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_email
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_group_email` VALUES (1, 1, 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_permission
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_permission`;
CREATE TABLE `xes_alarm_alarm_group_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警通知组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组用户权限表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_permission
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_group_permission` VALUES (1, 1, 1);
INSERT INTO `xes_alarm_alarm_group_permission` VALUES (2, 2, 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_phone
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_phone`;
CREATE TABLE `xes_alarm_alarm_group_phone` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组电话关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_phone
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_sms
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_sms`;
CREATE TABLE `xes_alarm_alarm_group_sms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组短信关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_sms
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_webhook
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_webhook`;
CREATE TABLE `xes_alarm_alarm_group_webhook` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `url` varchar(511) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'url地址',
  `config` varchar(4095) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置信息',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组webhook关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_webhook
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_wechat
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_wechat`;
CREATE TABLE `xes_alarm_alarm_group_wechat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组微信关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_wechat
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_yachgroup
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_yachgroup`;
CREATE TABLE `xes_alarm_alarm_group_yachgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `webhook` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Yach机器人的WebHook地址',
  `secret` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Yach机器人安全签名secret',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组Yach群关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_yachgroup
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_group_yachworker
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_group_yachworker`;
CREATE TABLE `xes_alarm_alarm_group_yachworker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_groupid` (`group_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知组Yach工作通知关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_group_yachworker
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_history
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_history`;
CREATE TABLE `xes_alarm_alarm_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `uuid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警信息唯一ID',
  `batch` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收敛批次ID，crc32取无符号整数，若无收敛则为0或获取失败则为0',
  `metric` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收敛指标',
  `notice_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警通知时间',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '告警级别：0-通知；1-警告；2-错误；3-紧急',
  `ctn` text COLLATE utf8mb4_unicode_ci COMMENT '告警内容，json格式存储',
  `receiver` text COLLATE utf8mb4_unicode_ci COMMENT '自定义通知人配置',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '告警类型：1-正常告警；2-恢复告警；3-忽略告警',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_uuid` (`uuid`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_batch` (`batch`),
  KEY `idx_metric` (`metric`),
  KEY `idx_createdat` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警历史信息表';

-- ----------------------------
-- Records of xes_alarm_alarm_history
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_tag
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_tag`;
CREATE TABLE `xes_alarm_alarm_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签管理表';

-- ----------------------------
-- Records of xes_alarm_alarm_tag
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_tag` VALUES (1, '业务1', 'yewu1', '', 1, 1603789470, 1603789470);
INSERT INTO `xes_alarm_alarm_tag` VALUES (2, '业务2', 'yewu2', '', 1, 1603789474, 1603789474);
INSERT INTO `xes_alarm_alarm_tag` VALUES (3, '业务3', 'yewu3', '', 1, 1603789481, 1603789481);
INSERT INTO `xes_alarm_alarm_tag` VALUES (4, '业务4', 'yewu4', '', 1, 1603789486, 1603789486);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task`;
CREATE TABLE `xes_alarm_alarm_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警任务名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警上报接口用token',
  `secret` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他接口用secret',
  `department_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '部门ID',
  `flag_save_db` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否入库存储：1-是；0-否',
  `enable_workflow` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启工作流：1-是；0-否',
  `enable_filter` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启告警过滤：1-是；0-否',
  `enable_compress` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启告警收敛压缩：1-是；0-否',
  `enable_upgrade` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启告警升级：1-是；0-否',
  `enable_recovery` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启告警自动恢复：1-是；0-否',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '告警任务状态：0-已停止；1-运行中；2-已暂停',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `props` text COLLATE utf8mb4_unicode_ci COMMENT '任务限流等其它配置json',
  PRIMARY KEY (`id`),
  KEY `idx_departmentid` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知模板表';

-- ----------------------------
-- Records of xes_alarm_alarm_task
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_task` VALUES (1, '基本功能', 'jibengongneng', '6ce8cf30ef2fb3168dc07bf2be4b9aa2c0589dec', '4184c14f3f23f26a59cf04fcd0bf65cfd36add0a', 1, 1, 0, 0, 0, 0, 0, 1, 1, 1603789947, 1603789947, NULL);
INSERT INTO `xes_alarm_alarm_task` VALUES (2, '告警收敛', 'gaojingshoulian', '8e1dcf55c90415d8ea81967aa578aa3f19e9f7d8', 'a3c6626fc49f2787b87acd092b0fefce43f61ee0', 1, 1, 0, 0, 1, 0, 0, 1, 1, 1603790055, 1603790055, NULL);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task_alarm_group
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task_alarm_group`;
CREATE TABLE `xes_alarm_alarm_task_alarm_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警组ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '关联类型：1-告警通知人；2-告警升级；3-告警工作流',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_groupid` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警任务与告警组关联表';

-- ----------------------------
-- Records of xes_alarm_alarm_task_alarm_group
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_task_alarm_group` VALUES (1, 2, 2, 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task_config
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task_config`;
CREATE TABLE `xes_alarm_alarm_task_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `workflow` text COLLATE utf8mb4_unicode_ci COMMENT '工作流相关配置，以json存储',
  `compress` text COLLATE utf8mb4_unicode_ci COMMENT '告警压缩相关配置，以json存储',
  `filter` text COLLATE utf8mb4_unicode_ci COMMENT '告警过滤相关配置，以json存储',
  `recovery` text COLLATE utf8mb4_unicode_ci COMMENT '告警自动恢复相关配置，以json存储',
  `upgrade` text COLLATE utf8mb4_unicode_ci COMMENT '告警升级相关配置，以json存储',
  `receiver` text COLLATE utf8mb4_unicode_ci COMMENT '告警接收人相关配置，以json存储',
  `alarm_template_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '告警模板ID，未选择模板时为0',
  `alarm_template` text COLLATE utf8mb4_unicode_ci COMMENT '通知模板相关配置，以json存储',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_taskid` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警任务配置表';

-- ----------------------------
-- Records of xes_alarm_alarm_task_config
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_task_config` VALUES (1, 1, '{}', '{}', '{}', '{}', '{}', '{\"channels\":{\"dinggroup\":[{\"webhook\":\"eb1f93923a7803e261880974e5a152a5b55a9465af3cdbae0b2992f362c99298\",\"secret\":\"SEC433f3ff24968552cba71a11eba06a70e5bcfd851ef1dcc17664e43bfbafd35e8\"}]},\"alarmgroup\":[],\"dispatch\":[],\"mode\":1}', 0, '{}');
INSERT INTO `xes_alarm_alarm_task_config` VALUES (2, 2, '{}', '{\"conditions\":[{\"rule\":[{\"field\":\"ctn.fixed\",\"field_split\":[\"ctn\",\"fixed\"],\"operator\":\"eq-self\"}]}],\"method\":1,\"strategy\":1,\"strategy_cycle\":1,\"not_match\":1}', '{}', '{}', '{}', '{\"channels\":[],\"alarmgroup\":[2],\"dispatch\":[],\"mode\":1}', 0, '{}');
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task_permission
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task_permission`;
CREATE TABLE `xes_alarm_alarm_task_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '权限类型：1-读写；2-只读',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警任务用户权限表';

-- ----------------------------
-- Records of xes_alarm_alarm_task_permission
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_task_permission` VALUES (1, 1, 1, 1);
INSERT INTO `xes_alarm_alarm_task_permission` VALUES (2, 2, 1, 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task_qps
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task_qps`;
CREATE TABLE `xes_alarm_alarm_task_qps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `req_avg_qps` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '接口调用QPS',
  `req_max_qps` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接口调用QPS最大值',
  `prod_avg_qps` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '生产QPS',
  `prod_max_qps` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生产QPS最大值',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_createdat` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警任务QPS表';

-- ----------------------------
-- Records of xes_alarm_alarm_task_qps
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_task_tag
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_task_tag`;
CREATE TABLE `xes_alarm_alarm_task_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  PRIMARY KEY (`id`),
  KEY `idx_tagid` (`tag_id`),
  KEY `idx_taskid` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签关联告警任务表';

-- ----------------------------
-- Records of xes_alarm_alarm_task_tag
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_task_tag` VALUES (1, 1, 2);
INSERT INTO `xes_alarm_alarm_task_tag` VALUES (2, 2, 2);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_template
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_template`;
CREATE TABLE `xes_alarm_alarm_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警模板名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `template` text COLLATE utf8mb4_unicode_ci COMMENT '告警通知模板',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警通知模板表';

-- ----------------------------
-- Records of xes_alarm_alarm_template
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_template` VALUES (1, 'grafana告警模板', 'grafanagaojingmuban', '请勿修改，如需变更模板，请另存为', '{\"compressed\":{\"sms\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"email\":{\"format\":3,\"template\":\"<h3>{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66<\\/h3>\\n\\n<div><pre>{history.ctn.text}<\\/pre><\\/div>\\n\\n<p>\\u8be6\\u60c5\\u8bf7\\u67e5\\u770b <a href=\\\"{history.ctn.grafanaUrl}\\\" target=\\\"_blank\\\">{history.ctn.grafanaUrl}<\\/a><\\/p>\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"phone\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\uff0c\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"dingworker\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"dinggroup\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachworker\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachgroup\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}}},\"not_compress\":{\"sms\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"email\":{\"format\":3,\"template\":\"<h3>{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66<\\/h3>\\n\\n<div><pre>{history.ctn.text}<\\/pre><\\/div>\\n\\n<p>\\u8be6\\u60c5\\u8bf7\\u67e5\\u770b <a href=\\\"{history.ctn.grafanaUrl}\\\" target=\\\"_blank\\\">{history.ctn.grafanaUrl}<\\/a><\\/p>\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"phone\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\uff0c\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"dingworker\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"dinggroup\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachworker\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachgroup\":{\"format\":2,\"template\":\"### {task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}}},\"recovery\":{\"sms\":{\"format\":1,\"template\":\"{task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\n\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"email\":{\"format\":3,\"template\":\"<h3>{task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d<\\/h3>\\n\\n<div><pre>{history.ctn.text}<\\/pre><\\/div>\\n\\n<p>\\u8be6\\u60c5\\u8bf7\\u67e5\\u770b <a href=\\\"{history.ctn.grafanaUrl}\\\" target=\\\"_blank\\\">{history.ctn.grafanaUrl}<\\/a><\\/p>\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"phone\":{\"format\":1,\"template\":\"{task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\uff0c\\u8be6\\u60c5\\uff1a{history.ctn.text}\",\"vars\":[\"task.name\",\"history.ctn.text\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"]}},\"dingworker\":{\"format\":2,\"template\":\"### {task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"dinggroup\":{\"format\":2,\"template\":\"### {task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachworker\":{\"format\":2,\"template\":\"### {task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}},\"yachgroup\":{\"format\":2,\"template\":\"### {task.name}\\u544a\\u8b66\\u5df2\\u6062\\u590d\\n\\n{history.ctn.text}\\n\\n> \\u8be6\\u60c5\\u8bf7\\u67e5\\u770b [{history.ctn.grafanaUrl}]({history.ctn.grafanaUrl})\\n\",\"vars\":[\"task.name\",\"history.ctn.text\",\"history.ctn.grafanaUrl\",\"history.ctn.grafanaUrl\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.text\":[\"history\",\"ctn\",\"text\"],\"history.ctn.grafanaUrl\":[\"history\",\"ctn\",\"grafanaUrl\"]}}}}', 1, 1603789665, 1603789665);
INSERT INTO `xes_alarm_alarm_template` VALUES (2, 'RawBody告警', 'RawBodygaojing', '请勿修改，如需变更模板，请另存为', '{\"compressed\":{\"sms\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"email\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"phone\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\uff0c{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"dingworker\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"dinggroup\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"yachworker\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"yachgroup\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u4e00\\u4e2a[{task.compress_method}-{task.compress_type}\\u6536\\u655b]\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"task.compress_method\",\"task.compress_type\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"task.compress_method\":[\"task\",\"compress_method\"],\"task.compress_type\":[\"task\",\"compress_type\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}}},\"not_compress\":{\"sms\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\uff0c{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"email\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"phone\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\uff0c{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"dingworker\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"dinggroup\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"yachworker\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}},\"yachgroup\":{\"format\":1,\"template\":\"{task.name}\\u53d1\\u751f\\u4e86\\u544a\\u8b66\\n\\n{history.ctn.body}\",\"vars\":[\"task.name\",\"history.ctn.body\"],\"vars_split\":{\"task.name\":[\"task\",\"name\"],\"history.ctn.body\":[\"history\",\"ctn\",\"body\"]}}}}', 1, 1603789769, 1603789769);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_template_permission
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_template_permission`;
CREATE TABLE `xes_alarm_alarm_template_permission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警模板ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`),
  KEY `idx_templateid` (`template_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警模板用户权限表';

-- ----------------------------
-- Records of xes_alarm_alarm_template_permission
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_alarm_template_permission` VALUES (1, 1, 1);
INSERT INTO `xes_alarm_alarm_template_permission` VALUES (2, 2, 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_alarm_upgrade_metric
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_alarm_upgrade_metric`;
CREATE TABLE `xes_alarm_alarm_upgrade_metric` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `metric` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '告警收敛指标值，未收敛为空',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_taskid_metric` (`task_id`,`metric`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_metric` (`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='告警升级关联的metric信息表';

-- ----------------------------
-- Records of xes_alarm_alarm_upgrade_metric
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_business_unit
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_business_unit`;
CREATE TABLE `xes_alarm_business_unit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事业部名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `updated_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='事业部表';

-- ----------------------------
-- Records of xes_alarm_business_unit
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_business_unit` VALUES (1, '默认事业部', 'morenshiyebu', '这里是备注', 1, 1, 1603789386, 1603789386);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_config
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_config`;
CREATE TABLE `xes_alarm_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置KEY',
  `remark` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注KEY',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ----------------------------
-- Records of xes_alarm_config
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_delay_queue_alarm_task_pause
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_delay_queue_alarm_task_pause`;
CREATE TABLE `xes_alarm_delay_queue_alarm_task_pause` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `interval` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '延迟时间',
  `trigger_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '延迟队列触发时间',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_taskid` (`task_id`),
  KEY `idx_triggertime` (`trigger_time`),
  KEY `idx_updatedat` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='延迟队列告警任务停止表';

-- ----------------------------
-- Records of xes_alarm_delay_queue_alarm_task_pause
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_delay_queue_delay_compress
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_delay_queue_delay_compress`;
CREATE TABLE `xes_alarm_delay_queue_delay_compress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `metric` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收敛指标',
  `batch` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '批次ID，crc32取无符号整数',
  `history_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警历史信息ID',
  `trigger_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '延迟队列触发时间',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_triggertime` (`trigger_time`),
  KEY `idx_updatedat` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='延迟队列延迟收敛表';

-- ----------------------------
-- Records of xes_alarm_delay_queue_delay_compress
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_delay_queue_recovery
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_delay_queue_recovery`;
CREATE TABLE `xes_alarm_delay_queue_recovery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `metric` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收敛指标',
  `history_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警历史信息ID',
  `trigger_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '延迟队列触发时间',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_triggertime` (`trigger_time`),
  KEY `idx_updatedat` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='延迟队列自动恢复表';

-- ----------------------------
-- Records of xes_alarm_delay_queue_recovery
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_delay_queue_workflow
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_delay_queue_workflow`;
CREATE TABLE `xes_alarm_delay_queue_workflow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `workflow_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '工作流ID',
  `history_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警记录ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '工作流状态',
  `interval` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提醒时间间隔',
  `trigger_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '延迟队列触发时间',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_workflowid` (`workflow_id`),
  KEY `idx_triggertime` (`trigger_time`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_historyid` (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工作流延迟队列表';

-- ----------------------------
-- Records of xes_alarm_delay_queue_workflow
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_department
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_department`;
CREATE TABLE `xes_alarm_department` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `bu_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '事业部ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `updated_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新人',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_buid` (`bu_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';

-- ----------------------------
-- Records of xes_alarm_department
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_department` VALUES (1, 1, '测试部门1', 'ceshibumen1', '部门备注', 1, 1, 1603789405, 1603789405);
INSERT INTO `xes_alarm_department` VALUES (2, 1, '测试部门2', 'ceshibumen2', '部门备注', 1, 1, 1603789414, 1603789414);
INSERT INTO `xes_alarm_department` VALUES (3, 1, '测试部门3', 'ceshibumen3', '部门备注', 1, 1, 1603789423, 1603789423);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_migrations
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_migrations`;
CREATE TABLE `xes_alarm_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of xes_alarm_migrations
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_migrations` VALUES (1, '2020_03_02_170847_create_user_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (2, '2020_03_02_175056_create_alarm_group_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (3, '2020_03_02_175452_create_alarm_group_dingworker_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (4, '2020_03_02_175703_create_alarm_group_email_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (5, '2020_03_02_175917_create_alarm_group_sms_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (6, '2020_03_02_175945_create_alarm_group_phone_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (7, '2020_03_02_180022_create_alarm_group_wechat_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (8, '2020_03_02_180103_create_alarm_group_dinggroup_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (9, '2020_03_02_180500_create_user_audit_phone_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (10, '2020_03_02_181051_create_alarm_template_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (11, '2020_03_02_181409_create_alarm_task_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (12, '2020_03_02_181812_create_alarm_task_permission_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (13, '2020_03_02_185337_create_alarm_task_config_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (14, '2020_03_02_201342_create_alarm_history_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (15, '2020_03_02_224315_create_delay_queue_recovery_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (16, '2020_03_02_231618_create_delay_queue_delay_compress_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (17, '2020_03_02_232102_create_workflow_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (18, '2020_03_02_233424_create_workflow_pipeline_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (19, '2020_03_04_141202_create_alarm_group_dinggroupfocus_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (20, '2020_03_04_141426_create_alarm_task_alarm_group_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (21, '2020_03_10_140049_add_column_workflow_pipeline_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (22, '2020_03_12_154227_create_alarm_upgrade_metric_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (23, '2020_03_13_175621_add_column_alarm_group_dinggroup_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (24, '2020_03_16_155003_create_delay_queue_alarm_task_pause_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (25, '2020_03_16_195503_add_column_alarm_history_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (26, '2020_03_19_164748_create_openapi_app_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (27, '2020_03_21_205600_create_delay_queue_workflow_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (28, '2020_03_24_182505_add_column_delay_queue_workflow_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (29, '2020_03_29_180015_add_column_alarm_group_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (30, '2020_04_05_222941_create_alarm_group_yachgroup_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (31, '2020_04_05_222948_create_alarm_group_yachworker_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (32, '2020_04_16_210945_create_alarm_group_webhook_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (33, '2020_04_30_221117_create_monitor_datasource_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (34, '2020_04_30_222535_create_monitor_universal_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (35, '2020_04_30_223903_create_monitor_cycle_compare_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (36, '2020_04_30_224907_create_monitor_uprush_downrush_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (37, '2020_04_30_230449_create_monitor_protocol_detect_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (38, '2020_05_01_150252_add_column_alarm_task_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (39, '2020_05_01_152604_add_column_alarm_group_pinyin_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (40, '2020_05_01_152611_add_column_alarm_template_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (41, '2020_05_04_092731_add_column_monitor_cycle_compare_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (42, '2020_05_04_092751_add_column_monitor_protocol_detect_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (43, '2020_05_04_092808_add_column_monitor_universal_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (44, '2020_05_04_092822_add_column_monitor_uprush_downrush_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (45, '2020_05_21_101802_add_column_monitor_cycle_compare_data_init_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (46, '2020_05_21_101824_create_monitor_record_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (47, '2020_05_21_181816_add_column_monitor_cycle_compare_is_data_init_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (48, '2020_07_21_160214_create_alarm_tag_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (49, '2020_07_21_160328_create_alarm_task_tag_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (50, '2020_07_27_101731_create_alarm_group_permission_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (51, '2020_07_27_121458_create_alarm_template_permission_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (52, '2020_08_01_173925_add_column_props_alarm_task_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (53, '2020_08_25_203407_create_config_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (54, '2020_10_19_160057_create_alarm_task_qps_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (55, '2020_10_27_165511_create_department_table', 1);
INSERT INTO `xes_alarm_migrations` VALUES (56, '2020_10_27_165519_create_business_unit_table', 1);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_cycle_compare
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_cycle_compare`;
CREATE TABLE `xes_alarm_monitor_cycle_compare` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联告警任务ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '监控任务名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后面开放接口鉴权用',
  `datasource_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '数据源ID',
  `agg_cycle` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '聚合周期，单位秒，可枚举',
  `compare_cycle` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '参考对比周期，单位秒，可枚举',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '监控配置',
  `data_init` text COLLATE utf8mb4_unicode_ci COMMENT '数据初始化配置',
  `is_data_init` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已初始化数据',
  `alarm_condition` text COLLATE utf8mb4_unicode_ci COMMENT '告警条件',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '监控任务状态，见任务配置',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务启动时间',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_datasourceid` (`datasource_id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='同比环比监控任务表';

-- ----------------------------
-- Records of xes_alarm_monitor_cycle_compare
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_datasource
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_datasource`;
CREATE TABLE `xes_alarm_monitor_datasource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '数据源类型，枚举见代码配置',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '连接配置',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT '字段',
  `timestamp_field` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '时间戳字段',
  `timestamp_unit` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '时间戳类型：1-秒；2-毫秒；3-纳秒',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='监控数据源配置表';

-- ----------------------------
-- Records of xes_alarm_monitor_datasource
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_protocol_detect
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_protocol_detect`;
CREATE TABLE `xes_alarm_monitor_protocol_detect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联告警任务ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '监控任务名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后面开放接口鉴权用',
  `protocol` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '协议，枚举值参考配置文件',
  `monitor_frequency` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '监控频率，单位秒，可枚举',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '监控配置',
  `alarm_condition` text COLLATE utf8mb4_unicode_ci COMMENT '告警条件',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '监控任务状态，见任务配置',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务启动时间',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='心跳探活监控任务表';

-- ----------------------------
-- Records of xes_alarm_monitor_protocol_detect
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_record_1
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_record_1`;
CREATE TABLE `xes_alarm_monitor_record_1` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `monitor_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '监控类型：1-通用；2-同环比；3-突增突降',
  `taskid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '监控任务名称',
  `alarm_rule_id` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '命中告警的规则ID，未命中为空，多个以,分隔',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT '字段值',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`taskid`),
  KEY `idx_createdat` (`created_at`),
  KEY `idx_alarmruleid` (`alarm_rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='监控记录表';

-- ----------------------------
-- Records of xes_alarm_monitor_record_1
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_universal
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_universal`;
CREATE TABLE `xes_alarm_monitor_universal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联告警任务ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '监控任务名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后面开放接口鉴权用',
  `datasource_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '数据源ID',
  `agg_cycle` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '聚合周期，单位秒，可枚举',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '监控配置',
  `alarm_condition` text COLLATE utf8mb4_unicode_ci COMMENT '告警条件',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '监控任务状态，见任务配置',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务启动时间',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_datasourceid` (`datasource_id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用监控任务表';

-- ----------------------------
-- Records of xes_alarm_monitor_universal
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_monitor_uprush_downrush
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_monitor_uprush_downrush`;
CREATE TABLE `xes_alarm_monitor_uprush_downrush` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联告警任务ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '监控任务名称',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后面开放接口鉴权用',
  `datasource_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '数据源ID',
  `agg_cycle` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '聚合周期，单位秒，可枚举',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '监控配置',
  `alarm_condition` text COLLATE utf8mb4_unicode_ci COMMENT '告警条件',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '监控任务状态，见任务配置',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务启动时间',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_datasourceid` (`datasource_id`),
  KEY `idx_updatedat` (`updated_at`),
  KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='突增突降监控任务表';

-- ----------------------------
-- Records of xes_alarm_monitor_uprush_downrush
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_openapi_app
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_openapi_app`;
CREATE TABLE `xes_alarm_openapi_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '鉴权token',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '应用名称',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_appid` (`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OPENAPI应用表';

-- ----------------------------
-- Records of xes_alarm_openapi_app
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_user
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_user`;
CREATE TABLE `xes_alarm_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `account` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '帐号',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户姓名',
  `pinyin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '拼音',
  `user` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱前缀',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `department` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '部门',
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信ID，告警使用',
  `role` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '角色：9-超管；0-普通用户',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_uid` (`uid`),
  UNIQUE KEY `uniq_account` (`account`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ----------------------------
-- Records of xes_alarm_user
-- ----------------------------
BEGIN;
INSERT INTO `xes_alarm_user` VALUES (1, 1, 'admin', '超级管理员', 'chaojiguanliyuan', 'alarm-dog', 'alarm-dog@foo.bar', '', '', '$2y$10$pirLwGp.UMro27rB/ooE1eUl4geho8GXPtXt4M.RGrFTo1gsDHyKS', 9, 1603789320, 1603789320);
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_user_audit_phone
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_user_audit_phone`;
CREATE TABLE `xes_alarm_user_audit_phone` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `old_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '老手机号',
  `new_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '新手机号',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户手机号修改审计表';

-- ----------------------------
-- Records of xes_alarm_user_audit_phone
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_workflow
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_workflow`;
CREATE TABLE `xes_alarm_workflow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `metric` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收敛指标',
  `history_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警历史信息ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0-待处理；1-处理中；2-处理完成；9-关闭',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_createdat` (`created_at`),
  KEY `idx_updatedat` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工作流表';

-- ----------------------------
-- Records of xes_alarm_workflow
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for xes_alarm_workflow_pipeline
-- ----------------------------
DROP TABLE IF EXISTS `xes_alarm_workflow_pipeline`;
CREATE TABLE `xes_alarm_workflow_pipeline` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '告警任务ID',
  `workflow_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '工作流ID',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0-待处理；1-处理中；2-处理完成；9-关闭',
  `remark` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注留言',
  `props` text COLLATE utf8mb4_unicode_ci COMMENT '扩展属性信息，json格式存储',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人，0为系统',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_taskid` (`task_id`),
  KEY `idx_workflowid` (`workflow_id`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_createdat` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工作流pipeline表';

-- ----------------------------
-- Records of xes_alarm_workflow_pipeline
-- ----------------------------
BEGIN;
COMMIT;
