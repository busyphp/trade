<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\pay;

use BusyPHP\helper\TransHelper;
use BusyPHP\model\Entity;

/**
 * 支付订单信息结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午4:10 TradePayInfo.php $
 * @method static Entity formatCreateTime() 格式化的订单创建时间
 * @method static Entity formatPayTime() 格式化的支付时间
 * @method static Entity isPay() 是否支付
 * @method static Entity orderSuccess() 订单是否操作成功
 * @method static Entity orderFail() 订单是否操作失败
 * @method static Entity payTypeName() 支付方式名称
 * @method static Entity payName() 支付方式别名
 * @method static Entity ticketStatusName() 开票状态名称
 * @method static Entity ticketIsNone() 是否未开票
 * @method static Entity ticketIsPending() 是否开票中
 * @method static Entity ticketIsSuccess() 是否开票成功
 * @method static Entity ticketIsFail() 是否开票失败
 * @method static Entity canApplyTicket() 是否可以申请开票
 * @method static Entity refundStatus() 退款状态
 * @method static Entity refundAmountTotal() 已退款金额
 * @method static Entity canOrderSuccess() 是否可以恢复业务订单
 * @method static Entity canRefund() 是否可以退款
 * @method static Entity canPaySuccess() 是否可以设为支付成功
 */
class TradePayInfo extends TradePayField
{
    /**
     * 格式化的订单创建时间
     * @var string
     */
    public $formatCreateTime;
    
    /**
     * 格式化的支付时间
     * @var string
     */
    public $formatPayTime;
    
    /**
     * 是否支付
     * @var bool
     */
    public $isPay;
    
    /**
     * 订单是否操作成功
     * @var bool
     */
    public $orderSuccess;
    
    /**
     * 订单是否操作失败
     * @var bool
     */
    public $orderFail;
    
    /**
     * 支付方式名称
     * @var string
     */
    public $payTypeName;
    
    /**
     * 支付方式别名
     * @var string
     */
    public $payName;
    
    /**
     * 开票状态名称
     * @var string
     */
    public $ticketStatusName;
    
    /**
     * 是否未开票
     * @var bool
     */
    public $ticketIsNone;
    
    /**
     * 是否开票中
     * @var bool
     */
    public $ticketIsPending;
    
    /**
     * 是否开票成功
     * @var bool
     */
    public $ticketIsSuccess;
    
    /**
     * 是否开票失败
     * @var bool
     */
    public $ticketIsFail;
    
    /**
     * 是否可以申请开票
     * @var bool
     */
    public $canApplyTicket;
    
    /**
     * 退款状态
     * @var bool
     */
    public $refundStatus;
    
    /**
     * 已退款金额
     * @var float
     */
    public $refundAmountTotal;
    
    /**
     * 是否可以恢复业务订单
     * @var bool
     */
    public $canOrderSuccess;
    
    /**
     * 是否可以退款
     * @var bool
     */
    public $canRefund;
    
    /**
     * 是否可以设为支付成功
     * @var bool
     */
    public $canPaySuccess;
    
    /**
     * @var array
     */
    protected static $_ticketStatusList;
    
    /**
     * @var array
     */
    protected static $_payTypes;
    
    /**
     * @var array
     */
    protected static $_otherPayTypes;
    
    
    public function onParseAfter()
    {
        if (!isset(static::$_ticketStatusList)) {
            static::$_ticketStatusList = TradePay::getTicketStatus();
        }
        if (!isset(static::$_payTypes)) {
            static::$_payTypes = TradePay::init()->getPayTypes();
        }
        if (!isset(static::$_otherPayTypes)) {
            static::$_otherPayTypes = TradePay::getOtherPayTypes();
        }
        
        $this->formatCreateTime = TransHelper::date($this->createTime);
        $this->formatPayTime    = $this->payTime ? TransHelper::date($this->payTime) : '';
        $this->payType          = (int) $this->payType;
        $this->isPay            = $this->payTime > 0;
        
        // 状态
        $this->orderStatus  = (int) $this->orderStatus;
        $this->orderSuccess = $this->orderStatus == TradePay::ORDER_STATUS_SUCCESS;
        $this->orderFail    = $this->orderStatus == TradePay::ORDER_STATUS_FAIL;
        
        // 支付类型
        if ($types = static::$_payTypes[$this->payType] ?? []) {
            $this->payTypeName = $types['name'] ?? '';
            $this->payName     = $types['alias'] ?? '';
        } else {
            $name              = static::$_otherPayTypes[$this->payType] ?? [];
            $this->payTypeName = $name;
            $this->payName     = '其它';
        }
        
        // 开票类型
        $this->ticketStatus     = (int) $this->ticketStatus;
        $this->ticketStatusName = static::$_ticketStatusList[$this->ticketStatus] ?? '';
        $this->ticketIsNone     = $this->ticketStatus == TradePay::TICKET_STATUS_NONE;
        $this->ticketIsPending  = $this->ticketStatus == TradePay::TICKET_STATUS_PENDING;
        $this->ticketIsSuccess  = $this->ticketStatus == TradePay::TICKET_STATUS_SUCCESS;
        $this->ticketIsFail     = $this->ticketStatus == TradePay::TICKET_STATUS_FAIL;
        
        // 是否可以申请开票
        $this->canApplyTicket = ($this->isPay && $this->ticketIsNone) || $this->ticketIsFail;
        
        // 已退款金额
        $this->refundAmountTotal = $this->apiPrice - $this->refundAmount;
        $this->refundStatus      = TradePay::REFUND_STATUS_NONE;
        
        // 已全额退款
        if ((float) $this->refundAmount == 0) {
            $this->refundStatus = TradePay::REFUND_STATUS_WHOLE;
        }
        
        //
        // 部分退款
        elseif ($this->refundAmount > 0 && $this->refundAmount < $this->apiPrice) {
            $this->refundStatus = TradePay::REFUND_STATUS_PART;
        }
        
        $this->canRefund       = $this->isPay && $this->refundAmount > 0;
        $this->canOrderSuccess = $this->isPay && $this->orderFail;
        $this->canPaySuccess   = !$this->isPay;
    }
}