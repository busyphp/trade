<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;
use Exception;

/**
 * 支付关联订单接口，所有依赖支付的订单都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:39 下午 PayOrder.php $
 */
interface PayOrder
{
    /**
     * 获取支付需要的数据
     * @param string $orderTradeNo 业务订单号
     * @return PayOrderPayData
     * @throws Exception
     */
    public function getPayData($orderTradeNo) : PayOrderPayData;
    
    
    /**
     * 将订单设为支付成功，内部不要启用事物
     * @param string $orderTradeNo 业务订单号
     * @param int    $payId 支付订单ID
     * @param string $payTradeNo 支付订单交易号
     * @param float  $payPrice 实际支付金额
     * @return bool 返回false代表已经支付过，返回true代表操作成功
     * @throws Exception
     */
    public function setPaySuccess($orderTradeNo, $payId, $payTradeNo, $payPrice) : bool;
    
    
    /**
     * 设置订单退款状态，内部不要启用事物
     * @param string $orderTradeNo 业务订单号
     * @param int    $orderType 业务类型
     * @param string $orderValue 业务参数
     * @param bool   $status 退款状态，成功还是失败
     * @param string $statusRemark 退款状态原因，成功为退款退入的账户信息，失败为失败原因
     */
    public function setRefundStatus($orderTradeNo, $orderType, $orderValue, bool $status, $statusRemark = '');
}
