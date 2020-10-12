<?php

namespace BusyPHP\trade\model;

use BusyPHP\App;

/**
 * 配置
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/12 下午9:00 上午 TradeConfig.php $
 * @property App $app
 */
trait TradeConfig
{
    private $isLoad = false;
    
    
    /**
     * 获取配置
     * @param string $name 配置名称
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function getTradeConfig($name, $default = null)
    {
        if (!$this->isLoad) {
            $this->app->config->load($this->app->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'trade.php', 'trade');
            
            $this->isLoad = true;
        }
        
        return $this->app->config->get('trade.' . $name, $default);
    }
    
    
    public function getTradeConfigAdminPayMenuModule()
    {
        return $this->getTradeConfig('admin.pay.menu.module', 'system');
    }
    
    
    public function getTradeConfigAdminPayMenuControl()
    {
        return $this->getTradeConfig('admin.pay.menu.control', 'trade');
    }
    
    
    public function getTradeConfigAdminPayMenuAction()
    {
        return $this->getTradeConfig('admin.pay.menu.action', 'index');
    }
}