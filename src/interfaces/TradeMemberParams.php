<?php

namespace BusyPHP\trade\interfaces;

use BusyPHP\exception\ParamInvalidException;
use BusyPHP\model\Entity;
use BusyPHP\model\ObjectOption;
use Closure;

/**
 * 交易模型用户参数结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/29 下午下午12:25 TradeMemberParams.php $
 */
class TradeMemberParams extends ObjectOption
{
    /**
     * 用户名字段
     * @var Entity
     */
    private $usernameField;
    
    /**
     * 用户邮箱字段
     * @var Entity
     */
    private $emailField;
    
    /**
     * 用户手机号字段
     * @var Entity
     */
    private $phoneField;
    
    /**
     * 用户昵称字段
     * @var Entity
     */
    private $nicknameField;
    
    /**
     * 后台管理系统支付管理对用户的操作属性回调
     * @var Closure|TradeMemberAdminPayOperateAttr
     */
    private $adminPayOperateUserAttr;
    
    /**
     * 后台管理系统退款管理对用户的操作属性回调
     * @var Closure|TradeMemberAdminRefundOperateAttr
     */
    private $adminRefundOperateUserAttr;
    
    
    /**
     * TradeMemberParams constructor.
     * @param string|Entity $usernameField 用户名字段名称
     * @param string|Entity $phoneField 用户手机号字段名称
     * @param string|Entity $nicknameField 用户昵称字段名称
     */
    public function __construct($usernameField, $phoneField = null, $nicknameField = null)
    {
        if (!$usernameField) {
            throw new ParamInvalidException('usernameField');
        }
        if (!$usernameField instanceof Entity) {
            $usernameField = Entity::init((string) $usernameField);
        }
        
        if ($nicknameField && !$nicknameField instanceof Entity) {
            $nicknameField = Entity::init((string) $nicknameField);
        }
        
        if ($phoneField && !$phoneField instanceof Entity) {
            $phoneField = Entity::init((string) $phoneField);
        }
        
        $this->usernameField = $usernameField;
        $this->nicknameField = $nicknameField;
        $this->phoneField    = $phoneField;
    }
    
    
    /**
     * 设置后台管理系统中支付管理对用户的操作属性回调
     * @param Closure|TradeMemberAdminPayOperateAttr $adminPayOperateUserAttr
     * @return TradeMemberParams
     */
    public function setAdminPayOperateUserAttr($adminPayOperateUserAttr) : self
    {
        $this->adminPayOperateUserAttr = $adminPayOperateUserAttr;
        
        return $this;
    }
    
    
    /**
     * 设置后台管理系统中退款管理对用户的操作属性回调
     * @param TradeMemberAdminRefundOperateAttr|Closure $adminRefundOperateUserAttr
     * @return TradeMemberParams
     */
    public function setAdminRefundOperateUserAttr($adminRefundOperateUserAttr) : self
    {
        $this->adminRefundOperateUserAttr = $adminRefundOperateUserAttr;
        
        return $this;
    }
    
    
    /**
     * 设置邮箱字段
     * @param Entity|string $emailField
     * @return TradeMemberParams
     */
    public function setEmailField($emailField) : self
    {
        if (!$emailField instanceof Entity) {
            $emailField = Entity::init($emailField);
        }
        
        $this->emailField = $emailField;
        
        return $this;
    }
    
    
    /**
     * 获取用户名字段
     * @return Entity
     */
    public function getUsernameField() : Entity
    {
        return $this->usernameField;
    }
    
    
    /**
     * 获取邮箱字段
     * @return Entity|null
     */
    public function getEmailField() : ?Entity
    {
        return $this->emailField;
    }
    
    
    /**
     * 获取手机号字段
     * @return Entity|null
     */
    public function getPhoneField() : ?Entity
    {
        return $this->phoneField;
    }
    
    
    /**
     * 获取用户昵称字段
     * @return Entity|null
     */
    public function getNicknameField() : ?Entity
    {
        return $this->nicknameField;
    }
    
    
    /**
     * 获取后台管理系统中支付管理对用户的操作属性回调
     * @return TradeMemberAdminPayOperateAttr|Closure|null
     */
    public function getAdminPayOperateUserAttr()
    {
        return $this->adminPayOperateUserAttr;
    }
    
    
    /**
     * 获取后台管理系统中退款管理对用户的操作属性回调
     * @return TradeMemberAdminRefundOperateAttr|Closure|null
     */
    public function getAdminRefundOperateUserAttr()
    {
        return $this->adminRefundOperateUserAttr;
    }
}