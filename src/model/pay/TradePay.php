<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\pay;

use BusyPHP\App;
use BusyPHP\exception\ClassNotImplementsException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\LogHelper;
use BusyPHP\Model;
use BusyPHP\queue\facade\Queue;
use BusyPHP\trade\exception\PaidException;
use BusyPHP\trade\interfaces\PayCreate;
use BusyPHP\trade\interfaces\PayCreateSyncReturn;
use BusyPHP\trade\interfaces\PayNotify;
use BusyPHP\trade\interfaces\PayNotifyResult;
use BusyPHP\trade\interfaces\PayOrder;
use BusyPHP\trade\interfaces\PayOrderAfter;
use BusyPHP\trade\interfaces\PayOrderInvalid;
use BusyPHP\trade\interfaces\PayOrderPayData;
use BusyPHP\trade\interfaces\TradeMemberAdminPayOperateAttr;
use BusyPHP\trade\interfaces\TradeMemberModel;
use BusyPHP\trade\job\InvalidJob;
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\TradeConfig;
use BusyPHP\trade\Service;
use Closure;
use LogicException;
use RangeException;
use RuntimeException;
use think\Container;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;
use think\facade\Route;
use think\Response;
use think\route\Url;
use Throwable;

/**
 * 系统支付订单模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午4:06 TradePay.php $
 * @method TradePayInfo getInfo($data, $notFoundMessage = null)
 * @method TradePayInfo findInfo($data = null, $notFoundMessage = null)
 * @method TradePayInfo[] selectList()
 * @method TradePayInfo[] buildListWithField(array $values, $key = null, $field = null) : array
 * @method TradePayExtendInfo getExtendInfo($data, $notFoundMessage = null)
 * @method TradePayExtendInfo findExtendInfo($data = null, $notFoundMessage = null)
 * @method TradePayExtendInfo[] selectExtendList()
 * @method TradePayExtendInfo[] buildExtendListWithField(array $values, $key = null, $field = null) : array
 */
class TradePay extends Model
{
    use TradeConfig;
    
    // +----------------------------------------------------
    // + 业务订单状态
    // +----------------------------------------------------
    /** @var int 支付成功 */
    const ORDER_STATUS_SUCCESS = 1;
    
    /** @var int 支付失败 */
    const ORDER_STATUS_FAIL = 2;
    
    // +----------------------------------------------------
    // + 开票状态
    // +----------------------------------------------------
    /** @var int 未开票 */
    const TICKET_STATUS_NONE = 0;
    
    /** @var int 开票中 */
    const TICKET_STATUS_PENDING = 1;
    
    /** @var int 开票成功 */
    const TICKET_STATUS_SUCCESS = 2;
    
    /** @var int 开票失败 */
    const TICKET_STATUS_FAIL = 3;
    
    // +----------------------------------------------------
    // + 退款状态
    // +----------------------------------------------------
    /** @var int 无退款 */
    const REFUND_STATUS_NONE = 0;
    
    /** @var int 部分退款 */
    const REFUND_STATUS_PART = 1;
    
    /** @var int 全部退款 */
    const REFUND_STATUS_WHOLE = 2;
    
    // +----------------------------------------------------
    // + 其它付款方式
    // +----------------------------------------------------
    /** @var int 转账/收款 */
    const OTHER_PAY_TRANSFER = -98;
    
    /** @var int 其它支付方式 */
    const OTHER_PAY_OTHER = -99;
    
    protected $bindParseClass       = TradePayInfo::class;
    
    protected $bindParseExtendClass = TradePayExtendInfo::class;
    
    protected $dataNotFoundMessage  = '支付记录不存在';
    
