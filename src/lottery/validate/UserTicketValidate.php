<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\TicketEnum;

/**
 * 用户抽奖凭证验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserTicketValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'       => ['required', 'id'],
        'uid'       => ['required', 'int', 'min:1'],
        'tid'       => ['required', 'int', 'min:1'],
        'count'     => ['required', 'int', 'min:0'],
        'status'    => ['required', 'status'],
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'       => '参数异常',
        'uid'       => '请选择用户',
        'tid'       => '请选择凭证类型',
        'count'     => '请输入合法的数量',
        'status'    => '请选择合法的状态',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 创建
        'add'   => ['uid', 'tid', 'count', 'status'],
        // 修改
        'edit'  => ['idx', 'count', 'status'],
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(TicketEnum::TICKET_STATUS_TITLE[$value]);
    }
}
