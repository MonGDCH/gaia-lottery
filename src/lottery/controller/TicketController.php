<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\admin\comm\Controller;
use plugins\lottery\dao\TicketDao;
use plugins\lottery\contract\TicketEnum;

/**
 * 抽奖卡卷管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class TicketController extends Controller
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
            $result = TicketDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('ticket/index', [
            'uid' => $request->uid,
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
            $edit = TicketDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(TicketDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        return $this->fetch('ticket/add', ['status' => TicketEnum::TICKET_STATUS_TITLE]);
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
            $edit = TicketDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(TicketDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = TicketDao::instance()->where('id', $id)->get();
        if (!$data) {
            return $this->error('记录不存在');
        }

        return $this->fetch('ticket/edit', [
            'data' => $data,
            'status' => TicketEnum::TICKET_STATUS_TITLE
        ]);
    }
}
