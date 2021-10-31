<?php

namespace BusyPHP\trade\defines;

/**
 * 常用支付类型定义
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午上午11:05 PayType.php $
 */
class PayType
{
    // +----------------------------------------------------
    // + 支付类型
    // +----------------------------------------------------
    /**
     * 微信JSSDK支付
     */
    const WECHAT_JS = 1;
    
    /**
     * 微信H5支付
     */
    const WECHAT_H5 = 2;
    
    /**
     * 微信APP支付
     */
    const WECHAT_APP = 3;
    
    /**
     * 微信扫码支付
     */
    const WECHAT_NATIVE = 4;
    
    /**
     * 微信小程序支付
     */
    const WECHAT_MINI = 5;
    
    /**
     * 支付宝PC端支付
     */
    const ALIPAY_PC = 10;
    
    /**
     * 支付宝H5支付
     */
    const ALIPAY_H5 = 11;
    
    /**
     * 支付宝APP支付
     */
    const ALIPAY_APP = 12;
    
    /**
     * 支付宝小程序支付
     */
    const ALIPAY_MIMI = 13;
    
    /**
     * 苹果内购支付
     */
    const APPLE_IPA = 20;
    
    // +----------------------------------------------------
    // + 客户端类型
    // +----------------------------------------------------
    /**
     * PC网页端
     */
    const CLIENT_PC = 1;
    
    /**
     * 移动网页端
     */
    const CLIENT_H5 = 2;
    
    /**
     * 客户端
     */
    const CLIENT_APP = 3;
    
    /**
     * 微信端
     */
    const CLIENT_WECHAT = 4;
    
    /**
     * 支付宝端
     */
    const CLIENT_ALIPAY = 5;
}