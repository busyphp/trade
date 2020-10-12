<?php

namespace BusyPHP\trade\interfaces;

/**
 * 支付异步返回结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:25 下午 PayNotifyResult.php $
 */
class PayNotifyResult
{
    private $payTradeNo  = '';
    
    private $apiTradeNo  = '';
    
    private $apiPrice    = 0;
    
    private $apiBankName = '';
    
    private $attach      = '';
    
    private $payType     = 0;
    
    
    /**
     * 获取平台支付订单号
     * @return string
     */
    public function getPayTradeNo()
    {
        return $this->payTradeNo;
    }
    
    
    /**
     * 设置平台支付订单号
     * @param string $payTradeNo
     * @return $this
     */
    public function setPayTradeNo($payTradeNo)
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方支付订单号
     * @return string
     */
    public function getApiTradeNo()
    {
        return $this->apiTradeNo;
    }
    
    
    /**
     * 设置三方返回的支付订单号
     * @param string $apiTradeNo
     * @return $this
     */
    public function setApiTradeNo($apiTradeNo)
    {
        $this->apiTradeNo = trim($apiTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付金额
     * @return float
     */
    public function getApiPrice()
    {
        return $this->apiPrice;
    }
    
    
    /**
     * 设置支付金额
     * @param int $apiPrice
     * @return $this
     */
    public function setApiPrice($apiPrice)
    {
        $this->apiPrice = floatval($apiPrice);
        
        return $this;
    }
    
    
    /**
     * 获取支付银行名称
     * @return string
     */
    public function getApiBankName()
    {
        return $this->apiBankName;
    }
    
    
    /**
     * 设置支付银行名称
     * @param string $apiBankName
     * @return $this
     */
    public function setApiBankName($apiBankName)
    {
        $this->apiBankName = trim($apiBankName);
        
        return $this;
    }
    
    
    /**
     * 获取附加数据，下单的时候会传递并原样返回
     * @return string
     */
    public function getAttach()
    {
        return $this->attach;
    }
    
    
    /**
     * 设置附加数据，下单的时候会传递并原样返回
     * @param string $attach
     * @return $this
     */
    public function setAttach($attach)
    {
        $this->attach = trim($attach);
        
        return $this;
    }
    
    
    /**
     * 获取支付方式
     * @return int
     */
    public function getPayType()
    {
        return $this->payType;
    }
    
    
    /**
     * 设置支付方式
     * @param int $payType
     * @return $this
     */
    public function setPayType($payType)
    {
        $this->payType = intval($payType);
        
        return $this;
    }
    
    
    public function toArray()
    {
        return get_object_vars($this);
    }
}