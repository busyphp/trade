<?php

namespace BusyPHP\trade;

use BusyPHP\helper\util\Str;
use BusyPHP\trade\app\controller\InstallController;
use BusyPHP\trade\app\controller\TradeController;
use BusyPHP\trade\model\TradeConfig;
use think\Route;

class Service extends \think\Service
{
    use TradeConfig;
    
    public function boot()
    {
        $this->registerRoutes(function(Route $route) {
            $route->rule('general/plugin/install/trade', InstallController::class . '@index');
            
            // 后台路由
            if ($this->app->http->getName() === 'admin') {
                $group   = ucfirst(Str::camel($this->getTradeConfigAdminMenuModule()));
                $control = ucfirst(Str::camel($this->getTradeConfigAdminMenuControl()));
                if ($group && $control) {
                    $route->rule("{$group}.{$control}/<action>", TradeController::class . '@<action>')->append([
                        'group'   => $group,
                        'control' => $control,
                        'type'    => 'plugin'
                    ])->pattern([
                        'action' => '[pay_list|refund_list]+'
                    ]);
                }
            }
        });
    }
}