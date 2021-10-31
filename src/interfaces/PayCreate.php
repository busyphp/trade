<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\pay\TradePayInfo;
use Exception;

/**
 * 支付下单统一接口类，所有的支付下单均需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午4:22 PayCreate.php $
 */
interface PayCreate
{
    /**
     * 设置交易信息
     * @param TradePayInfo $tradeInfo
     */
    public function setTradeInfo(TradePayInfo $tradeInfo);
    
    
    /**
     * 设置附加数据会原样返回
     * @param string $attach
     */
    public function setAttach(string $attach);
    
    
    /**
     * 设置异步回调地址
     * @param string $notifyUrl
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
     * @return mixed 不同支付方式返回内容不同
     * @throws Exception
     */
    public function create();
    
    
    /**
     * 解析同步返回结果
     * @return PayCreateSyncReturn
     * @throws Exception
     */
    public function syncReturn() : PayCreateSyncReturn;
}
    

