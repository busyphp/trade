<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;

/**
 * 支付退款接口类，所有支付退款都应该集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:39 下午 PayRefund.php $
 */
interface PayRefund
{
    /**
     * 设置平台交易订单数据对象
     * @param TradePayField $info
     */
    public function setTradeInfo(TradePayField $info);
    
    
    /**
     * 设置平台退款单号
     * @param string $refundNo
     */
    public function setRefundTradeNo($refundNo);
    
    
    /**
     * 设置要申请退款的金额
     * @param float $refundPrice 精确到小数点2位
     */
    public function setRefundPrice($refundPrice);
    
    
    /**
     * 设置退款原因
     * @param string $reason
     */
    public function setRefundReason($reason);
    
    
    /**
     * 设置退款结果通知url
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl);
    
    
    /**
     * 执行退款
     * @return PayRefundResult
     * @throws AppException
     */
    public function refund() : PayRefundResult;
}