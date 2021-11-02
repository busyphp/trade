<?php
declare(strict_types = 1);

namespace BusyPHP\trade\app\controller;

use BusyPHP\app\admin\controller\AdminController;
use BusyPHP\app\admin\model\admin\group\AdminGroup;
use BusyPHP\app\admin\model\admin\user\AdminUserInfo;
use BusyPHP\app\admin\plugin\table\TableHandler;
use BusyPHP\app\admin\plugin\TablePlugin;
use BusyPHP\exception\VerifyException;
use BusyPHP\helper\TransHelper;
use BusyPHP\Model;
use BusyPHP\model\Map;
use BusyPHP\trade\interfaces\PayNotifyResult;
use BusyPHP\trade\interfaces\PayRefundNotifyResult;
use BusyPHP\trade\interfaces\TradeMemberModel;
use BusyPHP\trade\interfaces\TradeMemberParams;
use BusyPHP\trade\model\pay\TradePay;
use BusyPHP\trade\model\pay\TradePayExtendInfo;
use BusyPHP\trade\model\pay\TradePayField;
use BusyPHP\trade\model\refund\TradeRefund;
use BusyPHP\trade\model\refund\TradeRefundExtendInfo;
use BusyPHP\trade\model\refund\TradeRefundField;
use BusyPHP\trade\model\TradeConfig;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\Response;

/**
 * 交易管理
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/11/2 下午下午5:05 TradeController.php $
 */
class TradeController extends AdminController
{
    use TradeConfig;
    
    /**
     * 支付管理
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function pay()
    {
        $memberModel = TradePay::init()->getMemberModel();
        $userParams  = $memberModel->getTradeUserParams();
        $timeRange   = date('Y-m-d 00:00:00', strtotime('-29 days')) . ' - ' . date('Y-m-d 23:59:59');
        if ($this->pluginTable) {
            $this->pluginTable->isExtend = true;
            $this->pluginTable->setHandler(new class($timeRange, $memberModel, $userParams, $this->adminUser) extends TableHandler {
                /**
                 * @var string
                 */
                private $timeRange;
                
                /**
                 * @var TradeMemberModel
                 */
                private $memberModel;
                
                /**
                 * @var TradeMemberParams
                 */
                private $userParams;
                
