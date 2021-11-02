<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\refund;

use BusyPHP\App;
use BusyPHP\app\admin\model\system\lock\SystemLock;
use BusyPHP\app\admin\model\system\plugin\SystemPlugin;
use BusyPHP\exception\ClassNotFoundException;
use BusyPHP\exception\ClassNotImplementsException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\LogHelper;
use BusyPHP\Model;
use BusyPHP\trade\interfaces\PayRefund;
use BusyPHP\trade\interfaces\PayRefundNotify;
use BusyPHP\trade\interfaces\PayRefundNotifyResult;
use BusyPHP\trade\interfaces\PayRefundQuery;
use BusyPHP\trade\interfaces\TradeUpdateRefundAmountInterface;
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\pay\TradePayInfo;
use BusyPHP\trade\model\TradeConfig;
use BusyPHP\trade\Service;
use DomainException;
use Exception;
use RuntimeException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\exception\HttpException;
use think\facade\Route;
use think\Response;
use think\route\Url;

/**
 * 交易退款模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午5:25 TradeRefund.php $
 * @method TradeRefundInfo getInfo($data, $notFoundMessage = null)
 * @method TradeRefundInfo findInfo($data = null, $notFoundMessage = null)
 * @method TradeRefundInfo[] selectList()
 * @method TradeRefundExtendInfo getExtendInfo($data, $notFoundMessage = null)
 * @method TradeRefundExtendInfo findExtendInfo($data = null, $notFoundMessage = null)
 * @method TradeRefundExtendInfo[] selectExtendList()
 */
class TradeRefund extends Model
{
    use TradeConfig;
    
    // +----------------------------------------------------
    // + 退款状态
    // +----------------------------------------------------
    /** @var int 未退款 */
    const REFUND_STATUS_WAIT = 0;
    
    /** @var int 进入退款列队 */
    const REFUND_STATUS_IN_REFUND_QUEUE = 1;
    
    /** @var int 退款中 */
    const REFUND_STATUS_PENDING = 2;
    
    /** @var int 进入查询列队 */
    const REFUND_STATUS_IN_QUERY_QUEUE = 3;
    
    /** @var int 退款成功 */
    const REFUND_STATUS_SUCCESS = 8;
    
    /** @var int 退款失败 */
    const REFUND_STATUS_FAIL = 9;
    
    protected $bindParseClass       = TradeRefundInfo::class;
    
    protected $bindParseExtendClass = TradeRefundExtendInfo::class;
    
    protected $dataNotFoundMessage  = '退款记录不存在';
    
