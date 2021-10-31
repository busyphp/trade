<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use Exception;
use think\Response;
use Throwable;

/**
 * 支付退款异步回调处理接口，所有退款异步回调都需要集成该接口
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:20 PayRefundNotify.php $
 */
interface PayRefundNotify
{
    /**
     * 执行校验
     * @return PayRefundNotifyResult
     * @throws Exception
     */
    public function notify() : PayRefundNotifyResult;
    
    
    /**
     * 失败通知，不要抛出异常
     * @param Throwable $e
     * @return Response
     */
    public function onError(Throwable $e) : Response;
    
    
    /**
     * 成功通知，不要抛出异常
     * @param bool $status true 退款操作成功，false 属于重复通知，该订单已退款
     * @return Response
     */
    public function onSuccess(bool $status) : Response;
    
    
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
}
