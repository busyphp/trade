<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\trade\model\pay\TradePayInfo;
use Exception;

/**
 * 更新剩余可退金额回调
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午1:04 TradeUpdateRefundAmountInterface.php $
 */
interface TradeUpdateRefundAmountInterface
{
    /**
     * 执行更新，内部无需启动事物
     * @param TradePayInfo $tradePayInfo
     * @return float 返回要更新的金额，整数为加上，负数为减去，返回null或0则不更新
     * @throws Exception
     */
    public function onUpdate(TradePayInfo $tradePayInfo) : ?float;
}