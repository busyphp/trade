<?php

namespace BusyPHP\trade\model;

use BusyPHP\App;
use BusyPHP\app\admin\model\system\plugin\SystemPlugin;
use Exception;

/**
 * 配置
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午12:51 TradeConfig.php $
 */
trait TradeConfig
{
    /**
     * 获取配置
     * @param string $name 配置名称
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function getTradeConfig(string $name, $default = null)
    {
        return App::getInstance()->config->get('busy-trade' . ($name ? ".{$name}" : ''), $default);
    }
    
    
    /**
     * 获取设置
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getTradeSetting(string $key, $default = null)
    {
        try {
            return SystemPlugin::init()->getSetting('busyphp/trade', $key, $default);
        } catch (Exception $e) {
            return $default;
        }
    }
    
    
    /**
     * 获取需重新查询退款状态的任务延迟查询秒数
     * @return int
     */
    public function getRefundQueryDelay() : int
    {
        $delay = $this->getTradeSetting('refund_query_delay', $this->getTradeConfig('refund_queue.query_delay', 3600));
        
        return max($delay, 0);
    }
    
    
    /**
     * 获取需重新退款的任务延迟执行秒数
     * @return int
     */
    public function getRefundSubmitDelay() : int
    {
        $delay = $this->getTradeSetting('refund_submit_delay', $this->getTradeConfig('refund_queue.submit_delay', 3600));
        
        return max($delay, 0);
    }
    
    
    /**
     * 获取交易订单有效时长秒数
     * @return int
     */
    public function getPayValidDuration() : int
    {
        $duration = $this->getTradeSetting('pay_valid_duration', $this->getTradeConfig('pay_queue.valid_duration', 1800));
        
        return max($duration, 60);
    }
}