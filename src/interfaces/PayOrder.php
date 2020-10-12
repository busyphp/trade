<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;

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
     * @throws AppException
     */
    public function getPayData($orderTradeNo);
    
    
    /**
     * 将订单设为支付成功
     * @param string $orderTradeNo 业务订单号
     * @param int    $payId 支付订单ID
     * @param string $payTradeNo 支付订单交易号
     * @param float  $payPrice 实际支付金额
     * @return false|true 返回false代表已经支付过，返回true代表操作成功
     * @throws AppException
     */
    public function setPaySuccess($orderTradeNo, $payId, $payTradeNo, $payPrice);
    
    
    /**
     * 设置订单退款状态
     * @param string $payTradeNo 平台支付订单号
     * @param int    $payId 平台支付ID
     * @param bool   $status 退款状态，成功还是失败
     * @param string $customParam 自定义业务参数 todo ?
     * @param string $remark 退款备注，如失败原因
     * @throws AppException
     */
    public function setRefundStatus($payTradeNo, $payId, $status, $customParam = '', $remark = '');
}
