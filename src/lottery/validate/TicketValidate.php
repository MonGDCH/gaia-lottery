<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\TicketEnum;

/**
 * 抽奖卡卷验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class TicketValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'       => ['required', 'id'],
        'name'      => ['required', 'str'],
        'code'      => ['required', 'str'],
        'img'       => ['isset', 'str'],
        'remark'    => ['isset', 'str', 'maxLength:250'],
        'sort'      => ['required', 'int', 'min:0', 'max:100'],
        'status'    => ['required', 'status']
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'       => '参数异常',
        'name'      => '请输入名称',
        'code'      => '请输入编码',
        'img'       => '请上传合法的图片URL地址',
        'remark'    => '请输入备注信息',
        'sort'      => '请输入合法的排序权重',
        'status'    => '请选择合法的状态'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增奖品
        'add'   => ['name', 'code', 'img', 'remark', 'sort', 'status'],
        // 修改奖品
        'edit'  => ['idx', 'name', 'code', 'img', 'remark', 'sort', 'status'],
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
