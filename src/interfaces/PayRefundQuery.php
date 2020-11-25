<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;
use BusyPHP\trade\model\refund\TradeRefundField;

/**
 * 支付退款结果查询接口类
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:39 下午 PayRefundQuery.php $
 */
interface PayRefundQuery
{
    /**
     * 设置平台退款订单数据对象
     * @param TradeRefundField $info
     */
    public function setTradeRefundInfo(TradeRefundField $info);
    
    
    /**
     * 执行退款
     * @return PayRefundNotifyResult
     * @throws AppException
     */
    public function query() : PayRefundNotifyResult;
}