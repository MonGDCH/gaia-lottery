<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;
use plugins\admin\dao\AdminLogDao;

/**
 * 用户中奖奖品Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserGiftDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_user_gift';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

    /**
     * 修改状态
     *
     * @param integer $id
     * @param integer $status
     * @param integer $adminID
     * @return boolean
     */
    public function status(int $id, int $status, int $adminID): bool
    {
        $info = $this->where('id', $id)->get();
        if (!$info) {
            $this->error = '记录不存在';
            return false;
        }
        if ($status == $info['status']) {
            $this->error = '修改的状态与原状态一致';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('modify user gift status');
            $save = $this->where('id', $id)->save(['status' => $status]);
            if (!$save) {
                $this->rollback();
                $this->error = '修改状态失败';
                return false;
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '修改用户奖品状态',
                    'content' => '修改用户奖品状态为: ' . $status,
                    'sid' => $id
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
            $this->error = '修改用户奖品状态异常';
            Logger::instance()->channel()->error('modify user gift status exception, msg => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询日志列表
     *
     * @param array $option 请求参数
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
        $query->alias('g')
            ->join(UserDao::instance()->getTable() . ' u', 'g.uid = u.id')
            ->join(RoundDao::instance()->getTable() . ' r', 'g.round_id = r.id', 'left')
            ->field(['g.*', 'u.nickname as u_nickname', 'u.mobile as u_mobile', 'u.email as u_email', 'r.title AS r_title']);

        // 按用户ID
        if (isset($option['uid']) && $this->validate()->id($option['uid'])) {
            $query->where('g.uid', intval($option['uid']));
        }
        // 按活动
        if (isset($option['round_id']) && $this->validate()->id($option['round_id'])) {
            $query->where('g.round_id', intval($option['round_id']));
        }
        // 按领取状态
        if (isset($option['get_status']) && $this->validate()->int($option['get_status'])) {
            $query->where('g.get_status', intval($option['get_status']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('g.status', intval($option['status']));
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('g.create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('g.create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'g.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'create_time'])) {
            $order = 'g.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
