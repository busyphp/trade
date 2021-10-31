<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\refund\TradeRefundInfo;
use Exception;

/**
 * 支付退款接口类，所有支付退款都应该集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:20 PayRefund.php $
 */
interface PayRefund
{
    /**
     * 设置平台退款订单数据对象
     * @param TradeRefundInfo $info
     */
    public function setTradeRefundInfo(TradeRefundInfo $info);
    
    
    /**
     * 设置退款结果通知url
     * @param string $notifyUrl
     */
    public function setNotifyUrl(string $notifyUrl);
    
    
    /**
     * 执行退款
     * @return PayRefundResult
     * @throws Exception
     */
    public function refund() : PayRefundResult;
}