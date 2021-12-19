<?php

namespace BusyPHP\trade\job;

use BusyPHP\queue\contract\JobInterface;
use BusyPHP\queue\Job;
use BusyPHP\trade\model\refund\TradeRefund;
use Throwable;

/**
 * 退款查询任务
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/19 下午8:25 QueryJob.php $
 */
class QueryJob implements JobInterface
{
    /**
     * 执行任务
     * @param Job   $job 任务对象
     * @param mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data) : void
    {
        TradeRefund::log('退款查询')->info("开始: {$data}");
        try {
            TradeRefund::init()->inquiry($data);
            TradeRefund::log('退款查询')->info("完成: {$data}");
        } catch (Throwable $e) {
            TradeRefund::log("退款查询失败: {$data}")->error($e);
        }
    }
}