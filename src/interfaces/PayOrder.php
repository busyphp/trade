<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\exception\PaidException;
use BusyPHP\trade\model\pay\TradePayInfo;
use BusyPHP\trade\model\refund\TradeRefundInfo;

/**
 * 支付关联订单接口，所有依赖支付的订单都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:19 PayOrder.php $
 */
interface PayOrder
{
    /**
     * 获取支付需要的数据
     * @param string $orderTradeNo 业务订单号
     * @return PayOrderPayData
     */
    public function getPayData(string $orderTradeNo) : PayOrderPayData;
    
    
    /**
     * 将订单设为支付成功，内部不要启用事物
     * @param TradePayInfo $tradePayInfo 交易订单数据
     * @return mixed 返回内容可以原样传递到 {@see PayOrderAfter::setPaySuccessAfter()} 中
     * @throws PaidException 如果订单已支付，可以抛出该异常
     */
    public function setPaySuccess(TradePayInfo $tradePayInfo);
    
    
    /**
     * 设置订单退款状态，内部不要启用事物
     * @param TradeRefundInfo $tradeRefundInfo 退款订单数据
     * @param TradePayInfo    $tradePayInfo 交易订单数据
     * @param bool            $status 退款状态，true: 退款成功，false: 退款失败
     * @param string          $remark 退款成功失败说明，成功: 退入账户，失败：失败原因
     * @return mixed 返回内容可以原样传递到 {@see PayOrderAfter::setRefundStatusAfter()} 中
     */
    public function setRefundStatus(TradeRefundInfo $tradeRefundInfo, TradePayInfo $tradePayInfo, bool $status, string $remark);
}