                /**
                 * @var AdminUserInfo
                 */
                private $adminUserInfo;
                
                
                public function __construct(string $timeRange, TradeMemberModel $memberModel, TradeMemberParams $userParams, AdminUserInfo $adminUserInfo)
                {
                    $this->timeRange     = $timeRange;
                    $this->memberModel   = $memberModel;
                    $this->userParams    = $userParams;
                    $this->adminUserInfo = $adminUserInfo;
                }
                
                
                public function field(Model $model, string $field, string $word, string $op, string $sourceWord) : string
                {
                    if (0 === stripos($field, '_user_')) {
                        $field = substr($field, 6);
                        
                        $model->whereEntity(TradePayField::userId('in', $this->memberModel->field($this->userParams->getIdField())
                            ->where($field, $op, $word)
                            ->buildSql())->setValueToRaw(true));
                        
                        return '';
                    }
                    
                    return $field;
                }
                
                
                public function query(TablePlugin $plugin, Model $model, Map $data) : void
                {
                    // 支付方式
                    if ($data->get('pay_type', 0) == 0) {
                        $data->remove('pay_type');
                    }
                    
                    // 支付状态
                    switch ($data->get('status', 0)) {
                        case 1:
                            $model->whereEntity(TradePayField::payTime('>', 0));
                        break;
                        case 2:
                            $model->whereEntity(TradePayField::payTime(0));
                        break;
                        case 3:
                            $model->whereEntity(TradePayField::payTime('>', 0));
                            $model->whereEntity(TradePayField::refundAmount('<', TradePayField::apiPrice())
                                ->setValueToRaw(true));
                        break;
                    }
                    $data->remove('status');
                    
                    // 业务状态
                    switch ($data->get('order_status', 0)) {
                        case 1:
                            $model->whereEntity(TradePayField::orderStatus(TradePay::ORDER_STATUS_SUCCESS));
                        break;
                        case 2:
                            $model->whereEntity(TradePayField::orderStatus(TradePay::ORDER_STATUS_FAIL));
                        break;
                    }
                    $data->remove('order_status');
                    
                    // 时间范围
                    if ($time = $data->get('time', $this->timeRange)) {
                        $model->whereTimeIntervalRange(TradePayField::createTime(), $time, ' - ', true);
                    }
                    $data->remove('time');
                }
                
                
                /**
                 * @param TradePayExtendInfo[] $list
                 * @return array|null
                 */
                public function list(array &$list) : ?array
                {
                    $canPaySuccess   = AdminGroup::checkPermission($this->adminUserInfo, 'pay_success');
                    $canOrderSuccess = AdminGroup::checkPermission($this->adminUserInfo, 'pay_order_success');
                    $canRefund       = AdminGroup::checkPermission($this->adminUserInfo, 'pay_apply_refund');
                    
                    foreach ($list as $item) {
                        $item->canPaySuccess   = $item->canPaySuccess && $canPaySuccess;
                        $item->canOrderSuccess = $item->canOrderSuccess && $canOrderSuccess;
                        $item->canRefund       = $item->canRefund && $canRefund;
                    }
                    
                    return null;
                }
            });
            
            return $this->success($this->pluginTable->build(TradePay::init()));
        }
        
        // 扩展查询用户字段
        $queryFields = [];
        $fields      = [
            '用户账号'  => $userParams->getUsernameField(),
            '用户手机号' => $userParams->getPhoneField(),
            '用户邮箱'  => $userParams->getEmailField(),
            '用户昵称'  => $userParams->getNicknameField(),
        ];
        foreach ($fields as $name => $field) {
            if (!$field) {
                continue;
            }
            $queryFields[] = "_user_{$field}:{$name}";
        }
        
        $this->assign('query_fields', implode(',', $queryFields));
        $this->assign('type_options', TransHelper::toOptionHtml(TradePay::init()->getTypes()));
        $this->assign('other_type_options', TransHelper::toOptionHtml(TradePay::getOtherPayTypes()));
        $this->assign('ticket_status_options', TransHelper::toOptionHtml(TradePay::getTicketStatus()));
        $this->assign('order_status_options', TransHelper::toOptionHtml(TradePay::getOrderStatus()));
        $this->assign('time', $timeRange);
        
        return $this->display();
    }
    
    
    /**
     * 恢复业务订单
     * @return Response
     * @throws Exception
     */
    public function pay_order_success()
    {
        TradePay::init()->setOrderSuccess($this->get('id/d'));
        $this->log()->record(self::LOG_UPDATE, '恢复业务订单');
        
        return $this->success('操作成功');
    }
    
    
    /**
     * 支付订单
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function pay_success()
    {
        if ($this->isPost()) {
            $info       = TradePay::init()->getInfo($this->post('id/s', 'trim'));
            $payType    = $this->post('pay_type/d');
            $payPrice   = $this->post('pay_price/f');
            $payTime    = $this->post('pay_time/s', 'trim');
            $apiTradeNo = $this->post('api_trade_no/s', 'trim');
            
            if ($payPrice <= 0) {
                throw new VerifyException('请输入有效的支付金额', 'pay_price');
            }
            if (!$apiTradeNo) {
                throw new VerifyException('请输入三方支付单号', 'pay_type');
            }
            if (!$payTime || !strtotime($payTime)) {
                throw new VerifyException('请选择有效的支付时间', 'pay_time');
            }
            
            $param = new PayNotifyResult();
            $param->setPayType($payType);
            $param->setApiPrice($payPrice);
            $param->setPayDate($payTime);
            $param->setApiTradeNo($apiTradeNo);
            $param->setPayRemark($this->post('pay_remark/s', 'trim'));
            $param->setPayTradeNo($info->payTradeNo);
            $status = TradePay::init()->setPaySuccess($param);
            $this->log()->record(self::LOG_UPDATE, '支付订单');
            
            return $this->success(!$status ? '该订单已支付' : '支付成功');
        }
        
        $info = TradePay::init()->getInfo($this->get('id/s', 'trim'));
        $this->assign('info', $info);
        $this->assign('type_options', TransHelper::toOptionHtml(TradePay::init()->getTypes(), $info->payType));
        $this->assign('other_type_options', TransHelper::toOptionHtml(TradePay::getOtherPayTypes(), $info->payType));
        
        return $this->display();
    }
    
    
    /**
     * 创建退款单
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function pay_apply_refund()
    {
        if ($this->isPost()) {
            $id     = $this->post('id/d');
            $price  = $this->post('price/f');
            $remark = $this->post('remark/s', 'trim');
            $info   = TradePay::init()->getInfo($id);
            if ($price <= 0) {
                throw new VerifyException('请输入退款金额', 'price');
            }
            if (!$remark) {
                throw new VerifyException('请输入退款说明', 'remark');
            }
            
            TradeRefund::init()->joinRefund($info->orderTradeNo, 0, '', $remark, $price, true);
            $this->log()->record(self::LOG_INSERT, '创建退款单');
            
            return $this->success('创建退款单成功');
        }
        
        $this->assign('info', TradePay::init()->getInfo($this->get('id/s', 'trim')));
        
        return $this->display();
    }
    
    
    /**
     * 退款管理
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     */
    public function refund()
    {
        $memberModel = TradePay::init()->getMemberModel();
        $userParams  = $memberModel->getTradeUserParams();
        $timeRange   = date('Y-m-d 00:00:00', strtotime('-29 days')) . ' - ' . date('Y-m-d 23:59:59');
        if ($this->pluginTable) {
            $this->pluginTable->isExtend = true;
            
            $this->pluginTable->setHandler(new class($timeRange, $memberModel, $userParams, $this->adminUser) extends TableHandler {
                /**
                 * @var string
                 */
                private $timeRange;
                
                /**
                 * @var TradeMemberModel
                 */
                private $memberModel;
                
                /**
                 * @var TradeMemberParams
                 */
                private $userParams;
                
                /**
                 * @var AdminUserInfo
                 */
                private $adminUserInfo;
                
                
                public function __construct(string $timeRange, TradeMemberModel $memberModel, TradeMemberParams $userParams, AdminUserInfo $adminUserInfo)
                {
                    $this->timeRange     = $timeRange;
                    $this->memberModel   = $memberModel;
                    $this->userParams    = $userParams;
                    $this->adminUserInfo = $adminUserInfo;
                }
                
                
                public function field(Model $model, string $field, string $word, string $op, string $sourceWord) : string
                {
                    if (0 === stripos($field, '_user_')) {
                        $field = substr($field, 6);
                        
                        $model->whereEntity(TradePayField::userId('in', $this->memberModel->field($this->userParams->getIdField())
                            ->where($field, $op, $word)
                            ->buildSql())->setValueToRaw(true));
                        
                        return '';
                    }
                    
                    return $field;
                }
                
                
                public function query(TablePlugin $plugin, Model $model, Map $data) : void
                {
                    // 支付方式
                    if ($data->get('pay_type', 0) == 0) {
                        $data->remove('pay_type');
                    }
                    
                    // 支付状态
                    switch ($data->get('status', 0)) {
                        case 1:
                            $model->whereEntity(TradeRefundField::status(TradeRefund::REFUND_STATUS_SUCCESS));
                        break;
                        case 2:
                            $model->whereEntity(TradeRefundField::status(TradeRefund::REFUND_STATUS_FAIL));
                        break;
                        case 3:
                            $model->whereEntity(TradeRefundField::status('in', [
                                TradeRefund::REFUND_STATUS_WAIT,
                                TradeRefund::REFUND_STATUS_PENDING,
                                TradeRefund::REFUND_STATUS_IN_QUERY_QUEUE,
                                TradeRefund::REFUND_STATUS_IN_REFUND_QUEUE,
                            ]));
                        break;
                        case 4:
                            $model->whereEntity(TradeRefundField::status(TradeRefund::REFUND_STATUS_WAIT_MANUAL));
                        break;
                    }
                    $data->remove('status');
                    
                    if ($time = $data->get('time', $this->timeRange)) {
                        $model->whereTimeIntervalRange(TradePayField::createTime(), $time, ' - ', true);
                    }
                    $data->remove('time');
                }
                
                
                /**
                 * @param TradeRefundExtendInfo[] $list
                 * @return array|null
                 */
                public function list(array &$list) : ?array
                {
                    $canQuery   = AdminGroup::checkPermission($this->adminUserInfo, 'refund_query');
                    $canRetry   = AdminGroup::checkPermission($this->adminUserInfo, 'refund_retry');
                    $canSuccess = AdminGroup::checkPermission($this->adminUserInfo, 'refund_success');
                    
                    foreach ($list as $item) {
                        $item->canQuery   = $item->canQuery && $canQuery;
                        $item->canRetry   = $item->canRetry && $canRetry;
                        $item->canSuccess = $item->canSuccess && $canSuccess;
                    }
                    
                    return null;
                }
            });
            
            return $this->success($this->pluginTable->build(TradeRefund::init()));
        }
        
        // 扩展查询用户字段
        $queryFields = [];
        $fields      = [
            '用户账号'  => $userParams->getUsernameField(),
            '用户手机号' => $userParams->getPhoneField(),
            '用户邮箱'  => $userParams->getEmailField(),
            '用户昵称'  => $userParams->getNicknameField(),
        ];
        foreach ($fields as $name => $field) {
            if (!$field) {
                continue;
            }
            $queryFields[] = "_user_{$field}:{$name}";
        }
        $this->assign('query_fields', implode(',', $queryFields));
        $this->assign('type_options', TransHelper::toOptionHtml(TradePay::init()->getTypes()));
        $this->assign('other_type_options', TransHelper::toOptionHtml(TradePay::getOtherPayTypes()));
        $this->assign('time', $timeRange);
        
        return $this->display();
    }
    
    
    /**
     * 重试退款
     * @return Response
     * @throws Exception
     */
    public function refund_retry()
    {
        TradeRefund::init()->retryRefund($this->get('id/s', 'trim'));
        $this->log()->record(self::LOG_UPDATE, '重试退款');
        
        return $this->success('操作成功');
    }
    
    
    /**
     * 设为退款成功
     * @return Response
     * @throws Exception
     */
    public function refund_success()
    {
        if ($this->isPost()) {
            $id            = $this->post('id/s', 'trim');
            $apiRefundNo   = $this->post('api_refund_no/s', 'trim');
            $refundAccount = $this->post('refund_account/s', 'trim');
            if (!$apiRefundNo) {
                throw new VerifyException('请输入三方退款单号', 'api_refund_no');
            }
            
            $info   = TradeRefund::init()->getInfo($id);
            $result = new PayRefundNotifyResult();
            $result->setRefundNo($info->refundNo);
            $result->setStatus(true);
            $result->setApiRefundNo($apiRefundNo);
            $result->setRefundAccount($refundAccount);
            $result->setNeedReHandle(false);
            TradeRefund::init()->setRefundStatus($result, true);
            
            $this->log()->record(self::LOG_UPDATE, '设为退款成功');
            
            return $this->success('操作成功');
        }
        
        $this->assign('info', TradeRefund::init()->getInfo($this->get('id/s', 'trim')));
        
        return $this->display();
    }
    
    
    /**
     * 查询退款结果
     * @return Response
     * @throws Exception
     */
    public function refund_query()
    {
        $result = TradeRefund::init()->inquiry($this->get('id/s', 'trim'), $status);
        if ($status) {
            $this->log()->record(self::LOG_UPDATE, '查询退款结果');
        }
        $this->assign('list', $result->getDetail());
        
        return $this->display();
    }
    
    
    protected function display($template = '', $charset = 'utf-8', $contentType = '', $content = '')
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'trade' . DIRECTORY_SEPARATOR;
        if ($template) {
            $template = $dir . $template . '.html';
        } else {
            $template = $dir . $this->request->action() . '.html';
        }
        
        return parent::display($template, $charset, $contentType, $content);
    }
}