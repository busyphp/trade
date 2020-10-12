<?php

namespace BusyPHP\trade\model\pay;

use BusyPHP\exception\AppException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\exception\SQLException;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\util\Transform;
use BusyPHP\Model;
use BusyPHP\trade\interfaces\PayCreate;
use BusyPHP\trade\interfaces\PayCreateSyncReturn;
use BusyPHP\trade\interfaces\PayNotify;
use BusyPHP\trade\interfaces\PayNotifyResult;
use BusyPHP\trade\interfaces\PayOrder;
use BusyPHP\trade\interfaces\PayOrderPayData;
use BusyPHP\trade\interfaces\TradeMemberModel;
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\TradeConfig;
use Exception;
use think\exception\HttpException;
use think\facade\Log;
use think\Response;
use Throwable;

/**
 * 系统支付订单模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/6/17 下午4:46 下午 TradePay.php $
 */
class TradePay extends Model
{
    use TradeConfig;
    
    // +----------------------------------------------------
    // + 订单状态
    // +----------------------------------------------------
    /**
     * 操作成功
     */
    const ORDER_STATUS_SUCCESS = 1;
    
    /**
     * 操作失败
     */
    const ORDER_STATUS_FAIL = 2;
    
    // +----------------------------------------------------
    // + 开票状态
    // +----------------------------------------------------
    /**
     * 未开票
     */
    const TICKET_STATUS_NONE = 0;
    
    /**
     * 开票中
     */
    const TICKET_STATUS_PENDING = 1;
    
    /**
     * 开票成功
     */
    const TICKET_STATUS_SUCCESS = 2;
    
