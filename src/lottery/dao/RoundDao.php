<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\contract\RoundEnum;
use plugins\lottery\contract\RoundGiftEnum;
use plugins\lottery\validate\RoundValidate;

/**
 * 抽奖活动Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RoundDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_round';

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
    protected $validate = RoundValidate::class;

    /**
     * 查询活动信息
     *
     * @param integer $id   活动ID
     * @return array
     */
    public function getInfo(int $id): array
    {
        $round_gift_table = RoundGiftDao::instance()->getTable();
        $norm = RoundGiftEnum::GIFT_WIN_LEVEL['norm'];
        $special = RoundGiftEnum::GIFT_WIN_LEVEL['special'];
        $roundGiftStatus = RoundGiftEnum::GIFT_STATUS['enable'];
        $sql = "SELECT `round`.*,
                    SUM(CASE WHEN `gift`.`level` = {$norm} AND `gift`.`status` = {$roundGiftStatus} THEN `gift`.`count` ELSE 0 END) AS norm_gift_count,
                    SUM(CASE WHEN `gift`.`level` = {$special} AND `gift`.`status` = {$roundGiftStatus} THEN `gift`.`count` ELSE 0 END) AS special_gift_count
                FROM
                    `{$this->table}` AS `round`
                LEFT JOIN 
                    `{$round_gift_table}` AS `gift` ON `round`.`id` = `gift`.`round_id`
                WHERE `round`.`id` = ?
                GROUP BY `round`.`id`";

        $info = $this->query($sql, [$id]);
        return !$info ? [] : $info[0];
    }

    /**
     * 添加
     *
     * @param array $data
     * @param integer $adminID
     * @return integer
     */
    public function add(array $data, int $adminID): int
    {
        $check = $this->validate()->scope('add')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Add round');
            $round_id = $this->allowField(['tid', 'title', 'content', 'img', 'room_num', 'room_quency', 'sort'])->save($data, true, true);
            if (!$round_id) {
                $this->rollback();
                $this->error = '新增失败';
                return 0;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '添加抽奖活动',
                    'content' => '添加抽奖活动: ' . $data['title'] . ', ID: ' . $round_id,
                    'sid' => $round_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return 0;
                }
            }

            $this->commit();
            return $round_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '添加抽奖活动异常';
            Logger::instance()->channel()->error('Add round exception. msg: ' . $e->getMessage());
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
        $check = $this->validate()->data($data)->scope('edit')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }

        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '记录不存在';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit round');
            // 编辑后，活动状态统一变为草稿状态
            $data['status'] = RoundEnum::ROUND_STATUS['draft'];
            $field = ['tid', 'title', 'content', 'img', 'room_num', 'room_quency', 'sort', 'status'];
            $save = $this->allowField($field)->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '编辑失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '编辑抽奖活动',
                    'content' => '编辑抽奖活动: ' . $data['title'] . ', ID: ' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '编辑抽奖活动异常';
            Logger::instance()->channel()->error('Edit round exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发布、预发布活动
     *
     * @param array $data 发布信息
     * @return boolean
     */
    public function publish(array $data, int $adminID): bool
    {
        $check = $this->validate()->scope('publish')->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return false;
        }
        if (!in_array($data['status'], [RoundEnum::ROUND_STATUS['pre_publish'], RoundEnum::ROUND_STATUS['publish']])) {
            $this->error = '请选择合法的发布状态';
            return false;
        }
        // 获取信息
        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '获取活动信息失败';
            return false;
        }
        // if ($info['status'] == $data['status']) {
        //     $this->error  = '活动状态未发生变化';
        //     return false;
        // }
        // 原状态必须是生效中、预发布才可以发布
        if (!in_array($info['status'], [RoundEnum::ROUND_STATUS['effect'], RoundEnum::ROUND_STATUS['pre_publish']])) {
            $this->error = '原状态不为生效或预发布';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Publish round');
            // 修改状态
            $save = $this->allowField(['status', 'start_time', 'end_time'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '发布活动失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '发布抽奖活动',
                    'content' => '发布抽奖活动: ' . $info['title'] . ', ID: ' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '发布抽奖活动异常';
            Logger::instance()->channel()->error('Publish round exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 活动下线
     *
     * @param integer $id
     * @param integer $adminID
     * @return boolean
     */
    public function downline(int $id, int $adminID): bool
    {
        // 获取信息
        $info = $this->where('id', $id)->get();
        if (!$info) {
            $this->error = '获取活动信息失败';
            return false;
        }
        if ($info['status'] == RoundEnum::ROUND_STATUS['downline']) {
            $this->error  = '活动已下线';
            return false;
        }
        // 原状态必须是正式发布、预发布才可以发布
        if (!in_array($info['status'], [RoundEnum::ROUND_STATUS['publish'], RoundEnum::ROUND_STATUS['pre_publish']])) {
            $this->error = '活动未上线';
            return false;
        }
        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Downline round');
            // 修改状态
            $save = $this->where('id', $id)->save(['status' => RoundEnum::ROUND_STATUS['downline']]);
            if (!$save) {
                $this->rollback();
                $this->error = '下线活动失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '下线抽奖活动',
                    'content' => '下线抽奖活动: ' . $info['title'] . ', ID: ' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '下线抽奖活动异常';
            Logger::instance()->channel()->error('Downline round exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询列表
     *
     * @param array $data
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
     * 查询场景
     *
     * @param \mon\thinkOrm\extend\Query $query
     * @param array $option
     * @return mixed
     */
    public function scopeList($query, array $option)
    {
        $query->alias('r')->join(TicketDao::instance()->getTable() . ' t', 't.id = r.tid');
        $query->field(['r.*', 't.name AS t_name', 't.code AS t_code']);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('r.id', intval($option['idx']));
        }
        // 抽奖卷搜索
        if (isset($option['tid']) &&  $this->validate()->id($option['tid'])) {
            $query->where('r.tid', intval($option['tid']));
        }
        // 按名称
        if (isset($option['title']) && is_string($option['title']) && !empty($option['title'])) {
            $query->whereLike('title', '%' . trim($option['title']) . '%');
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('r.status', intval($option['status']));
        }
        // 按更新时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('r.start_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('r.end_time', '<=', intval($option['end_time']));
        }
        // 按更新时间搜索
        if (isset($option['update_start_time']) && $this->validate()->int($option['update_start_time'])) {
            $query->where('update_time', '>=', intval($option['update_start_time']));
        }
        if (isset($option['update_end_time']) && $this->validate()->int($option['update_end_time'])) {
            $query->where('update_time', '<=', intval($option['update_end_time']));
        }

        // 排序字段，默认id
        $order = 'r.sort';
        if (isset($option['order']) && in_array($option['order'], ['id', 'update_time', 'sort', 'create_time'])) {
            $order = 'r.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
