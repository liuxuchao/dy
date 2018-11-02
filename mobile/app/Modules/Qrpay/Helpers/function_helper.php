<?php

/**
 * 判断客户端是微信或支付宝
 */
function IsWeixinOrAlipay()
{
    //判断微信
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return "wxpay";
    }
    return "alipay";
}

/**
 *  取得某支付方式信息
 * @param  string $code 支付方式代码
 */
function get_payment_info($code)
{
    $payment = dao('payment')->where(['pay_code' => $code, 'enabled' => 1])->find();

    if ($payment) {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list as $config) {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

/**
 * 查询收款码信息
 * @param  $id
 * @return
 */
function get_qrpay_info($id)
{
    $res = dao('qrpay_manage')->where(['id' => $id])->find();
    return $res;
}

/**
 * 处理收款码优惠满减金额
 * 每满$dis['min_amount'] 减 $dis['discount_amount']， 最高优惠$dis['max_discount_amount']元
 * @param $qrpay_id
 * @return
 */
function do_discount_fee($qrpay_id, $pay_amount)
{
    $discount_fee = 0;
    $res = dao('qrpay_manage')->where(['id' => $qrpay_id])->find();
    if (!empty($res) && $res['discount_id'] > 0) {
        $dis = dao('qrpay_discounts')->where(['id' => $res['discount_id'], 'status' => 1])->find();
        if (!empty($dis)) {
            if ($pay_amount > 0 && $pay_amount >= $dis['min_amount']) {
                $per = intval($pay_amount / $dis['min_amount']);
                $discount_fee = $dis['discount_amount'] * $per;
                // 每笔最高优惠，未设置优惠金额(0.00) 无上限
                if (!empty(floatval($dis['max_discount_amount']))) {
                    $discount_fee = $discount_fee > $dis['max_discount_amount'] ? $dis['max_discount_amount'] : $discount_fee;
                }
                $discount_fee = number_format($discount_fee, 2, '.', '');
            }
        }
    }
    return $discount_fee;
}

/**
 * 查询收款码优惠名称
 * @return
 */
function get_discounts_name($id = 0)
{
    $res = dao('qrpay_discounts')
        ->field('min_amount, discount_amount, max_discount_amount')
        ->where(['status' => 1, 'id' => $id])
        ->find();
    return (!empty($res) && $res['min_amount'] > 0) ? "优惠满" . $res['min_amount'] . "元减" . $res['discount_amount'] : '';
}

/**
 * 更新收款码支付状态
 * @param  $log_id
 * @param  $pay_status 0 未支付 1 已支付
 * @return
 */
function qrpay_order_paid($log_id, $pay_status = 0)
{
    /* 取得支付编号 */
    $log_id = intval($log_id);
    if ($log_id > 0) {
        $pay_log = dao('qrpay_log')->where(['id' => $log_id, 'pay_status' => 0])->find();
        if (!empty($pay_log)) {
            dao('qrpay_log')->data(['pay_status' => $pay_status])->where(['id' => $log_id])->save();
        }
    }
}

/**
 * 更新保存交易信息 可用于对账查询
 * @param  $log_id
 * @param  $data
 * @return
 */
function update_trade_data($log_id, $data = [])
{
    $data = [
        'trade_no' => $data['transaction_id'],
        'notify_data' => serialize($data), // 保存序列化交易数据
    ];

    dao('qrpay_log')->data($data)->where(['id' => $log_id])->save();
}

/**
 * 结算商家收款码余额
 * @param  $order_id
 * @return
 */
function insert_seller_account_log($order_id)
{
    // 已支付 未结算 商家收款记录
    $res = dao('qrpay_log')->where(['id' => $order_id, 'is_settlement' => 0, 'pay_status' => 1])->find();
    if (!empty($res)) {
        if ($res['ru_id'] > 0) {
            $nowTime = gmtime();

            $other['admin_id'] = 0;
            $other['ru_id'] = $res['ru_id'];
            $other['order_id'] = 0;
            $other['amount'] = $res['pay_amount']; //结算金额
            $other['add_time'] = $nowTime; //结算时间
            $other['log_type'] = 2; // 2 结算 3 充值
            $other['is_paid'] = 1;
            $other['pay_id'] = dao('payment')->where(['pay_code' => $res['payment_code']])->getField('pay_id');
            $other['apply_sn'] = '【收款码订单】'.$res['pay_order_sn'];

            // 更新 已结算
            dao('qrpay_log')->data(['is_settlement' => 1])->where(['id' => $order_id, 'ru_id' => $res['ru_id']])->save();

            // 插入日志
            dao('seller_account_log')->data($other)->add();

            // 更新商家账户余额
            dao('seller_shopinfo')->where(['ru_id' => $res['ru_id']])->setInc('seller_money', $res['pay_amount']);

            $change_desc = '收款码自动结算商家应结金额';
            $user_account_log = [
                'user_id' => $res['ru_id'],
                'user_money' => $res['pay_amount'],
                'change_time' => $nowTime,
                'change_desc' => $change_desc,
                'change_type' => 2
            ];
            dao('merchants_account_log')->data($user_account_log)->add();
        } else {
            // 更新 已结算 平台默认
            dao('qrpay_log')->data(['is_settlement' => 1])->where(['id' => $order_id])->save();
        }

        return true;
    }

    return false;
}
