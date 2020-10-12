<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\helper\util\Transform;

/**
 * 支付退款异步通知返回数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:30 下午 PayRefundNotifyResult.php $
 */
class PayRefundNotifyResult
{
    private $refundTradeNo    = '';
    
    private $apiRefundTradeNo = '';
    
    private $apiPayTradeNo    = '';
    
    private $refundPrice      = 0;
    
    private $payTradeNo       = '';
    
    private $price            = 0;
    
    private $status           = false;
    
    private $errMsg           = '';
    
    private $successTime      = 0;
    
    private $refundAccount    = '';
    
    
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
        $this->apiRefundTradeNo = trim($apiRefundTradeNo);
        
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
    
    
    /**
     * 获取退款金额
     * @return float
     */
    public function getRefundPrice()
    {
        return $this->refundPrice;
    }
    
    
    /**
     * 设置退款金额，单位元，精确到2位小数
     * @param float $refundPrice
     * @return $this
     */
    public function setRefundPrice($refundPrice) : self
    {
        $this->refundPrice = floatval($refundPrice);
        
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
    public function setPayTradeNo(string $payTradeNo) : self
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付总金额
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
    
    
    /**
     * 设置支付总金额
     * @param float $price
     * @return $this
     */
    public function setPrice($price) : self
    {
        $this->price = floatval($price);
        
        return $this;
    }
    
    
    /**
     * 获取是否退款成功
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }
    
    
    /**
     * 设置是否退款成功
     * @param bool $status
     * @return $this
     */
    public function setStatus($status) : self
    {
        $this->status = Transform::dataToBool($status);
        
        return $this;
    }
    
    
    /**
     * 获取退款失败原因
     * @return string
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }
    
    
    /**
     * 设置退款失败原因
     * @param string $errMsg
     * @return $this
     */
    public function setErrMsg($errMsg) : self
    {
        $this->errMsg = trim($errMsg);
        
        return $this;
    }
    
    
    /**
     * 获取退款成功时间戳
     * @return int
     */
    public function getSuccessTime()
    {
        return $this->successTime;
    }
    
    
    /**
     * 设置退款成功时间戳
     * @param int $successTime
     * @return $this
     */
    public function setSuccessTime($successTime) : self
    {
        $this->successTime = intval($successTime);
        
        return $this;
    }
    
    
    /**
     * 获取退款的账户信息
     * @return string
     */
    public function getRefundAccount()
    {
        return $this->refundAccount;
    }
    
    
    /**
     * 设置退款的账户信息
     * @param string $refundAccount
     * @return $this
     */
    public function setRefundAccount($refundAccount) : self
    {
        $this->refundAccount = trim($refundAccount);
        
        return $this;
    }
    
    
    public function toArray()
    {
        return get_object_vars($this);
    }
}