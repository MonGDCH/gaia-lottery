<?php

declare(strict_types=1);

namespace plugins\lottery\controller;

use mon\http\Request;
use plugins\lottery\dao\GiftDao;
use plugins\admin\comm\Controller;
use plugins\lottery\contract\GiftEnum;

/**
 * 奖品管理控制器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GiftController extends Controller
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
            $result = GiftDao::instance()->getList($option);
            return $this->success('ok', $result['list'], ['count' => $result['count']]);
        }

        return $this->fetch('gift/index', [
            'uid' => $request->uid,
            'type' => GiftEnum::GIFT_TYPE_TITLE,
            'status' => GiftEnum::GIFT_STATUS_TITLE,
            'typeList' => json_encode(GiftEnum::GIFT_TYPE_TITLE, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * 获取奖品
     *
     * @param Request $request
     * @return mixed
     */
    public function getGift(Request $request)
    {
        $data = $request->get();
        $result = GiftDao::instance()->getList($data);
        $list = [];
        foreach ($result['list'] as $item) {
            $item['type_name'] = GiftEnum::GIFT_TYPE_TITLE[$item['type']];
            $list[] = $item;
        }

        return $this->success('ok', $list, ['count' => $result['count']]);
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
            $edit = GiftDao::instance()->add($option, $request->uid);
            if (!$edit) {
                return $this->error(GiftDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        return $this->fetch('gift/add', [
            'status' => GiftEnum::GIFT_STATUS_TITLE,
            'typeList' => GiftEnum::GIFT_TYPE_TITLE,
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
            $edit = GiftDao::instance()->edit($option, $request->uid);
            if (!$edit) {
                return $this->error(GiftDao::instance()->getError());
            }

            return $this->success('操作成功');
        }

        $id = $request->get('idx');
        if (!check('id', $id)) {
            return $this->error('参数错误');
        }
        // 查询规则
        $data = GiftDao::instance()->where('id', $id)->get();
        if (!$data) {
            return $this->error('记录不存在');
        }

        return $this->fetch('gift/edit', [
            'data' => $data,
            'status' => GiftEnum::GIFT_STATUS_TITLE,
            'typeList' => GiftEnum::GIFT_TYPE_TITLE,
        ]);
    }
}
