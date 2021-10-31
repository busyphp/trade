<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 支付异步返回结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/27 下午下午9:28 PayNotifyResult.php $
 */
class PayNotifyResult extends ObjectOption
{
    /**
     * 平台支付单号
     * @var string
     */
    private $payTradeNo = '';
    
    /**
     * 三方支付单号
     * @var string
     */
    private $apiTradeNo = '';
    
    /**
     * 真实支付金额
     * @var float
     */
    private $apiPrice = 0;
    
    /**
     * 支付描述
     * @var string
     */
    private $payRemark = '';
    
    /**
     * 支付附加数据
     * @var string
     */
    private $attach = '';
    
    /**
     * 支付类型
     * @var int
     */
    private $payType = 0;
    
    /**
     * 支付时间戳
     * @var int
     */
    private $payTime = 0;
    
    
    /**
     * 获取平台支付订单号
     * @return string
     */
    public function getPayTradeNo() : string
    {
        return $this->payTradeNo;
    }
    
    
    /**
     * 设置平台支付订单号
     * @param string $payTradeNo
     * @return $this
     */
    public function setPayTradeNo(string $payTradeNo) : self
    {
        $this->payTradeNo = trim($payTradeNo);
        
        return $this;
    }
    
    
    /**
     * 获取三方支付订单号
     * @return string
     */
    public function getApiTradeNo() : string
    {
        return $this->apiTradeNo;
    }
    
    
    /**
     * 设置三方返回的支付订单号
     * @param string $apiTradeNo
     * @return $this
     */
    public function setApiTradeNo(string $apiTradeNo) : self
    {
        $this->apiTradeNo = trim($apiTradeNo);
        
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
     * 设置支付金额
     * @param float $apiPrice
     * @return $this
     */
    public function setApiPrice(float $apiPrice) : self
    {
        $this->apiPrice = $apiPrice;
        
        return $this;
    }
    
    
    /**
     * 获取支付银行名称
     * @return string
     */
    public function getPayRemark() : string
    {
        return $this->payRemark;
    }
    
    
    /**
     * 设置支付银行名称
     * @param string $payRemark
     * @return $this
     */
    public function setPayRemark(string $payRemark) : self
    {
        $this->payRemark = trim($payRemark);
        
        return $this;
    }
    
    
    /**
     * 获取附加数据，下单的时候会传递并原样返回
     * @return string
     */
    public function getAttach() : string
    {
        return $this->attach;
    }
    
    
    /**
     * 设置附加数据，下单的时候会传递并原样返回
     * @param string $attach
     * @return $this
     */
    public function setAttach(string $attach) : self
    {
        $this->attach = trim($attach);
        
        return $this;
    }
    
    
    /**
     * 获取支付方式
     * @return int
     */
    public function getPayType() : int
    {
        return $this->payType;
    }
    
    
    /**
     * 设置支付方式
     * @param int $payType
     * @return $this
     */
    public function setPayType(int $payType) : self
    {
        $this->payType = $payType;
        
        return $this;
    }
    
    
    /**
     * 获取支付成功时间
     * @return int
     */
    public function getPayTime() : int
    {
        return $this->payTime;
    }
    
    
    /**
     * 设置支付成功时间
     * @param int $payTime
     * @return $this
     */
    public function setPayTime(int $payTime) : self
    {
        $this->payTime = $payTime;
        
        return $this;
    }
    
    
    /**
     * 设置支付成功时间
     * @param string $date
     * @return $this
     */
    public function setPayDate(string $date) : self
    {
        return $this->setPayTime((int) strtotime($date));
    }
}