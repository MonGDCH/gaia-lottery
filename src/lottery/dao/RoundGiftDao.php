<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\validate\RoundGiftValidate;

/**
 * 抽奖活动奖品Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundGiftDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_round_gift';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 验证器
     *
     * @var string
     */
    protected $validate = RoundGiftValidate::class;

    /**
     * 新增
     *
     * @param array $data 奖品参数
     * @param integer $adminID 管理员ID
     * @return integer
     */
    public function add(array $data, int $adminID): int
    {
        $check = $this->validate()->scope('add')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        // 验证奖品信息
        $info = $this->where('round_id', $data['round_id'])->where('gift_id', $data['gift_id'])->get();
        if ($info) {
            $this->error = '活动奖品已存在';
            return 0;
        }

        // 验证活动状态
        $roundInfo = RoundDao::instance()->where('id', $data['round_id'])->field(['id', 'status'])->get();
        if (!$roundInfo) {
            $this->error = '活动不存在';
            return 0;
        }
        if (in_array($roundInfo['status'], [RoundEnum::ROUND_STATUS['pre_publish'], RoundEnum::ROUND_STATUS['publish']])) {
            $this->error = '活动已发布，如需修改请先【编辑活动】将活动状态变为【草稿】状态';
            return 0;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Add lottery round gift');
            $round_gift_id = $this->allowField(['level', 'round_id', 'gift_id', 'count', 'status'])->save($data, true, true);
            if (!$round_gift_id) {
                $this->rollback();
                $this->error = '保存奖品失败';
                return 0;
            }

            // 判断活动是否为草稿状态，非草稿状态则更新状态
            if ($roundInfo['status'] != RoundEnum::ROUND_STATUS['draft']) {
                $saveRound = RoundDao::instance()->where('id', $roundInfo['id'])->save(['status' => RoundEnum::ROUND_STATUS['draft']]);
                if (!$saveRound) {
                    $this->rollback();
                    $this->error = '重置活动状态失败';
                    return 0;
                }
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '新增活动奖品',
                    'content' => '新增活动奖品，活动ID：' . $data['round_id'],
                    'sid' => $round_gift_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();;
                    return 0;
                }
            }

            $this->commit();
            return $round_gift_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '新增活动奖品异常';
            Logger::instance()->channel()->error('Add lottery round gift exception, msg => ' . $e->getMessage(), ['trace' => true]);
            return 0;
        }
    }

    /**
     * 编辑
     *
     * @param array $data
     * @param integer $adminID
     * @return boolean
     */
    public function edit(array $data, int $adminID): bool
    {
        $check = $this->validate()->scope('edit')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 验证奖品信息
        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '活动奖品不存在';
            return false;
        }
        $exists = $this->where('round_id', $data['round_id'])->where('gift_id', $data['gift_id'])->where('id', '<>', $info['id'])->get();
        if ($exists) {
            $this->error = '活动奖品已存在';
            return false;
        }

        // 验证活动状态
        $roundInfo = RoundDao::instance()->where('id', $data['round_id'])->field(['id', 'status'])->get();
        if (!$roundInfo) {
            $this->error = '活动不存在';
            return false;
        }
        if (in_array($roundInfo['status'], [RoundEnum::ROUND_STATUS['pre_publish'], RoundEnum::ROUND_STATUS['publish']])) {
            $this->error = '活动已发布，如需修改请先【编辑活动】将活动状态变为【草稿】状态';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit lottery round gift');
            $save = $this->allowField(['level', 'round_id', 'gift_id', 'count', 'status'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '保存奖品信息失败';
                return false;
            }

            // 判断活动是否为草稿状态，非草稿状态则更新状态
            if ($roundInfo['status'] != RoundEnum::ROUND_STATUS['draft']) {
                $saveRound = RoundDao::instance()->where('id', $roundInfo['id'])->save(['status' => RoundEnum::ROUND_STATUS['draft']]);
                if (!$saveRound) {
                    $this->rollback();
                    $this->error = '重置活动状态失败';
                    return false;
                }
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '编辑活动奖品',
                    'content' => '编辑活动奖品, ID：' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();;
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '编辑活动奖品异常';
            Logger::instance()->channel()->error('Edit lottery round gift exception, msg => ' . $e->getMessage(), ['trace' => true]);
            return false;
        }
    }

    /**
     * 删除活动奖品
     *
     * @param array $data
     * @param integer $adminID
     * @return boolean
     */
    public function remove(array $data, int $adminID): bool
    {
        $check = $this->validate()->scope('remove')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        // 验证活动状态
        $info = $this->where('id', $data['idx'])->where('round_id', $data['round_id'])->where('gift_id', $data['gift_id'])->get();
        if (!$info) {
            $this->error = '活动奖品不存在';
            return false;
        }

        // 验证活动状态
        $roundInfo = RoundDao::instance()->where('id', $data['round_id'])->field(['id', 'status'])->get();
        if (!$roundInfo) {
            $this->error = '活动不存在';
            return false;
        }
        if (in_array($roundInfo['status'], [RoundEnum::ROUND_STATUS['pre_publish'], RoundEnum::ROUND_STATUS['publish']])) {
            $this->error = '活动已发布，如需修改请先【编辑活动】将活动状态变为【草稿】状态';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Remove lottery round gift');
            $del = $this->where('id', $info['id'])->delete();
            if (!$del) {
                $this->rollback();
                $this->error = '删除活动奖品失败';
                return false;
            }

            // 判断活动是否为草稿状态，非草稿状态则更新状态
            if ($roundInfo['status'] != RoundEnum::ROUND_STATUS['draft']) {
                $saveRound = RoundDao::instance()->where('id', $roundInfo['id'])->save(['status' => RoundEnum::ROUND_STATUS['draft']]);
                if (!$saveRound) {
                    $this->rollback();
                    $this->error = '重置活动状态失败';
                    return false;
                }
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '删除活动奖品',
                    'content' => '删除活动奖品, ID：' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();;
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '删除活动奖品异常';
            Logger::instance()->channel()->error('Remove lottery round gift exception, msg => ' . $e->getMessage(), ['trace' => true]);
            return false;
        }
    }

    /**
     * 查询列表
     *
     * @param array $data 请求参数
     * @return array
     */
    public function getList(array $data): array
    {
        $limit = isset($data['limit']) ? intval($data['limit']) : 10;
        $page = isset($data['page']) && is_numeric($data['page']) ? intval($data['page']) : 1;
        // 查询
        $list = $this->scope('list', $data)->page($page, $limit)->all();
        $total = $this->scope('list', $data)->count();
        return [
            'list'      => $list,
            'count'     => $total,
            'pageSize'  => $limit,
            'page'      => $page
        ];
    }

    /**
     * 查询列表场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    protected function scopeList($query, $option)
    {
        $query->alias('a')->join(GiftDao::instance()->getTable() . ' g', 'a.gift_id = g.id', 'left');
        $query->field(['a.*', 'g.type AS g_type', 'g.title AS g_title', 'g.img AS g_img', 'g.status AS g_status']);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('a.id', intval($option['idx']));
        }
        // 活动ID搜索
        if (isset($option['round_id']) &&  $this->validate()->id($option['round_id'])) {
            $query->where('a.round_id', intval($option['round_id']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('a.status', intval($option['status']));
        }
        // 按中奖级别
        if (isset($option['level']) && $this->validate()->int($option['level'])) {
            $query->where('a.level', intval($option['level']));
        }

        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('a.update_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('a.update_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认 sort
        $order = 'a.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'update_time', 'status', 'level'])) {
            $order = 'a.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'ASC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
