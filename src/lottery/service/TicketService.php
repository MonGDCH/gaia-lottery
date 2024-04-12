<?php

declare(strict_types=1);

namespace plugins\lottery\service;

use Throwable;
use mon\log\Logger;
use think\facade\Db;
use mon\util\Instance;
use plugins\admin\dao\AdminLogDao;
use plugins\lottery\contract\LogEnum;
use plugins\lottery\dao\UserTicketDao;
use plugins\lottery\contract\TicketEnum;
use plugins\lottery\dao\UserTicketLogDao;

/**
 * 抽奖凭证服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class TicketService
{
    use Instance;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 修改用户抽奖卷数
     *
     * @param integer $uid  用户ID
     * @param integer $tid  凭证ID
     * @param integer $count    操作数量
     * @param boolean $type 是否新增
     * @param integer $adminID  管理员ID
     * @return boolean
     */
    public function modify(int $uid, int $tid, int $count, bool $type, int $adminID): bool
    {
        $info = UserTicketDao::instance()->where('uid', $uid)->where('tid', $tid)->get();
        if (!$info) {
            $this->error = '抽奖卷账户不存在';
            return false;
        }
        // if ($info['status'] != TicketEnum::TICKET_STATUS['enable']) {
        //     $this->error = '抽奖卷账户不可用';
        //     return false;
        // }
        if (!$type && $info['count'] < $count) {
            $this->error = '当前抽奖卷不足';
            return false;
        }
        $typeMsg = $type ? 'add' : 'reduce';
        Db::startTrans();
        try {
            Logger::instance()->channel()->info("Admin {$typeMsg} user ticket");
            if ($type) {
                $save = UserTicketDao::instance()->inc('count', $count)->where('id', $info['id'])->save();
                $after_count = $info['count'] + $count;
                $ticketLogType = LogEnum::TICKET_LOG_TYPE['sys_add'];
            } else {
                $save = UserTicketDao::instance()->dec('count', $count)->where('id', $info['id'])->save();
                $after_count = $info['count'] - $count;
                $ticketLogType = LogEnum::TICKET_LOG_TYPE['sys_reduce'];;
            }
            if (!$save) {
                Db::rollback();
                $this->error = '修改失败';
                return false;
            }

            // 抽奖卷日志
            $recordTicketLog = UserTicketLogDao::instance()->record([
                'uid'           => $info['uid'],
                'tid'           => $info['tid'],
                'sid'           => $adminID,
                'from'          => 0,
                'type'          => $ticketLogType,
                'remark'        => '系统处理',
                'before_count'  => $info['count'],
                'count'         => $count,
                'after_count'   => $after_count,
            ]);
            if (!$recordTicketLog) {
                Db::rollback();
                $this->error = '记录用户奖卷日志失败,' . UserTicketLogDao::instance()->getError();
                return false;
            }

            // 管理员日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => 'lottery',
                    'action' => '修改用户抽奖卷数量',
                    'content' => '修改用户抽奖卷数量，type: ' . $type . ', count: ' . $count . ', id: ' . $info['id'],
                    'sid' => $info['id']
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录操作日志失败,' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '增加用户抽奖卷异常';
            Logger::instance()->channel()->error('Admin ' . $type . ' ticket exception. msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 增加用户抽奖卷
     *
     * @param integer $uid  用户ID
     * @param integer $tid  抽奖卷ID
     * @param integer $count    操作数量
     * @return boolean
     */
    public function add(int $uid, int $tid, int $count): bool
    {
        $info = UserTicketDao::instance()->where('uid', $uid)->where('tid', $tid)->get();
        if (!$info) {
            $this->error = '抽奖卷账户不存在';
            return false;
        }
        if ($info['status'] != TicketEnum::TICKET_STATUS['enable']) {
            $this->error = '抽奖卷账户不可用';
            return false;
        }

        Logger::instance()->channel()->info('Add user ticket, id: ' . $info['id'] . ', count: ' . $count);
        $save = UserTicketDao::instance()->inc('count', $count)->where('id', $info['id'])->save();
        if (!$save) {
            $this->error = '增加用户抽奖卷失败';
            return false;
        }

        return true;
    }

    /**
     * 扣减用户抽奖卷
     *
     * @param integer $uid  用户ID
     * @param integer $tid  抽奖卷ID
     * @param integer $count    操作数量
     * @return boolean
     */
    public function reduce(int $uid, int $tid, int $count): bool
    {
        $info = UserTicketDao::instance()->where('uid', $uid)->where('tid', $tid)->get();
        if (!$info) {
            $this->error = '抽奖卷账户不存在';
            return false;
        }
        if ($info['status'] != TicketEnum::TICKET_STATUS['enable']) {
            $this->error = '抽奖卷账户不可用';
            return false;
        }
        if ($info['count'] < $count) {
            $this->error = '当前抽奖卷不足';
            return false;
        }

        Logger::instance()->channel()->info('Reduce user ticket, id: ' . $info['id'] . ', count: ' . $count);
        $save = UserTicketDao::instance()->dec('count', $count)->where('id', $info['id'])->save();
        if (!$save) {
            $this->error = '扣减用户抽奖卷失败';
            return false;
        }

        return true;
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }
}