    protected $listNotFoundMessage  = '退款记录不存在';
    
    
    /**
     * 获取状态
     * @param int $val
     * @return array|string
     */
    public static function getStatus($val = null)
    {
        return self::parseVars(self::parseConst(self::class, 'REFUND_', [], function($item) {
            return $item['name'];
        }), $val);
    }
    
    
    /**
     * 日志驱动
     * @param string      $tag 标签/标题
     * @param string|bool $method 出错方法名 或 标签是否为出错方法名
     * @return LogHelper
     */
    public static function log(string $tag = '', $method = '') : LogHelper
    {
        $log = LogHelper::plugin('trade_refund');
        if ($tag && $method === true) {
            $log->method($tag);
        } else {
            $log->tag($tag, $method);
        }
        
        return $log;
    }
    
    
    /**
     * 创建退款订单
     * @param string $orderTradeNo 业务订单号
     * @param int    $orderType 业务类型
     * @param string $orderValue 业务参数
     * @param string $refundRemark 退款原因
     * @param float  $price 退款金额，传0则全退
     * @param bool   $mustRefund 是否强制退款剩余金额
     * @return TradeRefundField
     * @throws ParamInvalidException
     * @throws Exception
     */
    public function joinRefund(string $orderTradeNo, int $orderType = 0, string $orderValue = '', string $refundRemark = '', float $price = 0, bool $mustRefund = false) : TradeRefundField
    {
        // 获取订单号前缀配置
        if (!$refundNoPrefix = $this->getTradeConfig('refund_no_prefix', 1002)) {
            throw new RuntimeException('未配置退款订单号前缀: refund_no_prefix');
        }
        
        $insert = TradeRefundField::init();
        TradePay::init()
            ->updateRefundAmountByCallback($orderTradeNo, new class($orderTradeNo, $orderType, $orderValue, $refundRemark, $price, $mustRefund, $insert, $this) implements TradeUpdateRefundAmountInterface {
                /**
                 * 业务订单号
                 * @var string
                 */
                private $orderTradeNo;
                
                /**
                 * 业务类型
                 * @var int
                 */
                private $orderType;
                
                /**
                 * 业务参数
                 * @var string
                 */
                private $orderValue;
                
                /**
                 * 退款说明
                 * @var string
                 */
                private $refundRemark;
                
                /**
                 * 退款金额
                 * @var float
                 */
                private $price;
                
                /**
                 * 剩余金额不足，是否强制退掉剩余金额
                 * @var bool
                 */
                private $mustRefund;
                
                /**
                 * @var TradeRefundField
                 */
                private $insert;
                
                /**
                 * @var TradeRefund
                 */
                private $refundTarget;
                
                
                public function __construct(string $orderTradeNo, int $orderType, string $orderValue, string $refundRemark, float $price, bool $mustRefund, TradeRefundField $insert, TradeRefund $refundTarget)
                {
                    $this->orderTradeNo = $orderTradeNo;
                    $this->orderType    = $orderType;
                    $this->orderValue   = $orderValue;
                    $this->refundRemark = $refundRemark;
                    $this->price        = $price;
                    $this->mustRefund   = $mustRefund;
                    $this->insert       = $insert;
                    $this->refundTarget = $refundTarget;
                }
                
                
                /**
                 * 执行更新，内部无需启动事物
                 * @param TradePayInfo $tradePayInfo
                 * @return float 返回要更新的金额，整数为加上，负数为减去，返回null或0则不更新
                 * @throws Exception
                 */
                public function onUpdate(TradePayInfo $tradePayInfo) : ?float
                {
                    // 获取订单号前缀
                    if (!$refundNoPrefix = $this->refundTarget->getTradeConfig('refund_no_prefix', 1002)) {
                        throw new RuntimeException('未配置退款订单号前缀: refund_no_prefix');
                    }
                    
                    // 传入的退款金额为0，则取实际支付金额
                    if ($this->price <= 0) {
                        $this->price = $tradePayInfo->apiPrice;
                    }
                    
                    // 传入的退款金额 大于 剩余可退金额
                    if ($this->price > $tradePayInfo->refundAmount) {
                        // 强制退款剩余可退金额
                        if ($this->mustRefund) {
                            $this->price = $tradePayInfo->refundAmount;
                        } else {
                            throw new VerifyException("剩余可退金额为{$tradePayInfo->refundAmount},不足本次退款", 'refund_amount_not_enough');
                        }
                    }
                    
                    if ($this->price <= 0) {
                        throw new VerifyException("退款金额为0，无法退款", 'refund_amount_empty');
                    }
                    
                    // 统计累计退款金额是否大于实际支付金额
                    $totalAmount = (float) $this->refundTarget->whereEntity(TradeRefundField::payId($tradePayInfo->id))
                        ->whereEntity(TradeRefundField::status('<>', TradeRefund::REFUND_STATUS_FAIL))
                        ->sum(TradeRefundField::refundPrice());
                    if ($totalAmount + $this->price > $tradePayInfo->apiPrice) {
                        throw new VerifyException('订单累计退款金额超出实际支付金额', 'refund_overstep');
                    }
                    
                    // 创建退款订单
                    $this->insert->userId        = $tradePayInfo->userId;
                    $this->insert->refundNo      = TradeNo::init()->get($refundNoPrefix);
                    $this->insert->payId         = $tradePayInfo->id;
                    $this->insert->payTradeNo    = $tradePayInfo->payTradeNo;
                    $this->insert->payApiTradeNo = $tradePayInfo->apiTradeNo;
                    $this->insert->payPrice      = $tradePayInfo->apiPrice;
                    $this->insert->payType       = $tradePayInfo->payType;
                    $this->insert->orderTradeNo  = $tradePayInfo->orderTradeNo;
                    $this->insert->orderType     = $this->orderType;
                    $this->insert->orderValue    = $this->orderValue;
                    $this->insert->refundPrice   = $this->price;
                    $this->insert->createTime    = time();
                    $this->insert->status        = TradeRefund::REFUND_STATUS_WAIT;
                    $this->insert->remark        = $this->refundRemark;
                    $this->insert->id            = $this->refundTarget->addData($this->insert);
                    
                    return 0 - $this->price;
                }
            });
        
        return $insert;
    }
    
    
    /**
     * @param array $list
     * @throws DataNotFoundException
     * @throws DbException
     */
    protected function onParseBindExtendList(array &$list)
    {
        $userKey   = (string) TradeRefundExtendInfo::user();
        $userIdKey = (string) TradeRefundField::userId();
        $userIds   = [];
        foreach ($list as $item) {
            if (isset($item[$userIdKey])) {
                $userIds[] = $item[$userIdKey];
            }
        }
        
        $userList = TradePay::init()->getMemberModel()->buildListWithField($userIds);
        foreach ($list as $i => $r) {
            $r[$userKey] = $userList[$r[$userIdKey]] ?? null;
            $list[$i]    = $r;
        }
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
        
        return Route::buildUrl('/' . Service::URL_NOTIFY_PATH . "refund/{$payTypeVar}/{$payType}")
            ->domain($host)
            ->https($ssl);
    }
    
    
    /**
     * 执行单步三方退款
     * @param int $id 订单ID
     * @throws Exception
     */
    public function refund($id)
    {
        $this->startTrans();
        try {
            $info = $this->lock(true)->getInfo($id);
            if (!$info->isRefundInQueue) {
                throw new RuntimeException('当前订单未进入退款列队，无法执行退款');
            }
            
            $payType           = $info->payType;
            $update            = TradeRefundField::init();
            $update->startTime = time();
            
            try {
                // 实例化三方退款类
                $class = $this->getTradeConfig("apis.{$payType}.refund", '');
                if (!$class || !class_exists($class)) {
                    throw new ClassNotFoundException($class, "该支付方式[ {$payType} ]未绑定退款下单接口");
                }
                
                $api = new $class();
                if (!$api instanceof PayRefund) {
                    throw new ClassNotImplementsException($api, PayRefund::class, "退款下单类");
                }
                
                // 执行三方退款
                $api->setTradeRefundInfo($info);
                $api->setNotifyUrl($this->createNotifyUrl($payType)->build());
                $result = $api->refund();
                
                // 需要重新处理
                if ($result->isNeedRehandle()) {
                    $update->status    = self::REFUND_STATUS_WAIT;
                    $update->queueTime = time();
                }
                
                //
                // 退款申请成功
                else {
                    if ($result->getApiRefundNo()) {
                        $update->apiRefundNo = $result->getApiRefundNo();
                    }
                    if ($result->getRefundAccount()) {
                        $update->refundAccount = $result->getRefundAccount();
                    }
                    
                    // 加入列队
                    $update->status    = self::REFUND_STATUS_PENDING;
                    $update->queueTime = 0;
                }
            } catch (Exception $e) {
                // 退款失败
                $update->status       = self::REFUND_STATUS_FAIL;
                $update->failRemark   = $e->getMessage();
                $update->completeTime = time();
                $update->queueTime    = 0;
            }
            
            
            TradePay::init()
                ->updateRefundAmountByCallback($info->orderTradeNo, new class($update, $info, $this) implements TradeUpdateRefundAmountInterface {
                    /**
                     * @var TradeRefundField
                     */
                    private $update;
                    
                    /**
                     * @var TradeRefundInfo
                     */
                    private $refundInfo;
                    
                    /**
                     * @var TradeRefund
                     */
                    private $refundTarget;
                    
                    
                    public function __construct(TradeRefundField $update, TradeRefundInfo $refundInfo, TradeRefund $refundTarget)
                    {
                        $this->update       = $update;
                        $this->refundInfo   = $refundInfo;
                        $this->refundTarget = $refundTarget;
                    }
                    
                    
                    /**
                     * 执行更新，内部无需启动事物
                     * @param TradePayInfo $tradePayInfo
                     * @return float 返回要更新的金额，整数为加上，负数为减去，返回null或0则不更新
                     * @throws Exception
                     */
                    public function onUpdate(TradePayInfo $tradePayInfo) : ?float
                    {
                        // 更新退款订单
                        $this->refundTarget->whereEntity(TradeRefundField::id($this->refundInfo->id))
                            ->saveData($this->update);
                        
                        // 退款失败，则还原可退金额
                        if ($this->update->status == TradeRefund::REFUND_STATUS_FAIL) {
                            try {
                                // 触发业务订单状态
                                $modal = TradePay::init()->getOrderModel($this->refundInfo->orderTradeNo);
                                $modal->setRefundStatus($this->refundInfo, $tradePayInfo, false, $this->update->failRemark);
                            } catch (Exception $e) {
                                TradeRefund::log("退款失败处理完成，但通知业务订单失败", __METHOD__)->error($e);
                            }
                            
                            return (float) $this->refundInfo->refundPrice;
                        }
                        
                        return 0;
                    }
                });
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 执行单步三方查询
     * @param $id
     * @throws Exception
     */
    public function inquiry($id)
    {
        $info = $this->getInfo($id);
        
        // 获取查询接口
        $class = $this->getTradeConfig("apis.{$info->payType}.refund_query", '');
        if (!$class || !class_exists($class)) {
            throw new ClassNotFoundException($class, "该支付方式[ {$info->payType} ]未绑定退款查询接口");
        }
        
        // 执行查询
        $api = new $class();
        if (!$api instanceof PayRefundQuery) {
            throw new ClassNotImplementsException($class, PayRefundQuery::class, '退款查询类');
        }
        $api->setTradeRefundInfo($info);
        $result = $api->query();
        $this->setRefundStatus($result->getNotifyResult());
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
            $payType = App::init()->request->param("{$var}/d", 0);
        }
        
        $tag      = '退款异步通知';
        $payTypes = TradePay::init()->getPayTypes();
        if (!isset($payTypes[$payType])) {
            $message = "支付类型为 {$payType}，但无法识别该支付类型";
            self::log($tag, __METHOD__)->error($message);
            throw new HttpException(503, $message);
        }
        
        $payName = $payTypes[$payType]['name'] ?: $payType;
        self::log($tag)->info("收到支付类型为: {$payName}");
        
        try {
            $class = $this->getTradeConfig("apis.{$payType}.refund_notify", '');
            if (!$class || !class_exists($class)) {
                throw new ClassNotFoundException($class, '退款异步处理类');
            }
            
            // 实例化异步处理类
            $notify = new $class();
            if (!$notify instanceof PayRefundNotify) {
                throw new ClassNotImplementsException($class, PayRefundNotify::class, '异步处理程序');
            }
        } catch (Exception $e) {
            self::log($tag, __METHOD__)->error($e);
            throw new HttpException(503, $e->getMessage());
        }
        
        try {
            self::log($tag)->info($notify->getRequestSourceParams());
            
            try {
                $this->setRefundStatus($notify->notify());
                $status = true;
            } catch (VerifyException $e) {
                if ($e->getField() === 'refunded') {
                    $status = false;
                } else {
                    throw $e;
                }
            }
            self::log($tag)->info('处理完成');
            
            return $notify->onSuccess($status);
        } catch (Exception $e) {
            self::log($tag, __METHOD__)->error($e);
            
            return $notify->onError($e);
        }
    }
    
    
    /**
     * 设置退款状态
     * @param PayRefundNotifyResult $result 退款返回数据
     * @throws Exception
     */
    protected function setRefundStatus(PayRefundNotifyResult $result)
    {
        $this->startTrans();
        try {
            if ($result->getRefundNo()) {
                $this->whereEntity(TradeRefundField::refundNo($result->getRefundNo()));
            } elseif ($result->getApiRefundNo()) {
                $this->whereEntity(TradeRefundField::apiRefundNo($result->getApiRefundNo()));
            } elseif ($result->getPayTradeNo()) {
                $this->whereEntity(TradeRefundField::payTradeNo($result->getPayTradeNo()));
            } elseif ($result->getPayApiTradeNo()) {
                $this->whereEntity(TradeRefundField::payApiTradeNo($result->getPayApiTradeNo()));
            } else {
                throw new DomainException('异步退款返回数据中必须返回refund_no,api_refund_no,pay_trade_no,pay_api_trade_no其中的任意一个值');
            }
            
            $info = $this->lock(true)->failException(true)->findInfo();
            if (!$info->isPending && !$info->isQueryInQueue) {
                throw new VerifyException('该退款订单已处理过', 'refunded');
            }
            
            
            TradePay::init()
                ->updateRefundAmountByCallback($info->orderTradeNo, new class($info, $result, $this) implements TradeUpdateRefundAmountInterface {
                    /**
                     * @var TradeRefundInfo
                     */
                    private $refundInfo;
                    
                    /**
                     * @var PayRefundNotifyResult
                     */
                    private $result;
                    
                    /**
                     * @var TradeRefund
                     */
                    private $refundTarget;
                    
                    
                    public function __construct(TradeRefundInfo $refundInfo, PayRefundNotifyResult $result, TradeRefund $refundTarget)
                    {
                        $this->refundInfo   = $refundInfo;
                        $this->result       = $result;
                        $this->refundTarget = $refundTarget;
                    }
                    
                    
                    /**
                     * 执行更新，内部无需启动事物
                     * @param TradePayInfo $tradePayInfo
                     * @return float 返回要更新的金额，整数为加上，负数为减去，返回null或0则不更新
                     * @throws Exception
                     */
                    public function onUpdate(TradePayInfo $tradePayInfo) : ?float
                    {
                        // 构建参数
                        $update = TradeRefundField::init();
                        
                        // 需要重新处理的
                        if ($this->result->isNeedReHandle()) {
                            $update->status    = TradeRefund::REFUND_STATUS_PENDING;
                            $update->queueTime = time();
                        } else {
                            $update->status       = $this->result->isStatus() ? TradeRefund::REFUND_STATUS_SUCCESS : TradeRefund::REFUND_STATUS_FAIL;
                            $update->completeTime = time();
                            $update->queueTime    = 0;
                            
                            if ($this->result->getApiRefundNo()) {
                                $update->apiRefundNo = $this->result->getApiRefundNo();
                            }
                            
                            if ($this->result->getRefundAccount()) {
                                $update->refundAccount = $this->result->getRefundAccount();
                            }
                            
                            if ($this->result->getErrMsg()) {
                                $update->failRemark = $this->result->getErrMsg();
                            }
                        }
                        
                        $this->refundTarget->whereEntity(TradeRefundField::id($this->refundInfo->id))
                            ->saveData($update);
                        
                        
                        // 触发业务订单事件
                        try {
                            $modal = TradePay::init()->getOrderModel($this->refundInfo->orderTradeNo);
                            $modal->setRefundStatus($this->refundInfo, $tradePayInfo, $this->result->isStatus(), $this->result->isStatus() ? $update->refundAccount : $update->failRemark);
                        } catch (Exception $e) {
                            TradeRefund::log('退款处理完成，但通知业务订单失败', __METHOD__)->error($e);
                        }
                        
                        // 退款失败的要还原支付订单可退款金额
                        if (!$this->result->isStatus()) {
                            return 0 - $this->refundInfo->refundPrice;
                        }
                        
                        return 0;
                    }
                });
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 退款任务
     */
    public function taskRefund()
    {
        $delaySec    = (int) $this->getSetting('submit_delay', 0);
        $recoverySec = (int) $this->getSetting('submit_timeout', 0);
        $infoId      = null;
        try {
            $infoId = SystemLock::init()->do('trade_task_refund', function() use ($delaySec) {
                $delayTime = time() - $delaySec;
                
                // 无锁查询一条记录，防止锁表
                // 虽然会出现资源浪费，但目前只有这种办法方式争抢
                $id = $this->field(TradeRefundField::id())
                    ->whereEntity(TradeRefundField::status(self::REFUND_STATUS_WAIT))
                    ->whereEntity(TradeRefundField::queueTime('<', $delayTime))
                    ->order(TradeRefundField::id(), 'asc')
                    ->val(TradeRefundField::id());
                if (!$id) {
                    return null;
                }
                
                // 锁定单条记录
                $info            = $this->lock(true)->getInfo($id);
                $save            = TradeRefundField::init();
                $save->status    = self::REFUND_STATUS_IN_REFUND_QUEUE;
                $save->queueTime = time();
                $this->whereEntity(TradeRefundField::id($info->id))->saveData($save);
                
                return $info->id;
            }, '退款任务-加入退款列队锁');
        } catch (Exception $e) {
            self::log("加入退款下单列队", __METHOD__)->error($e);
        }
        
        
        // 回收超时的订单
        if ($recoverySec > 0) {
            try {
                $save            = TradeRefundField::init();
                $save->status    = self::REFUND_STATUS_WAIT;
                $save->queueTime = time();
                
                $result = $this
                    // 在列队中
                    ->whereEntity(TradeRefundField::status(self::REFUND_STATUS_IN_REFUND_QUEUE))
                    // queueTime必须大于0
                    ->whereEntity(TradeRefundField::queueTime('>', 0))
                    // 进入列队的时间 小于 当前时间减去延迟执行时间
                    ->whereEntity(TradeRefundField::queueTime('<', time() - $recoverySec))
                    // 回收
                    ->saveData($save);
                
                if ($result > 0) {
                    self::log('回收退款下单任务')->info("{$result}条");
                }
            } catch (Exception $e) {
                self::log("回收退款下单任务", __METHOD__)->error($e);
            }
        }
        
        
        if (!$infoId) {
            return;
        }
        
        
        $tag = "执行退款下单";
        try {
            self::log($tag)->info("开始: {$infoId}");
            $this->refund($infoId);
            self::log($tag)->info("完成: {$infoId}");
        } catch (Exception $e) {
            self::log($tag, __METHOD__)->error($e);
        }
    }
    
    
    /**
     * 查询任务
     */
    public function taskQuery()
    {
        $delaySec    = (int) $this->getSetting('query_delay', 0);
        $recoverySec = (int) $this->getSetting('query_timeout', 0);
        $infoId      = null;
        try {
            $infoId = SystemLock::init()->do('trade_task_query', function() use ($delaySec) {
                $delayTime = time() - $delaySec;
                
                // 无锁查询一次，防止锁表
                // 虽然会出现资源浪费，但目前只有这种办法方式争抢
                $id = $this->field(TradeRefundField::id())
                    ->whereEntity(TradeRefundField::status(self::REFUND_STATUS_PENDING))
                    ->whereEntity(TradeRefundField::queueTime('<', $delayTime))
                    ->order(TradeRefundField::id(), 'asc')
                    ->val(TradeRefundField::id());
                if (!$id) {
                    return null;
                }
                
                // 锁定单条记录
                $info            = $this->lock(true)->getInfo($id);
                $save            = TradeRefundField::init();
                $save->status    = self::REFUND_STATUS_IN_QUERY_QUEUE;
                $save->queueTime = time();
                $this->whereEntity(TradeRefundField::id($info->id))->saveData($save);
                
                return $info->id;
            }, '退款任务-加入查询列队锁');
        } catch (Exception $e) {
            self::log('加入退款查询列队', __METHOD__)->error($e);
        }
        
        
        // 回收超时的订单
        if ($recoverySec > 0) {
            try {
                $save            = TradeRefundField::init();
                $save->status    = self::REFUND_STATUS_PENDING;
                $save->queueTime = time();
                
                $result = $this
                    // 必须是在查询列队中状态
                    ->whereEntity(TradeRefundField::status(self::REFUND_STATUS_IN_QUERY_QUEUE))
                    // 列队时间大于0
                    ->whereEntity(TradeRefundField::queueTime('>', 0))
                    // 进入列队的时间 小于 当前时间减去延迟执行时间
                    ->whereEntity(TradeRefundField::queueTime('<', time() - $recoverySec))
                    // 回收
                    ->saveData($save);
                if ($result > 0) {
                    self::log('回收退款查询任务')->info("{$result}条");
                }
            } catch (Exception $e) {
                self::log('回收退款查询任务', __METHOD__)->error($e);
            }
        }
        
        
        if (!$infoId) {
            return;
        }
        
        $tag = "执行退款查询";
        try {
            self::log($tag)->info("开始: {$infoId}");
            $this->inquiry($infoId);
            self::log($tag)->info("完成: {$infoId}");
        } catch (Exception $e) {
            self::log($tag, __METHOD__)->error($e);
        }
    }
}