<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use Exception;
use think\Response;
use Throwable;

/**
 * 支付异步回调处理接口，所有异步回调都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:19 PayNotify.php $
 */
interface PayNotify
{
    /**
     * 执行异步通知
     * @return PayNotifyResult
     * @throws Exception
     */
    public function notify() : PayNotifyResult;
    
    
    /**
     * 失败通知
     * @param Throwable $e
     * @return Response
     */
    public function onError(Throwable $e) : Response;
    
    
    /**
     * 成功通知
     * @param bool $payStatus true 支付成功，false 属于重复通知，该订单已支付
     * @return Response
     */
    public function onSuccess(bool $payStatus) : Response;
    
    
    /**
     * 获取源请求参数
     * @return string
     */
    public function getRequestSourceParams() : string;
    
    
    /**
     * 获取解析后的请求参数
     * @return array
     */
    public function getRequestParams() : array;
    
    
    /**
     * 获取平台交易单号
     * @return string
     */
    public function getPayTradeNo();
}
