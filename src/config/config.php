<?php
/**
 * 交易插件配置
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/31 下午下午12:51 config.php $
 */

return [
    // 异步通知服务域名，如：www.harter.cn，退款功能必须设置该项
    'host'             => '',
    
    // 异步通知服务是否启用ssl
    'ssl'              => false,
    
    // 接收异步或同步请求的时候获取支付类型的参数键
    'var_pay_type'     => 'pay_type',
    
    // 指定支付订单需要的的会员模型，必须集成 {@link BusyPHP\trade\interfaces\TradeMemberModel}
    'trade_member'     => '',
    
    // 支付订单号前缀
    'trade_no_prefix'  => 1001,
    
    // 退款订单号前缀
    'refund_no_prefix' => 1002,
    
    // 业务订单模型绑定
    'models'           => [
        // 订单号前缀(int) => [
        //     'model'   => 业务订单类 必须集成 BusyPHP\trade\interfaces\PayOrder,
        //     'exclude' => [], // 排除的支付类型
        // ]
    ],
    
    // 支付接口绑定
    'apis'             => [
        // 支付类型(int) => [
        //      'name'   => '支付类型名称',
        //      'alias'  => '支付厂商',
        //      'client' => 支付客户端类型,
        //      'create' => 支付下单接口类 必须集成 BusyPHP\trade\interfaces\PayCreate,
        //      'notify' => 支付异步通知接口类 必须集成 BusyPHP\trade\interfaces\PayNotify,
        //      'refund' => 支付退款接口类 必须集成 BusyPHP\trade\interfaces\PayRefund,
        //      'refund_notify' => 支付退款异步通知接口类 必须集成 BusyPHP\trade\interfaces\PayRefundNotify,
        //      'refund_query' => 退款查询接口类，必须集成 BusyPHP\trade\interfaces\PayRefundQuery,
        // ]
    ]
];