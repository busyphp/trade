<?php
declare(strict_types = 1);

namespace BusyPHP\trade\app\controller;

use BusyPHP\Controller;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\refund\TradeRefund;
use BusyPHP\trade\model\TradeConfig;
use think\Response;

/**
 * 异步通知入口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午1:50 NotifyController.php $
 */
class NotifyController extends Controller
{
    use TradeConfig;
    
    /**
     * 支付异步通知
     * @return Response
     */
    public function pay()
    {
        return TradePay::init()->notify();
    }
    
    
    /**
     * 退款异步通知
     * @return Response
     */
    public function refund()
    {
        return TradeRefund::init()->notify();
    }
}