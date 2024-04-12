<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\LogDao;
use plugins\lottery\dao\RoundDao;
use plugins\lottery\dao\TicketDao;
use plugins\admin\comm\Controller;
use plugins\lottery\contract\LogEnum;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\dao\UserTicketLogDao;

/**
 * 抽奖卡卷管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LogController extends Controller
{
    /**
     * 用户抽奖凭证日志
     *
     * @param Request $request
     * @return mixed
     */
    public function userTicket(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get(null, []);
            $result = UserTicketLogDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        $typeAttr = LogEnum::TICKET_LOG_TYPE_TITLE;
        $tickets = TicketDao::instance()->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }

        return $this->fetch('log/userTicket', [
            'uid' => $request->uid,
            'typeAttr' => $typeAttr,
            'ticketList' => $ticketList,
            'typeAttrJson' => json_encode($typeAttr, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * 抽奖记录
     *
     * @param Request $request
     * @return mixed
     */
    public function lottery(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get(null, []);
            $result = LogDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        $roundList = RoundDao::instance()->field(['id', 'title'])->all();
        array_unshift($roundList, ['id' => '', 'title' => '']);
        return $this->fetch('log/lottery', [
            'uid' => $request->uid,
            'roundList' => $roundList,
            'giftType' => json_encode(GiftEnum::GIFT_TYPE_TITLE, JSON_UNESCAPED_UNICODE)
        ]);
    }
}
