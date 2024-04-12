<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\RoundDao;
use plugins\admin\comm\Controller;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\contract\ErrorCode;
use plugins\lottery\dao\ProbabilityDao;
use plugins\lottery\contract\RoundGiftEnum;
use plugins\lottery\service\LotteryService;
use plugins\lottery\exception\LotteryException;

/**
 * 活动抽奖奖池控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ProbabilityController extends Controller
{
    /**
     * 查看
     *
     * @param Request $request  请求实例
     * @return mixed
     */
    public function index(Request $request)
    {
        $options = $request->get();
        // 处理请求的活动参数
        $round_id = $request->get('round_id');
        if (!check('id', $round_id)) {
            return $this->error('Params faild');
        }
        $roundInfo = RoundDao::instance()->where('id', $round_id)->get();
        if (!$roundInfo) {
            return $this->error('活动不存在');
        }

        if ($request->get('isApi')) {
            $result = ProbabilityDao::instance()->getList($options);
            // 是否有效
            $effect = ($roundInfo['status'] != RoundEnum::ROUND_STATUS['draft'] && $roundInfo['status'] != RoundEnum::ROUND_STATUS['downline']) ? 1 : 0;
            // 剩余奖品数
            $isWinCount = ProbabilityDao::instance()->where('round_id', $round_id)->where('is_win', 1)->where('is_use', RoundEnum::PROBABILITY_USE_STATUS['enable'])->count();
            // 已抽奖数
            $isUseCount = ProbabilityDao::instance()->where('round_id', $round_id)->where('is_use', 1)->count();
            // 奖品总数
            $giftCount = ProbabilityDao::instance()->where('round_id', $round_id)->where('is_win', 1)->count();
            return $this->success('ok', $result['list'], [
                'count' => $result['count'],
                'effect' => $effect,
                'totalRow' => [
                    'giftCount' => $giftCount,
                    'isWinCount' => $isWinCount,
                    'isUseCount' => $isUseCount
                ]
            ]);
        }

        return $this->fetch('probability/index', [
            'uid' => $request->uid,
            'roundInfo' => $roundInfo,
            'giftType' => json_encode(GiftEnum::GIFT_TYPE_TITLE, JSON_UNESCAPED_UNICODE),
            'roundGiftType' => json_encode(RoundGiftEnum::GIFT_WIN_LEVEL_TITLE, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 生成活动奖池
     *
     * @param Request $request
     * @return mixed
     */
    public function build(Request $request)
    {
        if ($request->isPost()) {
            // 处理请求的活动参数
            $round_id = $request->post('round_id');
            if (!check('id', $round_id)) {
                return $this->error('Params faild');
            }
            $roundInfo = RoundDao::instance()->where('id', $round_id)->get();
            if (!$roundInfo) {
                return $this->error('活动不存在');
            }

            try {
                $cover = $request->post('cover', 0);
                LotteryService::instance()->effect(intval($round_id), $cover == 1);

                return $this->success('生成成功');
            } catch (LotteryException $e) {
                if ($e->getCode() == ErrorCode::LOTTERY_EXCEPTION) {
                    throw $e->getPrevious();
                }
                return $this->error($e->getMessage());
            }
        }

        // 处理请求的活动参数
        $round_id = $request->get('round_id');
        if (!$round_id) {
            return $this->error('Params faild');
        }
        $roundInfo = RoundDao::instance()->getInfo(intval($round_id));
        if (!$roundInfo) {
            return $this->error('活动不存在');
        }
        $roundInfo['norm_title'] = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE[RoundGiftEnum::GIFT_WIN_LEVEL['norm']];
        $roundInfo['special_title'] = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE[RoundGiftEnum::GIFT_WIN_LEVEL['special']];

        return $this->fetch('probability/build', ['roundInfo' => $roundInfo]);
    }
}
