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
    
    const URL_NOTIFY_PATH      = 'plugins_service/trade/notify/';
    
    const QUEUE_CONNECTION     = 'plugin_trade';
    
    const DEFAULT_REFUND_QUEUE = 'plugin_trade_refund';
    
    const DEFAULT_PAY_QUEUE    = 'plugin_trade_pay';
    
    
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
        $refundQueueEnable = $this->getTradeConfig('refund_queue.enable', false);
        $payQueueEnable    = $this->getTradeConfig('pay_queue.enable', false);
        if ($refundQueueEnable || $payQueueEnable) {
            // 队列配置
            $queue                = $this->app->config->get('queue', []);
            $queue['connections'] = $queue['connections'] ?? [];
            if (!isset($queue['connections'][self::QUEUE_CONNECTION])) {
                $queue['connections'][self::QUEUE_CONNECTION] = $this->getTradeConfig('queue_connection', [
                    'type'  => 'database',
                    'queue' => 'default',
                    'table' => 'system_jobs',
                ]);
            }
            $this->app->config->set($queue, 'queue');
            
            
            // Swoole队列配置
            $swoole                     = $this->app->config->get('swoole', []);
            $swoole['queue']            = $swoole['queue'] ?? [];
            $swoole['queue']['workers'] = $swoole['queue']['workers'] ?? [];
            $swoole['queue']['enable']  = true;
            
            // 退款队列
            if ($refundQueueEnable) {
                $name = $this->app->config->get('refund_queue.name', self::DEFAULT_REFUND_QUEUE);
                if (!isset($swoole['queue']['workers'][$name])) {
                    $swoole['queue']['workers'][$name]               = $this->getTradeConfig('refund_queue.worker', [
                        'number'  => 1,
                        'delay'   => 3600,
                        'sleep'   => 60,
                        'tries'   => 0,
                        'timeout' => 60,
                    ]);
                    $swoole['queue']['workers'][$name]['tries'] = 0;
                    $swoole['queue']['workers'][$name]['connection'] = self::QUEUE_CONNECTION;
                }
            }
            
            // 交易队列
            if ($payQueueEnable) {
                $name = $this->app->config->get('pay_queue.name', self::DEFAULT_PAY_QUEUE);
                if (!isset($swoole['queue']['workers'][$name])) {
                    $swoole['queue']['workers'][$name]               = $this->getTradeConfig('pay_queue.worker', [
                        'number'  => 1,
                        'delay'   => 0,
                        'sleep'   => 60,
                        'tries'   => 0,
                        'timeout' => 60,
                    ]);
                    $swoole['queue']['workers'][$name]['connection'] = self::QUEUE_CONNECTION;
                }
            }
            
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