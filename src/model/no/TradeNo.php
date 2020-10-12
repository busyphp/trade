<?php

namespace BusyPHP\trade\model\no;

use BusyPHP\exception\SQLException;
use BusyPHP\Model;

/**
 * 交易号分配模型
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2019 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2020/6/17 下午4:41 下午 TradeNo.php $
 */
class TradeNo extends Model
{
    /**
     * 获取交易号
     * @param string|true $prefix 前缀，输入true则返回id自己处理
     * @return string 返回18位+前缀长度的交易号 = 22位
     * @throws SQLException
     */
    public function get($prefix)
    {
        if (!$lastId = $this->addData(['create_time' => time()])) {
            throw new SQLException('交易号生成失败[' . $prefix . ']', $this);
        }
        
        //$this->deleteData($lastId);
        
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
     * 通过订单号解析出订单类型
     * @param string $tradeNo
     * @return int
     */
    public static function getType($tradeNo)
    {
        return intval(substr($tradeNo, 0, 4));
    }
}