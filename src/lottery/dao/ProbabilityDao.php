<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;

/**
 * 抽奖概率Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ProbabilityDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_probability';

    /**
     * 自动写入时间戳
     *
     * @var boolean
     */
    protected $autoWriteTimestamp = true;

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
        $field = [
            'p.*', 'g.gift_id AS g_gift_id', 'g.type AS g_type', 'g.title AS g_title', 'g.img AS g_img',
            'u.nickname AS u_nickname', 'u.mobile AS u_mobile', 'u.email AS u_email'
        ];
        $query->alias('p')->join(ProbabilityGiftDao::instance()->getTable() . ' g', 'p.probability_gift_id=g.id', 'left')
            ->join(UserDao::instance()->getTable() . ' u', 'p.lottery_uid=u.id', 'left')->field($field);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('p.id', intval($option['idx']));
        }
        // 按活动
        if (isset($option['round_id']) && $this->validate()->int($option['round_id'])) {
            $query->where('p.round_id', intval($option['round_id']));
        }
        // 按是否中奖
        if (isset($option['is_win']) && $this->validate()->int($option['is_win'])) {
            $query->where('p.is_win', intval($option['is_win']));
        }
        // 按是否使用
        if (isset($option['is_use']) && $this->validate()->int($option['is_use'])) {
            $query->where('p.is_use', intval($option['is_use']));
        }
        // 按中奖等级
        if (isset($option['win_level']) && $this->validate()->int($option['win_level'])) {
            $query->where('p.win_level', intval($option['win_level']));
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('p.status', intval($option['status']));
        }
        // 按抽奖时间
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('p.lottery_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('p.lottery_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'p.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'is_win', 'is_use', 'win_level', 'lottery_uid', 'lottery_time', 'status'])) {
            $order = 'p.' . $option['order'];
        }
        // 排序类型，默认 ASC
        $sort = 'ASC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
