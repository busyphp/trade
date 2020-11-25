<?php

namespace BusyPHP\trade\model\pay;

use BusyPHP\exception\VerifyException;
use BusyPHP\model\Field;

/**
 * 支付模型字段
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/6/17 下午4:44 下午 TradePayField.php $
 * @method static mixed id($op = null, $value = null)
 * @method static mixed payTradeNo($op = null, $value = null) 交易号
 * @method static mixed createTime($op = null, $value = null) 创建时间
 * @method static mixed updateTime($op = null, $value = null) 更新时间
 * @method static mixed userId($op = null, $value = null) 会员ID
 * @method static mixed price($op = null, $value = null) 需支付金额
 * @method static mixed title($op = null, $value = null) 支付描述
 * @method static mixed orderTradeNo($op = null, $value = null) 业务订单号
 * @method static mixed orderStatus($op = null, $value = null) 业务订单支付状态 0 未支付, 1 支付成功, 2 支付失败
 * @method static mixed orderStatusRemark($op = null, $value = null) 业务订单状态备注
 * @method static mixed orderRetryCount($op = null, $value = null) 业务订单失败的重试次数
 * @method static mixed orderRetryTime($op = null, $value = null) 业务订单失败的重试时间
 * @method static mixed apiTradeNo($op = null, $value = null) 三方平台的支付订单号
 * @method static mixed apiPrice($op = null, $value = null) 三方平台返回支付的金额
 * @method static mixed apiBank($op = null, $value = null) 三方支付返回的用户支付的银行账户信息
 * @method static mixed payTime($op = null, $value = null) 支付时间
 * @method static mixed payType($op = null, $value = null) 支付类型
 * @method static mixed refundAmount($op = null, $value = null) 剩余可退款金额
 * @method static mixed ticketStatus($op = null, $value = null) 开票状态
 */
class TradePayField extends Field
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * 创建时间
     * @var int
     */
    public $createTime;
    
    /**
     * 更新时间
     * @var int
     */
    public $updateTime;
    
    /**
     * 会员ID
     * @var int
     */
    public $userId;
    
    /**
     * 交易号
     * @var string
     */
    public $payTradeNo;
    
    /**
     * 支付描述
     * @var string
     */
    public $title;
    
    /**
     * 需支付金额
     * @var float
     */
    public $price;
    
    /**
     * 业务订单号
     * @var string
     */
    public $orderTradeNo;
    
    /**
     * 业务订单支付状态 0 未支付, 1 支付成功, 2 支付失败
     * @var int
     */
    public $orderStatus;
    
    /**
     * 业务订单状态备注
     * @var string
     */
    public $orderStatusRemark;
    
    /**
     * 业务订单失败的重试时间
     * @var int
     */
    public $orderRetryTime;
    
    /**
     * 业务订单失败的重试次数
     * @var int
     */
    public $orderRetryCount;
    
    /**
     * 三方平台返回的支付订单号
     * @var string
     */
    public $apiTradeNo;
    
    /**
     * 三方平台返回的支付金额
     * @var float
     */
    public $apiPrice;
    
    /**
     * 三方支付返回的用户支付的银行账户信息
     * @var string
     */
    public $apiBank;
    
    /**
     * 支付时间
     * @var int
     */
    public $payTime;
    
    /**
     * 支付类型
     * @var int
     */
    public $payType;
    
    /**
     * 剩余的可退款金额
     * @var float
     */
    public $refundAmount;
    
    /**
     * 开票状态
     * @var int
     */
    public $ticketStatus;
    
    
    /**
     * 设置会员ID
     * @param int $userId
     * @return TradePayField
     */
    public function setUserId($userId)
    {
        $this->userId = floatval($userId);
        
        return $this;
    }
    
    
    /**
     * 设置订单金额
     * @param float $price
     * @return TradePayField
     */
    public function setPrice($price)
    {
        $this->price = floatval($price);
        
        return $this;
    }
    
    
    /**
     * 设置订单交易号
     * @param string $orderTradeNo
     * @return TradePayField
     */
    public function setOrderTradeNo($orderTradeNo)
    {
        $this->orderTradeNo = trim($orderTradeNo);
        
        return $this;
    }
    
    
    /**
     * 设置支付描述
     * @param string $title
     * @return TradePayField
     */
    public function setTitle($title)
    {
        $this->title = trim($title);
        
        return $this;
    }
    
    
    /**
     * 设置支付方式
     * @param int $payType
     * @throws VerifyException
     */
    public function setPayType($payType)
    {
        $this->payType = intval($payType);
        if ($this->payType < 1) {
            throw new VerifyException('请选择支付方式', 'pay_type');
        }
    }
}