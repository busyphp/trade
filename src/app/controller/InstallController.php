<?php

namespace BusyPHP\trade\app\controller;

use BusyPHP\app\admin\model\system\menu\SystemMenu;
use BusyPHP\app\admin\model\system\menu\SystemMenuField;
use BusyPHP\Controller;
use BusyPHP\exception\AppException;
use BusyPHP\helper\util\Str;
use BusyPHP\trade\model\TradeConfig;

/**
 * 安装
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/10/7 下午5:41 下午 InstallController.php $
 */
class InstallController extends Controller
{
    use TradeConfig;
    
    public function index()
    {
        $groupName   = Str::snake($this->getTradeConfigAdminPayMenuModule());
        $controlName = Str::snake($this->getTradeConfigAdminPayMenuControl());
        $actionName  = $this->getTradeConfigAdminPayMenuAction();
        
        try {
            $db = SystemMenu::init();
            
            // 是否有该分组
            $where          = SystemMenuField::init();
            $where->action  = '';
            $where->control = '';
            $where->module  = $groupName;
            if (!$db->whereof($where)->findData()) {
                throw new AppException("没有找到菜单: [{$groupName}]分组");
            }
            
            
            // 是否有该控制器
            $where          = SystemMenuField::init();
            $where->action  = '';
            $where->control = $controlName;
            $where->module  = $groupName;
            if (!$db->whereof($where)->findData()) {
                throw new AppException("没有找到菜单: [{$groupName}.{$controlName}]控制器");
            }
            
            
            // 是否安装过该菜单
            $where          = SystemMenuField::init();
            $where->action  = $actionName;
            $where->control = $controlName;
            $where->module  = $groupName;
            if ($db->whereof($where)->findData()) {
                throw new AppException('您已安装过该插件，请勿重复安装');
            }
            
            // 查询是否创建了表
            if ($db->query("SELECT table_name FROM information_schema.TABLES where table_name IN ('busy_trade_pay', 'busy_trade_no') AND table_schema='{$db->getConfig('database')}'")) {
                throw new AppException('您已安装过该插件，请勿重复安装');
            }
            
            
            // 创建表 busy_trade_pay
            $createSQL = <<<SQL
CREATE TABLE `busy_trade_pay` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `pay_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '交易号',
  `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '需支付金额',
  `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '支付描述',
  `order_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '业务订单号',
  `order_status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '业务订单支付状态 0 未支付, 1 支付成功, 2 支付失败',
  `order_status_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '业务订单状态备注',
  `order_retry_count` INT(11) NOT NULL DEFAULT '0' COMMENT '业务订单失败的重试次数',
  `order_retry_time` INT(11) NOT NULL DEFAULT '0' COMMENT '业务订单失败的重试时间',
  `api_trade_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '三方平台的支付订单号',
  `api_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '三方平台返回支付的金额',
  `api_bank` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '三方支付返回的用户支付的银行账户信息',
  `pay_time` INT(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '支付类型',
  `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '剩余可退款金额',
  `ticket_status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '开票状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_trade_no` (`pay_trade_no`) USING BTREE,
  KEY `order_trade_no` (`order_trade_no`),
  KEY `user_id` (`user_id`),
  KEY `ticket_status` (`ticket_status`),
  KEY `order_status` (`order_status`),
  KEY `order_retry_count` (`order_retry_count`),
  KEY `order_retry_time` (`order_retry_time`),
  KEY `refund_amount` (`refund_amount`),
  KEY `pay_type` (`pay_type`),
  KEY `pay_time` (`pay_time`),
  KEY `api_trade_no` (`api_trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易支付表';
SQL;
            $db->execute($createSQL);
            
            // 创建表 busy_trade_no
            $insertSQL = <<<SQL
CREATE TABLE `busy_trade_no` (
  `id` BIGINT(16) NOT NULL AUTO_INCREMENT,
  `create_time` INT(11) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易号生成表';
SQL;
            $db->execute($insertSQL);
            
            // 插入菜单数据
            $dataSQL = <<<SQL
INSERT INTO `busy_system_menu` (`name`, `action`, `control`, `module`, `pattern`, `params`, `higher`, `icon`, `link`, `target`, `is_default`, `is_show`, `is_disabled`, `is_has_action`, `is_system`, `sort`) VALUES
    ('支付订单', '{$actionName}', '{$controlName}', '{$groupName}', '', '', '', 'list-ul', '', '', 0, 1, 0, 1, 0, 50)
SQL;
            $db->execute($dataSQL);
            
            return $this->success('安装成功', '/');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), '/');
        }
    }
}