<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\refund;

use BusyPHP\model\Entity;
use BusyPHP\model\Field;

/**
 * 交易退款模型字段
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午5:23 TradeRefundField.php $
 * @method static Entity id($op = null, $value = null)
 * @method static Entity userId($op = null, $value = null) 会员ID
 * @method static Entity refundNo($op = null, $value = null) 平台退款单号
 * @method static Entity refundPrice($op = null, $value = null) 退款金额
 * @method static Entity apiRefundNo($op = null, $value = null) 三方退款单号
 * @method static Entity status($op = null, $value = null) 退款状态 0 未处理，1:退款中 8退款成功，9退款失败
 * @method static Entity failRemark($op = null, $value = null) 失败备注
 * @method static Entity refundAccount($op = null, $value = null) 退入账户说明
 * @method static Entity createTime($op = null, $value = null) 创建时间
 * @method static Entity startTime($op = null, $value = null) 开始执行退款时间
 * @method static Entity completeTime($op = null, $value = null) 退款完成时间
 * @method static Entity orderTradeNo($op = null, $value = null) 业务订单号
 * @method static Entity orderType($op = null, $value = null) 业务类型
 * @method static Entity orderValue($op = null, $value = null) 业务参数
 * @method static Entity payId($op = null, $value = null) 交易订单ID
 * @method static Entity payType($op = null, $value = null) 交易订单支付类型
 * @method static Entity payTradeNo($op = null, $value = null) 交易订单号
 * @method static Entity payApiTradeNo($op = null, $value = null) 交易订单三方支付订单号
 * @method static Entity payPrice($op = null, $value = null) 交易订单实际支付金额
 * @method static Entity remark($op = null, $value = null) 退款原因备注
 */
class TradeRefundField extends Field
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * 会员ID
     * @var int
     */
    public $userId;
    
    /**
     * 平台退款单号
     * @var string
     */
    public $refundNo;
    
    /**
     * 退款金额
     * @var float
     */
    public $refundPrice;
    
    /**
     * 三方退款单号
     * @var string
     */
    public $apiRefundNo;
    
    /**
     * 退款状态 0 未处理，1:退款中 8退款成功，9退款失败
     * @var int
     */
    public $status;
    
    /**
     * 失败备注
     * @var string
     */
    public $failRemark;
    
    /**
     * 退入账户说明
     * @var string
     */
    public $refundAccount;
    
    /**
     * 创建时间
     * @var int
     */
    public $createTime;
    
    /**
     * 开始执行退款时间
     * @var int
     */
    public $startTime;
    
    /**
     * 退款完成时间
     * @var int
     */
    public $completeTime;
    
    /**
     * 业务订单号
     * @var string
     */
    public $orderTradeNo;
    
    /**
     * 业务类型
     * @var int
     */
    public $orderType;
    
    /**
     * 业务参数
     * @var string
     */
    public $orderValue;
    
    /**
     * 交易订单ID
     * @var int
     */
    public $payId;
    
    /**
     * 交易订单支付类型
     * @var int
     */
    public $payType;
    
    /**
     * 交易订单号
     * @var string
     */
    public $payTradeNo;
    
    /**
     * 交易订单三方支付订单号
     * @var string
     */
    public $payApiTradeNo;
    
    /**
     * 交易订单实际支付金额
     * @var float
     */
    public $payPrice;
    
    /**
     * 退款原因备注
     * @var string
     */
    public $remark;
}