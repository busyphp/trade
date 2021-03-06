<?php

namespace BusyPHP\trade\app\controller;

use BusyPHP\app\admin\controller\AdminCurdController;
use BusyPHP\helper\util\Transform;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\refund\TradeRefund;
use BusyPHP\trade\model\TradeConfig;

/**
 * 支付订单管理
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/6/17 下午4:32 下午 Finance.php $
 */
class TradeController extends AdminCurdController
{
    use TradeConfig;
    
    /**
     * 支付记录
     */
    public function pay_list()
    {
        // 时间范围查询
        $timeRangeStatus = $this->getPayConfig('time_range.status', false);
        if ($timeRangeStatus) {
            if (!isset($_REQUEST['static']['start_time']) && !isset($_REQUEST['static']['end_time'])) {
                $this->request->setParam('static', ['start_time' => date('Y-m-d')]);
                $this->request->setParam('static', ['end_time' => date('Y-m-d')]);
            }
        }
        
        // 默认查询条件
        $where = $this->getPayConfig('where', []);
        $where = is_callable($where) ? call_user_func($where) : $where;
        
        // 设置查询条件解析
        $this->setSelectWhere($where, function($where) use ($timeRangeStatus) {
            // 支付类型
            if (!$where['pay_type']) {
                unset($where['pay_type']);
            }
            
            // 支付状态
            switch (intval($where['status'])) {
                case 1:
                    $where['pay_time'] = ['gt', 0];
                break;
                case 2:
                    $where['pay_time'] = 0;
                break;
            }
            unset($where['status']);
            
            
            // 时间范围查询
            if ($timeRangeStatus) {
                if ($where['start_time'] && $where['end_time']) {
                    $where['create_time'] = [
                        ['egt', strtotime($where['start_time'])],
                        ['elt', strtotime($where['end_time']) + 86399]
                    ];
                } elseif ($where['start_time']) {
                    $where['create_time'] = ['egt', strtotime($where['start_time'])];
                } elseif ($where['end_time']) {
                    $where['create_time'] = ['elt', strtotime($where['end_time']) + 86399];
                }
                
                unset($where['start_time'], $where['end_time']);
            }
            
            
            // 字段匹配
            $callback = $this->getPayConfig('select_where');
            if ($callback && is_callable($callback)) {
                $where = call_user_func_array($callback, [$where]);
            }
            
            return $where;
        });
        
        // 支付类型
        $this->assign('type_options', Transform::arrayToOption(TradePay::init()->getTypes()));
        
        // todo 开票状态
        $this->assign('ticket_status_options', Transform::arrayToOption(TradePay::getTicketStatus()));
        
        
        // 搜索字段
        $searchFieldConfig = $this->getPayConfig('select_fields', []);
        $selectFields      = ['pay_trade_no', 'api_trade_no', 'order_trade_no'];
        $selectFields      = array_merge($selectFields, array_keys($searchFieldConfig));
        $selectValues      = ['支付订单号', '三方订单号', '业务订单号'];
        $selectValues      = array_merge($selectValues, array_values($searchFieldConfig));
        
        
        $this->assign('select_fields', implode(',', $selectFields));
        $this->assign('select_values', implode(',', $selectValues));
        
        
        // 时间范围
        $startTime  = $this->getPayConfig('time_range.start_time', '');
        $startTime  = is_callable($startTime) ? call_user_func($startTime) : $startTime;
        $endTime    = $this->getPayConfig('time_range.end_time', '');
        $endTime    = is_callable($endTime) ? call_user_func($endTime) : $endTime;
        $timeFormat = $this->getPayConfig('time_range.format', 'yyyy-MM-dd');
        $this->assign('time_range_status', $timeRangeStatus);
        $this->assign('time_range_start_time', $startTime);
        $this->assign('time_range_end_time', $endTime);
        $this->assign('time_range_format', $timeFormat);
        $this->assign('time_range_size', strlen($timeFormat));
        
        
        // 展示数据解析
        $this->bind(self::CALL_SELECT_LIST, function($list) {
            $user = $this->getPayConfig('list.user', 'username');
            foreach ($list as $i => $r) {
                $r['show_user'] = is_callable($user) ? call_user_func_array($user, [$r['user'] ?? []]) : ($r['user'][$user] ?? '');
                $list[$i]       = $r;
            }
            
            return $list;
        });
        
        return $this->select(TradePay::init(), false, true);
    }
    
    
    /**
     * 退款记录
     */
    public function refund_list()
    {
        // 时间范围查询
        $timeRangeStatus = $this->getRefundConfig('time_range.status', false);
        if ($timeRangeStatus) {
            if (!isset($_REQUEST['static']['start_time']) && !isset($_REQUEST['static']['end_time'])) {
                $this->request->setParam('static', ['start_time' => date('Y-m-d')]);
                $this->request->setParam('static', ['end_time' => date('Y-m-d')]);
            }
        }
        
        // 默认查询条件
        $where = $this->getRefundConfig('where', []);
        $where = is_callable($where) ? call_user_func($where) : $where;
        
        // 设置查询条件解析
        $this->setSelectWhere($where, function($where) use ($timeRangeStatus) {
            // 支付类型
            if (!$where['pay_type']) {
                unset($where['pay_type']);
            }
            
            // 支付状态
            if (!$where['status']) {
                unset($where['status']);
            }
            
            
            // 时间范围查询
            if ($timeRangeStatus) {
                if ($where['start_time'] && $where['end_time']) {
                    $where['create_time'] = [
                        ['egt', strtotime($where['start_time'])],
                        ['elt', strtotime($where['end_time']) + 86399]
                    ];
                } elseif ($where['start_time']) {
                    $where['create_time'] = ['egt', strtotime($where['start_time'])];
                } elseif ($where['end_time']) {
                    $where['create_time'] = ['elt', strtotime($where['end_time']) + 86399];
                }
                
                unset($where['start_time'], $where['end_time']);
            }
            
            
            // 字段匹配
            $callback = $this->getRefundConfig('select_where');
            if ($callback && is_callable($callback)) {
                $where = call_user_func_array($callback, [$where]);
            }
            
            return $where;
        });
        
        // 支付类型
        $this->assign('type_options', Transform::arrayToOption(TradePay::init()->getTypes()));
        $this->assign('status_options', Transform::arrayToOption(TradeRefund::getStatus(), '__index', '__index'));
        
        // 搜索字段
        $searchFieldConfig = $this->getRefundConfig('select_fields', []);
        $selectFields      = ['refund_no', 'api_refund_no', 'pay_trade_no', 'pay_api_trade_no', 'order_trade_no'];
        $selectFields      = array_merge($selectFields, array_keys($searchFieldConfig));
        $selectValues      = ['退款单号', '三方退款单号', '支付订单号', '三方支付订单号', '业务订单号'];
        $selectValues      = array_merge($selectValues, array_values($searchFieldConfig));
        $this->assign('select_fields', implode(',', $selectFields));
        $this->assign('select_values', implode(',', $selectValues));
        
        // 时间范围
        $startTime  = $this->getRefundConfig('time_range.start_time', '');
        $startTime  = is_callable($startTime) ? call_user_func($startTime) : $startTime;
        $endTime    = $this->getRefundConfig('time_range.end_time', '');
        $endTime    = is_callable($endTime) ? call_user_func($endTime) : $endTime;
        $timeFormat = $this->getRefundConfig('time_range.format', 'yyyy-MM-dd');
        $this->assign('time_range_status', $timeRangeStatus);
        $this->assign('time_range_start_time', $startTime);
        $this->assign('time_range_end_time', $endTime);
        $this->assign('time_range_format', $timeFormat);
        $this->assign('time_range_size', strlen($timeFormat));
        
        // 展示数据解析
        $this->bind(self::CALL_SELECT_LIST, function($list) {
            $user = $this->getRefundConfig('list.user', 'username');
            foreach ($list as $i => $r) {
                $r['show_user'] = is_callable($user) ? call_user_func_array($user, [$r['user'] ?? []]) : ($r['user'][$user] ?? '');
                $list[$i]       = $r;
            }
            
            return $list;
        });
        
        return $this->select(TradeRefund::init(), false, true);
    }
    
    
    /**
     * 获取支付页面配置
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    protected function getPayConfig($name, $default = null)
    {
        return $this->getTradeConfig('admin.pay.' . $name, $default);
    }
    
    
    /**
     * 获取退款页面配置
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    protected function getRefundConfig($name, $default = null)
    {
        return $this->getTradeConfig('admin.refund.' . $name, $default);
    }
    
    
    protected function display($template = '', $charset = 'utf-8', $contentType = '', $content = '')
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        if ($template) {
            $template = $dir . $template . '.html';
        } else {
            $template = $dir . ACTION_NAME . '.html';
        }
        
        return parent::display($template, $charset, $contentType, $content);
    }
}