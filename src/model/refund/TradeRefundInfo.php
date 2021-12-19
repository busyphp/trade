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
 * @method static Entity statusName() 状态名称
 * @method static Entity payTypeName() 支付方式名称
 * @method static Entity payName() 支付方式别名
 * @method static Entity canSuccess() 是否可以手动操作为成功
 * @method static Entity canQuery() 是否可以手动查询
 * @method static Entity canRetry() 是否可以重试退款
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
     * 是否等待手动处理
     * @var bool
     */
    public $isWaitManual;
    
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
     * 设置可以手动设为成功
     * @var bool
     */
    public $canSuccess;
    
    /**
     * 是否可以手动查询
     * @var bool
     */
    public $canQuery;
    
    /**
     * 是否可以重试退款
     * @var bool
     */
    public $canRetry;
    
    /**
     * @var array
     */
    protected static $_status;
    
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
        if (!isset(static::$_status)) {
            static::$_status = TradeRefund::getStatus();
        }
        if (!isset(static::$_payTypes)) {
            static::$_payTypes = TradePay::init()->getPayTypes();
        }
        if (!isset(static::$_otherPayTypes)) {
            static::$_otherPayTypes = TradePay::getOtherPayTypes();
        }
        
        $this->formatCreateTime   = $this->createTime > 0 ? TransHelper::date($this->createTime) : '';
        $this->formatStartTime    = $this->startTime > 0 ? TransHelper::date($this->startTime) : '';
        $this->formatCompleteTime = $this->completeTime > 0 ? TransHelper::date($this->completeTime) : '';
        $this->payType            = (int) $this->payType;
        $this->status             = (int) $this->status;
        
        $this->isSuccess    = $this->status == TradeRefund::REFUND_STATUS_SUCCESS;
        $this->isFail       = $this->status == TradeRefund::REFUND_STATUS_FAIL;
        $this->isPending    = $this->status == TradeRefund::REFUND_STATUS_PENDING;
        $this->isWait       = $this->status == TradeRefund::REFUND_STATUS_WAIT;
        $this->isWaitManual = $this->status == TradeRefund::REFUND_STATUS_WAIT_MANUAL;
        $this->statusName   = static::$_status[$this->status] ?? '';
        
        $this->canRetry   = $this->isFail;
        $this->canSuccess = $this->isFail || $this->isWaitManual;
        $this->canQuery   = ($this->isPending || $this->isSuccess) && !TradePay::checkPayTypeIsManual($this->payType);
        
        // 支付类型
        if ($types = static::$_payTypes[$this->payType] ?? []) {
            $this->payTypeName = $types['name'] ?? '';
            $this->payName     = $types['alias'] ?? '';
        } else {
            $name              = static::$_otherPayTypes[$this->payType] ?? [];
            $this->payTypeName = $name;
            $this->payName     = '其它';
        }
    }
}