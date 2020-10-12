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
    
    private $apiPayTradeNo    = '';
    
    private $refundPrice      = 0;
    
    private $refundTradeNo    = '';
    
    private $payTradeNo       = '';
    
    
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
    
    
    /**
     * 获取退款金额
     * @return float
     */
    public function getRefundPrice()
    {
        return $this->refundPrice;
    }
    
    
    /**
     * 设置退款金额，单位元，保留小数点2位
     * @param float $refundPrice
     * @return $this
     */
    public function setRefundPrice($refundPrice) : self
    {
        $this->refundPrice = floatval($refundPrice);
        
        return $this;
    }
    
    
    /**
     * 获取平台退款单号
     * @return string
     */
    public function getRefundTradeNo()
    {
        return $this->refundTradeNo;
    }
    
    
    /**
     * 设置平台退款单号
     * @param string $refundTradeNo
     * @return $this
     */
    public function setRefundTradeNo($refundTradeNo) : self
    {
        $this->refundTradeNo = trim($refundTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取平台支付单号
     * @return string
     */
    public function getPayTradeNo()
    {
        return $this->payTradeNo;
    }
    
    
    /**
     * 设置平台支付单号
     * @param string $payTradeNo
     * @return $this
     */
    public function setPayTradeNo($payTradeNo) : self
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方支付单号
     * @return string
     */
    public function getApiPayTradeNo()
    {
        return $this->apiPayTradeNo;
    }
    
    
    /**
     * 设置三方支付单号
     * @param string $apiPayTradeNo
     * @return $this
     */
    public function setApiPayTradeNo($apiPayTradeNo) : self
    {
        $this->apiPayTradeNo = trim($apiPayTradeNo);
        
        return $this;
    }
    
    
    public function toArray()
    {
        return get_object_vars($this);
    }
}