    protected $listNotFoundMessage  = '支付记录不存在';
    
    
    /**
     * 创建支付订单
     * @param TradePayField $insert
     * @param bool          $disabledTrans
     * @return string
     * @throws Throwable
     */
    protected function createOrder(TradePayField $insert, $disabledTrans = false)
    {
        // 校验是否支付
        if ($this->checkPayByOrderTradeNo($insert->orderTradeNo)) {
            throw new VerifyException('该订单已支付，请勿重复支付', 'pay_success');
        }
        
        // 获取订单号前缀配置
        if (!$type = $this->getTradeConfig('pay_no_prefix', 1001)) {
            throw new RuntimeException('请前往 config/busy-trade.php 配置 pay_no_prefix');
        }
        
        $this->startTrans($disabledTrans);
        try {
            $time                = time();
            $insert->payTradeNo  = TradeNo::init()->get($type);
            $insert->createTime  = $time;
            $insert->updateTime  = $time;
            $insert->invalidTime = $time + $this->getPayValidDuration();
            
            $insertId = $this->addData($insert);
            
            $this->commit($disabledTrans);
            
            return $insertId;
        } catch (Throwable $e) {
            $this->rollback($disabledTrans);
            
            throw $e;
        }
    }
    
    
    /**
     * 通过交易号获取支付订单
     * @param string $payTradeNo 支付订单号
     * @return TradePayInfo
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function getInfoByPayTradeNo(string $payTradeNo) : TradePayInfo
    {
        return $this->whereEntity(TradePayField::payTradeNo($payTradeNo))
            ->failException(true)
            ->findInfo();
    }
    
    
    /**
     * 通过业务订单号获取支付订单
     * @param string $orderTradeNo
     * @return TradePayInfo
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function getInfoByOrderTradeNo(string $orderTradeNo) : TradePayInfo
    {
        return $this->whereEntity(TradePayField::orderTradeNo($orderTradeNo))
            ->failException(true)
            ->findInfo();
    }
    
    
    /**
     * 通过业务订单号获取已支付的订单
     * @param string $orderTradeNo
     * @return TradePayInfo
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function getPayInfoByOrderTradeNo(string $orderTradeNo) : TradePayInfo
    {
        return $this->whereEntity(TradePayField::payTime('>', 0))->getInfoByOrderTradeNo($orderTradeNo);
    }
    
    
    /**
     * 检测外部订单号是否已支付
     * @param string $orderTradeNo
     * @return TradePayInfo|null
     * @throws DbException
     */
    public function checkPayByOrderTradeNo(string $orderTradeNo) : ?TradePayInfo
    {
        try {
            return $this->getPayInfoByOrderTradeNo($orderTradeNo);
        } catch (DataNotFoundException $e) {
            return null;
        }
    }
    
    
    /**
     * 通过订单号获取支付需要的数据
     * @param string $orderTradeNo
     * @return PayOrderPayData
     * @throws Throwable
     */
    public function getPayData(string $orderTradeNo) : PayOrderPayData
    {
        $model = $this->getOrderModel($orderTradeNo);
        
        if ($this->checkPayByOrderTradeNo($orderTradeNo)) {
            throw new VerifyException('该订单已支付，请勿重复支付', 'pay_success');
        }
        
        return $model->getPayData($orderTradeNo);
    }
    
    
    /**
     * 获取会员模型
     * @return TradeMemberModel
     */
    public function getMemberModel() : TradeMemberModel
    {
        $class = $this->getTradeConfig('trade_member', '');
        if (!$class || !class_exists($class)) {
            throw new ClassNotFoundException('会员模型未绑定或不存在', $class);
        }
        
        $parentClass = TradeMemberModel::class;
        if (!is_subclass_of($class, $parentClass)) {
            throw new ClassNotImplementsException($class, $parentClass, "会员模型类");
        }
        
        return Container::getInstance()->make($class, [], true);
    }
    
    
    /**
     * 通过业务订单号获取订单模型
     * @param string $orderTradeNo 业务订单号
     * @return PayOrder|PayOrderInvalid|PayOrderAfter
     */
    public function getOrderModel(string $orderTradeNo)
    {
        if (!$orderTradeNo) {
            throw new ParamInvalidException('order_trade_no');
        }
        
        $type = TradeNo::getType($orderTradeNo);
        if (!$type) {
            throw new ParamInvalidException('trade type');
        }
        
        $class = $this->getTradeConfig("models.{$type}.model", '');
        if (!$class || !class_exists($class)) {
            throw new ClassNotFoundException("业务订单[{$orderTradeNo}]未绑定模型", $class);
        }
        
        $parentClass = PayOrder::class;
        if (!is_subclass_of($class, $parentClass)) {
            throw new ClassNotImplementsException($class, $parentClass, '订单模型类');
        }
        
        return Container::getInstance()->make($class, [], true);
    }
    
    
    /**
     * 设置绑定模型为失效
     * @param int $id
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function invalidModel($id)
    {
        $info = $this->getInfo($id);
        
        // 已支付的不在设置
        if ($info->isPay) {
            return;
        }
        
        $model = $this->getOrderModel($info->orderTradeNo);
        if ($model instanceof PayOrderInvalid) {
            $model->onPayInvalid($info);
        }
    }
    
    
    /**
     * 获取支付模型
     * @param int    $userId 会员ID
     * @param int    $payType 支付类型
     * @param string $orderTradeNo 业务订单号
     * @param float  $debugPrice 调试支付金额
     * @return PayCreate
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Throwable
     */
    public function pay($userId, int $payType, string $orderTradeNo, float $debugPrice = 0.0) : PayCreate
    {
        // 校验支付方式
        $payList = $this->getPayTypes($orderTradeNo);
        if (!isset($payList[$payType])) {
            throw new RangeException("不支持该支付方式: {$payType}");
        }
        
        // 实例化三方支付类
        $class = $this->getTradeConfig("apis.{$payType}.create", '');
        if (!$class || !class_exists($class)) {
            throw new ClassNotFoundException("该支付方式[ {$payType} ]未绑定支付接口", $class);
        }
        
        $create = Container::getInstance()->make($class, [], true);
        if (!$create instanceof PayCreate) {
            throw new ClassNotImplementsException($class, PayCreate::class, '支付接口类');
        }
        
        // 创建支付订单
        $payData = $this->getPayData($orderTradeNo);
        if ($payData->isPay()) {
            throw new VerifyException('该订单已支付成功', 'paid');
        }
        
        $data               = TradePayField::init();
        $data->userId       = $userId;
        $data->payType      = $payType;
        $data->price        = $payData->getPrice();
        $data->orderTradeNo = $payData->getOrderTradeNo();
        $data->title        = $payData->getBody();
        
        // 赋值模型
        $info = $this->getInfo($this->createOrder($data));
        if ($debugPrice > 0) {
            $info->price = $debugPrice;
        }
        
        $create->setTradeInfo($info);
        $create->setNotifyUrl($this->createNotifyUrl($payType)->build());
        
        // 发布失效任务
        if ($this->getTradeConfig('pay_queue.enable', false)) {
            Queue::connection(Service::QUEUE_CONNECTION)
                ->later($this->getPayValidDuration(), InvalidJob::class, $info->id, $this->getTradeConfig('pay_queue.name', Service::DEFAULT_PAY_QUEUE));
        }
        
        return $create;
    }
    
    
    /**
     * 生成异步通知地址
     * @param int $payType
     * @return Url
     */
    public function createNotifyUrl(int $payType) : Url
    {
        $payTypeVar = $this->getTradeConfig('var_pay_type', 'pay_type');
        $host       = $this->getTradeConfig('host', '') ?: true;
        $ssl        = $this->getTradeConfig('ssl', false);
        
        return Route::buildUrl('/' . Service::URL_NOTIFY_PATH . "pay/{$payTypeVar}/{$payType}")
            ->domain($host)
            ->https($ssl);
    }
    
    
    /**
     * 解析同步返回结果
     * @param int $payType
     * @return PayCreateSyncReturn
     * @throws Throwable
     */
    public function parseReturn(int $payType) : PayCreateSyncReturn
    {
        $payType = trim("$payType");
        $class   = $this->getTradeConfig("apis.{$payType}.create", '');
        if (!$class || !class_exists($class)) {
            throw new ClassNotFoundException('解析同步返回结果处理类不存在或未配置', $class);
        }
        
        $class = Container::getInstance()->make($class, [], true);
        if (!$class instanceof PayCreate) {
            throw new ClassNotImplementsException($class, PayCreate::class, '同步结果解析类');
        }
        
        return $class->syncReturn();
    }
    
    
    /**
     * 日志驱动
     * @param string      $tag 标签/标题
     * @param string|bool $method 出错方法名 或 标签是否为出错方法名
     * @return LogHelper
     */
    public static function log(string $tag = '', $method = '') : LogHelper
    {
        $log = LogHelper::plugin('trade_pay');
        if ($tag && $method === true) {
            $log->method($tag);
        } else {
            $log->tag($tag, $method);
        }
        
        return $log;
    }
    
    
    /**
     * 异步通知处理
     * @param int $payType 支付类型
     * @return Response
     */
    public function notify(int $payType = 0) : Response
    {
        // 获取支付类型
        if (!$payType) {
            $var     = $this->getTradeConfig('var_pay_type', 'pay_type');
            $payType = App::getInstance()->request->param("{$var}/d", 0);
        }
        
        $tag      = '支付异步通知';
        $payTypes = $this->getPayTypes();
        if (!isset($payTypes[$payType])) {
            $message = "支付类型为 {$payType}，但无法识别该支付类型";
            self::log($tag, __METHOD__)->error($message);
            throw new HttpException(503, $message);
        }
        
        $payName = $payTypes[$payType]['name'] ?: $payType;
        self::log($tag)->info("收到支付类型为: {$payName}");
        
        try {
            $class = $this->getTradeConfig("apis.{$payType}.notify", '');
            if (!$class || !class_exists($class)) {
                throw new ClassNotFoundException("{$payName}对应的支付类型{$payType}异步处理程序不存在", $class);
            }
            
            // 实例化异步处理类
            $notify = Container::getInstance()->make($class, [], true);
            if (!$notify instanceof PayNotify) {
                throw new ClassNotImplementsException($class, PayNotify::class, '异步处理程序');
            }
            
            if (!$notify->getPayTradeNo()) {
                throw new RuntimeException("无法获取支付订单号，请确认异步处理程序中已配置 setPayTradeNo()");
            }
        } catch (Throwable $e) {
            self::log($tag, __METHOD__)->error($e);
            throw new HttpException(503, $e->getMessage());
        }
        
        
        try {
            self::log($tag)->info($notify->getRequestSourceParams());
            $result = $this->setPaySuccess($notify->notify());
            self::log($tag)->info($result ? '支付成功' : '重复通知，该订单已支付');
            
            return $notify->onSuccess($result);
        } catch (Throwable $e) {
            self::log($tag, __METHOD__)->error($e);
            
            return $notify->onError($e);
        }
    }
    
    
    /**
     * 设置业务订单为支付成功
     * @param $id
     * @throws Throwable
     */
    public function setOrderSuccess($id)
    {
        $triggerStatus = false;
        $triggerResult = null;
        $this->startTrans();
        try {
            $info       = $this->lock(true)->getInfo($id);
            $orderModel = $this->getOrderModel($info->orderTradeNo);
            if (!$info->orderFail) {
                throw new LogicException('订单状态异常');
            }
            
            try {
                $triggerResult     = $orderModel->setPaySuccess($info);
                $orderStatus       = self::ORDER_STATUS_SUCCESS;
                $orderStatusRemark = '';
                $triggerStatus     = true;
            } catch (Throwable $e) {
                if ($e instanceof PaidException) {
                    throw $e;
                }
                
                $orderStatus       = self::ORDER_STATUS_FAIL;
                $orderStatusRemark = $e->getMessage();
            }
            
            $save                    = TradePayField::init();
            $save->orderStatus       = $orderStatus;
            $save->orderStatusRemark = $orderStatusRemark;
            $save->updateTime        = time();
            $this->whereEntity(TradePayField::id($info->id))->saveData($save);
            
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            
            throw $e;
        }
        
        // 触发模型支付成功后置操作
        if ($orderModel instanceof PayOrderAfter && $triggerStatus) {
            try {
                $orderModel->setPaySuccessAfter($info, $triggerResult);
            } catch (Throwable $e) {
                self::log("触发模型支付成功后置操作失败, id: {$info->id}, order_trade_no: {$info->orderTradeNo}")->error($e);
            }
        }
    }
    
    
    /**
     * 设置订单支付成功
     * @param PayNotifyResult $result
     * @param bool            $checkPay 是否检测业务订单已支付
     * @param bool            $disabledTrans 是否禁用内部事物，默认不禁用
     * @return bool true: 支付成功，false: 已支付过
     * @throws Throwable
     */
    public function setPaySuccess(PayNotifyResult $result, bool $checkPay = false, bool $disabledTrans = false) : bool
    {
        $payTradeNo    = $result->getPayTradeNo();
        $info          = $this->getInfoByPayTradeNo($payTradeNo);
        $triggerStatus = false;
        $triggerResult = null;
        
        // 设为支付成功
        $this->startTrans($disabledTrans);
        try {
            $info       = $this->lock(true)->getInfo($info->id);
            $orderModel = $this->getOrderModel($info->orderTradeNo);
            $return     = true;
            
            // 检测是否支付过
            if ($info->isPay) {
                $return = false;
                goto commit;
            }
            
            // 检测业务订单是否支付
            if ($checkPay) {
                if ($this->checkPayByOrderTradeNo($info->orderTradeNo)) {
                    throw new VerifyException('重复支付，该业务订单已被支付，请检查与该业务订单相关的支付记录', 'paid');
                }
            }
            
            
            // 获取订单模型将订单设为支付成功
            try {
                $triggerResult     = $orderModel->setPaySuccess($info);
                $orderStatus       = self::ORDER_STATUS_SUCCESS;
                $orderStatusRemark = '';
                $triggerStatus     = true;
            } catch (Throwable $e) {
                $orderStatus = self::ORDER_STATUS_FAIL;
                if ($e instanceof PaidException) {
                    $orderStatusRemark = '重复支付，该业务订单已被支付，请检查与该业务订单相关的支付记录';
                } else {
                    $orderStatusRemark = $e->getMessage();
                }
            }
            
            
            // 设置支付订单状态
            $save                    = TradePayField::init();
            $save->orderStatus       = $orderStatus;
            $save->orderStatusRemark = $orderStatusRemark;
            $save->apiTradeNo        = $result->getApiTradeNo();
            $save->apiPrice          = $result->getApiPrice();
            $save->refundAmount      = $result->getApiPrice();
            $save->payRemark         = $result->getPayRemark();
            $save->payTime           = $result->getPayTime() > 0 ? $result->getPayTime() : time();
            $save->payType           = $result->getPayType();
            $save->updateTime        = time();
            $this->whereEntity(TradePayField::id($info->id))->saveData($save);
            
            commit:
            $this->commit($disabledTrans);
        } catch (Throwable $e) {
            $this->rollback($disabledTrans);
            
            throw $e;
        }
        
        // 触发模型支付成功后置操作
        if ($triggerStatus && $orderModel instanceof PayOrderAfter) {
            try {
                $orderModel->setPaySuccessAfter($info, $triggerResult);
            } catch (Throwable $e) {
                self::log("触发模型支付成功后置操作失败, id: {$info->id}, order_trade_no: {$info->orderTradeNo}")->error($e);
            }
        }
        
        return $return;
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
            $apis = $this->getTradeConfig("apis", []);
            $list = [];
            foreach ($apis as $type => $r) {
                $list[$type] = $r['name'];
            }
        }
        
