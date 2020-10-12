<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;
use BusyPHP\trade\model\pay\TradePayField;

/**
 * 支付下单统一接口类，所有的支付下单均需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:34 下午 PayCreate.php $
 */
interface PayCreate
{
    /**
     * 设置交易信息
     * @param TradePayField $tradeInfo
     */
    public function setTradeInfo(TradePayField $tradeInfo);
    
    
    /**
     * 设置附加数据会原样返回
     * @param string $attach
     */
    public function setAttach(string $attach);
    
    
    /**
     * 设置异步回调地址
     * @param string $notifyUrl
     * @throws AppException
     */
    public function setNotifyUrl(string $notifyUrl);
    
    
    /**
     * 设置同步回调地址
     * @param string $returnUrl
     */
    public function setReturnUrl(string $returnUrl);
    
    
    /**
     * 设置商品展示地址
     * @param string $showUrl
     */
    public function setShowUrl(string $showUrl);
    
    
    /**
     * 执行下单
     * @return mixed|void
     * @throws AppException
     */
    public function create();
    
    
    /**
     * 解析同步返回结果
     * @return PayCreateSyncReturn
     * @throws AppException
     */
    public function syncReturn() : PayCreateSyncReturn;
}
    

