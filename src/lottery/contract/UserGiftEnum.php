<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 用户奖品相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface UserGiftEnum
{
    /**
     * 用户奖品状态
     * 
     * @var array
     */
    const GIFT_STATUS = [
        // 正常
        'disable'   => 0,
        // 禁用
        'enable'    => 1,
    ];

    /**
     * 用户奖品状态名称
     * 
     * @var array
     */
    const GIFT_STATUS_TITLE = [
        // 禁用
        self::GIFT_STATUS['disable']    => '禁用',
        // 正常
        self::GIFT_STATUS['enable']     => '正常',
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
}
