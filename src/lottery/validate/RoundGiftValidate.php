<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\RoundGiftEnum;

/**
 * 活动奖品验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundGiftValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'       => ['required', 'id'],
        'round_id'  => ['required', 'id'],
        'gift_id'   => ['required', 'id'],
        'level'     => ['required', 'int', 'min:0'],
        'count'     => ['required', 'int', 'min:1'],
        'status'    => ['required', 'status']
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'       => '参数异常',
        'round_id'  => '活动参数异常',
        'gift_id'   => '奖品异参数常',
        'level'     => '类型不合法',
        'count'     => '请输入合法的奖品数量',
        'status'    => '请选择合法的奖品状态'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增奖品
        'add'       => ['level', 'round_id', 'gift_id', 'count', 'status'],
        // 修改奖品
        'edit'      => ['idx', 'level', 'round_id', 'gift_id', 'count', 'status'],
        // 删除奖品
        'remove'    => ['idx', 'round_id', 'gift_id'],
        // 批量设置活动奖品
        'create'    => ['gift_id', 'level', 'count'],
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(RoundGiftEnum::GIFT_STATUS_TITLE[$value]);
    }
}
