<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\pay\TradePayExtendInfo;

/**
 * 后台管理系统支付管理中对用户的操作属性回调
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/29 下午下午2:30 TradeMemberAdminPayOperateAttr.php $
 */
interface TradeMemberAdminPayOperateAttr
{
    /**
     * 执行回调
     * @param TradePayExtendInfo $info
     * @return array
     */
    public function callback(TradePayExtendInfo $info) : array;
}