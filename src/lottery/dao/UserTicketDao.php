<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\validate\UserTicketValidate;

/**
 * 用户抽奖凭证Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserTicketDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_user_ticket';

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
    protected $validate = UserTicketValidate::class;

    /**
     * 创建抽奖账户
     *
     * @param array $data
     * @param integer $adminID
     * @return integer
     */
    public function add(array $data, int $adminID): int
    {
        // 校验数据
        $check = $this->validate()->data($data)->scope('add')->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        $uid = $data['uid'];
        $tid = $data['tid'];
        $count = $data['count'];
        $userInfo = UserDao::instance()->where('id', $uid)->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return 0;
        }
        $ticketInfo = TicketDao::instance()->where('id', $tid)->get();
        if (!$ticketInfo) {
            $this->error = '抽奖卷不存在';
            return 0;
        }
        if ($ticketInfo['status'] != 1) {
            $this->error = '抽奖卷已停用';
            return 0;
        }
        $info = $this->where('uid', $uid)->where('tid', $tid)->get();
        if ($info) {
            $this->error = '用户奖卷账户已存在';
            return 0;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Create user ticket');
            $account_id = $this->allowField(['uid', 'tid', 'count', 'status'])->save([
                'uid' => $uid,
                'tid' => $tid,
                'count' => $count,
                'status' => $data['status']
            ], true, true);
            if (!$account_id) {
                $this->rollback();
                $this->error = '保存失败';
                return 0;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '创建用户抽奖凭证',
                    'content' => '创建用户抽奖凭证, uid: ' . $uid . ' tid: ' . $tid . ' count: ' . $count,
                    'sid' => $account_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return 0;
                }
            }

            $this->commit();
            return $account_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '保存用户抽奖卷异常';
            Logger::instance()->channel()->error('Create user ticket exception. msg: ' . $e->getMessage());
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
            Logger::instance()->channel()->info('Edit user ticket');
            $save = $this->allowField(['count', 'status'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '编辑失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '编辑用户抽奖凭证',
                    'content' => '编辑用户抽奖凭证，count: ' . $data['count'] . ', status: ' . $data['status'] . ', id: ' . $info['id'],
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
            $this->error = '编辑用户抽奖凭证异常';
            Logger::instance()->channel()->error('Edit ticket exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询记录信息
     *
     * @param integer $id
     * @return array
     */
    public function getInfo(int $id): array
    {
        $field = ['ut.*', 'u.nickname', 'u.mobile', 'u.email', 't.name', 't.code'];
        return $this->alias('ut')->join(UserDao::instance()->getTable() . ' u', 'u.id = ut.uid')
            ->join(TicketDao::instance()->getTable() . ' t', 't.id = ut.tid')->field($field)
            ->where('ut.id', $id)->get();
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
        $field = ['ut.*', 'u.nickname', 'u.mobile', 'u.email', 't.name', 't.code'];
        $query->alias('ut')->join(UserDao::instance()->getTable() . ' u', 'u.id = ut.uid')
            ->join(TicketDao::instance()->getTable() . ' t', 't.id = ut.tid')->field($field);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('ut.id', intval($option['idx']));
        }
        // 抽用户
        if (isset($option['uid']) &&  $this->validate()->id($option['uid'])) {
            $query->where('ut.uid', intval($option['uid']));
        }
        // 抽奖卷
        if (isset($option['tid']) &&  $this->validate()->id($option['tid'])) {
            $query->where('ut.tid', intval($option['tid']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('ut.status', intval($option['status']));
        }
        // 按更新时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('ut.update_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('ut.update_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'ut.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'update_time', 'create_time'])) {
            $order = 'ut.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
