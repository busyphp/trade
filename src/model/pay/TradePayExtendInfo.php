<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\pay;

use BusyPHP\model\Entity;
use BusyPHP\model\Field;

/**
 * 支付订单扩展信息结构
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午4:10 TradePayExtendInfo.php $
 * @method static Entity user() 所属用户信息
 * @method static Entity username() 用户名
 * @method static Entity userEmail() 用户邮箱
 * @method static Entity userPhone() 用户手机号
 * @method static Entity adminUserOperateAttr() 管理员模板对用户的操作属性
 */
class TradePayExtendInfo extends TradePayInfo
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
}