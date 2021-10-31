<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\refund\TradeRefundField;
use Exception;

/**
 * 支付退款结果查询接口类
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:21 PayRefundQuery.php $
 */
interface PayRefundQuery
{
    /**
     * 设置平台退款订单数据对象
     * @param TradeRefundField $info
     */
    public function setTradeRefundInfo(TradeRefundField $info);
    
    
    /**
     * 执行查询
     * @return PayRefundQueryResult
     * @throws Exception
     */
    public function query() : PayRefundQueryResult;
}