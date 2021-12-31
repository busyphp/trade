<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\pay\TradePayInfo;

/**
 * 交易订单失效接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2022 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/31 上午10:04 PayOrderInvalid.php $
 */
interface PayOrderInvalid
{
    /**
     * 交易订单已失效事件
     * @param TradePayInfo $payInfo
     */
    public function onPayInvalid(TradePayInfo $payInfo);
}