<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\GiftDao;
use plugins\lottery\dao\RoundDao;
use plugins\admin\comm\Controller;
use plugins\lottery\dao\RoundGiftDao;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\contract\RoundGiftEnum;

/**
 * 活动奖品控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundGiftController extends Controller
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
        $checkRespon = $this->handlerQueryCheck($options);
        if ($checkRespon !== true) {
            return $checkRespon;
        }

        // 查询列表数据
        if ($request->get('isApi')) {
            // 查看抽奖奖品不分页，这里用一个大数去处理
            $list = RoundGiftDao::instance()->scope('list', $options)->all();
            return $this->success('ok', $list);
        }

        return $this->fetch('roundGift/index', [
            'uid' => $request->uid,
            'gift_type' => json_encode(GiftEnum::GIFT_TYPE_TITLE, JSON_UNESCAPED_UNICODE),
            'win_level' => json_encode(RoundGiftEnum::GIFT_WIN_LEVEL, JSON_UNESCAPED_UNICODE),
            'win_level_title' => json_encode(RoundGiftEnum::GIFT_WIN_LEVEL_TITLE, JSON_UNESCAPED_UNICODE),
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
            // 处理请求的活动参数
            $checkRespon = $this->handlerQueryCheck($option);
            if ($checkRespon !== true) {
                return $checkRespon;
            }
            $edit = RoundGiftDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(RoundGiftDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        // 处理请求的活动参数
        $checkRespon = $this->handlerQueryCheck($request->get());
        if ($checkRespon !== true) {
            return $checkRespon;
        }
        $level = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE;
        unset($level[RoundGiftEnum::GIFT_WIN_LEVEL['off']]);
        return $this->fetch('roundGift/add', [
            'level' => $level,
            'status' => RoundGiftEnum::GIFT_STATUS_TITLE
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
            // 处理请求的活动参数
            $checkRespon = $this->handlerQueryCheck($option);
            if ($checkRespon !== true) {
                return $checkRespon;
            }
            $edit = RoundGiftDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(RoundGiftDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = RoundGiftDao::instance()->where('id', $id)->get();
        if (!$data) {
            return $this->error('记录不存在');
        }

        $giftInfo = GiftDao::instance()->where('id', $data['gift_id'])->field(['id', 'title', 'type'])->get();
        if (!$giftInfo) {
            return $this->error('奖品不存在');
        }
        $giftInfo['type_name'] = GiftEnum::GIFT_TYPE_TITLE[$giftInfo['type']] ?? '';

        $level = RoundGiftEnum::GIFT_WIN_LEVEL_TITLE;
        unset($level[RoundGiftEnum::GIFT_WIN_LEVEL['off']]);
        return $this->fetch('roundGift/edit', [
            'data' => $data,
            'level' => $level,
            'status' => RoundGiftEnum::GIFT_STATUS_TITLE,
            'giftData' => json_encode([$giftInfo], JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 删除
     *
     * @param Request $request
     * @return void
     */
    public function remove(Request $request)
    {
        $option = $request->post();
        // 处理请求的活动参数
        $checkRespon = $this->handlerQueryCheck($option);
        if ($checkRespon !== true) {
            return $checkRespon;
        }
        $edit = RoundGiftDao::instance()->remove($option, $request->uid);
        if (!$edit) {
            return $this->error(RoundGiftDao::instance()->getError());
        }

        return $this->success('操作成功');
    }

    /**
     * 统一处理请求的活动ID参数
     *
     * @param Request $request
     * @return mixed
     */
    protected function handlerQueryCheck(array $data, bool $checkDraft = false)
    {
        $round_id = $data['round_id'] ?? '';
        if (!$round_id) {
            return $this->error('Params faild');
        }
        $roundInfo = RoundDao::instance()->where('id', $round_id)->get();
        if (!$roundInfo) {
            return $this->error('活动不存在');
        }
        if ($checkDraft && $roundInfo['status'] != RoundEnum::ROUND_STATUS['draft']) {
            return $this->error('活动非【草稿】状态');
        }
        $this->assign('roundInfo', $roundInfo);
        return true;
    }
}
