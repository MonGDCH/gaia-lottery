<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\RoundDao;
use plugins\admin\comm\Controller;
use plugins\lottery\dao\UserGiftDao;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\contract\UserGiftEnum;

/**
 * 用户奖品控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserGiftController extends Controller
{
    /**
     * 查看
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->get('isApi')) {
            $option = $request->get(null, []);
            $result = UserGiftDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        $roundList = RoundDao::instance()->field(['id', 'title'])->all();
        array_unshift($roundList, ['id' => '', 'title' => '']);
        return $this->fetch('userGift/index', [
            'uid' => $request->uid,
            'roundList' => $roundList,
            'status' => UserGiftEnum::GIFT_STATUS_TITLE,
            'get_status' => UserGiftEnum::GIFT_GET_STATUS_TITLE,
            'giftType' => json_encode(GiftEnum::GIFT_TYPE_TITLE, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 修改状态
     *
     * @param Request $request
     * @return mixed
     */
    public function status(Request $request)
    {
        $id = $request->post('idx', 0);
        $status = $request->post('status', null);
        if (!check('id', $id)) {
            return $this->error('params faild');
        }
        if (!in_array($status, [UserGiftEnum::GIFT_STATUS['disable'], UserGiftEnum::GIFT_STATUS['enable']])) {
            return $this->error('status parans faild');
        }

        $save = UserGiftDao::instance()->status(intval($id), intval($status), $request->uid);
        if (!$save) {
            return $this->error(UserGiftDao::instance()->getError());
        }

        return $this->success('操作成功');
    }
}
