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
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\TradeConfig;
use Exception;
use think\exception\HttpException;
use think\facade\Log;
use think\Response;

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
     * 退款中
     */
    const REFUND_STATUS_PENDING = 1;
    
    /**
     * 退款成功
     */
    const REFUND_STATUS_SUCCESS = 8;
    
    /**
     * 退款失败
     */
    const REFUND_STATUS_FAIL = 9;
    
    
    /**
     * 创建退款订单
     * @param string $orderTradeNo 业务订单号
     * @param string $refundRemark 退款原因
     * @param int    $price 退款金额，传0则全退
     * @param bool   $mustRefund 是否强制退款剩余金额
     * @return string
     * @throws AppException
     * @throws ParamInvalidException
     * @throws Exception
     */
    public function insertData($orderTradeNo, $refundRemark = '', $price = 0, $mustRefund = false)
    {
        // 获取订单号前缀配置
        if (!$type = $this->getTradeConfig('refund_no_prefix', 1002)) {
            throw new ParamInvalidException('refund_no_prefix');
        }
        
        $insert = TradeRefundField::init();
        TradePay::init()
            ->updateRefundAmountByCallback($orderTradeNo, function(array $payInfo) use ($orderTradeNo, $refundRemark, $price, $mustRefund, $type, $insert) {
                if ($price <= 0) {
                    $price = $payInfo['api_price'];
                }
                
                if ($price > $payInfo['refund_amount']) {
                    if ($mustRefund) {
                        $price = $payInfo['refund_amount'];
                    } else {
                        throw new VerifyException("剩余可退金额为{$payInfo['refund_amount']},不足本次退款", 'refund_amount_not_enough');
                    }
                }
                
                // 统计累计退款金额是否大于支付订单金额
                $where         = TradeRefundField::init();
                $where->payId  = $payInfo['id'];
                $where->status = [
                    'in',
                    [self::REFUND_STATUS_WAIT, self::REFUND_STATUS_PENDING, self::REFUND_STATUS_SUCCESS]
                ];
                $totalAmount   = floatval($this->whereof($where)->sum('refund_price'));
                if ($totalAmount + $price > $payInfo['api_price']) {
                    throw new VerifyException('订单累计退款金额超出支付总金额', 'refund_overstep');
                }
                
                // 构建数据
                $insert->userId        = $payInfo['user_id'];
                $insert->refundNo      = TradeNo::init()->get($type);
                $insert->payId         = $payInfo['id'];
                $insert->payTradeNo    = $payInfo['pay_trade_no'];
                $insert->payApiTradeNo = $payInfo['api_trade_no'];
                $insert->payPrice      = $payInfo['api_price'];
                $insert->payType       = $payInfo['pay_type'];
                $insert->orderTradeNo  = $payInfo['order_trade_no'];
                $insert->refundPrice   = $price;
                $insert->createTime    = time();
                $insert->updateTime    = time();
                $insert->status        = self::REFUND_STATUS_WAIT;
                $insert->remark        = $refundRemark;
                
                if (!$this->addData($insert)) {
                    throw new SQLException('创建退款订单失败', $this);
                }
                
                return -$price;
            });
        
        return $insert->refundNo;
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
            $info    = $this->lock(true)->getInfo($id);
            $payType = $info['pay_type'];
            
            $update             = TradeRefundField::init();
            $update->updateTime = time();
            $update->startTime  = time();
            
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
                $result              = $api->refund();
                $update->apiRefundNo = $result->getApiRefundTradeNo();
                $update->status      = self::REFUND_STATUS_PENDING;
            } catch (Exception $e) {
                $update->status       = self::REFUND_STATUS_FAIL;
                $update->statusRemark = $e->getMessage();
                $update->completeTime = time();
            }
            
            if (false === $this->where('id', '=', $id)->saveData($update)) {
                throw new SQLException('更新退款订单失败', $this);
            }
            
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
     * @param PayRefundNotifyResult $result
     * @return void
     * @throws Exception
     */
    protected function setRefundStatus(PayRefundNotifyResult $result)
    {
        $this->startTrans();
        try {
            if ($result->getRefundNo()) {
                $this->where('refund_no', '=', $result->getRefundNo());
            } elseif ($result->getApiRefundNo()) {
                $this->where('api_refund_no', '=', $result->getApiRefundNo());
            } elseif ($result->getPayTradeNo()) {
                $this->where('pay_trade_no', '=', $result->getPayTradeNo());
            } elseif ($result->getPayApiTradeNo()) {
                $this->where('pay_api_trade_no', '=', $result->getPayApiTradeNo());
            } else {
                throw new AppException('异步退款返回数据中必须返回refund_no,api_refund_no,pay_trade_no,pay_api_trade_no其中的任意一个值');
            }
            
            $info = $this->lock(true)->findInfo();
            if (!$info['is_pending']) {
                throw new AppException('该退款订单已处理过');
            }
            
            // 锁定支付记录表
            TradePay::init()->lock(true)->getInfo($info['pay_id']);
            
            $update               = TradeRefundField::init();
            $update->updateTime   = time();
            $update->completeTime = time();
            $update->status       = $result->isStatus() ? self::REFUND_STATUS_SUCCESS : self::REFUND_STATUS_FAIL;
            $update->statusRemark = $result->isStatus() ? $result->getRefundAccount() : $result->getErrMsg();
            $update->apiRefundNo  = $result->getApiRefundNo();
            if (false === $this->where('id', '=', $info['id'])->saveData($update)) {
                throw new SQLException('更新退款订单失败', $this);
            }
            
            // 退款失败的要还原支付订单可退款金额
            if (!$result->isStatus()) {
                TradePay::init()->updateRefundAmount($info['pay_id'], $info['refund_price']);
            }
            
            // 触发业务订单事件
            try {
                $modal = TradePay::init()->getOrderModel($info['order_trade_no']);
                $modal->setRefundStatus($info['order_trade_no'], $result->isStatus(), $update->statusRemark);
            } catch (Exception $e) {
                $this->log("退款处理完成，但通知业务订单失败: {$e->getMessage()}");
            }
            
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
                $r['format_create_time'] = Transform::date($r['create_time']);
                $r['pay_type']           = intval($r['pay_type']);
                
                // 状态
                $r['is_success'] = $r['status'] == self::REFUND_STATUS_SUCCESS;
                $r['is_fail']    = $r['status'] == self::REFUND_STATUS_FAIL;
                $r['is_pending'] = $r['status'] == self::REFUND_STATUS_PENDING;
                $r['is_wait']    = $r['status'] == self::REFUND_STATUS_WAIT;
                
                // 支付类型
                $types              = $payTypes[$r['pay_type']] ?? [];
                $r['pay_type_name'] = $types['name'] ?? '';
                $r['pay_name']      = $types['alias'] ?? '';
                
                $list[$i] = $r;
            }
            
            return $list;
        });
    }
}