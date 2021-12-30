<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\refund;

use BusyPHP\App;
use BusyPHP\exception\ClassNotFoundException;
use BusyPHP\exception\ClassNotImplementsException;
use BusyPHP\exception\ParamInvalidException;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\LogHelper;
use BusyPHP\Model;
use BusyPHP\queue\facade\Queue;
use BusyPHP\trade\interfaces\PayRefund;
use BusyPHP\trade\interfaces\PayRefundNotify;
use BusyPHP\trade\interfaces\PayRefundNotifyResult;
use BusyPHP\trade\interfaces\PayRefundQuery;
use BusyPHP\trade\interfaces\PayRefundQueryResult;
use BusyPHP\trade\interfaces\TradeMemberAdminRefundOperateAttr;
use BusyPHP\trade\interfaces\TradeUpdateRefundAmountInterface;
use BusyPHP\trade\job\QueryJob;
use BusyPHP\trade\job\RefundJob;
use BusyPHP\trade\model\no\TradeNo;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\pay\TradePayInfo;
use BusyPHP\trade\model\TradeConfig;
use BusyPHP\trade\Service;
use Closure;
use DomainException;
use LogicException;
use RuntimeException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\exception\HttpException;
use think\facade\Route;
use think\Response;
use think\route\Url;
use Throwable;

/**
 * 交易退款模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午5:25 TradeRefund.php $
 * @method TradeRefundInfo getInfo($data, $notFoundMessage = null)
 * @method TradeRefundInfo findInfo($data = null, $notFoundMessage = null)
 * @method TradeRefundInfo[] selectList()
 * @method TradeRefundInfo[] buildListWithField(array $values, $key = null, $field = null) : array
 * @method TradeRefundExtendInfo getExtendInfo($data, $notFoundMessage = null)
 * @method TradeRefundExtendInfo findExtendInfo($data = null, $notFoundMessage = null)
 * @method TradeRefundExtendInfo[] selectExtendList()
 * @method TradeRefundExtendInfo[] buildExtendListWithField(array $values, $key = null, $field = null) : array
 */
class TradeRefund extends Model
{
    use TradeConfig;
    
    // +----------------------------------------------------
    // + 退款状态
    // +----------------------------------------------------
    /** @var int 等待退款 */
    const REFUND_STATUS_WAIT = 0;
    
    /** @var int 退款中 */
    const REFUND_STATUS_PENDING = 1;
    
    /** @var int 等待手动处理 */
    const REFUND_STATUS_WAIT_MANUAL = 7;
    
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
     * 发布任务到队列中
     * @param string $job 任务Job
     * @param int    $id 退款记录ID
     */
    public function queuePush($job, $id)
    {
        Queue::connection('plugin_trade')->push($job, $id, 'plugin_trade_refund');
    }
    
    
    /**
     * 发布延迟执行任务到队列中
     * @param int    $delay 延迟执行秒数
     * @param string $job 任务Job
     * @param int    $id 退款记录ID
     */
    public function queueLater($delay, $job, $id)
    {
        Queue::connection('plugin_trade')->later($delay, $job, $id, 'plugin_trade_refund');
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
     * @throws Throwable
     */
    public function joinRefund(string $orderTradeNo, int $orderType = 0, string $orderValue = '', string $refundRemark = '', float $price = 0, bool $mustRefund = false) : TradeRefundField
    {
        // 获取订单号前缀配置
        if (!$refundNoPrefix = $this->getTradeConfig('refund_no_prefix', 1002)) {
            throw new RuntimeException('请前往config/trade.php 配置 refund_no_prefix');
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
                 * @throws Throwable
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
                    $this->insert->status        = TradePay::checkPayTypeIsManual($tradePayInfo->payType) ? TradeRefund::REFUND_STATUS_WAIT_MANUAL : TradeRefund::REFUND_STATUS_WAIT;
                    $this->insert->remark        = $this->refundRemark;
                    $this->insert->id            = $this->refundTarget->addData($this->insert);
                    
                    // 发布退款任务到队列中
                    $this->refundTarget->queuePush(RefundJob::class, $this->insert->id);
                    
                    return 0 - $this->price;
                }
            });
        