        return self::parseVars($list, $val);
    }
    
    
    /**
     * @param TradePayExtendInfo[] $list
     * @throws DataNotFoundException
     * @throws DbException
     */
    protected function onParseBindExtendList(array &$list)
    {
        $userIds = [];
        foreach ($list as $item) {
            $userIds[] = $item->userId;
        }
        
        $model       = $this->getMemberModel();
        $userList    = $model->buildListWithField($userIds);
        $userParams  = $model->getTradeUserParams();
        $usernameKey = (string) $userParams->getUsernameField();
        $phoneKey    = $userParams->getPhoneField() ? (string) $userParams->getPhoneField() : '';
        $nicknameKey = $userParams->getNicknameField() ? (string) $userParams->getNicknameField() : '';
        $emailKey    = $userParams->getEmailField() ? (string) $userParams->getEmailField() : '';
        $callback    = $userParams->getAdminPayOperateUserAttr();
        foreach ($list as $item) {
            $item->user = $userList[$item->userId] ?? null;
            
            $item->username = $item->user[$usernameKey] ?? '';
            
            // 手机号
            if ($phoneKey) {
                $item->userPhone = $item->user[$phoneKey] ?? '';
                if ($item->userPhone && !$item->username) {
                    $item->username = $item->userPhone;
                }
            }
            
            // 昵称
            if ($nicknameKey) {
                $item->userNickname = $item->user[$nicknameKey] ?? '';
                if ($item->userNickname && !$item->username) {
                    $item->username = $item->userNickname;
                }
            }
            
            // 邮箱
            if ($emailKey) {
                $item->userEmail = $item->user[$emailKey] ?? '';
                if ($item->userEmail && !$item->username) {
                    $item->username = $item->userEmail;
                }
            }
            
            // 管理员模板对用户的操作属性
            $item->adminUserOperateAttr = '';
            $result                     = null;
            if ($callback instanceof Closure || is_callable($callback)) {
                $result = call_user_func_array($callback, [$item]);
            } elseif ($callback instanceof TradeMemberAdminPayOperateAttr) {
                $result = $callback->callback($item);
            }
            
            if ($result) {
                if (is_array($result)) {
                    $attrs = [];
                    foreach ($result as $key => $value) {
                        $attrs[] = "{$key}='{$value}'";
                    }
                    $item->adminUserOperateAttr = " " . implode(' ', $attrs);
                } else {
                    $item->adminUserOperateAttr = " {$result}";
                }
            }
        }
    }
    
    
    /**
     * 获取支持的支付方式
     * @param string $orderTradeNo 业务订单号，不传则全部获取
     * @param array  $sort 排序和过滤
     * @param int    $defaultType 默认支付类型
     * @return array<string,array{type: string, alias: string, name: string, is_active: bool}>
     */
    public function getPayTypes($orderTradeNo = '', $sort = [], $defaultType = null) : array
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
        return self::parseVars(self::parseConst(self::class, 'TICKET_STATUS_', [], function($item) {
            return $item['name'];
        }), $val);
    }
    
    
    /**
     * 获取业务状态
     * @param int $val
     * @return array|string
     */
    public static function getOrderStatus($val = null)
    {
        return self::parseVars(self::parseConst(self::class, 'ORDER_STATUS_', [], function($item) {
            return $item['name'];
        }), $val);
    }
    
    
    /**
     * 获取其它支付方式
     * @param int $val
     * @return array|string
     */
    public static function getOtherPayTypes($val = null)
    {
        return self::parseVars(self::parseConst(self::class, 'OTHER_PAY_', [], function($item) {
            return $item['name'];
        }), $val);
    }
    
    
    /**
     * 检测支付类型是否手动处理的类型
     * @param int $payType
     * @return bool
     */
    public static function checkPayTypeIsManual(int $payType) : bool
    {
        return in_array($payType, array_keys(self::getOtherPayTypes()));
    }
    
    
    /**
     * 设置开票状态
     * @param array $ids ID集合
     * @param int   $status 状态
     * @return int
     * @throws DbException
     */
    public function setTicketStatus(array $ids, int $status) : int
    {
        return $this->whereEntity()
            ->whereEntity(TradePayField::id('in', $ids))
            ->setField(TradePayField::ticketStatus(), $status);
    }
    
    
    /**
     * 更新剩余可退金额
     * @param int   $id 订单ID
     * @param float $amount 减少或增加的金额
     * @return int
     * @throws DbException
     */
    public function updateRefundAmount($id, float $amount) : int
    {
        if ($amount < 0) {
            return $this->whereEntity(TradePayField::id($id))->setDec(TradePayField::refundAmount(), abs($amount));
        } elseif ($amount > 0) {
            return $this->whereEntity(TradePayField::id($id))->setInc(TradePayField::refundAmount(), $amount);
        }
        
        return 0;
    }
}