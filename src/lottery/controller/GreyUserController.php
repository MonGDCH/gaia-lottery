<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\log\Logger;
use mon\http\Request;
use support\service\LockService;
use plugins\lottery\dao\RoundDao;
use plugins\admin\comm\Controller;
use plugins\lottery\dao\GreyUserDao;
use plugins\lottery\contract\ErrorCode;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\service\LotteryService;
use plugins\lottery\validate\GreyUserValidate;
use plugins\lottery\exception\LotteryException;

/**
 * 灰度用户控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GreyUserController extends Controller
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
            $result = GreyUserDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('greyUser/index', [
            'uid' => $request->uid,
            'status' => RoundEnum::GREY_USER_STATUS_TITLE
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
            $edit = GreyUserDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(GreyUserDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        return $this->fetch('greyUser/add', [
            'status' => RoundEnum::GREY_USER_STATUS_TITLE
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
            $edit = GreyUserDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(GreyUserDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询
        $data = GreyUserDao::instance()->getInfo(intval($id));
        if (!$data) {
            return $this->error('记录不存在');
        }

        return $this->fetch('greyUser/edit', [
            'data' => $data,
            'status' => RoundEnum::GREY_USER_STATUS_TITLE
        ]);
    }

    /**
     * 灰度用户抽奖
     *
     * @param Request $request
     * @return mixed
     */
    public function drawLottery(Request $request, GreyUserValidate $validate)
    {
        // post抽奖
        if ($request->isPost()) {
            $data = $request->post();
            $check = $validate->data($data)->scope('lottery')->check();
            if (!$check) {
                return $this->error($validate->getError());
            }

            $userInfo = GreyUserDao::instance()->where('id', $data['idx'])->where('status', RoundEnum::GREY_USER_STATUS['enable'])->get();
            if (!$userInfo) {
                return $this->error('灰度用户不存在!');
            }

            $round_id = intval($data['round_id']);
            $room_num = intval($data['room_num']);
            $step = intval($data['step']);
            $uid = $userInfo['uid'];
            $ip = $request->ip();
            // 加锁，开始执行抽奖操作
            $lock_name = LotteryService::LOCK_LOTTERY_KEY . '_' . $round_id . '_' . $room_num;
            $lock = LockService::instance()->lock(LotteryService::LOCK_LOTTERY_APP, $lock_name, $ip, LotteryService::LOCK_LOTTERY_APP, LotteryService::LOCK_LOTTERY_TIME);
            if ($lock['code'] != 1) {
                Logger::instance()->channel()->error("Act grey lottery lock faild. round_id: {$round_id}, room_num: {$room_num}, code: {$lock['code']}, msg: {$lock['msg']}");
                return $this->error($lock['msg']);
            }
            try {
                $lotteryGift = LotteryService::instance()->lottery($round_id, $room_num, $uid, $step, $ip);
                return $this->success('ok', $lotteryGift);
            } catch (LotteryException $e) {
                if ($e->getCode() == ErrorCode::LOTTERY_EXCEPTION) {
                    throw $e->getPrevious();
                }
                return $this->error($e->getMessage());
            } finally {
                // 释放锁
                $unlock = LockService::instance()->unLock($lock['data']);
                if ($unlock['code'] != 1) {
                    Logger::instance()->channel()->error("Act lottery unlock faild. round_id: {$round_id}, room_num: {$room_num}, code: {$unlock['code']}, msg: {$unlock['msg']}");
                    return $this->error($unlock['msg']);
                }
            }
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询信息
        $data = GreyUserDao::instance()->where('id', $id)->where('status', RoundEnum::GREY_USER_STATUS['enable'])->get();
        if (!$data) {
            return $this->error('用户不存在');
        }

        $rounds = RoundDao::instance()->where('status', RoundEnum::ROUND_STATUS['pre_publish'])->field(['id', 'title', 'room_num'])->all();
        array_unshift($rounds, ['id' => '', 'title' => '', 'room_num' => 0]);
        return $this->fetch('greyUser/drawLottery', [
            'data' => $data,
            'rounds' => $rounds,
            'roundsJson' => json_encode($rounds, JSON_UNESCAPED_UNICODE)
        ]);
    }
}
