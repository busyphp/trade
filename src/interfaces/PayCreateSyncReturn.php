<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 支付同步返回数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午11:08 PayCreateSyncReturn.php $
 */
class PayCreateSyncReturn extends ObjectOption
{
    /**
     * 三方交易单号
     * @var string
     */
    private $apiTradeNo = '';
    
    /**
     * 实际支付金额
     * @var float
     */
    private $apiPrice = 0;
    
    /**
     * 平台交易单号
     * @var string
     */
    private $payTradeNo = '';
    
    /**
     * 是否支付成功
     * @var bool
     */
    private $status = false;
    
    /**
     * 支付失败描述
     * @var string
     */
    private $errorMessage = '';
    
    
    /**
     * 获取三方支付单号
     * @return string
     */
    public function getApiTradeNo() : string
    {
        return $this->apiTradeNo;
    }
    
    
    /**
     * 设置三方支付单号
     * @param string $apiTradeNo
     * @return $this
     */
    public function setApiTradeNo(string $apiTradeNo) : self
    {
        $this->apiTradeNo = trim($apiTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取平台交易单号
     * @return string
     */
    public function getPayTradeNo() : string
    {
        return $this->payTradeNo;
    }
    
    
    /**
     * 设置平台交易单号
     * @param string $payTradeNo
     * @return $this
     */
    public function setPayTradeNo(string $payTradeNo) : self
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付金额
     * @return float
     */
    public function getApiPrice() : float
    {
        return $this->apiPrice;
    }
    
    
    /**
     * 设置金额，小数保留2位，单位元
     * @param float $apiPrice
     * @return $this
     */
    public function setApiPrice(float $apiPrice) : self
    {
        $this->apiPrice = $apiPrice;
        
        return $this;
    }
    
    
    /**
     * 获取支付状态
     * @return bool
     */
    public function isStatus() : bool
    {
        return $this->status;
    }
    
    
    /**
     * 设置支付状态
     * @param bool $status
     * @return $this
     */
    public function setStatus(bool $status) : self
    {
        $this->status = $status;
        
        return $this;
    }
    
    
    /**
     * 获取错误消息
     * @return string
     */
    public function getErrorMessage() : string
    {
        return $this->errorMessage;
    }
    
    
    /**
     * 设置错误消息
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage(string $errorMessage) : self
    {
        $this->errorMessage = trim($errorMessage);
        
        return $this;
    }
}