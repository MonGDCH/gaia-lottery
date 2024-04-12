<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use Throwable;
use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\validate\GiftValidate;

/**
 * 奖品Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class GiftDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_gift';

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
    protected $validate = GiftValidate::class;

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

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Add lottery gift');
            $gift_id = $this->allowField(['type', 'title', 'content', 'img', 'status'])->save($data, true, true);
            if (!$gift_id) {
                $this->rollback();
                $this->error = '保存奖品失败';
                return 0;
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '新增抽奖奖品',
                    'content' => '新增抽奖奖品' . $data['title'],
                    'sid' => $gift_id
                ]);
                if (!$record) {
                    $this->rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();;
                    return 0;
                }
            }

            $this->commit();
            return $gift_id;
        } catch (Throwable $e) {
            $this->rollback();
            $this->error = '新增抽奖奖品异常';
            Logger::instance()->channel()->error('Add lottery gift exception, msg => ' . $e->getMessage(), ['trace' => true]);
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
            $this->error = '奖品不存在';
            return false;
        }

        $this->startTrans();
        try {
            Logger::instance()->channel()->info('Edit lottery gift');
            $save = $this->allowField(['type', 'title', 'content', 'img', 'status'])->where('id', $info['id'])->save($data);
            if (!$save) {
                $this->rollback();
                $this->error = '保存奖品失败';
                return false;
            }

            // 记录操作日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '编辑抽奖奖品',
                    'content' => '编辑抽奖奖品' . $data['title'] . ' ID：' . $info['id'],
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
            $this->error = '编辑抽奖奖品异常';
            Logger::instance()->channel()->error('Edit lottery gift exception, msg => ' . $e->getMessage(), ['trace' => true]);
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
        // 按状态
        if (isset($option['status']) && $this->validate()->int($option['status'])) {
            $query->where('status', intval($option['status']));
        }
        // 按类型
        if (isset($option['type']) && $this->validate()->int($option['type'])) {
            $query->where('type', intval($option['type']));
        }
        // 按名
        if (isset($option['title']) && is_string($option['title']) && !empty($option['title'])) {
            $query->whereLike('title', '%' . trim($option['title']) . '%');
        }
        // 时间搜索
        if (isset($option['start_time']) && $this->validate()->int($option['start_time'])) {
            $query->where('update_time', '>=', intval($option['start_time']));
        }
        if (isset($option['end_time']) && $this->validate()->int($option['end_time'])) {
            $query->where('update_time', '<=', intval($option['end_time']));
        }

        // 排序字段，默认 sort
        $order = 'id';
        if (isset($option['order']) && in_array($option['order'], ['id', 'type', 'status', 'update_time', 'create_time'])) {
            $order = '' . $option['order'];
        }
        // 排序类型，默认 DESC
        $sort = 'DESC';
        if (isset($option['sort']) && in_array(strtoupper($option['sort']), ['ASC', 'DESC'])) {
            $sort = $option['sort'];
        }

        return $query->order($order, $sort);
    }
}
