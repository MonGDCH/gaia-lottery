<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\env\Config;
use mon\http\Route;
use plugins\admin\middleware\AuthMiddleware;
use plugins\admin\middleware\LoginMiddleware;
use plugins\lottery\controller\LogController;
use plugins\lottery\controller\GiftController;
use plugins\lottery\controller\RoundController;
use plugins\lottery\controller\TicketController;
use plugins\lottery\controller\UserGiftController;
use plugins\lottery\controller\GreyUserController;
use plugins\lottery\controller\RoundGiftController;
use plugins\lottery\controller\UserTicketController;
use plugins\lottery\controller\ProbabilityController;

Route::instance()->group(Config::instance()->get('admin.app.root_path', ''), function (Route $route) {

    $route->group(['path' => '/lottery', 'middleware' => LoginMiddleware::class], function (Route $route) {
        // 获取奖品
        $route->get('/getGift', [GiftController::class, 'getGift']);

        // 权限控制
        $route->group(['middleware' => AuthMiddleware::class], function (Route $route) {
            // 奖品
            $route->group('/gift', function (Route $route) {
                // 列表
                $route->get('', [GiftController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [GiftController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [GiftController::class, 'edit']);
            });

            // 奖卷
            $route->group('/ticket', function (Route $route) {
                // 列表
                $route->get('', [TicketController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [TicketController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [TicketController::class, 'edit']);
            });

            // 用户抽奖凭证
            $route->group('/userTicket', function (Route $route) {
                // 列表
                $route->get('', [UserTicketController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [UserTicketController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [UserTicketController::class, 'edit']);
                // 修改数量
                $route->map(['GET', 'POST'], '/modify', [UserTicketController::class, 'modify']);
            });

            // 活动
            $route->group('/round', function (Route $route) {
                // 列表
                $route->get('', [RoundController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [RoundController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [RoundController::class, 'edit']);
                // 活动发布
                $route->map(['GET', 'POST'], '/publish', [RoundController::class, 'publish']);
                // 下线活动
                $route->post('/downline', [RoundController::class, 'downline']);

                // 活动奖品
                $route->group('/gift', function (Route $route) {
                    // 列表
                    $route->get('', [RoundGiftController::class, 'index']);
                    // 新增
                    $route->map(['GET', 'POST'], '/add', [RoundGiftController::class, 'add']);
                    // 编辑
                    $route->map(['GET', 'POST'], '/edit', [RoundGiftController::class, 'edit']);
                    // 删除
                    $route->post('/remove', [RoundGiftController::class, 'remove']);
                });

                // 活动奖池
                $route->group('/probability', function (Route $route) {
                    // 列表
                    $route->get('', [ProbabilityController::class, 'index']);
                    // 生成奖池
                    $route->map(['GET', 'POST'], '/build', [ProbabilityController::class, 'build']);
                });
            });

            // 灰度用户
            $route->group('/greyUser', function (Route $route) {
                // 列表
                $route->get('', [GreyUserController::class, 'index']);
                // 新增
                $route->map(['GET', 'POST'], '/add', [GreyUserController::class, 'add']);
                // 编辑
                $route->map(['GET', 'POST'], '/edit', [GreyUserController::class, 'edit']);
                // 灰度抽奖
                $route->map(['GET', 'POST'], '/drawLottery', [GreyUserController::class, 'drawLottery']);
            });

            // 日志
            $route->group('/log', function (Route $route) {
                // 用户凭证流水
                $route->get('/userTicket', [LogController::class, 'userTicket']);
                // 用户抽奖记录
                $route->get('/lottery', [LogController::class, 'lottery']);
            });

            // 用户奖品
            $route->group('/userGift', function (Route $route) {
                // 列表
                $route->get('', [UserGiftController::class, 'index']);
                // 修改状态
                $route->post('/status', [UserGiftController::class, 'status']);
            });
        });
    });
});
