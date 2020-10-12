<?php

namespace BusyPHP\trade\interfaces;

/**
 * 支付下单需要需要的数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:26 下午 PayOrderPayData.php $
 */
class PayOrderPayData
{
    private $body         = '';
    
    private $orderTradeNo = '';
    
    private $price        = 0;
    
    private $isPay        = false;
    
    
    /**
     * 获取支付商品信息
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    
    
    /**
     * 设置支付商品信息
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = trim($body);
        
        return $this;
    }
    
    
    /**
     * 获取平台支付订单号
     * @return string
     */
    public function getOrderTradeNo()
    {
        return $this->orderTradeNo;
    }
    
    
    /**
     * 设置平台支付订单号
     * @param string $orderTradeNo
     * @return $this
     */
    public function setOrderTradeNo($orderTradeNo)
    {
        $this->orderTradeNo = trim($orderTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付金额
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }
    
    
    /**
     * 设置支付金额，单位元，保留2位小数
     * @param string $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = floatval($price);
        
        return $this;
    }
    
    
    /**
     * 设置是否已支付
     * @param bool $isPay
     */
    public function setIsPay($isPay)
    {
        $this->isPay = $isPay;
    }
    
    
    /**
     * 获取是否已支付
     * @return bool
     */
    public function isPay()
    {
        return $this->isPay;
    }
}