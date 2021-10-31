<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\refund\TradeRefundExtendInfo;

/**
 * 后台管理系统退款管理中对用户的操作属性回调
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/29 下午下午2:30 TradeMemberAdminRefundOperateAttr.php $
 */
interface TradeMemberAdminRefundOperateAttr
{
    /**
     * 执行回调
     * @param TradeRefundExtendInfo $info
     * @return array
     */
    public function callback(TradeRefundExtendInfo $info) : array;
}