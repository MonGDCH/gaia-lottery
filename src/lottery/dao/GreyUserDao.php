<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\ucenter\dao\UserDao;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\validate\GreyUserValidate;

/**
 * 灰度用户Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GreyUserDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_grey_user';

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
    protected $validate = GreyUserValidate::class;

    /**
     * 新增
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

        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '请选择合法的用户';
            return 0;
        }

        $exists = $this->where('uid', $data['uid'])->get();
        if ($exists) {
            $this->error = '用户已存在';
            return 0;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Add grey user');
            $user_id = $this->allowField(['uid', 'round_ids', 'start_time', 'end_time', 'status'])->save($data, true, true);
            if (!$user_id) {
                $this->rollback();
                $this->error = '新增失败';
                return 0;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '添加灰度用户',
                    'content' => '添加灰度用户: ' . $data['uid'],
                    'sid' => $user_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return 0;
                }
            }

            $this->commit();
            return $user_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '添加灰度用户异常';
            Logger::instance()->channel()->error('Add grey user exception. msg: ' . $e->getMessage());
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

        $info = $this->where('id', $data['idx'])->get();
        if (!$info) {
            $this->error = '记录不存在';
            return false;
        }

        $userInfo = UserDao::instance()->where('id', $data['uid'])->get();
        if (!$userInfo) {
            $this->error = '请选择合法的用户';
            return false;
        }

        $exists = $this->where('uid', $data['uid'])->where('id', '<>', $info['id'])->get();
        if ($exists) {
            $this->error = '用户已存在';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit grey user');
            $save = $this->allowField(['uid', 'round_ids', 'start_time', 'end_time', 'status'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '编辑失败';
                return false;
            }

            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '编辑灰度用户',
                    'content' => '编辑灰度用户: ' . $data['uid'],
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
            $this->error = '编辑灰度用户异常';
            Logger::instance()->channel()->error('Edit grey user exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询用户信息
     *
     * @param integer $id
     * @return array
     */
    public function getInfo(int $id): array
    {
        $field = ['a.*', 'b.nickname', 'b.avatar', 'b.mobile', 'b.email'];
        $userInfo = $this->alias('a')->join(UserDao::instance()->getTable() . ' b', 'a.uid=b.id')->where('a.id', $id)->field($field)->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return [];
        }

        return $userInfo;
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
        $field = ['a.*', 'b.nickname', 'b.avatar', 'b.mobile', 'b.email'];
        $query->alias('a')->join(UserDao::instance()->getTable() . ' b', 'a.uid=b.id', 'LEFT')->field($field);
        // ID搜索
        if (isset($option['idx']) &&  $this->validate()->id($option['idx'])) {
            $query->where('a.id', intval($option['idx']));
        }
        // 按用户ID
        if (isset($option['uid']) &&  $this->validate()->id($option['uid'])) {
            $query->where('a.uid', intval($option['uid']));
        }
        // 按名称
        if (isset($option['nickname']) && is_string($option['nickname']) && !empty($option['nickname'])) {
            $query->whereLike('b.nickname', '%' . trim($option['nickname']) . '%');
        }
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('a.status', intval($option['status']));
        }
        // 按时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('a.start_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('a.end_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认id
        $order = 'a.id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'create_time'])) {
            $order = 'a.' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
