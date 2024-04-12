<?php

declare(strict_types=1);

namespace plugins\lottery\validate;

use mon\util\Validate;
use plugins\lottery\contract\RoundEnum;

/**
 * 活动验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'           => ['required', 'id'],
        'tid'           => ['required', 'id'],
        'title'         => ['required', 'str', 'maxLength:100'],
        'content'       => ['required', 'str'],
        'img'           => ['isset', 'str'],
        'room_num'      => ['required', 'int', 'min:1'],
        'room_quency'   => ['required', 'int', 'min:1'],
        'start_time'    => ['required', 'timestamp'],
        'end_time'      => ['required', 'timestamp'],
        'sort'          => ['required', 'int', 'min:0', 'max:100'],
        'status'        => ['required', 'status']
    ];

    /**
     * 错误描述
     *
     * @var array
     */
    public $message = [
        'idx'           => '参数异常',
        'tid'           => '请选择使用的卡卷',
        'title'         => '请输入合法的活动名称',
        'content'       => '请输入合法的活动描述',
        'img'           => '请上传合法的图片URL地址',
        'room_num'      => '请输入合法的场次数',
        'room_quency'   => '请输入合法的场次可抽奖数',
        'start_time'    => '请选择合法的活动开始时间',
        'end_time'      => '请选择合法的活动结束时间',
        'sort'          => '请输入合法的排序权重',
        'status'        => '请选择合法的活动状态'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 新增
        'add'       => ['tid', 'title', 'content', 'img', 'room_num', 'room_quency', 'sort'],
        // 修改
        'edit'      => ['idx', 'tid', 'title', 'content', 'img', 'room_num', 'room_quency', 'sort'],
        // 发布
        'publish'   => ['idx', 'start_time', 'end_time', 'status'],
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(RoundEnum::ROUND_STATUS_TITLE[$value]);
    }
}
