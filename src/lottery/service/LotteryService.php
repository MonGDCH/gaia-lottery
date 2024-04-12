<?php

declare(strict_types=1);

namespace plugins\lottery\service;

use Throwable;
use mon\log\Logger;
use think\facade\Db;
use mon\util\Common;
use mon\util\Instance;
use plugins\lottery\dao\LogDao;
use plugins\lottery\dao\RoundDao;
use plugins\lottery\dao\GreyUserDao;
use plugins\lottery\dao\UserGiftDao;
use plugins\lottery\dao\RoundGiftDao;
use plugins\lottery\contract\LogEnum;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\dao\UserTicketDao;
use plugins\lottery\contract\ErrorCode;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\dao\ProbabilityDao;
use plugins\lottery\contract\TicketEnum;
use plugins\lottery\dao\UserTicketLogDao;
use plugins\admin\service\CounterService;
use plugins\lottery\dao\ProbabilityGiftDao;
use plugins\lottery\contract\RoundGiftEnum;
use plugins\lottery\exception\LotteryException;

/**
 * 抽奖服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LotteryService
{
    use Instance;

    /**
     * 抽奖应用锁名称
     * 
     * @var string
     */
    const LOCK_LOTTERY_APP = 'Lottery_Act';

    /**
     * 房间抽奖业务锁名称
     * 
     * @var string
     */
    const LOCK_LOTTERY_KEY = 'LOTY:DRAW';

    /**
     * 抽奖业务锁锁定时间
     * 
     * @var integer
     */
    const LOCK_LOTTERY_TIME = 5;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 是否灰度用户
     *
     * @param integer $uid  用户ID
     * @return boolean
     */
    public function isGreyUser(int $uid): bool
    {
        $userInfo = GreyUserDao::instance()->where('uid', $uid)->get();
        if (!$userInfo) {
            $this->error = '灰度用户不存在';
            return false;
        }
        if ($userInfo['status'] != RoundEnum::GREY_USER_STATUS['enable']) {
            $this->error = '灰度用户已无效';
            return false;
        }
        if ($userInfo['end_time'] > 0 || $userInfo['start_time'] > 0) {
            $now = time();
            if ($userInfo['start_time'] > $now || $userInfo['end_time'] < $now) {
                $this->error = '灰度用户已过期';
                return false;
            }
        }

        return true;
    }

    /**
     * 抽奖
     *
     * @param integer $round_id 活动ID
     * @param integer $room_num 房间号
     * @param integer $uid      用户ID
     * @param integer $step     抽奖次数
     * @param string  $ip       请求IP
     * @throws LotteryException
     * @return array 中奖奖品列表
     */
    public function lottery(int $round_id, int $room_num, int $uid, int $step = 1, string $ip = ''): array
    {
        // 获取活动信息
        $roundInfo = RoundDao::instance()->getInfo($round_id);
        if (!$roundInfo) {
            throw new LotteryException('活动不存在', ErrorCode::LOTTERY_ROUND_NOT_FOUND);
        }
        // 活动是否发布状态
        if (!in_array($roundInfo['status'], [RoundEnum::ROUND_STATUS['pre_publish'], RoundEnum::ROUND_STATUS['publish']])) {
            throw new LotteryException('活动不存在!', ErrorCode::LOTTERY_ROUND_NOT_USE);
        }
        // 是否在活动时间内
        $now = time();
        if ($roundInfo['start_time'] > $now || $roundInfo['end_time'] < $now) {
            throw new LotteryException('未在活动时间', ErrorCode::LOTTERY_ROUND_NOT_EXPIRE);
        }
        // 是否预发布，预发布判断用户是否灰度用户
        if ($roundInfo['status'] == RoundEnum::ROUND_STATUS['pre_publish']) {
            $isGreyUser = $this->isGreyUser($uid);
            if (!$isGreyUser) {
                throw new LotteryException($this->getError(), ErrorCode::USER_NOT_GREY);
            }
        }
        // 获取用户凭证信息
        $userTicket = UserTicketDao::instance()->where('uid', $uid)->where('tid', $roundInfo['tid'])->where('status', TicketEnum::TICKET_STATUS['enable'])->get();
        if (!$userTicket) {
            throw new LotteryException('用户抽奖凭证不存在', ErrorCode::USER_NOT_FOUND);
        }
        if ($userTicket['count'] < $step) {
            throw new LotteryException('用户可抽奖次数不足', ErrorCode::USER_LOTTERY_INADEQUATE);
        }
        // 可抽奖次数
        $leftCount = ProbabilityDao::instance()->where('round_id', $round_id)->where('room', $room_num)->where('is_use', RoundEnum::PROBABILITY_USE_STATUS['enable'])->count('id');
        if ($step > $leftCount) {
            throw new LotteryException('房间剩余抽奖次数不足', ErrorCode::LOTTERY_DRAW_INADEQUATE);
        }
        // 获取抽奖号码
        $darwList = ProbabilityDao::instance()->where('round_id', $round_id)->where('room', $room_num)->where('is_use', RoundEnum::PROBABILITY_USE_STATUS['enable'])->order('id', 'ASC')->limit($step)->select();
        if (!$darwList || count($darwList) != $step) {
            throw new LotteryException('获取抽奖号码失败', ErrorCode::LOTTERY_GET_DRAW_ERROR);
        }

        // 抽奖号码列表
        $darw_ids = [];
        // 抽奖记录列表
        $darw_log = [];
        // 抽中奖品ID列表
        $darw_win_gift = [];
        // 抽奖奖品奖项映射
        $darw_win_gift_map = [];
        // 中奖奖品订单列表
        $darw_win_order = [];
        // 解析抽奖信息
        foreach ($darwList as $item) {
            // 记录抽奖号码列表
            $darw_ids[] = $item['id'];
            // 记录抽奖记录
            $darw_log[] = [
                'ip' => $ip,
                'uid' => $uid,
                'round_id' => $round_id,
                'probability_id' => $item['id'],
                'create_time' => $now
            ];
            // 记录中奖信息
            if ($item['is_win'] == RoundEnum::PROBABILITY_WIN_STATUS['enable'] && $item['probability_gift_id'] > 0) {
                // 中奖奖品ID
                $darw_win_gift[] = $item['probability_gift_id'];
                // 中奖奖品类型
                $darw_win_gift_map[$item['probability_gift_id']] = $item['win_level'];
                // 中奖奖品列表
                $darw_win_order[] = [
                    'uid' => $uid,
                    'round_id' => $round_id,
                    'probability_id' => $item['id'],
                    'probability_gift_id' => $item['probability_gift_id'],
                ];
            }
        }

        Db::startTrans();
        try {
            // 修改用户抽奖次数
            $reduceUserTicket = TicketService::instance()->reduce($uid, $roundInfo['tid'], $step);
            if (!$reduceUserTicket) {
                throw new LotteryException('扣减用户抽奖卷失败', ErrorCode::USER_SAVE_LOTTERY_ERROR);
            }
            // 记录抽奖卷日志
            $recordTicketLog = UserTicketLogDao::instance()->record([
                'uid'           => $uid,
                'tid'           => $roundInfo['tid'],
                'sid'           => $round_id,
                'from'          => 0,
                'type'          => LogEnum::TICKET_LOG_TYPE['user_use'],
                'remark'        => '抽奖消耗',
                'before_count'  => $userTicket['count'],
                'count'         => $step,
                'after_count'   => $userTicket['count'] - $step,
            ]);
            if (!$recordTicketLog) {
                Logger::instance()->channel()->error('Lottery darw save user ticket faild: ' . '记录用户奖卷日志失败,' . UserTicketLogDao::instance()->getError());
                throw new LotteryException('保存用户抽奖卷失败', ErrorCode::USER_SAVE_LOTTERY_ERROR);
            }

            // 标记抽奖号码已使用
            $saveCode = ProbabilityDao::instance()->where('id', 'IN', $darw_ids)->save(['is_use' => RoundEnum::PROBABILITY_USE_STATUS['disable'], 'lottery_uid' => $uid, 'lottery_time' => $now]);
            if (!$saveCode) {
                throw new LotteryException('保存已使用抽奖号码失败', ErrorCode::LOTTERY_SAVE_DRAW_CODE_ERROR);
            }
            // 保存用户抽奖记录
            $saveLog = LogDao::instance()->saveAll($darw_log);
            if (!$saveLog) {
                throw new LotteryException('保存用户抽奖记录失败', ErrorCode::USER_SAVE_LOTTERY_LOG_ERROR);
            }
            // 记录用户抽奖次数
            $saveCounter = CounterService::instance()->add(self::LOCK_LOTTERY_APP, $round_id . '_' . $room_num, $uid, $step);
            if (!$saveCounter) {
                throw new LotteryException('记录抽奖次数失败', ErrorCode::LOTTERY_SAVE_DRAW_COOUNT_ERROR);
            }

            // 中奖信息
            $data = [];
            if (!empty($darw_win_gift)) {
                // 奖品列表
                $gift_list = ProbabilityGiftDao::instance()->where('id', 'in', array_unique($darw_win_gift))->all();
                if (!$gift_list) {
                    // 获取奖品信息失败
                    throw new LotteryException('获取中奖奖品信息失败', ErrorCode::LOTTERY_GET_DRAW_GIFT_ERROR);
                }
                // 整理奖品信息
                $format_gift_list = [];
                foreach ($gift_list as $gift) {
                    $gift['win_level'] = $darw_win_gift_map[$gift['id']];
                    $gift['win_level_title'] = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE[$gift['win_level']];
                    $gift['type_title'] = GiftEnum::GIFT_TYPE_TITLE[$gift['type']];
                    $format_gift_list[$gift['id']] = $gift;
                }
                foreach ($darw_win_gift as $item) {
                    $data[] = $format_gift_list[$item];
                }
                // 记录用户中奖奖品信息，记录当时的奖品信息
                $userGiftList = [];
                foreach ($darw_win_order as $value) {
                    $giftInfo = $format_gift_list[$value['probability_gift_id']];
                    $value['type'] = $giftInfo['type'];
                    $value['title'] = $giftInfo['title'];
                    $value['content'] = $giftInfo['content'];
                    $value['img'] = $giftInfo['img'];
                    // unset($value['probability_gift_id']);
                    $userGiftList[] = $value;
                }
                $saveOrder = UserGiftDao::instance()->saveAll($userGiftList);
                if (!$saveOrder) {
                    throw new LotteryException('保存中奖奖品失败', ErrorCode::LOTTERY_SAVE_DRAW_GIFT_ERROR);
                }
            }

            Db::commit();
            return $data;
        } catch (LotteryException $e) {
            Db::rollBack();
            throw $e;
        } catch (Throwable $e) {
            Db::rollBack();
            Logger::instance()->channel()->error('Lottery darw exception: ' . $e->getMessage() . ' line: ' . $e->getLine() . ' file: ' . $e->getFile());
            throw new LotteryException('抽奖操作异常!', ErrorCode::LOTTERY_EXCEPTION, $e);
        }
    }

    /**
     * 生成活动奖池
     *
     * @param integer $round_id 活动ID
     * @param boolean $cover    终极大奖是否允许覆盖场次奖
     * @param boolean $allCover 直接从所有号码覆盖，不优先未中奖的号码，$cover 为 true 有效
     * @param boolean $clear    清除原有奖池数据
     * @param boolean $effect   标记活动状态为生效
     * @throws LotteryException
     * @return array    抽奖号码记录
     */
    public function effect(int $round_id, bool $cover = false, bool $allCover = false, bool $clear = true, bool $effect = true): array
    {
        $info = RoundDao::instance()->getInfo($round_id);
        if (!$info) {
            throw new LotteryException('活动不存在', ErrorCode::LOTTERY_ROUND_NOT_FOUND);
        }
        if ($info['status'] != RoundEnum::ROUND_STATUS['draft'] && $info['status'] != RoundEnum::ROUND_STATUS['effect']) {
            throw new LotteryException('活动已发布或下线，不允许操作奖池', ErrorCode::LOTTERY_ROUND_IS_USE);
        }
        if ($info['norm_gift_count'] <= 0 && $info['special_gift_count'] <= 0) {
            throw new LotteryException('活动奖品不能为空', ErrorCode::LOTTERY_ROUND_IS_USE);
        }

        Db::startTrans();
        try {
            // 清除原数据
            if ($clear) {
                // 删除原奖品信息
                $clearProbabilityGift = ProbabilityGiftDao::instance()->where('round_id', $round_id)->delete();
                if (!$clearProbabilityGift && $clearProbabilityGift !== 0) {
                    throw new LotteryException('清除原奖池奖品信息失败', ErrorCode::LOTTERY_CLEAR_AWARD_ERROR);
                }
                // 删除原抽奖号码
                $clearLotteryNumber = ProbabilityDao::instance()->where('round_id', $round_id)->delete();
                if (!$clearLotteryNumber && $clearLotteryNumber !== 0) {
                    throw new LotteryException('清除原奖池抽奖号码信息失败', ErrorCode::LOTTERY_CLEAR_CODE_ERROR);
                }
                // 删除原有抽奖记录
                $clearLotteryRecord = LogDao::instance()->where('round_id', $round_id)->delete();
                if (!$clearLotteryRecord && $clearLotteryRecord !== 0) {
                    throw new LotteryException('清除原奖池抽奖记录信息失败', ErrorCode::LOTTERY_CLEAR_LOG_ERROR);
                }
            }

            // 复制活动奖品
            $copyGift = ProbabilityGiftDao::instance()->copyRoundEffectGift($round_id);
            if (!$copyGift) {
                throw new LotteryException('生成活动奖品失败', ErrorCode::LOTTERY_COPY_AWARD_ERROR);
            }
            // 场次奖品（普通奖）
            $award = $this->getGiftList($round_id, RoundGiftEnum::GIFT_WIN_LEVEL['norm']);
            if (count($award) > $info['room_quency']) {
                throw new LotteryException('场次奖品数不能超过场次抽奖数', ErrorCode::LOTTERY_ROUND_AWARD_INVALID);
            }
            // 大奖（特别奖）
            $max_award = $this->getGiftList($round_id, RoundGiftEnum::GIFT_WIN_LEVEL['special']);
            if (!$cover && ($info['room_num'] * $info['room_quency']) < ($info['room_num'] * $info['norm_gift_count'] + $info['special_gift_count'])) {
                throw new LotteryException('不允许奖品覆盖，总奖品数不能超过总抽奖次数', ErrorCode::LOTTERY_ROUND_AWARD_COUNT_INVALID);
            }
            // 活动抽奖号码生成
            $probability_data = $this->getProbabilityData($round_id, $info['room_num'], $info['room_quency'], $award, $max_award, $cover, $allCover);
            // 保存抽奖号码
            $saveProbability = ProbabilityDao::instance()->saveAll($probability_data, 1000);
            if (!$saveProbability) {
                throw new LotteryException('生成活动抽奖号码失败', ErrorCode::LOTTERY_CREATE_CODE_ERROR);
            }
            // 更新活动状态
            if ($effect && $info['status'] != RoundEnum::ROUND_STATUS['effect']) {
                $saveRound = RoundDao::instance()->where('id', $round_id)->where('status', RoundEnum::ROUND_STATUS['draft'])->save(['status' => RoundEnum::ROUND_STATUS['effect']]);
                if (!$saveRound) {
                    throw new LotteryException('保存活动信息失败', ErrorCode::LOTTERY_ROUND_SAVE_ERROR);
                }
            }

            Db::commit();
            return $probability_data;
        } catch (LotteryException $e) {
            Db::rollBack();
            throw $e;
        } catch (Throwable $e) {
            Db::rollBack();
            Logger::instance()->channel()->error('Lottery effect exception: ' . $e->getMessage() . ' line: ' . $e->getLine() . ' file: ' . $e->getFile());
            throw new LotteryException('生成活动抽奖信息异常!', ErrorCode::LOTTERY_EXCEPTION, $e);
        }
    }

    /**
     * 生成抽奖号码结果集
     *
     * @param integer $round_id     活动ID
     * @param integer $room         场次数
     * @param integer $quency       场次抽奖次数
     * @param integer $award        场次奖品ID列表
     * @param integer $max_award    终极大奖ID列表
     * @param boolean $cover        终极大奖是否允许覆盖场次奖
     * @param boolean $allCover     直接从所有号码覆盖，不优先未中奖的号码，$cover 为 true 有效
     * @throws LotteryException
     * @return array
     */
    protected function getProbabilityData(int $round_id, int $room, int $quency, array $award = [], array $max_award = [], bool $cover = false, bool $allCover = false): array
    {
        // 抽奖号码列表
        $probability = [];
        // 未中奖号码列表
        $not_win_list = [];
        // 场次奖品数
        $awardCount = count($award);
        // 打乱大奖奖品
        $maxGift = $max_award;
        shuffle($maxGift);
        // 生成抽奖号码数据
        for ($i = 0; $i < $room; $i++) {
            $min = $i * $quency;
            $max = $min + $quency;
            // 获取场次奖品
            $minGift = $award;
            shuffle($minGift);
            $awardList = Common::instance()->randomNumberForArray($min, $max - 1, $awardCount);
            for ($j = 0; $j < $quency; $j++) {
                $k = $min + $j;
                // 奖品ID
                $probability_gift_id = 0;
                if (in_array($k, $awardList)) {
                    // 中场次奖
                    $probability_gift_id = array_pop($minGift) ?: 0;
                } else {
                    // 未中奖
                    $not_win_list[] = $k;
                }
                $probability[$k] = [
                    'round_id'  => $round_id,
                    'room'      => $i,
                    'is_win'    => $probability_gift_id ? RoundEnum::PROBABILITY_WIN_STATUS['enable'] : RoundEnum::PROBABILITY_WIN_STATUS['disable'],
                    'is_use'    => RoundEnum::PROBABILITY_USE_STATUS['enable'],
                    'win_level' => $probability_gift_id ? RoundGiftEnum::GIFT_WIN_LEVEL['norm'] : RoundGiftEnum::GIFT_WIN_LEVEL['off'],
                    'probability_gift_id' => $probability_gift_id
                ];
            }
        }
        // 分配大奖号码
        shuffle($not_win_list);
        // 是否允许覆盖奖项
        if (!$cover) {
            // 不覆盖奖项，从未中奖号码中选取号码
            if (count($not_win_list) < count($max_award)) {
                throw new LotteryException('抽奖号码集合无效, 奖品总数超过总抽奖次数', ErrorCode::LOTTERY_ROOM_CODE_INVALID);
            }
            foreach ($maxGift as $i => $v) {
                $k = $not_win_list[$i];
                $probability[$k]['is_win'] = RoundEnum::PROBABILITY_WIN_STATUS['enable'];
                $probability[$k]['probability_gift_id'] = $v;
                $probability[$k]['win_level'] = RoundGiftEnum::GIFT_WIN_LEVEL['special'];
            }
        } else {
            // 最大抽奖数
            $max_quency = $room * $quency;
            // 允许覆盖奖项
            if ($allCover) {
                // 全号码覆盖
                $max_award_list = Common::instance()->randomNumberForArray(0, $max_quency - 1, count($max_award));
                foreach ($max_award_list as $k => $v) {
                    $probability[$v]['is_win'] = RoundEnum::PROBABILITY_WIN_STATUS['enable'];
                    $probability[$v]['probability_gift_id'] = $maxGift[$k];
                    $probability[$k]['win_level'] = RoundGiftEnum::GIFT_WIN_LEVEL['special'];
                }
            } else {
                // 缓存的大奖终极号码列表
                $max_win_list_cache = $not_win_list;
                // 剩余未中奖号码不足，则覆盖小奖号码
                foreach ($maxGift as $i => $v) {
                    // 存在未中奖号码，先从未中奖号码中选取
                    if (isset($not_win_list[$i])) {
                        $k = $not_win_list[$i];
                    } else {
                        // 未中奖号码不足，从已中奖号码中覆盖
                        $key = Common::instance()->randomNumberForArray(0, $max_quency - 1, 1, $max_win_list_cache);
                        $k = $key[0];
                        $max_win_list_cache[] = $k;
                    }
                    $probability[$k]['is_win'] = RoundEnum::PROBABILITY_WIN_STATUS['enable'];
                    $probability[$k]['probability_gift_id'] = $v;
                    $probability[$k]['win_level'] = RoundGiftEnum::GIFT_WIN_LEVEL['special'];
                }
            }
        }

        return $probability;
    }

    /**
     * 生成生效奖品对应奖品列表
     *
     * @param integer $round_id 活动ID
     * @param integer $level     奖品级别，0普通奖，1大奖
     * @param boolean $shuffle  是否打乱排序
     * @return array
     */
    protected function getGiftList(int $round_id, int $level = 0, bool $shuffle = false): array
    {
        $result = [];
        // 活动奖品信息，用于对照获取奖品数及奖品类型
        $list = RoundGiftDao::instance()->where('round_id', $round_id)->where('level', $level)->where('status', RoundGiftEnum::GIFT_STATUS['enable'])->all();
        // 生效活动奖品信息，用于关联奖品概率
        $map = ProbabilityGiftDao::instance()->where('round_id', $round_id)->column('id', 'gift_id');
        foreach ($list as $item) {
            for ($i = 0; $i < $item['count']; $i++) {
                $result[] = $map[$item['gift_id']] ?? 0;
            }
        }
        // 打乱顺序
        if ($shuffle) {
            shuffle($result);
        }
        return $result;
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }
}
