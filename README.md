交易中心模块
===============

## 说明

用于BusyPHP支付、退款、转账等操作，使用三方支付必须安装的基本模块

## 安装
```
composer require busyphp/trade
```

## 配置
> 通过composer安装成功后，登录后台，前往开发模式下的插件管理进行安装/卸载/设置

## 启动退款任务

配置 `config/trade.php` 中的 `refund_queue.enable` 为 `true`

> 如果安装了`busyphp/swoole` 请使用swoole命令启动退款任务，参考[https://github.com/busyphp/swoole#readme](https://github.com/busyphp/swoole#readme)


`cd` 到项目根目录下执行

> 以下两种命令二选一执行，具体参考 [https://github.com/busyphp/queue#readme](https://github.com/busyphp/queue#readme)
>
```shell script
php think queue:listen plugin_trade --queue plugin_trade_refund --delay 3600 --sleep 60 

php think queue:work plugin_trade --queue plugin_trade_refund --delay 3600 --sleep 60 
```


## 配置 `config/trade.php`

```php
<?php
return [
    // 异步通知服务域名，如：www.harter.cn，退款功能必须设置该项
    'host'             => '',
    
    // 异步通知服务是否启用ssl
    'ssl'              => false,
    
    // 接收异步或同步请求的时候获取支付类型的参数键
    'var_pay_type'     => 'pay_type',
    
    // 指定支付订单需要的的会员模型，必须实现 BusyPHP\trade\interfaces\TradeMemberModel 接口
    'trade_member'     => '',
    
    // 支付订单号前缀
    'trade_no_prefix'  => 1001,
    
    // 退款订单号前缀
    'refund_no_prefix' => 1002,
    
    // 退款队列配置
    'refund_queue'     => [
        // 是否启用
        'enable'       => false,
        
        // 获取需重新退款的任务延迟执行秒数
        // 优先插件管理设置中的值
        'submit_delay' => 3600,
        
        // 获取需重新查询退款状态的任务延迟查询秒数
        // 优先插件管理设置中的值
        'query_delay'  => 3600,
        
        // 参见 config/queue.php 中的 connections
        'connection'   => [
            'type'  => 'database',
            'queue' => 'plugin_trade_refund',
            'table' => 'system_jobs',
        ],
        
        // 参见 config/swoole.php 中的 queue
        'worker'       => [
            'number'  => 1,
            'delay'   => 3600,
            'sleep'   => 60,
            'tries'   => 0,
            'timeout' => 60,
        ]
    ],
    
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
```