        return $insert;
    }
    
    
    /**
     * @param TradeRefundExtendInfo[] $list
     * @throws DataNotFoundException
     * @throws DbException
     */
    protected function onParseBindExtendList(array &$list)
    {
        $userIds = [];
        foreach ($list as $item) {
            $userIds[] = $item->userId;
        }
        
        $model       = TradePay::init()->getMemberModel();
        $userList    = $model->buildListWithField($userIds);
        $userParams  = $model->getTradeUserParams();
        $usernameKey = (string) $userParams->getUsernameField();
        $phoneKey    = $userParams->getPhoneField() ? (string) $userParams->getPhoneField() : '';
        $nicknameKey = $userParams->getNicknameField() ? (string) $userParams->getNicknameField() : '';
        $emailKey    = $userParams->getEmailField() ? (string) $userParams->getEmailField() : '';
        $callback    = $userParams->getAdminRefundOperateUserAttr();
        foreach ($list as $i => $item) {
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
            } elseif ($callback instanceof TradeMemberAdminRefundOperateAttr) {
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
     * @throws Throwable
     */
    public function refund($id)
    {
        $this->startTrans();
        try {
            $info = $this->lock(true)->getInfo($id);
            if (!$info->isWait) {
                throw new LogicException("当前订单状为{$info->statusName}，无法执行退款");
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
                    $update->status = self::REFUND_STATUS_WAIT;
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
                    $update->status = self::REFUND_STATUS_PENDING;
                }
            } catch (Throwable $e) {
                // 退款失败
                $update->status       = self::REFUND_STATUS_FAIL;
                $update->failRemark   = $e->getMessage();
                $update->completeTime = time();
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
                     * @throws Throwable
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
                            } catch (Throwable $e) {
                                TradeRefund::log("退款失败处理完成，但通知业务订单失败", __METHOD__)->error($e);
                            }
                            
                            return (float) $this->refundInfo->refundPrice;
                        }
                        
                        return 0;
                    }
                });
            
            // 需要重新处理
            // 重新加入退款队列
            if ($update->status == self::REFUND_STATUS_WAIT) {
                $this->queueLater($this->getRefundSubmitDelay(), RefundJob::class, $info->id);
            }
            
            // 退款申请成功
            // 加入到查询队列
            elseif ($update->status == self::REFUND_STATUS_PENDING) {
                $this->queueLater($this->getRefundQueryDelay(), QueryJob::class, $info->id);
            }
            
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 执行单步三方查询
     * @param string $id 订单ID
     * @param bool   $isSetStatus 是否设置了订单状态
     * @return PayRefundQueryResult
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Throwable
     */
    public function inquiry($id, &$isSetStatus = false) : PayRefundQueryResult
    {
        $query  = null;
        $info   = $this->getInfo($id);
        $result = new PayRefundNotifyResult();
        $result->setRefundNo($info->refundNo);
        $result->setPayTradeNo($info->payTradeNo);
        $result->setPayApiTradeNo($info->payApiTradeNo);
        
        try {
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
            $query     = $api->query();
            $notifyRes = $query->getNotifyResult();
            
            $result->setStatus($notifyRes->isStatus());
            $result->setErrMsg($notifyRes->getErrMsg());
            $result->setNeedReHandle($notifyRes->isNeedReHandle());
            $result->setRefundAccount($notifyRes->getRefundAccount());
            $result->setApiRefundNo($notifyRes->getApiRefundNo());
        } catch (Throwable $e) {
            $result->setStatus(false);
            $result->setErrMsg($e->getMessage());
        }
        
        // 等待结果的则设置状态
        if ($info->isPending) {
            $this->setRefundStatus($result);
            $isSetStatus = true;
        }
        
        if (!empty($e)) {
            throw $e;
        }
        
        return $query;
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            self::log($tag, __METHOD__)->error($e);
            
            return $notify->onError($e);
        }
    }
    
    
    /**
     * 重试退款
     * @param $id
     * @throws Throwable
     */
    public function retryRefund($id)
    {
        $this->startTrans();
        try {
            $info = $this->lock(true)->getInfo($id);
            if (!$info->isFail) {
                throw new LogicException('当前订单不是退款失败状态，无法重试');
            }
            
            TradePay::init()
                ->updateRefundAmountByCallback($info->orderTradeNo, new class($info, $this) implements TradeUpdateRefundAmountInterface {
                    /**
                     * @var TradeRefundInfo
                     */
                    private $refundInfo;
                    
                    /**
                     * @var TradeRefund
                     */
                    private $refundTarget;
                    
                    
                    public function __construct(TradeRefundInfo $refundInfo, TradeRefund $refundTarget)
                    {
                        $this->refundInfo   = $refundInfo;
                        $this->refundTarget = $refundTarget;
                    }
                    
                    
                    /**
                     * 执行更新，内部无需启动事物
                     * @param TradePayInfo $tradePayInfo
                     * @return float 返回要更新的金额，整数为加上，负数为减去，返回null或0则不更新
                     * @throws Throwable
                     */
                    public function onUpdate(TradePayInfo $tradePayInfo) : ?float
                    {
                        $update         = TradeRefundField::init();
                        $update->status = TradeRefund::REFUND_STATUS_WAIT;
                        TradeRefund::init()
                            ->whereEntity(TradeRefundField::id($this->refundInfo->id))
                            ->saveData($update);
                        
                        // 发布任务到队列中
                        $this->refundTarget->queuePush(RefundJob::class, $this->refundInfo->id);
                        
                        return 0 - $this->refundInfo->refundPrice;
                    }
                });
            
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            
            throw $e;
        }
    }
    
    
    /**
     * 设置退款状态
     * @param PayRefundNotifyResult $result 退款返回数据
     * @param bool                  $must 是否强制将退款失败的订单设为成功
     * @throws Throwable
     */
    public function setRefundStatus(PayRefundNotifyResult $result, $must = false)
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
            if ($must) {
                // 不是失败状态的
                // 不是等待手动处理的
                $result->setStatus(true);
                $result->setNeedReHandle(false);
                if (!$info->canSuccess) {
                    throw new LogicException('该订单状态不允许手动操作为成功');
                }
            } else {
                // 不是等待中
                // 不是等待查询中
                if (!$info->isPending) {
                    throw new VerifyException('该退款订单已处理过', 'refunded');
                }
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
                     * @throws Throwable
                     */
                    public function onUpdate(TradePayInfo $tradePayInfo) : ?float
                    {
                        // 构建参数
                        $update = TradeRefundField::init();
                        
                        // 需要重新处理的
                        if ($this->result->isNeedReHandle()) {
                            $update->status = TradeRefund::REFUND_STATUS_PENDING;
                        } else {
                            $update->status       = $this->result->isStatus() ? TradeRefund::REFUND_STATUS_SUCCESS : TradeRefund::REFUND_STATUS_FAIL;
                            $update->completeTime = time();
                            
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
                            $modal->setRefundStatus($this->refundInfo, $tradePayInfo, $this->result->isStatus(), $this->result->isStatus() ? ($update->refundAccount ?: '') : ($update->failRemark ?: ''));
                        } catch (Throwable $e) {
                            TradeRefund::log('退款处理完成，但通知业务订单失败', __METHOD__)->error($e);
                        }
                        
                        // 需要重新处理的
                        // 发布查询任务到队列中
                        if ($update->status == TradeRefund::REFUND_STATUS_PENDING) {
                            $this->refundTarget->queueLater($this->refundTarget->getRefundQueryDelay(), QueryJob::class, $this->refundInfo->id);
                        }
                        
                        // 退款失败的要还原支付订单可退款金额
                        if (!$this->result->isStatus()) {
                            return 0 - $this->refundInfo->refundPrice;
                        }
                        
                        return 0;
                    }
                });
            
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            
            throw $e;
        }
    }
}