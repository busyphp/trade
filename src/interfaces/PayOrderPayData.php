<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 支付下单需要需要的数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:20 PayOrderPayData.php $
 */
class PayOrderPayData extends ObjectOption
{
    /**
     * 支付商品信息
     * @var string
     */
    private $body = '';
    
    /**
     * 业务订单号
     * @var string
     */
    private $orderTradeNo = '';
    
    /**
     * 支付金额
     * @var float
     */
    private $price = 0;
    
    /**
     * 是否已支付
     * @var bool
     */
    private $pay = false;
    
    
    /**
     * 获取支付商品信息
     * @return string
     */
    public function getBody() : string
    {
        return $this->body;
    }
    
    
    /**
     * 设置支付商品信息
     * @param string $body
     * @return $this
     */
    public function setBody(string $body) : self
    {
        $this->body = trim($body);
        
        return $this;
    }
    
    
    /**
     * 获取平台支付订单号
     * @return string
     */
    public function getOrderTradeNo() : string
    {
        return $this->orderTradeNo;
    }
    
    
    /**
     * 设置平台支付订单号
     * @param string $orderTradeNo
     * @return $this
     */
    public function setOrderTradeNo(string $orderTradeNo) : self
    {
        $this->orderTradeNo = trim($orderTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取支付金额
     * @return float
     */
    public function getPrice() : float
    {
        return $this->price;
    }
    
    
    /**
     * 设置支付金额，单位元，保留2位小数
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price) : self
    {
        $this->price = $price;
        
        return $this;
    }
    
    
    /**
     * 设置是否已支付
     * @param bool $pay
     * @return $this
     */
    public function setPay(bool $pay) : self
    {
        $this->pay = $pay;
        
        return $this;
    }
    
    
    /**
     * 是否已支付
     * @return bool
     */
    public function isPay() : bool
    {
        return $this->pay;
    }
}