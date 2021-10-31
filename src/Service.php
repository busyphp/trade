<?php

namespace BusyPHP\trade;

use BusyPHP\Service as BaseService;
use BusyPHP\trade\app\controller\InstallController;
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
    
    public function boot()
    {
        $this->registerRoutes(function(Route $route) {
            $actionPattern = '<' . BaseService::ROUTE_VAR_ACTION . '>';
            
            $route->rule('general/plugin/install/trade', InstallController::class . '@index');
            
            // 注册异步通知路由
            $route->rule("service/plugins/trade/notify/{$actionPattern}", NotifyController::class . "@{$actionPattern}")
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