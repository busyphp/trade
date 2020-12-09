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
    /**
     * 三方退款单号
     * @var string
     */
    protected $apiRefundNo = '';
    
    /**
     * 是否需要重新处理
     * @var bool
     */
    protected $needRehandle = false;
    
    /**
     * 退款退入账户
     * @var string
     */
    protected $refundAccount = '';
    
    
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
        $this->apiRefundNo = $apiRefundNo;
        
        return $this;
    }
    
    
    /**
     * 设置是否需要重新处理
     * @param bool $needRehandle
     */
    public function setNeedRehandle(bool $needRehandle) : void
    {
        $this->needRehandle = $needRehandle;
    }
    
    
    /**
     * 获取是否需要重新处理
     * @return bool
     */
    public function isNeedRehandle() : bool
    {
        return $this->needRehandle;
    }
    
    
    /**
     * 设置退款退入账户
     * @param string $refundAccount
     */
    public function setRefundAccount($refundAccount) : void
    {
        $this->refundAccount = trim($refundAccount);
    }
    
    
    /**
     * 获取退款退入账户
     * @return string
     */
    public function getRefundAccount()
    {
        return $this->refundAccount;
    }
}