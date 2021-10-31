<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 支付退款下单结果结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午1:21 PayRefundResult.php $
 */
class PayRefundResult extends ObjectOption
{
    /**
     * 三方退款单号
     * @var string
     */
    protected $apiRefundNo = '';
    
    /**
     * 是否需要重新处理
     * @var bool
     */
    protected $needRehandle = false;
    
    /**
     * 退款退入账户
     * @var string
     */
    protected $refundAccount = '';
    
    
    /**
     * 获取三方退款单号
     * @return string
     */
    public function getApiRefundNo() : string
    {
        return $this->apiRefundNo;
    }
    
    
    /**
     * 设置三方退款单号
     * @param string $apiRefundNo
     * @return $this
     */
    public function setApiRefundNo(string $apiRefundNo) : self
    {
        $this->apiRefundNo = trim($apiRefundNo);
        
        return $this;
    }
    
    
    /**
     * 设置是否需要重新处理
     * @param bool $needRehandle
     * @return $this
     */
    public function setNeedRehandle(bool $needRehandle) : self
    {
        $this->needRehandle = $needRehandle;
        
        return $this;
    }
    
    
    /**
     * 获取是否需要重新处理
     * @return bool
     */
    public function isNeedRehandle() : bool
    {
        return $this->needRehandle;
    }
    
    
    /**
     * 设置退款退入账户
     * @param string $refundAccount
     * @return $this
     */
    public function setRefundAccount(string $refundAccount) : self
    {
        $this->refundAccount = trim($refundAccount);
        
        return $this;
    }
    
    
    /**
     * 获取退款退入账户
     * @return string
     */
    public function getRefundAccount() : string
    {
        return $this->refundAccount;
    }
}