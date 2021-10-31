<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\refund;

use BusyPHP\model\Entity;
use BusyPHP\model\Field;
use BusyPHP\trade\interfaces\TradeMemberAdminRefundOperateAttr;
use BusyPHP\trade\interfaces\TradeMemberParams;
use BusyPHP\trade\model\pay\TradePay;
use Closure;

/**
 * 退款信息扩展结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午5:24 TradeRefundExtendInfo.php $
 * @method static Entity user() 所属用户信息
 * @method static Entity username() 用户名
 * @method static Entity userEmail() 用户邮箱
 * @method static Entity userPhone() 用户手机号
 * @method static Entity adminUserOperateAttr() 管理员模板对用户的操作属性
 */
class TradeRefundExtendInfo extends TradeRefundInfo
{
    /**
     * @var Field|array|null
     */
    public $user;
    
    /**
     * 用户名
     * @var string
     */
    public $username;
    
    /**
     * 邮箱
     * @var string
     */
    public $userEmail;
    
    /**
     * 手机号
     * @var string
     */
    public $userPhone;
    
    /**
     * 用户昵称
     * @var string
     */
    public $userNickname;
    
    /**
     * 管理员模板对用户的操作属性
     * @var string
     */
    public $adminUserOperateAttr;
    
    /**
     * @var TradeMemberParams
     */
    protected static $_userParams;
    
    
    public function onParseAfter()
    {
        if (!isset(static::$_userParams)) {
            static::$_userParams = TradePay::init()->getMemberModel()->getTradeUserParams();
        }
        parent::onParseAfter();
        
        // 用户名
        $this->username = $this->user[(string) static::$_userParams->getUsernameField()] ?? '';
        
        // 手机号
        $phoneField = static::$_userParams->getPhoneField();
        if ($phoneField) {
            $this->userPhone = $this->user[(string) $phoneField] ?? '';
        }
        if ($this->userPhone && !$this->username) {
            $this->username = $this->userPhone;
        }
        
        // 昵称
        $nicknameField = static::$_userParams->getNicknameField();
        if ($nicknameField) {
            $this->userNickname = $this->user[(string) $nicknameField] ?? '';
        }
        if ($this->userNickname && !$this->username) {
            $this->username = $this->userNickname;
        }
        
        // 邮箱
        $emailField = static::$_userParams->getEmailField();
        if ($emailField) {
            $this->userEmail = $this->user[(string) $emailField] ?? '';
        }
        if ($this->userEmail && !$this->username) {
            $this->username = $this->userEmail;
        }
        
        // 管理员模板对用户的操作属性
        $this->adminUserOperateAttr   = '';
        $adminUserOperateAttrCallback = static::$_userParams->getAdminRefundOperateUserAttr();
        $result                       = null;
        if ($adminUserOperateAttrCallback instanceof Closure || is_callable($adminUserOperateAttrCallback)) {
            $result = call_user_func_array($adminUserOperateAttrCallback, [$this]);
        } elseif ($adminUserOperateAttrCallback instanceof TradeMemberAdminRefundOperateAttr) {
            $result = $adminUserOperateAttrCallback->callback($this);
        }
        
        if ($result) {
            if (is_array($result)) {
                $attrs = [];
                foreach ($result as $key => $item) {
                    $attrs[] = "{$key}='{$item}'";
                }
                $this->adminUserOperateAttr = " " . implode(' ', $attrs);
            } else {
                $this->adminUserOperateAttr = " {$result}";
            }
        }
    }
}