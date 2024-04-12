<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 活动相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface RoundEnum
{
    /**
     * 活动状态
     * 
     * @var array
     */
    const ROUND_STATUS = [
        // 草稿
        'draft'         => 0,
        // 生效
        'effect'        => 1,
        // 预发布
        'pre_publish'   => 2,
        // 正式发布
        'publish'       => 3,
        // 下线
        'downline'      => 4,
    ];

    /**
     * 活动状态描述
     * 
     * @var array
     */
    const ROUND_STATUS_TITLE = [
        // 草稿
        self::ROUND_STATUS['draft'] => '草稿',
        // 已生效
        self::ROUND_STATUS['effect'] => '已生效',
        // 预发布
        self::ROUND_STATUS['pre_publish'] => '预发布',
        // 正式发布
        self::ROUND_STATUS['publish'] => '正式发布',
        // 已下线
        self::ROUND_STATUS['downline'] => '已下线',
    ];

    /**
     * 灰度用户状态
     * 
     * @var array
     */
    const GREY_USER_STATUS = [
        // 禁用
        'disable'   => 0,
        // 正常
        'enable'    => 1,
    ];

    /**
     * 灰度用户状态名称
     * 
     * @var array
     */
    const GREY_USER_STATUS_TITLE = [
        // 禁用
        self::GREY_USER_STATUS['disable']   => '禁用',
        // 正常
        self::GREY_USER_STATUS['enable']    => '正常',
    ];

    /**
     * 奖池号码状态
     * 
     * @var array
     */
    const PROBABILITY_USE_STATUS = [
        // 可使用
        'enable'    => 0,
        // 已使用
        'disable'   => 1,
    ];

    /**
     * 奖池号码状态名称
     * 
     * @var array
     */
    const PROBABILITY_USE_STATUS_TITLE = [
        // 可使用
        self::PROBABILITY_USE_STATUS['enable']    => '可使用',
        // 已使用
        self::PROBABILITY_USE_STATUS['disable']   => '已使用',
    ];

    /**
     * 奖池号码中奖状态
     * 
     * @var array
     */
    const PROBABILITY_WIN_STATUS = [
        // 未中奖
        'disable'   => 0,
        // 中奖
        'enable'    => 1,
    ];

    /**
     * 奖池号码中奖状态名称
     * 
     * @var array
     */
    const PROBABILITY_WIN_STATUS_TITLE = [
        // 未中奖
        self::PROBABILITY_WIN_STATUS['disable']   => '未中奖',
        // 中奖
        self::PROBABILITY_WIN_STATUS['enable']    => '中奖',
    ];

    /**
     * 奖品领取状态
     * 
     * @var array
     */
    const GIFT_GET_STATUS = [
        // 未领取
        'disable'   => 0,
        // 已领取
        'enable'    => 1,
    ];

    /**
     * 奖品领取状态名称
     * 
     * @var array
     */
    const GIFT_GET_STATUS_TITLE = [
        // 未领取
        self::GIFT_GET_STATUS['disable']   => '未领取',
        // 已领取
        self::GIFT_GET_STATUS['enable']    => '已领取',
    ];

    /**
     * 用户奖品状态
     * 
     * @var array
     */
    const USER_GIFT_STATUS = [
        // 可使用
        'enable'    => 0,
        // 已使用
        'disable'   => 1,
    ];

    /**
     * 用户奖品状态名称
     * 
     * @var array
     */
    const USER_GIFT_STATUS_TITLE = [
        // 可使用
        self::USER_GIFT_STATUS['enable']    => '可使用',
        // 已使用
        self::USER_GIFT_STATUS['disable']   => '已使用',
    ];
}
