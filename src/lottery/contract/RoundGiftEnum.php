<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 活动奖品相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface RoundGiftEnum
{
    /**
     * 奖品状态
     * 
     * @var array
     */
    const GIFT_STATUS = [
        // 禁用
        'disable'   => 0,
        // 正常
        'enable'    => 1,
    ];

    /**
     * 奖品状态名称
     * 
     * @var array
     */
    const GIFT_STATUS_TITLE = [
        // 禁用
        self::GIFT_STATUS['disable']   => '禁用',
        // 正常
        self::GIFT_STATUS['enable']    => '正常',
    ];

    /**
     * 中奖奖品等级类型
     * 
     * @var array
     */
    const GIFT_WIN_LEVEL = [
        // 未中奖
        'off'       => 0,
        // 普通奖
        'norm'      => 1,
        // 特别奖
        'special'   => 2
    ];

    /**
     * 中奖奖品等级描述
     * 
     * @var array
     */
    const GIFT_WIN_LEVEL_TITLE = [
        // 未中奖
        self::GIFT_WIN_LEVEL['off'] => '未中奖',
        // 普通奖
        self::GIFT_WIN_LEVEL['norm'] => '普通奖',
        // 特别奖
        self::GIFT_WIN_LEVEL['special'] => '特别奖',
    ];
}
