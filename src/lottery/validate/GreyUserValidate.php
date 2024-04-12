<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\RoundEnum;

/**
 * 灰度用户验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GreyUserValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'           => ['required', 'id'],
        'round_id'      => ['required', 'id'],
        'room_num'      => ['required', 'int', 'min:0'],
        'step'          => ['required', 'int', 'min:1'],
        'uid'           => ['required', 'int', 'min:1'],
        'round_ids'     => ['isset', 'str', 'list:id'],
        'start_time'    => ['isset', 'timestamp'],
        'end_time'      => ['isset', 'timestamp'],
        'status'        => ['required', 'status']
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'           => '参数异常',
        'uid'           => '请选择用户',
        'round_ids'     => '请选择合法的活动',
        'start_time'    => '请选择有效时间',
        'end_time'      => '请选择有效时间',
        'status'        => '请选择合法的状态'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 增加
        'add'       => ['uid', 'start_time', 'end_time', 'status'],
        // 修改
        'edit'      => ['idx', 'uid', 'start_time', 'end_time', 'status'],
        // 抽奖
        'lottery'   => ['idx', 'round_id', 'room_num', 'step']
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(RoundEnum::GREY_USER_STATUS_TITLE[$value]);
    }
}
