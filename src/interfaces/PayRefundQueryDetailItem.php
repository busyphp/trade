<?php
declare(strict_types = 1);

namespace BusyPHP\trade\interfaces;

use BusyPHP\model\ObjectOption;

/**
 * 退款查询返回数据单项结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午12:03 PayRefundQueryDetailItem.php $
 */
class PayRefundQueryDetailItem extends ObjectOption
{
    /**
     * 参数名称
     * @var string
     */
    public $name = '';
    
    /**
     * 参数键名
     * @var string
     */
    public $key;
    
    /**
     * 参数值
     * @var string
     */
    public $value;
    
    /**
     * 描述
     * @var string
     */
    public $desc = '';
    
    
    /**
     * PayRefundQueryDetailItem constructor.
     * @param string $key 参数键名
     * @param mixed  $value 参数值
     */
    public function __construct(string $key, $value)
    {
        $this->setKey($key);
        $this->setValue((string) $value);
    }
    
    
    /**
     * 获取参数名称
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    
    
    /**
     * 设置参数名称
     * @param string $name
     * @return $this
     */
    public function setName(string $name) : self
    {
        $this->name = trim($name);
        
        return $this;
    }
    
    
    /**
     * 获取参数键名
     * @return string
     */
    public function getKey() : string
    {
        return $this->key;
    }
    
    
    /**
     * 设置参数键名
     * @param string $key
     * @return $this
     */
    public function setKey(string $key) : self
    {
        $this->key = trim($key);
        
        return $this;
    }
    
    
    /**
     * 获取参数值
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }
    
    
    /**
     * 设置参数值
     * @param string $value
     * @return $this
     */
    public function setValue(string $value) : self
    {
        $this->value = trim($value);
        
        return $this;
    }
    
    
    /**
     * 获取参数描述
     * @return string
     */
    public function getDesc() : string
    {
        return $this->desc;
    }
    
    
    /**
     * 设置参数描述
     * @param string $desc
     * @return $this
     */
    public function setDesc(string $desc) : self
    {
        $this->desc = trim($desc);
        
        return $this;
    }
}