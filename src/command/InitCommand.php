<?php

declare(strict_types=1);

namespace gaia\lottery\command;

use Throwable;
use mon\util\Sql;
use ErrorException;
use mon\env\Config;
use think\facade\Db;
use mon\thinkORM\Dao;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use plugins\admin\dao\MenuDao;
use plugins\admin\dao\AuthRuleDao;

/**
 * 数据库初始化
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class InitCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'lottery:init';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Initialization lottery database';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'Admin';

    /**
     * 菜单
     *
     * @var array
     */
    protected $menu = [
        ['name' => 'lottery', 'title' => '抽奖管理', 'icon' => 'layui-icon layui-icon-gift', 'chilid' => [
            ['name' => 'lottery_log', 'title' => '日志流水', 'icon' => 'layui-icon layui-icon-list', 'chilid' => [
                ['name' => '/lottery/log/userTicket', 'title' => '用户凭证流水', 'icon' => 'layui-icon layui-icon-template-1'],
                ['name' => '/lottery/log/lottery', 'title' => '用户抽奖记录', 'icon' => 'layui-icon layui-icon-template-1'],
            ]],
            ['name' => 'lottery_ticket', 'title' => '凭证管理', 'icon' => 'layui-icon layui-icon-key', 'chilid' => [
                ['name' => '/lottery/ticket', 'title' => '抽奖卷管理', 'icon' => 'layui-icon layui-icon-template-1'],
                ['name' => '/lottery/userTicket', 'title' => '用户凭证管理', 'icon' => 'layui-icon layui-icon-template-1'],
            ]],
            ['name' => '/lottery/gift', 'title' => '奖品管理', 'icon' => 'layui-icon layui-icon-gift'],
            ['name' => '/lottery/round', 'title' => '活动管理', 'icon' => 'layui-icon layui-icon-flag'],
            ['name' => '/lottery/greyUser', 'title' => '灰度用户', 'icon' => 'layui-icon layui-icon-username'],
            ['name' => '/lottery/userGift', 'title' => '用户奖品', 'icon' => 'layui-icon layui-icon-heart-fill'],
        ]],
    ];

    /**
     * 权限
     *
     * @var array
     */
    protected $rule = [
        ['name' => 'lottery', 'title' => '抽奖管理', 'chilid' => [
            ['name' => 'lottery_log', 'title' => '日志流水', 'chilid' => [
                ['name' => '/lottery/log/userTicket', 'title' => '用户凭证流水'],
                ['name' => '/lottery/log/lottery', 'title' => '用户抽奖记录'],
            ]],
            ['name' => 'ticket', 'title' => '抽奖卷管理', 'chilid' => [
                ['name' => '/lottery/ticket', 'title' => '查看'],
                ['name' => '/lottery/ticket/add', 'title' => '新增'],
                ['name' => '/lottery/ticket/edit', 'title' => '编辑'],
            ]],
            ['name' => 'user_ticket', 'title' => '用户抽奖卷', 'chilid' => [
                ['name' => '/lottery/userTicket', 'title' => '查看'],
                ['name' => '/lottery/userTicket/add', 'title' => '新增'],
                ['name' => '/lottery/userTicket/edit', 'title' => '编辑'],
                ['name' => '/lottery/userTicket/modify', 'title' => '修改数量'],
            ]],
            ['name' => 'gift', 'title' => '奖品管理', 'chilid' => [
                ['name' => '/lottery/gift', 'title' => '查看'],
                ['name' => '/lottery/gift/add', 'title' => '新增'],
                ['name' => '/lottery/gift/edit', 'title' => '编辑'],
            ]],
            ['name' => 'round', 'title' => '活动管理', 'chilid' => [
                ['name' => '/lottery/round', 'title' => '查看'],
                ['name' => '/lottery/round/add', 'title' => '新增'],
                ['name' => '/lottery/round/edit', 'title' => '编辑'],
                ['name' => '/lottery/round/publish', 'title' => '发布'],
                ['name' => '/lottery/round/downline', 'title' => '下线'],
            ]],
            ['name' => 'grey_user', 'title' => '灰度用户', 'chilid' => [
                ['name' => '/lottery/greyUser', 'title' => '查看'],
                ['name' => '/lottery/greyUser/add', 'title' => '新增'],
                ['name' => '/lottery/greyUser/edit', 'title' => '编辑'],
                ['name' => '/lottery/greyUser/drawLottery', 'title' => '抽奖'],
            ]],
            ['name' => 'user_gift', 'title' => '用户奖品', 'chilid' => [
                ['name' => '/lottery/userGift', 'title' => '查看'],
                ['name' => '/lottery/userGift/status', 'title' => '修改状态'],
            ]],
        ]]
    ];

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        // 读取sql文件
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database.sql';
        $sqls = Sql::instance()->parseFile($file);
        // 执行sql
        Db::setConfig(Config::instance()->get('database', []));
        $out->block('Installation bootstrap');
        $out->spinBegiin();
        foreach ($sqls as $i => $sql) {
            Db::execute($sql);
            if ($i % 5 == 0) {
                $out->spin();
            }
        }

        $out->spin();

        $this->createMenu($this->menu, MenuDao::instance());
        $out->spin();
        $this->createRule($this->rule, AuthRuleDao::instance());

        $out->spinEnd();
        $out->block('Installation done!', 'SUCCESS');
    }

    /**
     * 创建菜单
     *
     * @param array $list   菜单列表
     * @param Dao $dao      菜单Dao操作实例
     * @param integer $pid  父级ID
     * @return void
     */
    public function createMenu(array $list, Dao $dao, int $pid = 0)
    {
        $dao->startTrans();
        try {
            foreach ($list as $item) {
                // 判断是否存在后代
                $hasChild = isset($item['chilid']) && $item['chilid'] ? true : false;
                // 写入记录
                $data = [
                    'pid'   => $pid,
                    'name'  => $item['name'],
                    'title' => $item['title'],
                    'icon'  => $item['icon'],
                    'type'  => $hasChild ? '0' : '1',
                ];
                $menu_id = $dao->save($data, true, true);
                if (!$menu_id) {
                    $dao->rollback();
                    throw new ErrorException('新增菜单失败：' . $item['name']);
                }
                // 判断是否存在后代，存在则递归执行
                if ($hasChild) {
                    $this->createMenu($item['chilid'], $dao, $menu_id);
                }
            }

            $dao->commit();
            return;
        } catch (Throwable $e) {
            $dao->rollback();
            throw $e;
        }
    }

    /**
     * 创建权限
     *
     * @param array $list   权限列表
     * @param Dao $dao      权限Dao操作实例
     * @param integer $pid  父级ID
     * @return void
     */
    public function createRule(array $list, Dao $dao, int $pid = 0)
    {
        $dao->startTrans();
        try {
            foreach ($list as $item) {
                // 判断是否存在后代
                $hasChild = isset($item['chilid']) && $item['chilid'] ? true : false;
                // 写入记录
                $data = [
                    'pid'   => $pid,
                    'name'  => $item['name'],
                    'title' => $item['title'],
                ];
                $rule_id = $dao->save($data, true, true);
                if (!$rule_id) {
                    $dao->rollback();
                    throw new ErrorException('新增权限失败：' . $item['name']);
                }
                // 判断是否存在后代，存在则递归执行
                if ($hasChild) {
                    $this->createRule($item['chilid'], $dao, $rule_id);
                }
            }

            $dao->commit();
            return;
        } catch (Throwable $e) {
            $dao->rollback();
            throw $e;
        }
    }
}
