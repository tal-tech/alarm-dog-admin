CREATE TABLE xes_alarm_alarm_history_all (
	`id` UInt32 COMMENT '自增ID',
	`task_id` UInt32 COMMENT '告警任务ID',
	`uuid` String COMMENT '告警信息唯一ID',
	`batch` UInt32 COMMENT '收敛批次ID',
	`metric` String COMMENT '收敛指标',
	`notice_time` UInt32 COMMENT '告警通知时间',
	`level` UInt8 COMMENT '告警级别：0-通知；1-警告；2-错误；3-紧急',
	`ctn` String COMMENT '告警内容，json格式存储',
	`receiver` String COMMENT '自定义通知人配置',
	`type` UInt8 COMMENT '告警类型：1-正常告警；2-恢复告警；3-忽略告警',
	`created_at` UInt32 COMMENT '创建时间' 
) ENGINE = MergeTree () PARTITION BY toDate ( created_at ) 
ORDER BY
	( id, task_id, uuid, metric ) SETTINGS index_granularity = 8192
