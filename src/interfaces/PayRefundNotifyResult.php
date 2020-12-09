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
    private $refundNo      = '';
    
    private $apiRefundNo   = '';
    
    private $payApiTradeNo = '';
    
    private $payTradeNo    = '';
    
    private $status        = false;
    
    private $errMsg        = '';
    
    private $refundAccount = '';
    
    private $needRehandle  = false;
    
    
    /**
     * 获取平台退款单号
     * @return string
     */
    public function getRefundNo()
    {
        return $this->refundNo;
    }
    
    
    /**
     * 设置平台退款单号
     * @param string $refundNo
     * @return $this
     */
    public function setRefundNo($refundNo) : self
    {
        $this->refundNo = trim($refundNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方退款单号
     * @return string
     */
    public function getApiRefundNo()
    {
        return $this->apiRefundNo;
    }
    
    
    /**
     * 设置三方退款单号
     * @param string $apiRefundNo
     * @return $this
     */
    public function setApiRefundNo($apiRefundNo) : self
    {
        $this->apiRefundNo = trim($apiRefundNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方支付单号
     * @return string
     */
    public function getPayApiTradeNo()
    {
        return $this->payApiTradeNo;
    }
    
    
    /**
     * 设置三方支付单号
     * @param string $payApiTradeNo
     * @return $this
     */
    public function setPayApiTradeNo($payApiTradeNo) : self
    {
        $this->payApiTradeNo = trim($payApiTradeNo);
        
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
    
    
    /**
     * 设置是否重新处理
     * @param bool $needRehandle
     */
    public function setNeedRehandle(bool $needRehandle) : void
    {
        $this->needRehandle = $needRehandle;
    }
    
    
    /**
     * 是否不处理
     * @return bool
     */
    public function isNeedRehandle() : bool
    {
        return $this->needRehandle;
    }
}