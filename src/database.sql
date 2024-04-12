CREATE TABLE IF NOT EXISTS `lottery_gift` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型: 0实物, 1虚拟道具',
    `title` varchar(255) NOT NULL COMMENT '奖品名称',
    `content` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品描述',
    `img` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1:有效,2:无效',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '奖品表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_ticket` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT '名称',
    `code` varchar(255) NOT NULL COMMENT '编号',
    `img` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
    `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 30 COMMENT '排序权重',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1:有效,2:无效',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `code`(`code`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '抽奖卡券表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_user_ticket`(
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `tid` int(10) UNSIGNED NOT NULL COMMENT '抽奖卡券ID',
    `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '数量',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0:无效, 1:有效',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user` (`uid`) USING BTREE,
    UNIQUE INDEX `item`(`uid`, `tid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户抽奖卡券表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_user_ticket_log` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `tid` int(10) UNSIGNED NOT NULL COMMENT '抽奖卡券ID',
    `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联记录ID',
    `from` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '来源用户ID，0表示系统',
    `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型: 0-用户兑换 1-系统发放 2-系统扣减',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '描述信息',
    `before_count` int(10) UNSIGNED NOT NULL COMMENT '操作前数量',
    `count` int(10) UNSIGNED NOT NULL COMMENT '操作数量',
    `after_count` int(10) UNSIGNED NOT NULL COMMENT '操作后数量',
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户抽奖卷日志表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_round` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tid` int(10) UNSIGNED NOT NULL COMMENT '使用抽奖卡券ID',
    `title` varchar(100) NOT NULL COMMENT '活动名称',
    `content` text NOT NULL COMMENT '活动描述',
    `img` varchar(255) NOT NULL DEFAULT '' COMMENT '活动封面',
    `room_num` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '抽奖房间数',
    `room_quency` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '房间可抽奖次数',
    `start_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动开始时间',
    `end_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动结束时间',
    `sort` tinyint(3) UNSIGNED NOT NULL DEFAULT 30 COMMENT '排序权重',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0:草稿, 1:已生效(概率表已生成), 2:预发布, 3:正式发布, 4:已下线',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '活动表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_round_gift` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `round_id` int(10) UNSIGNED NOT NULL COMMENT '活动ID',
    `gift_id` int(10) UNSIGNED NOT NULL COMMENT '奖品ID',
    `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0:保留, 1:普通奖, 2:特别奖',
    `count` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1:有效,2:无效',
    `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
    `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `item`(`round_id`, `gift_id`) USING BTREE,
    INDEX `round_id`(`round_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '活动奖品表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_probability` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `round_id` int(10) UNSIGNED NOT NULL COMMENT '活动ID',
    `room` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间号',
    `is_win` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否中奖, 0:未中奖, 1:中奖',
    `is_use` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否使用, 0:未使用, 1:已使用',
    `probability_gift_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖品ID',
    `win_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中奖奖品等级，对应活动奖品等级',
    `lottery_uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '抽奖用户ID',
    `lottery_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '抽奖时间',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `round_id`(`round_id`) USING BTREE,
    INDEX `item`(`round_id`, `room`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '活动号码表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_probability_gift` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `round_id` int(10) UNSIGNED NOT NULL COMMENT '活动ID',
    `gift_id` int(10) UNSIGNED NOT NULL COMMENT '奖品ID',
    `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖品类型: 0积分, 1实物, 2道具',
    `title` varchar(100) NOT NULL COMMENT '奖品名称',
    `content` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品信息',
    `img` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `item`(`round_id`, `gift_id`) USING BTREE,
    INDEX `round_id`(`round_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '活动号码对应奖品信息表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_user_gift`(
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `round_id` int(10) UNSIGNED NOT NULL COMMENT '活动ID',
    `probability_id` bigint(20) UNSIGNED NOT NULL COMMENT '抽奖号码',
    `probability_gift_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖品ID，可能会丢失，所以只能用来做奖品归类的索引',
    `title` varchar(100) NOT NULL COMMENT '奖品名称',
    `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '奖品类型: 0积分, 1实物, 2道具',
    `content` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品信息',
    `img` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
    `get_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0:未领取, 1:已领取',
    `get_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取时间',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0:无效, 1:有效',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user` (`uid`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户中奖奖品表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_log` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `round_id` int(10) UNSIGNED NOT NULL COMMENT '活动ID',
    `probability_id` bigint(20) UNSIGNED NOT NULL COMMENT '抽奖号码ID',
    `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
    `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP',
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user` (`uid`),
    INDEX `round_id`(`round_id`),
    KEY `probability_id` (`probability_id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '抽奖日志表' ROW_FORMAT = Dynamic;

CREATE TABLE IF NOT EXISTS `lottery_grey_user` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `round_ids` varchar(250) NOT NULL DEFAULT '' COMMENT '活动ID列表',
    `start_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效时间',
    `end_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效时间',
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '0:无效, 1:有效',
    `update_time` int(10) UNSIGNED NOT NULL,
    `create_time` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '灰度用户表' ROW_FORMAT = Dynamic;