<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use mon\http\Context;
use mon\http\Request;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;

/**
 * 抽奖记录Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class LogDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_log';

    /**
     * 记录日志
     *
     * @param array $data     请求参数
     * @return integer 日志ID
     */
    public function record(array $data): int
    {
        $check = $this->validate()->rule([
            'uid'               => ['required', 'id'],
            'round_id'          => ['required', 'id'],
            'probability_id'    => ['required', 'id'],
            'description'       => ['isset', 'str'],
            'ip'                => ['ip'],
        ])->message([
            'uid'               => '请输入用户ID',
            'probability_id'    => '请输入抽奖卷ID',
            'description'       => '请输入合法的备注',
            'ip'                => '请输入合法的IP地址',
        ])->data($data)->check();
        if (!$check) {
            $this->error = $this->validate()->getError();
            return 0;
        }

        /** @var Request $request 上下文请求实例 */
        $request = Context::get(Request::class);
        $data['ip'] = $option['ip'] ?? ($request ? $request->ip() : '0.0.0.0');
        $data['create_time'] = time();
        $log_id = $this->allowField(['uid', 'round_id', 'probability_id', 'description', 'ip', 'create_time'])->save($data, true, true);
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
    protected function scopeList($query, $option)
    {
        $query->alias('log')
            ->join(UserDao::instance()->getTable() . ' u', 'log.uid = u.id')
            ->join(ProbabilityDao::instance()->getTable() . ' p', 'log.probability_id = p.id', 'left')
            ->join(RoundDao::instance()->getTable() . ' r', 'log.round_id = r.id', 'left')
            ->join(ProbabilityGiftDao::instance()->getTable() . ' g', 'p.probability_gift_id = g.id', 'left')
            ->field([
                'log.*', 'u.nickname as u_nickname', 'u.mobile as u_mobile', 'u.email as u_email', 'p.is_win',
                'p.probability_gift_id', 'r.title AS r_title', 'g.type AS g_type', 'g.title AS g_title', 'g.img AS g_img'
            ]);

        // 按用户ID
        if (isset($option['uid']) && $this->validate()->id($option['uid'])) {
            $query->where('log.uid', intval($option['uid']));
        }
        // 按活动
        if (isset($option['round_id']) && $this->validate()->id($option['round_id'])) {
            $query->where('log.round_id', intval($option['round_id']));
        }
        // 按中奖
        if (isset($option['is_win']) && $this->validate()->int($option['is_win'])) {
            $query->where('p.is_win', intval($option['is_win']));
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
