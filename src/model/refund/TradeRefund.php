<?php

namespace BusyPHP\trade\model\refund;

use BusyPHP\exception\AppException;
use BusyPHP\exception\ClassNotFoundException;
use BusyPHP\exception\ClassNotImplementsException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\exception\SQLException;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\util\Transform;
use BusyPHP\Model;
use BusyPHP\trade\interfaces\PayRefund;
use BusyPHP\trade\interfaces\PayRefundNotify;
use BusyPHP\trade\interfaces\PayRefundNotifyResult;
use BusyPHP\trade\interfaces\PayRefundQuery;
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\pay\TradePayField as PayKey;
use BusyPHP\trade\model\TradeConfig;
use Exception;
use think\exception\HttpException;
use think\facade\Log;
use think\Response;
use BusyPHP\trade\model\refund\TradeRefundField as Key;

/**
 * 交易退款模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/11/25 下午12:57 下午 TradeRefund.php $
 */
class TradeRefund extends Model
{
    use TradeConfig;
    
    // +----------------------------------------------------
    // + 退款状态
    // +----------------------------------------------------
    /**
     * 未退款
     */
    const REFUND_STATUS_WAIT = 0;
    
    /**
     * 进入退款列队
     */
    const REFUND_STATUS_IN_REFUND_QUEUE = 1;
    
    /**
     * 退款中
     */
    const REFUND_STATUS_PENDING = 2;
    
    /**
     * 进入查询列队
     */
    const REFUND_STATUS_IN_QUERY_QUEUE = 3;
    
    /**
     * 退款成功
     */
    const REFUND_STATUS_SUCCESS = 8;
    
