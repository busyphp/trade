<?php

namespace BusyPHP\trade\model\refund;

use BusyPHP\model\Field;

/**
 * 交易退款模型字段
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/11/25 下午12:57 下午 TradeRefundField.php $
 * @method static mixed id($op = null, $value = null)
 * @method static mixed userId($op = null, $value = null) 会员ID
 * @method static mixed refundNo($op = null, $value = null) 平台退款单号
 * @method static mixed refundPrice($op = null, $value = null) 退款金额
 * @method static mixed apiRefundNo($op = null, $value = null) 三方退款单号
 * @method static mixed status($op = null, $value = null) 退款状态 0 未处理，1:退款中 8退款成功，9退款失败
 * @method static mixed statusRemark($op = null, $value = null) 状态备注
 * @method static mixed completeTime($op = null, $value = null) 退款完成时间
 * @method static mixed orderTradeNo($op = null, $value = null) 业务订单号
 * @method static mixed payId($op = null, $value = null) 交易订单ID
 * @method static mixed payType($op = null, $value = null) 交易订单支付类型
 * @method static mixed payTradeNo($op = null, $value = null) 交易订单号
 * @method static mixed payApiTradeNo($op = null, $value = null) 交易订单三方支付订单号
 * @method static mixed payPrice($op = null, $value = null) 交易订单实际支付金额
 * @method static mixed remark($op = null, $value = null) 退款原因备注
 * @method static mixed createTime($op = null, $value = null) 创建时间
 * @method static mixed updateTime($op = null, $value = null) 修改时间
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
     * 状态备注
     * @var string
     */
    public $statusRemark;
    
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
    
    /**
     * 创建时间
     * @var int
     */
    public $createTime;
    
    /**
     * 修改时间
     * @var int
     */
    public $updateTime;
}