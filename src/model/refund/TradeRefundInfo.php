<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\refund;

use BusyPHP\helper\TransHelper;
use BusyPHP\model\Entity;
use BusyPHP\trade\model\pay\TradePay;

/**
 * 退款信息结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午5:24 TradeRefundInfo.php $
 * @method static Entity formatCreateTime() 格式化的创建时间
 * @method static Entity formatStartTime() 格式化的开始退款时间
 * @method static Entity formatCompleteTime() 格式化的完成时间
 * @method static Entity isSuccess() 是否退款成功
 * @method static Entity isFail() 是否退款失败
 * @method static Entity isPending() 是否退款中
 * @method static Entity isWait() 是否等待退款
 * @method static Entity isRefundInQueue() 是否进入退款列队
 * @method static Entity isQueryInQueue() 是否进入查询列队
 * @method static Entity statusName() 状态名称
 * @method static Entity payTypeName() 支付方式名称
 * @method static Entity payName() 支付方式别名
 */
class TradeRefundInfo extends TradeRefundField
{
    /**
     * 格式化的创建时间
     * @var string
     */
    public $formatCreateTime;
    
    /**
     * 格式化的开始退款时间
     * @var string
     */
    public $formatStartTime;
    
    /**
     * 格式化的完成时间
     * @var string
     */
    public $formatCompleteTime;
    
    /**
     * 是否退款成功
     * @var bool
     */
    public $isSuccess;
    
    /**
     * 是否退款失败
     * @var bool
     */
    public $isFail;
    
    /**
     * 是否退款中
     * @var bool
     */
    public $isPending;
    
    /**
     * 是否等待退款
     * @var bool
     */
    public $isWait;
    
    /**
     * 是否进入退款列队
     * @var bool
     */
    public $isRefundInQueue;
    
    /**
     * 是否进入查询列队
     * @var bool
     */
    public $isQueryInQueue;
    
    /**
     * 状态名称
     * @var string
     */
    public $statusName;
    
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
     * @var array
     */
    protected static $_status;
    
    /**
     * @var array
     */
    protected static $_payTypes;
    
    
    public function onParseAfter()
    {
        if (!isset(static::$_status)) {
            static::$_status = TradeRefund::getStatus();
        }
        if (!isset(static::$_payTypes)) {
            static::$_payTypes = TradePay::init()->getPayTypes();
        }
        
        $this->formatCreateTime   = $this->createTime > 0 ? TransHelper::date($this->createTime) : '';
        $this->formatStartTime    = $this->startTime > 0 ? TransHelper::date($this->startTime) : '';
        $this->formatCompleteTime = $this->completeTime > 0 ? TransHelper::date($this->completeTime) : '';
        $this->payType            = (int) $this->payType;
        $this->status             = (int) $this->status;
        
        $this->isSuccess       = $this->status == TradeRefund::REFUND_STATUS_SUCCESS;
        $this->isFail          = $this->status == TradeRefund::REFUND_STATUS_FAIL;
        $this->isPending       = $this->status == TradeRefund::REFUND_STATUS_PENDING;
        $this->isWait          = $this->status == TradeRefund::REFUND_STATUS_WAIT;
        $this->isQueryInQueue  = $this->status == TradeRefund::REFUND_STATUS_IN_QUERY_QUEUE;
        $this->isRefundInQueue = $this->status == TradeRefund::REFUND_STATUS_IN_REFUND_QUEUE;
        $this->statusName      = static::$_status[$this->status] ?? '';
        
        // 支付类型
        $types             = static::$_payTypes[$this->payType] ?? [];
        $this->payTypeName = $types['name'] ?? '';
        $this->payName     = $types['alias'] ?? '';
    }
}