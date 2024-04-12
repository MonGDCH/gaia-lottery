<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\GiftEnum;

/**
 * 奖品验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GiftValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'       => ['required', 'id'],
        'gift_id'   => ['required', 'id'],
        'type'      => ['required', 'int', 'min:0'],
        'count'     => ['required', 'int', 'min:1'],
        'title'     => ['required', 'str', 'maxLength:100'],
        'content'   => ['required', 'str', 'maxLength:250'],
        'img'       => ['required', 'str'],
        'status'    => ['required', 'status']
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'       => '参数异常',
        'gift_id'   => '奖品异常',
        'type'      => '类型不合法',
        'count'     => '请输入合法的奖品数量',
        'title'     => '请输入合法的奖品名称',
        'content'   => '请输入合法的奖品描述',
        'img'       => [
            'required'  => '请上传合法的图片URL地址',
            'str'       => '图片URL地址格式错误',
        ],
        'status'    => '请选择合法的奖品状态'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增奖品
        'add'       => ['type', 'title', 'content', 'img'],
        // 修改奖品
        'edit'      => ['idx', 'type', 'title', 'content', 'img', 'status'],
        // 创建活动
        'create'    => ['gift_id', 'type', 'count'],
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(GiftEnum::GIFT_STATUS_TITLE[$value]);
    }
}
