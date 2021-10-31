<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 支付退款异步通知返回数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:20 PayRefundNotifyResult.php $
 */
class PayRefundNotifyResult extends ObjectOption
{
    /**
     * 平台退款单号
     * @var string
     */
    private $refundNo = '';
    
    /**
     * 三方退款单号
     * @var string
     */
    private $apiRefundNo = '';
    
    /**
     * 三方支付单号
     * @var string
     */
    private $payApiTradeNo = '';
    
    /**
     * 平台交易单号
     * @var string
     */
    private $payTradeNo = '';
    
    /**
     * 是否退款成功
     * @var bool
     */
    private $status = false;
    
    /**
     * 退款失败原因
     * @var string
     */
    private $errMsg = '';
    
    /**
     * 退入账户说明
     * @var string
     */
    private $refundAccount = '';
    
    /**
     * 是否需要重新处理
     * @var bool
     */
    private $needReHandle = false;
    
    
    /**
     * 获取平台退款单号
     * @return string
     */
    public function getRefundNo() : string
    {
        return $this->refundNo;
    }
    
    
    /**
     * 设置平台退款单号
     * @param string $refundNo
     * @return $this
     */
    public function setRefundNo(string $refundNo) : self
    {
        $this->refundNo = trim($refundNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方退款单号
     * @return string
     */
    public function getApiRefundNo() : string
    {
        return $this->apiRefundNo;
    }
    
    
    /**
     * 设置三方退款单号
     * @param string $apiRefundNo
     * @return $this
     */
    public function setApiRefundNo(string $apiRefundNo) : self
    {
        $this->apiRefundNo = trim($apiRefundNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方支付单号
     * @return string
     */
    public function getPayApiTradeNo() : string
    {
        return $this->payApiTradeNo;
    }
    
    
    /**
     * 设置三方支付单号
     * @param string $payApiTradeNo
     * @return $this
     */
    public function setPayApiTradeNo(string $payApiTradeNo) : self
    {
        $this->payApiTradeNo = trim($payApiTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取平台支付单号
     * @return string
     */
    public function getPayTradeNo() : string
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
    public function isStatus() : bool
    {
        return $this->status;
    }
    
    
    /**
     * 设置是否退款成功
     * @param bool $status
     * @return $this
     */
    public function setStatus(bool $status) : self
    {
        $this->status = $status;
        
        return $this;
    }
    
    
    /**
     * 获取退款失败原因
     * @return string
     */
    public function getErrMsg() : string
    {
        return $this->errMsg;
    }
    
    
    /**
     * 设置退款失败原因
     * @param string $errMsg
     * @return $this
     */
    public function setErrMsg(string $errMsg) : self
    {
        $this->errMsg = trim($errMsg);
        
        return $this;
    }
    
    
    /**
     * 获取退款的账户信息
     * @return string
     */
    public function getRefundAccount() : string
    {
        return $this->refundAccount;
    }
    
    
    /**
     * 设置退款的账户信息
     * @param string $refundAccount
     * @return $this
     */
    public function setRefundAccount(string $refundAccount) : self
    {
        $this->refundAccount = trim($refundAccount);
        
        return $this;
    }
    
    
    /**
     * 设置是否重新处理
     * @param bool $needReHandle
     * @return $this
     */
    public function setNeedReHandle(bool $needReHandle) : self
    {
        $this->needReHandle = $needReHandle;
        
        return $this;
    }
    
    
    /**
     * 是否不处理
     * @return bool
     */
    public function isNeedReHandle() : bool
    {
        return $this->needReHandle;
    }
}