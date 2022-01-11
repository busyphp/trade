<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\pay\TradePayInfo;
use BusyPHP\trade\model\refund\TradeRefundInfo;

/**
 * 支付关联订单后置操作接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:19 PayOrderAfter.php $
 */
interface PayOrderAfter
{
    /**
     * 将订单设为支付成功后置操作
     * @param TradePayInfo $tradePayInfo 交易订单数据
     */
    public function setPaySuccessAfter(TradePayInfo $tradePayInfo);
    
    
    /**
     * 设置订单退款状态后置操作
     * @param TradeRefundInfo $tradeRefundInfo 退款订单数据
     * @param TradePayInfo    $tradePayInfo 交易订单数据
     * @param bool            $status 退款状态，true: 退款成功，false: 退款失败
     * @param string          $remark 退款成功失败说明，成功: 退入账户，失败：失败原因
     */
    public function setRefundStatusAfter(TradeRefundInfo $tradeRefundInfo, TradePayInfo $tradePayInfo, bool $status, string $remark);
}