    /**
     * 退款失败
     */
    const REFUND_STATUS_FAIL = 9;
    
    
    /**
     * 获取状态
     * @param int $val
     * @return array|string
     */
    public static function getStatus($val = null)
    {
        return self::parseVars([
            self::REFUND_STATUS_WAIT            => '未开始',
            self::REFUND_STATUS_IN_REFUND_QUEUE => '等待退款',
            self::REFUND_STATUS_PENDING         => '退款中',
            self::REFUND_STATUS_IN_QUERY_QUEUE  => '查询中',
            self::REFUND_STATUS_SUCCESS         => '退款成功',
            self::REFUND_STATUS_FAIL            => '退款失败',
        ], $val);
    }
    
    
    /**
     * 创建退款订单
     * @param string $orderTradeNo 业务订单号
     * @param int    $orderType 业务类型
     * @param string $orderValue 业务参数
     * @param string $refundRemark 退款原因
     * @param int    $price 退款金额，传0则全退
     * @param bool   $mustRefund 是否强制退款剩余金额
     * @return TradeRefundField
     * @throws ParamInvalidException
     * @throws Exception
     */
    public function joinRefund($orderTradeNo, int $orderType = 0, $orderValue = '', $refundRemark = '', $price = 0, $mustRefund = false) : TradeRefundField
    {
        // 获取订单号前缀配置
        if (!$type = $this->getTradeConfig('refund_no_prefix', 1002)) {
            throw new ParamInvalidException('refund_no_prefix');
        }
        
        $insert = TradeRefundField::init();
        TradePay::init()
            ->updateRefundAmountByCallback($orderTradeNo, function(array $payInfo) use ($orderTradeNo, $orderType, $orderValue, $refundRemark, $price, $mustRefund, $type, $insert) {
                if ($price <= 0) {
                    $price = $payInfo[PayKey::apiPrice()];
                }
                
                if ($price > $payInfo[PayKey::refundAmount()]) {
                    if ($mustRefund) {
                        $price = $payInfo[PayKey::refundAmount()];
                    } else {
                        throw new VerifyException("剩余可退金额为{$payInfo[PayKey::refundAmount()]},不足本次退款", 'refund_amount_not_enough');
                    }
                }
                
                if ($price <= 0) {
                    throw new VerifyException("退款金额为0，无法退款", 'refund_amount_empty');
                }
                
                // 统计累计退款金额是否大于支付订单金额
                $where         = TradeRefundField::init();
                $where->payId  = $payInfo[PayKey::id()];
                $where->status = ['neq', self::REFUND_STATUS_FAIL];
                $totalAmount   = floatval($this->whereof($where)->sum(Key::refundPrice()));
                if ($totalAmount + $price > $payInfo[PayKey::apiPrice()]) {
                    throw new VerifyException('订单累计退款金额超出支付总金额', 'refund_overstep');
                }
                
                // 构建数据
                $insert->userId        = $payInfo[PayKey::userId()];
                $insert->refundNo      = TradeNo::init()->get($type);
                $insert->payId         = $payInfo[PayKey::id()];
                $insert->payTradeNo    = $payInfo[PayKey::payTradeNo()];
                $insert->payApiTradeNo = $payInfo[PayKey::apiTradeNo()];
                $insert->payPrice      = $payInfo[PayKey::apiPrice()];
                $insert->payType       = $payInfo[PayKey::payType()];
                $insert->orderTradeNo  = $payInfo[PayKey::orderTradeNo()];
                $insert->orderType     = $orderType;
                $insert->orderValue    = $orderValue;
                $insert->refundPrice   = $price;
                $insert->createTime    = time();
                $insert->status        = self::REFUND_STATUS_WAIT;
                $insert->remark        = $refundRemark;
                if (!$this->addData($insert)) {
                    throw new SQLException('创建退款订单失败', $this);
                }
                
                return -$price;
            });
        
        return $insert;
    }
    
    
    /**
     * 执行单步退款
     * @param int $id 订单ID
     * @throws Exception
     */
    public function refund($id)
    {
        $this->startTrans();
        try {
            $info = $this->lock(true)->getInfo($id);
            if (!$info['is_refund_in_queue']) {
                throw new AppException('当前订单未进入退款列队，无法执行退款');
            }
            
            $payType           = $info[Key::payType()];
            $update            = TradeRefundField::init();
            $update->startTime = time();
            
            try {
                // 实例化三方退款类
                $class = $this->getTradeConfig("apis.{$payType}.refund");
                if (!$class || !class_exists($class)) {
                    throw new ClassNotFoundException($class, "该支付方式[ {$payType} ]未绑定退款接口");
                }
                
                $api = new $class();
                if (!$api instanceof PayRefund) {
                    throw new ClassNotImplementsException($api, PayRefund::class, "退款类");
                }
                
                // 执行三方退款
                $refundNotifyUrl = $this->getTradeConfig('refund_notify_url');
                if (!$refundNotifyUrl) {
                    throw new VerifyException('没有配置退款异步通知地址', 'refund_notify_url');
                }
                if (false === strpos($refundNotifyUrl, '://')) {
                    throw new VerifyException('退款异步通知地址必须包含http://或https://前缀');
                }
                if (false === strpos($refundNotifyUrl, '__TYPE__')) {
                    throw new VerifyException('退款异步通知地址必须包含__TYPE__变量', 'refund_notify_url');
                }
                
                $api->setTradeRefundInfo(TradeRefundField::parse($info));
                $api->setNotifyUrl(str_replace('__TYPE__', $payType, $refundNotifyUrl));
                $result = $api->refund();
                
                // 需要重新处理
                if ($result->isNeedRehandle()) {
                    $update->status    = self::REFUND_STATUS_WAIT;
                    $update->queueTime = time();
                }
                
                //
                // 退款申请成功
                else {
                    if ($result->getApiRefundNo()) {
                        $update->apiRefundNo = $result->getApiRefundNo();
                    }
                    if ($result->getRefundAccount()) {
                        $update->refundAccount = $result->getRefundAccount();
                    }
                    
                    // 加入列队
                    $update->status    = self::REFUND_STATUS_PENDING;
                    $update->queueTime = 0;
                }
            } catch (Exception $e) {
                // 退款失败
                $update->status       = self::REFUND_STATUS_FAIL;
                $update->failRemark   = $e->getMessage();
                $update->completeTime = time();
                $update->queueTime    = 0;
            }
            
            
            TradePay::init()
                ->updateRefundAmountByCallback($info[Key::orderTradeNo()], function() use ($update, $info, $id) {
                    if (false === $this->whereEnum(Key::id($id))->saveData($update)) {
                        throw new SQLException('更新退款订单失败', $this);
                    }
                    
                    // 还原可退金额
                    if ($update->status == self::REFUND_STATUS_FAIL) {
                        // 触发业务订单事件
                        try {
                            $modal = TradePay::init()->getOrderModel($info[Key::orderTradeNo()]);
                            $modal->setRefundStatus($info[Key::orderTradeNo()], $info[Key::orderType()], $info[Key::orderValue()], false, $update->failRemark);
                        } catch (Exception $e) {
                            $this->log("退款失败处理完成，但通知业务订单失败: {$e->getMessage()}");
                        }
                        
                        return $info[Key::refundPrice()];
                    }
                    
                    return false;
                });
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 记录日志
     * @param string $message 日志内容
     * @param bool   $isError 是否错误的日志内容
     * @param bool   $isThrow 是否停止运行并抛出异常
     * @throws HttpException
     */
    protected function log($message, $isError = false, $isThrow = false)
    {
        Log::record($message, $isError ? 'error' : 'info');
        
        if ($isThrow) {
            throw new HttpException(503, $message);
        }
    }
    
    
    /**
     * 异步通知处理
     * @param string $payType 支付类型
     * @return Response
     * @throws Exception
     */
    public function notify($payType) : Response
    {
        // 获取支付类型
        $payType = trim($payType);
        if (!$payType) {
            $this->log('收到三方退款异步通知, 但无法获取支付类型', true, true);
        }
        
        $payTypes = TradePay::init()->getPayTypes();
        $payName  = $payTypes[$payType]['name'] ?: $payType;
        try {
            $this->log("收到三方退款异步通知, 支付类型为: {$payName}");
            $class = $this->getTradeConfig("apis.{$payType}.refund_notify");
            if (!$class) {
                throw new AppException("{$payName}对应的退款类型:{$payType}未配置异步处理程序");
            }
            if (!class_exists($class)) {
                throw new ClassNotFoundException($class, '退款异步处理类');
            }
            
            // 实例化异步处理类
            $notify = new $class();
            if (!$notify instanceof PayRefundNotify) {
                throw new ClassNotImplementsException($class, PayRefundNotify::class, '异步处理程序');
            }
        } catch (Exception $e) {
            $this->log("异步退款通知处理失败: {$e->getMessage()}", true, true);
        }
        
        try {
            $this->log("开始处理异步退款通知, 通知参数为: {$notify->getRequestString()}");
            
            $this->setRefundStatus($notify->notify());
            $this->log("异步退款处理完成");
            
            return $notify->onSuccess();
        } catch (Exception $e) {
            $this->log("异步通知处理失败: {$e->getMessage()}", true);
            
            return $notify->onError($e);
        }
    }
    
    
    /**
     * 设置退款状态
     * @param PayRefundNotifyResult $result 退款返回数据
     * @return void
     * @throws Exception
     */
    protected function setRefundStatus(PayRefundNotifyResult $result)
    {
        $this->startTrans();
        try {
            if ($result->getRefundNo()) {
                $this->whereEnum(Key::refundNo($result->getRefundNo()));
            } elseif ($result->getApiRefundNo()) {
                $this->whereEnum(Key::apiRefundNo($result->getApiRefundNo()));
            } elseif ($result->getPayTradeNo()) {
                $this->whereEnum(Key::payTradeNo($result->getPayTradeNo()));
            } elseif ($result->getPayApiTradeNo()) {
                $this->whereEnum(Key::payApiTradeNo($result->getPayApiTradeNo()));
            } else {
                throw new AppException('异步退款返回数据中必须返回refund_no,api_refund_no,pay_trade_no,pay_api_trade_no其中的任意一个值');
            }
            
            $info = $this->lock(true)->findInfo();
            if (!$info['is_pending'] && !$info['is_query_in_queue']) {
                throw new AppException('该退款订单已处理过');
            }
            
            TradePay::init()->updateRefundAmountByCallback($info[Key::orderTradeNo()], function() use ($info, $result) {
                // 构建参数
                $update = TradeRefundField::init();
                
                // 需要重新处理的
                if ($result->isNeedRehandle()) {
                    $update->status    = self::REFUND_STATUS_PENDING;
                    $update->queueTime = time();
                } else {
                    $update->status       = $result->isStatus() ? self::REFUND_STATUS_SUCCESS : self::REFUND_STATUS_FAIL;
                    $update->completeTime = time();
                    $update->queueTime    = 0;
                    
                    if ($result->getApiRefundNo()) {
                        $update->apiRefundNo = $result->getApiRefundNo();
                    }
                    
                    if ($result->getRefundAccount()) {
                        $update->refundAccount = $result->getRefundAccount();
                    }
                    
                    if ($result->getErrMsg()) {
                        $update->failRemark = $result->getErrMsg();
                    }
                }
                
                if (false === $this->whereEnum(Key::id($info[Key::id()]))->saveData($update)) {
                    throw new SQLException('更新退款订单失败', $this);
                }
                
                // 触发业务订单事件
                try {
                    $modal = TradePay::init()->getOrderModel($info[Key::orderTradeNo()]);
                    $modal->setRefundStatus($info[Key::orderTradeNo()], $info[Key::orderType()], $info[Key::orderValue()], $result->isStatus(), $result->isStatus() ? $update->refundAccount : $update->failRemark);
                } catch (Exception $e) {
                    $this->log("退款处理完成，但通知业务订单失败: {$e->getMessage()}");
                }
                
                // 退款失败的要还原支付订单可退款金额
                if (!$result->isStatus()) {
                    return $info[Key::refundPrice()];
                }
                
                return false;
            });
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 解析数据记录
     * @param array $list
     * @return array
     */
    public static function parseList($list)
    {
        return parent::parseList($list, function($list) {
            $payTypes = TradePay::init()->getPayTypes();
            foreach ($list as $i => $r) {
                $r['format_create_time']   = Transform::date($r['create_time']);
                $r['format_start_time']    = Transform::date($r['start_time']);
                $r['format_complete_time'] = Transform::date($r['complete_time']);
                $r['pay_type']             = intval($r['pay_type']);
                
                // 状态
                $r['is_success']         = $r['status'] == self::REFUND_STATUS_SUCCESS;
                $r['is_fail']            = $r['status'] == self::REFUND_STATUS_FAIL;
                $r['is_pending']         = $r['status'] == self::REFUND_STATUS_PENDING;
                $r['is_wait']            = $r['status'] == self::REFUND_STATUS_WAIT;
                $r['is_refund_in_queue'] = $r['status'] == self::REFUND_STATUS_IN_REFUND_QUEUE;
                $r['is_query_in_queue']  = $r['status'] == self::REFUND_STATUS_IN_QUERY_QUEUE;
                $r['status_name']        = self::getStatus($r['status']);
                
                // 支付类型
                $types              = $payTypes[$r['pay_type']] ?? [];
                $r['pay_type_name'] = $types['name'] ?? '';
                $r['pay_name']      = $types['alias'] ?? '';
                
                $list[$i] = $r;
            }
            
            return $list;
        });
    }
    
    
    /**
     * 查询任务
     * @param int $delaySec 设置对于重新入队的延迟执行秒数
     * @param int $recoverySec 设置回收超过一定秒没有查询成功的列队的回收秒数
     */
    public function taskQueue($delaySec = 3600, $recoverySec = 3600)
    {
        $info = null;
        $api  = null;
        $this->startTrans();
        try {
            $status    = self::REFUND_STATUS_PENDING;
            $save      = Key::init();
            $delayTime = time() - $delaySec;
            $or        = [];
            $or[]      = "{$save::status()} = {$status} AND {$save::queueTime()} = 0";
            $or[]      = "{$save::status()} = {$status} AND {$save::queueTime()} < {$delayTime}";
            
            $info = $this->lock(true)
                ->whereRaw('(' . implode(') OR (', $or) . ')')
                ->order(Key::id() . ' ASC')
                ->findData();
            if (!$info) {
                goto commit;
            }
            
            $queryClass = $this->getTradeConfig("apis.{$info['pay_type']}.refund_query");
            if (!$queryClass) {
                goto commit;
            }
            
            if (!class_exists($queryClass)) {
                throw new ClassNotFoundException($queryClass, '退款查询类');
            }
            
            $api = new $queryClass();
            if (!$api instanceof PayRefundQuery) {
                throw new ClassNotImplementsException($queryClass, PayRefundQuery::class, '退款查询类');
            }
            
            $save            = Key::init();
            $save->status    = self::REFUND_STATUS_IN_QUERY_QUEUE;
            $save->queueTime = time();
            if (false === $this->whereEnum(Key::id($info[Key::id()]))->saveData($save)) {
                throw new SQLException('更新退款订单状态失败', $this);
            }
            
            commit:
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            $message = $e->getMessage();
            if ($e instanceof SQLException) {
                $message .= " SQL: " . $e->getLastSQL();
                $message .= " ErrorSQL: " . $e->getErrorSQL();
            }
            Log::error("加入查询退款列队失败: {$message}");
        }
        
        
        // 回收超时的订单
        try {
            $result = $this->whereEnum(Key::status(self::REFUND_STATUS_IN_QUERY_QUEUE), Key::queueTime('<', time() - $recoverySec))
                ->saveData([
                    Key::status()    => self::REFUND_STATUS_PENDING,
                    Key::queueTime() => time()
                ]);
            if ($result > 0) {
                Log::info("本次一共回收了{$result}条退款查询超时的订单");
            }
        } catch (Exception $e) {
            // 忽略
        }
        
        
        if (!$api) {
            Log::save();
            
            return;
        }
        
        try {
            $api->setTradeRefundInfo(TradeRefundField::parse($info));
            $result = $api->query();
            $this->setRefundStatus($result);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e instanceof SQLException) {
                $message .= " SQL: " . $e->getLastSQL();
                $message .= " ErrorSQL: " . $e->getErrorSQL();
            }
            Log::error("查询列队失败: {$message}");
        }
        
        
        Log::save();
    }
    
    
    /**
     * 退款任务
     * @param int $delaySec 设置对于重新入队的延迟执行秒数
     * @param int $recoverySec 设置回收超过一定秒数没有执行完成退款队列的回收秒数
     */
    public function taskRefund($delaySec = 3600, $recoverySec = 3600)
    {
        $infoId = 0;
        $this->startTrans();
        try {
            $status    = self::REFUND_STATUS_WAIT;
            $save      = Key::init();
            $delayTime = time() - $delaySec;
            
            $or   = [];
            $or[] = "{$save::status()} = {$status} AND {$save::queueTime()} = 0";
            $or[] = "{$save::status()} = {$status} AND {$save::queueTime()} < {$delayTime}";
            
            $info = $this->field($save::id())
                ->lock(true)
                ->whereRaw('(' . implode(') OR (', $or) . ')')
                ->order($save::id() . ' ASC')
                ->findData();
            if (!$info) {
                goto commit;
            }
            
            $save->status    = self::REFUND_STATUS_IN_REFUND_QUEUE;
            $save->queueTime = time();
            if (false === $this->whereEnum($save::id($info[$save::id()]))->saveData($save)) {
                throw new SQLException('更新退款订单状态失败', $this);
            }
            
            $infoId = $info[$save::id()];
            
            commit:
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            $message = $e->getMessage();
            if ($e instanceof SQLException) {
                $message .= " SQL: " . $e->getLastSQL();
                $message .= " ErrorSQL: " . $e->getErrorSQL();
            }
            Log::error("加入退款列队失败: {$message}");
        }
        
        // 回收超时的订单
        try {
            // 状态为在列队中 且 进入列队的时间 小于 当前时间减去延迟执行时间
            $result = $this->whereEnum(Key::status(self::REFUND_STATUS_IN_REFUND_QUEUE), Key::queueTime('<', time() - $recoverySec))
                ->saveData([
                    Key::status()    => self::REFUND_STATUS_WAIT,
                    Key::queueTime() => time()
                ]);
            if ($result > 0) {
                Log::info("本次一共回收了{$result}条超时退款订单");
            }
        } catch (Exception $e) {
            // 忽略
        }
        
        
        // 没有需要操作的订单则退出
        if ($infoId < 1) {
            Log::save();
            
            return;
        }
        
        
        // 执行退款
        try {
            Log::info("开始处理退款列队: {$infoId}");
            
            $this->refund($infoId);
            
            Log::info("退款列队处理成功: {$infoId}");
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e instanceof SQLException) {
                $message .= " SQL: " . $e->getLastSQL();
                $message .= " ErrorSQL: " . $e->getErrorSQL();
            }
            Log::error("退款列队处理失败: {$message}");
        }
        
        Log::save();
    }
}