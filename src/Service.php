<?php

namespace BusyPHP\trade;

use BusyPHP\Service as BaseService;
use BusyPHP\trade\app\controller\NotifyController;
use BusyPHP\trade\app\controller\TradeController;
use BusyPHP\trade\model\TradeConfig;
use think\Route;

/**
 * 服务类
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午3:34 Service.php $
 */
class Service extends \think\Service
{
    use TradeConfig;
    
    const URL_NOTIFY_PATH = 'service/plugins/trade/notify/';
    
    
    public function boot()
    {
        $this->registerRoutes(function(Route $route) {
            $actionPattern = '<' . BaseService::ROUTE_VAR_ACTION . '>';
            
            // 注册异步通知路由
            $route->rule(self::URL_NOTIFY_PATH . "{$actionPattern}", NotifyController::class . "@{$actionPattern}")
                ->append([
                    BaseService::ROUTE_VAR_TYPE    => 'plugin',
                    BaseService::ROUTE_VAR_CONTROL => 'notify',
                ]);
            
            // 后台路由
            if ($this->app->http->getName() === 'admin') {
                $route->rule("plugins_trade/{$actionPattern}", TradeController::class . "@{$actionPattern}")->append([
                    BaseService::ROUTE_VAR_TYPE    => 'plugin',
                    BaseService::ROUTE_VAR_CONTROL => 'plugins_trade',
                ]);
            }
        });
    }
}