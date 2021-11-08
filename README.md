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

`cd` 到到项目根目录下执行

### 启动命令

```shell script
php think swoole
```

### 停止命令
```shell script
php think swoole stop
```

### 重启命令
```shell script
php think swoole restart
```

### 在`www`用户下运行

```shell script
su -c "php think swoole start|stop|restart" -s /bin/sh www
```

## 配置 `config/extend/trade.php`

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
    
    // 任务配置
    'task'             => [
        // 退款任务
        'refund' => [
            // 是否启用
            'enable'          => false,
            
            // 下单任务间隔执行毫秒
            'submit_interval' => 1000,
            
            // 查询任务间隔执行毫秒
            'query_interval'  => 1000
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