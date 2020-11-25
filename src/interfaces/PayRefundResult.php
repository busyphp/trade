<?php

namespace BusyPHP\trade\interfaces;

/**
 * 支付退款结果结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:27 下午 PayRefundResult.php $
 */
class PayRefundResult
{
    private $apiRefundTradeNo = '';
    
    
    /**
     * 获取三方退款单号
     * @return string
     */
    public function getApiRefundTradeNo()
    {
        return $this->apiRefundTradeNo;
    }
    
    
    /**
     * 设置三方退款单号
     * @param string $apiRefundTradeNo
     * @return $this
     */
    public function setApiRefundTradeNo($apiRefundTradeNo) : self
    {
        $this->apiRefundTradeNo = $apiRefundTradeNo;
        
        return $this;
    }
}