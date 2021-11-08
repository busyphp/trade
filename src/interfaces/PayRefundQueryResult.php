<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 退款查询返回数据结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午12:03 PayRefundQueryResult.php $
 */
class PayRefundQueryResult extends ObjectOption
{
    /**
     * 异步通知数据
     * @var PayRefundNotifyResult
     */
    private $notifyResult;
    
    /**
     * 详情数据
     * @var PayRefundQueryDetailItem[]
     */
    private $detail = [];
    
    
    /**
     * PayRefundQueryResult constructor.
     * @param PayRefundNotifyResult $notifyResult
     */
    public function __construct(PayRefundNotifyResult $notifyResult)
    {
        $this->notifyResult = $notifyResult;
    }
    
    
    /**
     * 设置异步通知数据
     * @param PayRefundNotifyResult $notifyResult
     * @return $this
     */
    public function setNotifyResult(PayRefundNotifyResult $notifyResult) : self
    {
        $this->notifyResult = $notifyResult;
        
        return $this;
    }
    
    
    /**
     * 获取异步通知数据
     * @return PayRefundNotifyResult
     */
    public function getNotifyResult() : PayRefundNotifyResult
    {
        return $this->notifyResult;
    }
    
    
    /**
     * 设置查询详情
     * @param PayRefundQueryDetailItem[] $detail
     * @return $this
     */
    public function setDetail(array $detail) : self
    {
        $this->detail = $detail;
        
        return $this;
    }
    
    
    /**
     * 添加查询单项
     * @param PayRefundQueryDetailItem $item
     * @return $this
     */
    public function addDetailItem(PayRefundQueryDetailItem $item) : self
    {
        $this->detail[] = $item;
        
        return $this;
    }
    
    
    /**
     * 添加查询单项
     * @param string $key 参数键名
     * @param mixed  $value 参数值
     * @param string $name 参数名称
     * @param string $desc 参数描述
     * @return $this
     */
    public function addDetail(string $key, $value, string $name = '', string $desc = '') : self
    {
        $item = new PayRefundQueryDetailItem($key, $value);
        $item->setName($name);
        $item->setDesc($desc);
        
        return $this;
    }
    
    
    /**
     * 获取查询详情
     * @return PayRefundQueryDetailItem[]
     */
    public function getDetail() : array
    {
        return $this->detail;
    }
}