<?php
declare(strict_types = 1);

namespace BusyPHP\trade\model\no;

use BusyPHP\Model;
use think\db\exception\DbException;

/**
 * 交易号分配模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午3:38 TradeNo.php $
 */
class TradeNo extends Model
{
    /**
     * 获取交易号
     * @param string|true $prefix 前缀，输入true则返回id自己处理
     * @return string 返回18位+前缀长度的交易号 = 22位
     * @throws DbException
     */
    public function get($prefix) : string
    {
        $lastId = $this->addData(['create_time' => time()]);
        $this->deleteInfo($lastId - 1);
        
        if (true === $prefix) {
            return $lastId;
        }
        
        // 小于10位要补齐
        if (strlen($lastId) < 10) {
            $lastId = str_pad($lastId, 10, '0', STR_PAD_LEFT);
        }
        
        return $prefix . date('Ymd') . $lastId;
    }
    
    
    /**
     * 通过交易号号解析出订单类型
     * @param string $tradeNo 交易号
     * @return int
     */
    public static function getType(string $tradeNo) : int
    {
        return intval(substr((string) $tradeNo, 0, 4));
    }
}