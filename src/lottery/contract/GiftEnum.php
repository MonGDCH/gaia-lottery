<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 奖品相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface GiftEnum
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
     * 奖品类型
     * 
     * @var array
     */
    const GIFT_TYPE = [
        // 实物
        'real'      => 0,
        // 虚拟
        'virtually' => 1
    ];

    /**
     * 奖品类型描述
     * 
     * @var array
     */
    const GIFT_TYPE_TITLE = [
        // 实物奖品
        self::GIFT_TYPE['real'] => '实物奖品',
        // 虚拟奖品
        self::GIFT_TYPE['virtually'] => '虚拟奖品',
    ];
}
