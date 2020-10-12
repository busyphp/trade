<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;
use think\Response;
use Throwable;

/**
 * 支付异步回调处理接口，所有异步回调都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:37 下午 PayNotify.php $
 */
interface PayNotify
{
    /**
     * 执行校验
     * @return PayNotifyResult
     * @throws AppException
     */
    public function notify();
    
    
    /**
     * 失败通知
     * @param Throwable $e
     * @return Response
     */
    public function onError(Throwable $e) : Response;
    
    
    /**
     * 成功通知
     * @param bool $payStatus true 支付成功，false 之前已支付，属于重复性的操作
     * @return Response
     */
    public function onSuccess(bool $payStatus) : Response;
    
    
    /**
     * 获取请求参数
     * @return array
     */
    public function getRequestParams();
    
    
    /**
     * 获取请求参数字符
     * @return string
     */
    public function getRequestString();
    
    
    /**
     * 获取平台支付订单号
     * @return string
     */
    public function getPayTradeNo();
}
