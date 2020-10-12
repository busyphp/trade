<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\helper\util\Transform;

/**
 * 支付同步返回数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:23 下午 PayCreateSyncReturn.php $
 */
class PayCreateSyncReturn
{
    private $apiTradeNo = '';
    
    private $apiPrice   = '0.00';
    
    private $payTradeNo = '';
    
    private $status     = false;
    
    private $message    = '';
    
    
    /**
     * 获取三方支付单号
     * @return string
     */
    public function getApiTradeNo()
    {
        return $this->apiTradeNo;
    }
    
    
    /**
     * 设置三方支付单号
     * @param string $apiTradeNo
     * @return $this
     */
    public function setApiTradeNo($apiTradeNo)
    {
        $this->apiTradeNo = trim($apiTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取平台订单号
     * @return string
     */
    public function getPayTradeNo()
    {
        return $this->payTradeNo;
    }
    
    
    /**
     * 设置平台订单号
     * @param string $payTradeNo
     * @return $this
     */
    public function setPayTradeNo($payTradeNo)
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付金额
     * @return string
     */
    public function getApiPrice()
    {
        return $this->apiPrice;
    }
    
    
    /**
     * 设置金额，小数保留2位，单位元
     * @param string $apiPrice
     * @return $this
     */
    public function setApiPrice($apiPrice)
    {
        $this->apiPrice = floatval($apiPrice);
        
        return $this;
    }
    
    
    /**
     * 获取状态
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }
    
    
    /**
     * 设置状态
     * @param bool $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = Transform::dataToBool($status);
        
        return $this;
    }
    
    
    /**
     * 获取错误消息
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    
    /**
     * 设置错误消息
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = trim($message);
        
        return $this;
    }
    
    
    public function toArray()
    {
        return get_object_vars($this);
    }
}