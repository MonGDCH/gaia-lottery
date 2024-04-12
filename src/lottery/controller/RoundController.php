<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\RoundDao;
use plugins\lottery\dao\TicketDao;
use plugins\admin\comm\Controller;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\contract\TicketEnum;
use plugins\lottery\contract\RoundGiftEnum;

/**
 * 抽奖卡卷管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundController extends Controller
{
    /**
     * 查看
     *
     * @param Request $request  请求实例
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get();
            $result = RoundDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        // 搜索抽奖卷
        $tickets = TicketDao::instance()->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }

        return $this->fetch('round/index', [
            'uid' => $request->uid,
            'ticketList' => $ticketList,
            'status' => RoundEnum::ROUND_STATUS_TITLE,
            'statusAttr' => json_encode(RoundEnum::ROUND_STATUS, JSON_UNESCAPED_UNICODE),
            'statusAttrTitle' => json_encode(RoundEnum::ROUND_STATUS_TITLE, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 新增
     *
     * @param Request $request
     * @return mixed
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $option = $request->post();
            $edit = RoundDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(RoundDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $tickets = TicketDao::instance()->where('status', TicketEnum::TICKET_STATUS['enable'])->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }

        return $this->fetch('round/add', ['ticketList' => $ticketList]);
    }

    /**
     * 编辑
     *
     * @param Request $request
     * @return mixed
     */
    public function edit(Request $request)
    {
        // post更新操作
        if ($request->isPost()) {
            $option = $request->post();
            $edit = RoundDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(RoundDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = RoundDao::instance()->where('id', $id)->get();
        if (!$data) {
            return $this->error('记录不存在');
        }
        if ($data['status'] == RoundEnum::ROUND_STATUS['publish']) {
            return $this->error('已正式发布的活动无法修改，如需修改请先下线活动');
        }

        $tickets = TicketDao::instance()->where('status', TicketEnum::TICKET_STATUS['enable'])->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }

        return $this->fetch('round/edit', [
            'data' => $data,
            'ticketList' => $ticketList
        ]);
    }

    /**
     * 活动发布
     *
     * @param Request $request
     * @return mixed
     */
    public function publish(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            $save = RoundDao::instance()->publish($data, $request->uid);
            if (!$save) {
                return $this->error(RoundDao::instance()->getError());
            }

            return $this->success('发布成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        $info = RoundDao::instance()->getInfo(intval($id));
        if (!$info) {
            return $this->error('活动不存在');
        }
        $info['norm_title'] = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE[RoundGiftEnum::GIFT_WIN_LEVEL['norm']];
        $info['special_title'] = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE[RoundGiftEnum::GIFT_WIN_LEVEL['special']];
        $statusList = [
            RoundEnum::ROUND_STATUS['pre_publish'] => RoundEnum::ROUND_STATUS_TITLE[RoundEnum::ROUND_STATUS['pre_publish']],
            RoundEnum::ROUND_STATUS['publish'] => RoundEnum::ROUND_STATUS_TITLE[RoundEnum::ROUND_STATUS['publish']],
        ];

        return $this->fetch('round/publish', [
            'data' => $info,
            'statusList' => $statusList
        ]);
    }

    /**
     * 活动下线
     *
     * @param Request $request
     * @return mixed
     */
    public function downline(Request $request)
    {
        $round_id = $request->post('round_id');
        if (!check('id', $round_id)) {
            return $this->error('Params faild');
        }
        $save = RoundDao::instance()->downline(intval($round_id), $request->uid);
        if (!$save) {
            return $this->error(RoundDao::instance()->getError());
        }

        return $this->success('发布成功');
    }
}