    /**
     * 开票失败
     */
    const TICKET_STATUS_FAIL = 3;
    
    
    /**
     * 获取支付订单
     * @param int  $id
     * @param bool $throw 是否抛出异常
     * @return array
     * @throws Exception
     */
    public function getInfo($id, $throw = true)
    {
        try {
            return parent::getInfo($id, '支付订单不存在');
        } catch (Exception $e) {
            if ($throw) {
                throw $e;
            }
            
            return [];
        }
    }
    
    
    /**
     * 创建支付订单
     * @param TradePayField $insert
     * @param bool          $disabledTrans
     * @return string
     * @throws ParamInvalidException
     * @throws SQLException
     * @throws VerifyException
     */
    public function insertData(TradePayField $insert, $disabledTrans = false)
    {
        // 校验是否支付
        if (false !== $this->checkPayByOrderTradeNo($insert->orderTradeNo)) {
            throw new VerifyException('该订单已支付，请勿重复支付', 'pay_success');
        }
        
        // 获取订单号前缀配置
        if (!$type = $this->getTradeConfig('trade_no_prefix', 1001)) {
            throw new ParamInvalidException('trade_no_prefix');
        }
        
        $this->startTrans($disabledTrans);
        try {
            $insert->payTradeNo = TradeNo::init()->get($type);
            $insert->createTime = time();
            $insert->updateTime = time();
            
            if (!$insertId = $this->addData($insert)) {
                throw new SQLException('创建支付订单失败', $this);
            }
            
            $this->commit($disabledTrans);
            
            return $insertId;
        } catch (SQLException $e) {
            $this->rollback($disabledTrans);
            
            throw $e;
        }
    }
    
    
    /**
     * 通过交易号获取支付订单
     * @param $payTradeNo
     * @return array
     * @throws SQLException
     */
    public function getInfoByPayTradeNo($payTradeNo)
    {
        $where             = TradePayField::init();
        $where->payTradeNo = trim($payTradeNo);
        $info              = $this->whereof($where)->findData();
        if (!$info) {
            throw new SQLException('支付订单不存在', $this);
        }
        
        return static::parseInfo($info);
    }
    
    
    /**
     * 通过业务订单号获取支付订单
     * @param $orderTradeNo
     * @return array
     * @throws SQLException
     */
    public function getInfoByOrderTradeNo($orderTradeNo)
    {
        $where               = TradePayField::init();
        $where->orderTradeNo = trim($orderTradeNo);
        $info                = $this->whereof($where)->findData();
        if (!$info) {
            throw new SQLException('支付订单不存在[' . $orderTradeNo . ']', $this);
        }
        
        return static::parseInfo($info);
    }
    
    
    /**
     * 通过业务订单号获取已支付的订单
     * @param $orderTradeNo
     * @return array
     * @throws SQLException
     */
    public function getPayInfoByOrderTradeNo($orderTradeNo)
    {
        $where              = TradePayField::init();
        $where->payTime     = ['gt', 0];
        $where->orderStatus = self::ORDER_STATUS_SUCCESS; // todo
        
        return $this->whereof($where)->getInfoByOrderTradeNo($orderTradeNo);
    }
    
    
    /**
     * 检测外部订单号是否已支付
     * @param $orderTradeNo
     * @return array|false
     */
    public function checkPayByOrderTradeNo($orderTradeNo)
    {
        try {
            return $this->getPayInfoByOrderTradeNo($orderTradeNo);
        } catch (AppException $e) {
            return false;
        }
    }
    
    
    /**
     * 通过订单号获取支付需要的数据
     * @param $orderTradeNo
     * @return PayOrderPayData
     * @throws VerifyException
     * @throws AppException
     */
    public function getPayData($orderTradeNo)
    {
        $model = $this->getOrderModel($orderTradeNo);
        
        if (false !== $this->checkPayByOrderTradeNo($orderTradeNo)) {
            throw new VerifyException('该订单已支付，请勿重复支付', 'pay_success');
        }
        
        return $model->getPayData($orderTradeNo);
    }
    
    
    /**
     * 获取会员模型
     * @return TradeMemberModel
     * @throws AppException
     */
    public function getMemberModel()
    {
        $class = $this->getTradeConfig('trade_member', '');
        if (!$class || !class_exists($class)) {
            throw new AppException("会员模型未绑定或不存在: {$class}");
        }
        
        $parentClass = TradeMemberModel::class;
        if (!is_subclass_of($class, $parentClass)) {
            throw new AppException("会员模型类: {$class} 需要集成: {$parentClass} 接口");
        }
        
        return call_user_func_array([$class, 'init'], []);
    }
    
    
    /**
     * 通过业务订单号获取订单模型
     * @param string $orderTradeNo 业务订单号
     * @return PayOrder
     * @throws ParamInvalidException
     * @throws AppException
     */
    public function getOrderModel($orderTradeNo)
    {
        $orderTradeNo = trim($orderTradeNo);
        if (!$orderTradeNo) {
            throw new ParamInvalidException('order_trade_no');
        }
        
        $type = TradeNo::getType($orderTradeNo);
        if (!$type) {
            throw new ParamInvalidException('trade type');
        }
        
        $class = $this->getTradeConfig("models.{$type}.model");
        if (!$class || !class_exists($class)) {
            throw new AppException("订单未绑定模型[{$type} - {$orderTradeNo}]");
        }
        
        $parentClass = PayOrder::class;
        if (!is_subclass_of($class, $parentClass)) {
            throw new AppException("订单模型类: {$class} 需要集成: {$parentClass} 接口");
        }
        
        return call_user_func_array([$class, 'init'], []);
    }
    
    
    /**
     * 获取支付模型
     * @param int    $userId 会员ID
     * @param string $payType 支付类型
     * @param string $orderTradeNo 业务订单号
     * @return PayCreate
     * @throws AppException
     */
    public function pay($userId, $payType, $orderTradeNo)
    {
        // 校验支付方式
        $payType = trim($payType);
        $payList = $this->getPayTypes($orderTradeNo);
        if (!isset($payList[$payType])) {
            throw new AppException('不支持该支付方式');
        }
        
        // 实例化三方支付类
        $class = $this->getTradeConfig("apis.{$payType}.create");
        if (!$class || !class_exists($class)) {
            throw new AppException("该支付方式[ {$payType} ]未绑定支付接口");
        }
        
        $parentClass = PayCreate::class;
        if (!is_subclass_of($class, $parentClass)) {
            throw new AppException("支付接口类: {$class} 需要集成: {$parentClass} 接口");
        }
        
        /** @var PayCreate $model */
        $model = new $class();
        
        // 创建支付订单
        $payData = $this->getPayData($orderTradeNo);
        $create  = TradePayField::init();
        $create->setUserId($userId);
        $create->setPayType($payType);
        $create->setPrice($payData->getPrice());
        $create->setOrderTradeNo($payData->getOrderTradeNo());
        $create->setTitle($payData->getBody());
        $id = $this->insertData($create);
        
        
        // 赋值模型
        $model->setTradeInfo(TradePayField::parse($this->findData($id)));
        
        return $model;
    }
    
    
    /**
     * 解析同步返回结果
     * @param mixed $payType
     * @return PayCreateSyncReturn
     * @throws AppException
     */
    public function parseReturn($payType) : PayCreateSyncReturn
    {
        $payType = trim($payType);
        $class   = $this->getTradeConfig("apis.{$payType}.create");
        if (!$class || !class_exists($class) || !method_exists($class, 'parseReturn')) {
            throw new AppException('解析同步返回结果处理类不存在或未配置');
        }
        
        return call_user_func([$class, 'parseReturn']);
    }
    
    
    /**
     * 记录日志
     * @param string $message 日志内容
     * @param bool   $isError 是否错误的日志内容
     * @param bool   $isThrow 是否停止运行并抛出异常
     * @throws HttpException
     */
    protected function log($message, $isError = false, $isThrow = false)
    {
        Log::record($message, $isError ? 'error' : 'info');
        
        if ($isThrow) {
            throw new HttpException(404, $message);
        }
    }
    
    
    /**
     * 异步通知处理
     * @param string $payType 支付类型
     * @return Response
     */
    public function notify($payType) : Response
    {
        // 获取支付类型
        $payType = trim($payType);
        if (!$payType) {
            $var     = $this->getTradeConfig('var_pay_type', 'pay_type');
            $payType = $this->app->request->param($var);
        }
        if (!$payType) {
            $this->log('收到三方支付异步通知, 但无法获取支付类型', true, true);
        }
        
        $payTypes = $this->getPayTypes();
        if (!isset($payTypes[$payType])) {
            $this->log("收到三方支付异步通知, 支付类型为: {$payType}，但无法识别该支付类型", true, true);
        }
        
        $payName = $payTypes[$payType]['name'] ?: $payType;
        try {
            $this->log("收到三方支付异步通知, 支付类型为: {$payName}");
            
            $class = $this->getTradeConfig("apis.{$payType}.notify");
            if (!$class) {
                throw new AppException("{$payName}对应的支付类型:{$payType}未配置异步处理程序");
            }
            if (!class_exists($class)) {
                throw new AppException("无法找到异步处理程序: {$class}");
            }
            
            // 实例化异步处理类
            $notify = new $class();
            if (!$notify instanceof PayNotify) {
                $api = PayNotify::class;
                throw new AppException("异步处理程序: {$class} 未集成接口: {$api}");
            }
            
            if (!$notify->getPayTradeNo()) {
                throw new AppException("无法获取支付订单号，请确认异步处理程序中已配置 setPayTradeNo()");
            }
        } catch (Throwable $e) {
            $this->log("异步通知处理失败: {$e->getMessage()}", true, true);
        }
        
        
        try {
            $this->log("开始处理异步通知, 通知参数为: {$notify->getRequestString()}");
            $result  = $this->setPaySuccess($notify->notify());
            $message = $result ? '支付成功' : '已支付过';
            $this->log("异步通知处理完成: {$message}");
            
            return $notify->onSuccess($result);
        } catch (Throwable $e) {
            $this->log("异步通知处理失败: {$e->getMessage()}", true);
            
            return $notify->onError($e);
        }
    }
    
    
    /**
     * 设置订单支付成功
     * @param PayNotifyResult $result
     * @param bool            $disabledTrans 是否禁用内部事物，默认不禁用
     * @return bool true: 支付成功，false: 已支付过
     * @throws AppException
     */
    public function setPaySuccess(PayNotifyResult $result, bool $disabledTrans = false) : bool
    {
        $payTradeNo = $result->getPayTradeNo();
        
        // 设为支付成功
        $this->startTrans($disabledTrans);
        try {
            // 检测是否支付过
            $info   = $this->lock(true)->getInfoByPayTradeNo($payTradeNo);
            $return = true;
            if ($info['is_pay']) {
                $return = false;
                goto commit;
            }
            
            
            // 获取订单模型将订单设为支付成功
            $payTradeNo   = $info['pay_trade_no'];
            $orderTradeNo = $info['order_trade_no'];
            $payId        = $info['id'];
            try {
                $modal = $this->getOrderModel($orderTradeNo);
                $modal->setPaySuccess($orderTradeNo, $payId, $payTradeNo, $result->getApiPrice());
                $orderStatus       = self::ORDER_STATUS_SUCCESS;
                $orderStatusRemark = '';
            } catch (AppException $e) {
                $orderStatus       = self::ORDER_STATUS_FAIL;
                $orderStatusRemark = $e->getMessage();
            }
            
            
            // 设置支付订单状态
            $save                    = TradePayField::init();
            $save->orderStatus       = $orderStatus;
            $save->orderStatusRemark = $orderStatusRemark;
            $save->apiTradeNo        = $result->getApiTradeNo();
            $save->apiPrice          = $result->getApiPrice();
            $save->refundAmount      = $result->getApiPrice();
            $save->apiBank           = $result->getApiBankName();
            $save->payTime           = time();
            $save->updateTime        = time();
            if (false === $this->where('id', '=', $payId)->saveData($save)) {
                throw new SQLException('设置支付订单支付成功出现错误', $this);
            }
            
            commit:
            $this->commit($disabledTrans);
            
            return $return;
        } catch (AppException $e) {
            $this->rollback($disabledTrans);
            
            throw $e;
        }
    }
    
    
    /**
     * 获取支付类型
     * @param string $val
     * @return array
     */
    public function getTypes($val = null)
    {
        static $list;
        
        if (!isset($list)) {
            $apis = $this->getTradeConfig("apis");
            $list = [];
            foreach ($apis as $type => $r) {
                $list[$type] = $r['name'];
            }
        }
        
        return self::parseVars($list, $val);
    }
    
    
    /**
     * 解析数据记录
     * @param array $list
     * @return array
     */
    public static function parseList($list)
    {
        return parent::parseList($list, function($list) {
            $ticketStatusList = self::getTicketStatus();
            $payTypes         = self::init()->getPayTypes();
            foreach ($list as $i => $r) {
                $r['format_create_time'] = Transform::date($r['create_time']);
                $r['format_pay_time']    = Transform::date($r['pay_time']);
                $r['pay_type']           = intval($r['pay_type']);
                $r['is_pay']             = $r['pay_time'] > 0;
                
                // 状态
                $r['order_status']  = intval($r['order_status']);
                $r['order_success'] = $r['order_status'] == self::ORDER_STATUS_SUCCESS;
                $r['order_fail']    = $r['order_status'] == self::ORDER_STATUS_FAIL;
                
                // 支付类型
                $types              = $payTypes[$r['pay_type']] ?? [];
                $r['pay_type_name'] = $types['name'] ?? '未知';
                $r['pay_name']      = $types['alias'] ?? '未知';
                
                
                // 开票类型
                $r['ticket_status_name'] = $ticketStatusList[$r['ticket_status']];
                $r['ticket_is_none']     = $r['ticket_status'] == self::TICKET_STATUS_NONE;
                $r['ticket_is_pending']  = $r['ticket_status'] == self::TICKET_STATUS_PENDING;
                $r['ticket_is_success']  = $r['ticket_status'] == self::TICKET_STATUS_SUCCESS;
                $r['ticket_is_fail']     = $r['ticket_status'] == self::TICKET_STATUS_FAIL;
                
                // 是否可以申请开票
                $r['can_apply_ticket'] = ($r['is_pay'] && $r['ticket_is_none']) || $r['ticket_is_fail'];
                
                $list[$i] = $r;
            }
            
            return $list;
        });
    }
    
    
    /**
     * 解析订单扩展数据
     * @param array $list
     * @param bool  $isOnly
     * @return array
     */
    public static function parseExtendList($list, $isOnly = false)
    {
        return parent::parseExtendList($list, $isOnly, function($list) {
            $userIds = [];
            foreach ($list as $i => $r) {
                $userIds[] = $r['user_id'];
            }
            
            $userList = self::init()->getMemberModel()->buildListWithField($userIds);
            foreach ($list as $i => $r) {
                $r['user'] = $userList[$r['user_id']] ?? [];
                $list[$i]  = $r;
            }
            
            return $list;
        });
    }
    
    
    /**
     * 获取支持的支付方式
     * @param string $orderTradeNo 业务订单号，不传则全部获取
     * @param array  $sort 排序和过滤
     * @param int    $defaultType 默认支付类型
     * @return array
     */
    public function getPayTypes($orderTradeNo = '', $sort = [], $defaultType = null)
    {
        $orderTradeNo = trim($orderTradeNo);
        $excludes     = [];
        if ($orderTradeNo && $orderType = TradeNo::getType($orderTradeNo)) {
            $excludes = $this->getTradeConfig("models.{$orderType}.exclude", []);
        }
        
        
        $apis    = $this->getTradeConfig("apis", []);
        $payList = [];
        foreach ($apis as $payType => $r) {
            if (in_array($payType, $excludes)) {
                continue;
            }
            
            $payList[$payType] = [
                'type'      => $payType,
                'alias'     => $r['alias'],
                'name'      => $r['name'],
                'is_active' => $defaultType == $payType
            ];
        }
        
        // 排序
        if ($sort) {
            $list = [];
            foreach ($sort as $payType) {
                if (isset($payList[$payType])) {
                    $list[$payType] = $payList[$payType];
                }
            }
            
            return $list;
        } else {
            return $payList;
        }
    }
    
    
    /**
     * 获取开票状态
     * @param int $val
     * @return array|string
     */
    public static function getTicketStatus($val = null)
    {
        return self::parseVars([
            self::TICKET_STATUS_NONE    => '未开票',
            self::TICKET_STATUS_PENDING => '开票中',
            self::TICKET_STATUS_SUCCESS => '已开票',
            self::TICKET_STATUS_FAIL    => '开票失败'
        ], $val);
    }
    
    
    /**
     * 设置开票状态
     * @param $ids
     * @param $status
     * @throws SQLException
     */
    public function setTicketStatus($ids, $status)
    {
        $where              = TradePayField::init();
        $where->id          = ['in', $ids];
        $save               = TradePayField::init();
        $save->ticketStatus = $status;
        if (false === $this->whereof($where)->saveData($save)) {
            throw new SQLException('设为开票中失败', $this);
        }
    }
}