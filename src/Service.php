<?php

namespace BusyPHP\trade;

use BusyPHP\Service as BaseService;
use BusyPHP\trade\app\controller\NotifyController;
use BusyPHP\trade\app\controller\TradeController;
use BusyPHP\trade\model\TradeConfig;
use think\Container;
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
    
    const URL_NOTIFY_PATH = 'plugins_service/trade/notify/';
    
    
    /**
     * 单例模式
     * @return self
     */
    public static function init()
    {
        return Container::getInstance()->make(self::class);
    }
    
    
    public function boot()
    {
        // 注入任务
        if ($this->getTradeConfig('refund_queue.enable', false)) {
            // 队列配置
            $queue                = $this->app->config->get('queue', []);
            $queue['connections'] = $queue['connections'] ?? [];
            if (!isset($queue['connections']['plugin_trade'])) {
                $queue['connections']['plugin_trade'] = $this->getTradeConfig('refund.queue.connection', [
                    'type'  => 'database',
                    'queue' => 'plugin_trade_refund',
                    'table' => 'system_jobs',
                ]);
            }
            $this->app->config->set($queue, 'queue');
            
            
            // Swoole队列配置
            $name                       = $this->app->config->get('queue.connections.plugin_trade.queue', 'plugin_trade_refund');
            $swoole                     = $this->app->config->get('swoole', []);
            $swoole['queue']            = $swoole['queue'] ?? [];
            $swoole['queue']['workers'] = $swoole['queue']['workers'] ?? [];
            if (!isset($swoole['queue']['workers'][$name])) {
                $swoole['queue']['workers'][$name]               = $this->getTradeConfig('refund_queue.worker', [
                    'number'  => 1,
                    'delay'   => 3600,
                    'sleep'   => 60,
                    'tries'   => 0,
                    'timeout' => 60,
                ]);
                $swoole['queue']['workers'][$name]['connection'] = 'plugin_trade';
            }
            
            $swoole['queue']['enable'] = true;
            $this->app->config->set($swoole, 'swoole');
        }
        
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