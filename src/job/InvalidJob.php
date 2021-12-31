<?php

namespace BusyPHP\trade\job;

use BusyPHP\queue\contract\JobInterface;
use BusyPHP\queue\Job;
use BusyPHP\trade\model\pay\TradePay;
use Throwable;

/**
 * 支付订单失效任务
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2022 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/30 下午10:29 InvalidJob.php $
 */
class InvalidJob implements JobInterface
{
    /**
     * 执行任务
     * @param Job   $job 任务对象
     * @param mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data) : void
    {
        if ($job->attempts() > 10) {
            $job->delete();
            
            TradePay::log('失效任务')->info("超过10次尝试，不在入队，ID: {$data}");
            
            return;
        }
        
        TradePay::log('失效任务')->info("开始: {$data}");
        
        try {
            TradePay::init()->invalidModel($data);
            
            TradePay::log('失效任务')->info("完成: {$data}");
        } catch (Throwable $e) {
            $job->release(60);
            
            TradePay::log("失效任务执行失败: {$data}")->error($e);
            
            return;
        }
        
        $job->delete();
    }
}