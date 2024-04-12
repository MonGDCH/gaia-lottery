<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;

/**
 * 用户抽奖凭证流水Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserTicketLogDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_user_ticket_log';

    /**
     * 记录日志
     *
     * @param array $data     请求参数
     * @return integer 日志ID
     */
    public function record(array $data): int
    {
        $check = $this->validate()->rule([
            'uid'           => ['required', 'id'],
            'tid'           => ['required', 'id'],
            'sid'           => ['required', 'int', 'min:0'],
            'from'          => ['required', 'int', 'min:0'],
            'type'          => ['required', 'int'],
            'remark'        => ['isset', 'str'],
            'before_count'  => ['required', 'int'],
            'count'         => ['required', 'int'],
            'after_count'   => ['required', 'int'],
        ])->message([
            'uid'           => '请输入用户ID',
            'tid'           => '请输入抽奖卷ID',
            'sid'           => '请输入关联记录ID',
            'from'          => '请输入来源',
            'type'          => '请输入操作类型',
            'remark'        => '请输入合法的备注',
            'before_count'  => '请输入操作前数量',
            'count'         => '请输入操作数量',
            'after_count'   => '请输入操作后数量',
        ])->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        $data['create_time'] = time();
        $log_id = $this->allowField(['uid', 'tid', 'sid', 'from', 'type', 'remark', 'before_count', 'count', 'after_count', 'create_time'])->save($data, true, true);
        if (!$log_id) {
            $this->error = '记录日志失败';
            return 0;
        }

        return $log_id;
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
    protected function scopeList($query, array $option)
    {
        $query->alias('log')
            ->join(UserDao::instance()->getTable() . ' u', 'u.id = log.uid', 'left')
            ->join(UserDao::instance()->getTable() . ' form', 'log.from=form.id', 'left')
            ->join(TicketDao::instance()->getTable() . ' t', 't.id = log.tid', 'left')
            ->field([
                'log.*', 'u.nickname as u_nickname', 'u.mobile as u_mobile', 'u.email as u_email',
                'form.nickname as form_nickname', 'form.mobile as form_mobile', 'form.email as form_email',
                't.name as t_name', 't.code as t_code'
            ]);

        // 按用户ID
        if (isset($option['uid']) && $this->validate()->id($option['uid'])) {
            $query->where('log.uid', intval($option['uid']));
        }
        // 按来源
        if (isset($option['from']) && $this->validate()->int($option['from'])) {
            $query->where('log.from', intval($option['from']));
        }
        // 按类型
        if (isset($option['type']) && $this->validate()->int($option['type'])) {
            $query->where('log.type', intval($option['type']));
        }
        // 按抽奖卷
        if (isset($option['tid']) && $this->validate()->id($option['tid'])) {
            $query->where('log.tid', intval($option['tid']));
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('log.create_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('log.create_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'log.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'create_time'])) {
            $order = 'log.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
