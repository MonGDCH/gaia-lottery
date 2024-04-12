<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 抽奖凭证相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface TicketEnum
{
    /**
     * 抽奖凭证状态
     * 
     * @var array
     */
    const TICKET_STATUS = [
        // 禁用
        'disable'   => 0,
        // 正常
        'enable'    => 1,
    ];

    /**
     * 抽奖凭证状态名称
     * 
     * @var array
     */
    const TICKET_STATUS_TITLE = [
        // 禁用
        self::TICKET_STATUS['disable']   => '禁用',
        // 正常
        self::TICKET_STATUS['enable']    => '正常',
    ];
}
