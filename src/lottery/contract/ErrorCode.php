<?php

declare(strict_types=1);

namespace plugins\lottery\contract;

/**
 * 错误码
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface ErrorCode
{
    /**
     * 用户异常
     */
    const USER_EXCEPTION = 100000;

    /**
     * 用户抽奖凭证不存在
     */
    const USER_NOT_FOUND = 101000;

    /**
     * 用户抽奖次数不足
     */
    const USER_LOTTERY_INADEQUATE = 102000;

    /**
     * 保存次数失败
     */
    const USER_SAVE_LOTTERY_ERROR = 102010;

    /**
     * 保存抽奖记录失败
     */
    const USER_SAVE_LOTTERY_LOG_ERROR = 102020;

    /**
     * 用户非灰度用户
     */
    const USER_NOT_GREY = 102030;

    /**
     * 活动异常
     */
    const LOTTERY_EXCEPTION = 110000;


    // 活动轮次错误

    /**
     * 活动不存在
     */
    const LOTTERY_ROUND_NOT_FOUND = 111000;

    /**
     * 活动已生效，请勿重复操作
     */
    const LOTTERY_ROUND_IS_USE = 111010;

    /**
     * 活动未生效
     */
    const LOTTERY_ROUND_NOT_USE = 111020;

    /**
     * 活动时间未到
     */
    const LOTTERY_ROUND_NOT_EXPIRE = 111030;

    /**
     * 保存活动信息失败
     */
    const LOTTERY_ROUND_SAVE_ERROR = 111040;

    /**
     * 场次奖品数不能超过场次抽奖数
     */
    const LOTTERY_ROUND_AWARD_INVALID = 111050;

    /**
     * 总奖品数不能超过总抽奖次数
     */
    const LOTTERY_ROUND_AWARD_COUNT_INVALID = 111060;


    // 抽奖号码错误

    /**
     * 生成活动抽奖号码失败
     */
    const LOTTERY_CREATE_CODE_ERROR = 112010;

    /**
     * 清除原奖池抽奖号码信息失败
     */
    const LOTTERY_CLEAR_CODE_ERROR = 112011;

    /**
     * 清除原奖池抽奖记录信息失败
     */
    const LOTTERY_CLEAR_LOG_ERROR = 112012;

    /**
     * 生成活动奖品失败
     */
    const LOTTERY_COPY_AWARD_ERROR = 112020;

    /**
     * 清除原奖池奖品信息失败
     */
    const LOTTERY_CLEAR_AWARD_ERROR = 112021;

    /**
     * 生成无效抽奖号码集合
     */
    const LOTTERY_ROOM_CODE_INVALID = 112030;

    /**
     * 抽奖失败
     */
    const LOTTERY_DRAW_ERROR = 113000;

    /**
     * 场次抽奖次数不足
     */
    const LOTTERY_DRAW_INADEQUATE = 113010;

    /**
     * 获取抽奖号码失败
     */
    const LOTTERY_GET_DRAW_ERROR = 113020;

    /**
     * 抽奖号码已使用
     */
    const LOTTERY_DRAW_IS_USE = 113030;

    /**
     * 记录抽奖号码抽奖列表失败
     */
    const LOTTERY_SAVE_DRAW_LIST_ERROR = 113040;

    /**
     * 保存已使用抽奖号码失败
     */
    const LOTTERY_SAVE_DRAW_CODE_ERROR = 113050;

    /**
     * 保存已抽奖次数失败
     */
    const LOTTERY_SAVE_DRAW_COOUNT_ERROR = 113060;

    /**
     * 获取中奖奖品失败
     */
    const LOTTERY_GET_DRAW_GIFT_ERROR = 113070;

    /**
     * 保存中奖奖品失败
     */
    const LOTTERY_SAVE_DRAW_GIFT_ERROR = 113080;

    /**
     * 奖品错误
     */
    const LOTTERY_GIFT_ERROR = 114000;

    /**
     * 业务锁错误
     */
    const LOTTERY_LOCK_ERROR = 115000;
}
