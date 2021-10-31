<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\Model;

/**
 * 支付订单模型会员模型接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午10:28 TradeMemberModel.php $
 * @mixin Model
 */
interface TradeMemberModel
{
    /**
     * 获取交易订单需要的用户参数
     * @return TradeMemberParams
     */
    public function getTradeUserParams() : TradeMemberParams;
}