<?php

namespace BusyPHP\trade\model;

use BusyPHP\App;

/**
 * 配置
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午12:51 TradeConfig.php $
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
        $app = App::init();
        if (!$this->isLoad) {
            $app->config->load($app->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR . 'trade.php', 'trade');
            
            $this->isLoad = true;
        }
        
        return $app->config->get('trade.' . $name, $default);
    }
}