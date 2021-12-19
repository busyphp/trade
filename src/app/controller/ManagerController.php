<?php

namespace BusyPHP\trade\app\controller;

use BusyPHP\app\admin\model\system\menu\SystemMenu;
use BusyPHP\app\admin\model\system\menu\SystemMenuField;
use BusyPHP\app\admin\model\system\plugin\SystemPlugin;
use BusyPHP\contract\abstracts\PluginManager;
use BusyPHP\exception\VerifyException;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\Response;

/**
 * 插件管理
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/1 下午下午2:22 Manager.php $
 */
class ManagerController extends PluginManager
{
    /**
     * 创建表SQL
     * @var string[]
     */
    private $createTableSql = [
        'trade_pay' => "CREATE TABLE `#__table_prefix__#trade_pay` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `pay_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '交易号',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` INT(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
    `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '需支付金额',
    `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '支付描述',
    `order_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '业务订单号',
    `order_status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '业务订单支付状态 0 未支付, 1 业务支付成功, 2 业务支付失败',
    `order_status_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '业务订单状态备注',
    `order_retry_count` INT(11) NOT NULL DEFAULT '0' COMMENT '业务订单失败的重试次数',
    `order_retry_time` INT(11) NOT NULL DEFAULT '0' COMMENT '业务订单失败的重试时间',
    `api_trade_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '三方平台的支付订单号',
    `api_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '三方平台返回支付的金额',
    `pay_remark` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '三方支付说明',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易支付表'",
        
        'trade_refund' => "CREATE TABLE `#__table_prefix__#trade_refund` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
    `refund_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '平台退款单号',
    `refund_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
    `api_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '三方退款单号',
    `status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '退款状态 0 未处理，1:退款中 8退款成功，9退款失败',
    `fail_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败备注',
    `refund_account` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退入账户说明',
    `create_time` INT(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `start_time` INT(11) NOT NULL DEFAULT '0' COMMENT '开始执行退款时间',
    `complete_time` INT(11) NOT NULL DEFAULT '0' COMMENT '退款完成时间',
    `order_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '业务订单号',
    `order_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '业务类型',
    `order_value` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '业务参数',
    `pay_id` INT(11) NOT NULL DEFAULT '0' COMMENT '交易订单ID',
    `pay_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '交易订单支付类型',
    `pay_trade_no` CHAR(22) NOT NULL DEFAULT '' COMMENT '交易订单号',
    `pay_api_trade_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '交易订单三方支付订单号',
    `pay_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '交易订单实际支付金额',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因备注',
    PRIMARY KEY (`id`),
    UNIQUE KEY `refund_no` (`refund_no`) USING BTREE,
    KEY `pay_id` (`pay_id`),
    KEY `pay_trade_no` (`pay_trade_no`),
    KEY `pay_pay_trade_no` (`pay_api_trade_no`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    KEY `queue_time` (`queue_time`),
    KEY `status_2` (`status`,`queue_time`),
    KEY `order_type` (`order_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易退款表'",
        
        'trade_no' => "CREATE TABLE `#__table_prefix__#trade_no` (
    `id` BIGINT(16) NOT NULL AUTO_INCREMENT,
    `create_time` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='交易号生成表'"
    ];
    
    /**
     * 删除表SQL
     * @var string[]
     */
    private $deleteTableSql = [
        "DROP TABLE IF EXISTS `#__table_prefix__#trade_pay`",
        "DROP TABLE IF EXISTS `#__table_prefix__#trade_refund`",
        "DROP TABLE IF EXISTS `#__table_prefix__#trade_no`",
    ];
    
    
    /**
     * 返回模板路径
     * @return string
     */
    protected function viewPath() : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
    }
    
    
    /**
     * 安装插件
     * @return Response
     * @throws Exception
     */
    public function install() : Response
    {
        $menuModel = SystemMenu::init();
        
        if ($this->isPost()) {
            $parentPath   = $this->post('parent_path/s', 'trim');
            $replaceMenu  = $this->post('replace_menu/b');
            $replaceTable = $this->post('replace_table/b');
            if (!$parentPath) {
                throw new VerifyException('请选择菜单安装位置', 'parent_path');
            }
            
            $menuModel->startTrans();
            try {
                // 删除菜单
                $payPath    = 'plugins_trade/pay';
                $refundPath = 'plugins_trade/refund';
                if ($replaceMenu) {
                    $menuModel->deleteByPath($payPath, true);
                    $menuModel->deleteByPath($refundPath, true);
                }
                
                // 不存在退款管理则创建
                if (!$menuModel->whereEntity(SystemMenuField::path($refundPath))->count()) {
                    $menuModel->addMenu($refundPath, '退款管理', $parentPath, 'bicon bicon-refund', false, 2);
                    $menuModel->addMenu('plugins_trade/refund_retry', '重试退款', $refundPath, '', true, 1);
                    $menuModel->addMenu('plugins_trade/refund_success', '设为退款成功', $refundPath, '', true, 2);
                    $menuModel->addMenu('plugins_trade/refund_query', '查询退款结果', $refundPath, '', true, 3);
                }
                
                // 不存在交易管理则创建
                if (!$menuModel->whereEntity(SystemMenuField::path($payPath))->count()) {
                    $menuModel->addMenu($payPath, '交易管理', $parentPath, 'bicon bicon-pay', false, 1);
                    $menuModel->addMenu('plugins_trade/pay_success', '支付订单', $payPath, '', true, 1);
                    $menuModel->addMenu('plugins_trade/pay_order_success', '恢复业务订单', $payPath, '', true, 2);
                    $menuModel->addMenu('plugins_trade/pay_apply_refund', '创建退款单', $payPath, '', true, 3);
                }
                
                // 删除表
                if ($replaceTable) {
                    foreach ($this->deleteTableSql as $item) {
                        $this->executeSQL($item);
                    }
                }
                
                // 创建表
                foreach ($this->createTableSql as $name => $item) {
                    if (!$this->hasTable($name)) {
                        $this->executeSQL($item);
                    }
                }
                
                SystemPlugin::init()->setInstall($this->info->package);
                
                $menuModel->commit();
            } catch (Exception $e) {
                $menuModel->rollback();
                
                throw $e;
            }
            
            $this->updateCache();
            $this->logInstall();
            
            return $this->success('安装成功');
        }
        
        $this->assign('menu_options', $menuModel->getTreeOptions());
        
        return $this->display();
    }
    
    
    /**
     * 卸载插件
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function uninstall() : Response
    {
        if ($this->isPost()) {
            $retainMenu  = $this->post('retain_menu/b');
            $retainTable = $this->post('retain_table/b');
            if ($retainMenu && $retainTable) {
                throw new VerifyException('全部保留，无需卸载');
            }
            
            $menuModel = SystemMenu::init();
            $menuModel->startTrans();
            try {
                if (!$retainMenu) {
                    $menuModel->deleteByPath('plugins_trade/pay', true);
                    $menuModel->deleteByPath('plugins_trade/refund', true);
                }
                
                if (!$retainTable) {
                    foreach ($this->deleteTableSql as $item) {
                        $this->executeSQL($item);
                    }
                }
                
                SystemPlugin::init()->setUninstall($this->info->package);
                $menuModel->commit();
            } catch (Exception $e) {
                $menuModel->rollback();
                
                throw $e;
            }
            
            $this->updateCache();
            $this->logUninstall();
            
            return $this->success('卸载成功');
        }
        
        return $this->display();
    }
    
    
    /**
     * 设置插件
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function setting() : Response
    {
        if ($this->isPost()) {
            $data                        = $this->param('data/a');
            $data['refund_submit_delay'] = intval($data['refund_submit_delay']);
            $data['refund_query_delay']  = intval($data['refund_query_delay']);
            
            SystemPlugin::init()->setSetting($this->info->package, $data);
            $this->logSetting();
            
            return $this->success('设置成功');
        }
        
        $this->assign('info', SystemPlugin::init()->getSetting($this->info->package));
        $this->setPageTitle('交易中心模块设置');
        
        return $this->display();
    }
}