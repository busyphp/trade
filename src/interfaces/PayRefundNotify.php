<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\AppException;
use think\Response;
use Throwable;


/**
 * 支付退款异步回调处理接口，所有退款异步回调都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/7/8 下午6:46 下午 PayRefundNotify.php $
 */
interface PayRefundNotify
{
    /**
     * 执行校验
     * @return PayRefundNotifyResult
     * @throws AppException
     */
    public function notify() : PayRefundNotifyResult;
    
    
    /**
     * 失败通知
     * @param Throwable $e
     * @return Response
     */
    public function onError(Throwable $e) : Response;
    
    
    /**
     * 成功通知
     * @param string $message
     * @return Response
     */
    public function onSuccess($message = '') : Response;
    
    
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
     * 获取退款单号
     * @return string
     */
    public function getRefundTradeNo();
}
