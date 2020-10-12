<?php
/**
 * 交易插件配置
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/10 下午12:20 下午 config.php $
 */

return [
    // 后台配置
    'admin'           => [
        // 支付订单页面配置
        'pay' => [
            // 菜单配置
            'menu'          => [
                'module'  => 'system',
                'control' => 'trade',
                'action'  => 'index',
            ],
            
            // 默认Where条件
            'where'         => [],
            
            // 搜索字段可选项配置
            'select_fields' => [
                'phone' => '手机号',
            ],
            
            // 搜索字段Where解析
            'select_where'  => function(array $where) {
                return $where;
            },
            
            // 时间范围
            'time_range'    => [
                // 是否启用
                'status'     => true,
                
                // 时间格式
                'format'     => 'yyyy-MM-dd',
                
                // 默认开始时间
                'start_time' => function() {
                    return date('Y-m-d');
                },
                
                // 默认结束时间
                'end_time'   => function() {
                    return date('Y-m-d');
                }
            ],
            
            // 列表展示配置
            'list'          => [
                // 所属会员展示字段
                'user' => function(array $user) {
                    return $user['username'] ?? '';
                }
            ]
        ],
    ],
    
    // 指定支付订单需要的的会员模型
    'trade_member'    => '会员模型类，必须集成 BusyPHP\trade\interfaces\TradeMemberModel',
    
    // 支付订单号前缀
    'trade_no_prefix' => 1001,
    
    // 接收异步或同步请求的时候获取支付类型的参数键
    'var_pay_type'    => 'pay_type',
    
    // 业务订单模型绑定
    'models'          => [
        // 订单号前缀(int) => [
        //     'model'   => 业务订单类 必须集成 BusyPHP\trade\interfaces\PayOrder,
        //     'exclude' => [], // 排除的支付类型
        // ]
    ],
    
    // 支付接口绑定
    'apis'            => [
        // 支付类型(int) => [
        //      'name'   => '支付类型名称',
        //      'alias'  => '支付厂商',
        //      'client' => 支付客户端类型,
        //      'create' => 支付下单接口类 必须集成 BusyPHP\trade\interfaces\PayCreate,
        //      'notify' => 支付异步通知接口类 必须集成 BusyPHP\trade\interfaces\PayNotify,
        //      'refund' => 支付退款接口类 必须集成 BusyPHP\trade\interfaces\PayRefund,
        //      'refund_notify' => 支付退款异步通知接口类 必须集成 BusyPHP\trade\interfaces\PayRefundNotify,
        // ]
    ]
];