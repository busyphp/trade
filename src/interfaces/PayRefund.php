<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\refund\TradeRefundField;
use Exception;

/**
 * 支付退款接口类，所有支付退款都应该集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:39 下午 PayRefund.php $
 */
interface PayRefund
{
    /**
     * 设置平台退款订单数据对象
     * @param TradeRefundField $info
     */
    public function setTradeRefundInfo(TradeRefundField $info);
    
    
    /**
     * 设置退款结果通知url
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl);
    
    
    /**
     * 执行退款
     * @return PayRefundResult
     * @throws Exception
     */
    public function refund() : PayRefundResult;
}