<?php

namespace BusyPHP\trade\job;

use BusyPHP\queue\contract\JobInterface;
use BusyPHP\queue\Job;
use BusyPHP\trade\model\refund\TradeRefund;
use BusyPHP\trade\model\TradeConfig;
use Throwable;

/**
 * 退款查询任务
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/12/19 下午8:25 QueryJob.php $
 */
class QueryJob implements JobInterface
{
    use TradeConfig;
    
    /**
     * 执行任务
     * @param Job   $job 任务对象
     * @param mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data) : void
    {
        if ($job->attempts() > 10) {
            $job->delete();
            
            TradeRefund::log("退款查询")->info("超过10次尝试，不在入队，ID: {$data}");
            
            return;
        }
        
        TradeRefund::log('退款查询')->info("开始: {$data}");
        
        try {
            TradeRefund::init()->inquiry($data);
            
            TradeRefund::log('退款查询')->info("完成: {$data}");
        } catch (Throwable $e) {
            $job->release($this->getRefundQueryDelay());
            
            TradeRefund::log("退款查询失败: {$data}")->error($e);
            
            return;
        }
        
        $job->delete();
    }
}