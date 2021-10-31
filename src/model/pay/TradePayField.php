<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\pay;

use BusyPHP\model\Entity;
use BusyPHP\model\Field;

/**
 * 支付模型字段
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午4:05 TradePayField.php $
 * @method static Entity id($op = null, $value = null)
 * @method static Entity payTradeNo($op = null, $value = null) 交易号
 * @method static Entity createTime($op = null, $value = null) 创建时间
 * @method static Entity updateTime($op = null, $value = null) 更新时间
 * @method static Entity userId($op = null, $value = null) 会员ID
 * @method static Entity price($op = null, $value = null) 需支付金额
 * @method static Entity title($op = null, $value = null) 支付描述
 * @method static Entity orderTradeNo($op = null, $value = null) 业务订单号
 * @method static Entity orderStatus($op = null, $value = null) 业务订单支付状态 0 未支付, 1 支付成功, 2 支付失败
 * @method static Entity orderStatusRemark($op = null, $value = null) 业务订单状态备注
 * @method static Entity orderRetryCount($op = null, $value = null) 业务订单失败的重试次数
 * @method static Entity orderRetryTime($op = null, $value = null) 业务订单失败的重试时间
 * @method static Entity apiTradeNo($op = null, $value = null) 三方平台的支付订单号
 * @method static Entity apiPrice($op = null, $value = null) 三方平台返回支付的金额
 * @method static Entity payRemark($op = null, $value = null) 三方支付说明
 * @method static Entity payTime($op = null, $value = null) 支付时间
 * @method static Entity payType($op = null, $value = null) 支付类型
 * @method static Entity refundAmount($op = null, $value = null) 剩余可退款金额
 * @method static Entity ticketStatus($op = null, $value = null) 开票状态
 */
class TradePayField extends Field
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * 交易号
     * @var string
     */
    public $payTradeNo;
    
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
     * 需支付金额
     * @var float
     */
    public $price;
    
    /**
     * 支付描述
     * @var string
     */
    public $title;
    
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
     * 业务订单失败的重试次数
     * @var int
     */
    public $orderRetryCount;
    
    /**
     * 业务订单失败的重试时间
     * @var int
     */
    public $orderRetryTime;
    
    /**
     * 三方平台的支付订单号
     * @var string
     */
    public $apiTradeNo;
    
    /**
     * 三方平台返回支付的金额
     * @var float
     */
    public $apiPrice;
    
    /**
     * 三方支付返回的用户支付的银行账户信息
     * @var string
     */
    public $payRemark;
    
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
     * 剩余可退款金额
     * @var float
     */
    public $refundAmount;
    
    /**
     * 开票状态
     * @var int
     */
    public $ticketStatus;
}