<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\admin\comm\Controller;
use plugins\lottery\dao\TicketDao;
use plugins\lottery\dao\UserTicketDao;
use plugins\lottery\contract\TicketEnum;
use plugins\lottery\service\TicketService;

/**
 * 用户抽奖卡卷管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserTicketController extends Controller
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
            $result = UserTicketDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        $tickets = TicketDao::instance()->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }
        return $this->fetch('userTicket/index', [
            'uid' => $request->uid,
            'ticketList' => $ticketList,
            'status' => TicketEnum::TICKET_STATUS_TITLE
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
            $edit = UserTicketDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(UserTicketDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $tickets = TicketDao::instance()->where('status', TicketEnum::TICKET_STATUS['enable'])->field(['id', 'name', 'code'])->all();
        $ticketList = [['id' => '', 'title' => '']];
        foreach ($tickets as $item) {
            $ticketList[] = ['id' => $item['id'], 'title' => "{$item['name']} ({$item['code']})"];
        }
        return $this->fetch('userTicket/add', [
            'ticketList' => $ticketList,
            'status' => TicketEnum::TICKET_STATUS_TITLE
        ]);
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
            $edit = UserTicketDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(UserTicketDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = UserTicketDao::instance()->getInfo(intval($id));
        if (!$data) {
            return $this->error('记录不存在');
        }

        return $this->fetch('userTicket/edit', [
            'data' => $data,
            'status' => TicketEnum::TICKET_STATUS_TITLE
        ]);
    }

    /**
     * 修改数量
     *
     * @param Request $request
     * @return mixed
     */
    public function modify(Request $request)
    {
        if ($request->isPost()) {
            $uid = $request->post('uid', 0);
            if (!check('id', $uid)) {
                return $this->error('用户参数错误');
            }
            $tid = $request->post('tid', 0);
            if (!check('id', $tid)) {
                return $this->error('凭证参数错误');
            }
            $count = $request->post('count', 0);
            if (!check('int', $count) || $count < 1) {
                return $this->error('数量参数错误');
            }
            $type = $request->post('type', null);
            if (!in_array($type, [0, 1])) {
                return $this->error('操作类型参数错误');
            }
            $modify = TicketService::instance()->modify(intval($uid), intval($tid), intval($count), $type == 1, $request->uid);
            if (!$modify) {
                return $this->error(TicketService::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = UserTicketDao::instance()->getInfo(intval($id));
        if (!$data) {
            return $this->error('记录不存在');
        }

        return $this->fetch('userTicket/modify', [
            'data' => $data
        ]);
    }
}
