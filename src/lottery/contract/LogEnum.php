<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 日志相关枚举属性
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface LogEnum
{
    /**
     * 用户凭证日志类型
     * 
     * @var mixed
     */
    const TICKET_LOG_TYPE = [
        // 用户领取
        'user_redemption'   => 0,
        // 系统增加
        'sys_add'           => 1,
        // 系统扣减
        'sys_reduce'        => 2,
        // 用户使用
        'user_use'          => 3,
    ];

    /**
     * 用户凭证日志类型描述
     * 
     * @var mixed
     */
    const TICKET_LOG_TYPE_TITLE = [
        // 用户领取
        self::TICKET_LOG_TYPE['user_redemption']   => '用户领取',
        // 系统增加
        self::TICKET_LOG_TYPE['sys_add']           => '系统增加',
        // 系统扣减
        self::TICKET_LOG_TYPE['sys_reduce']        => '系统扣减',
        // 用户使用
        self::TICKET_LOG_TYPE['user_use']           => '用户使用',
    ];
}
