<?php

namespace App\Modules\Onlinepay\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{

    public function __construct()
    {
        parent::__construct();
        $files = [
            'order',
            'clips',
            'transaction',
        ];
        $this->load_helper($files);
        $this->check_login();
        //ecmoban模板堂 --zhuo start
        if (!empty($_SESSION['user_id'])) {
            $this->sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";

            $this->a_sess = " a.user_id = '" . $_SESSION['user_id'] . "' ";
            $this->b_sess = " b.user_id = '" . $_SESSION['user_id'] . "' ";
            $this->c_sess = " c.user_id = '" . $_SESSION['user_id'] . "' ";

            $this->sess_ip = "";
        } else {
            $this->sess_id = " session_id = '" . real_cart_mac_ip() . "' ";

            $this->a_sess = " a.session_id = '" . real_cart_mac_ip() . "' ";
            $this->b_sess = " b.session_id = '" . real_cart_mac_ip() . "' ";
            $this->c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";

            $this->sess_ip = real_cart_mac_ip();
        }
    }

    /**
     * 在线支付
     */
    public function actionIndex()
    {
        $order_sn = input('order_sn', '', ['trim', 'html_in']);
        $order_id = dao('order_info')->field('order_id')->where(['order_sn' => $order_sn, 'user_id'=>$_SESSION['user_id']])->find();
        if (empty($order_id)) {
            show_message('非法操作', '', url('/'), 'warning');
        }
        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = available_payment_list(0, 0);

        if (isset($payment_list)) {
            foreach ($payment_list as $key => $payment) {

                if ($payment['is_cod'] == '1') {
                    $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
                }
                //pc端去除ecjia的支付方式
                if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                    unset($payment_list[$key]);
                    continue;
                }
                if ($payment['is_online'] != 1) {
                    unset($payment_list[$key]);
                }
                if ($payment ['pay_code'] == 'cod') {
                    unset($payment_list [$key]);
                }
                if (!file_exists(ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php')) {
                    unset($payment_list[$key]);
                }
                // 不显示余额支付
                if ($payment ['pay_code'] == 'balance') {
                    unset($payment_list [$key]);
                }
                if ($payment['pay_code'] == 'wxpay') {
                    if (!is_dir(APP_WECHAT_PATH)) {
                        unset($payment_list[$key]);
                    }
                     //非微信浏览控制显示h5
                    if (is_wechat_browser() == false && is_wxh5() == 0) {
                        unset($payment_list[$key]);
                    }
                }
            }
        }
        if (empty($payment_list)) {
            show_message('请安装在线支付方式', '', url('user/order/index'), 'warning');
        }
        /* 订单详情 */
        $order = $this->db->getRow("SELECT * FROM {pre}order_info WHERE order_id='" . $order_id['order_id'] . "' LIMIT 1");

        //获取log_id
        $order['log_id'] = $GLOBALS['db']->getOne(" SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id = '" . $order_id['order_id'] . "' LIMIT 1 ");

        /* 取得支付信息，生成支付代码 */
        if ($order['order_amount'] > 0) {
            //查询"在线支付"的pay_id;
            $onlinepay_pay_id = $this->db->getOne("SELECT pay_id FROM {pre}payment WHERE pay_code='onlinepay'");
            $order_pay_enabled = $this->db->getOne("SELECT enabled FROM {pre}payment WHERE pay_code=$order[pay_id]");
            if ($order_pay_enabled == 0 || $order['pay_id'] == $onlinepay_pay_id) {
                $default_payment = reset($payment_list);
                $order['pay_id'] = $default_payment['pay_id'];
            }
        } else {
            show_message('非法操作', '', url('/'), 'warning');
        }
        //默认是支付宝
        if (!empty($order['pay_id'])) {
            $payment = payment_info($order['pay_id']);
            //改变订单的支付名称和支付id
            $sql="UPDATE {pre}order_info set pay_id='" .$order['pay_id']. "',pay_name='" .$payment['pay_name']. "' WHERE order_id = '" .$order['order_id']. "'";
            $this->db->query($sql);
            $sql = "SELECT order_id FROM ". $GLOBALS['ecs']->table('order_info') ." WHERE main_order_id = '$order_id[order_id]'";
            $child_order_id_arr = $GLOBALS['db']->getAll($sql);
            if ($order['main_order_id'] == 0 && count($child_order_id_arr) > 0 && $order['order_id'] > 0) {
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                    " SET pay_id = '" . $order['pay_id'] . "', " .
                    " pay_name = '" .$payment['pay_name'] . "'" .
                    "WHERE main_order_id = '$order[order_id]'";
                $GLOBALS['db']->query($sql);
            }
        } else {
            show_message('非法操作', '', url('user/order/index'), 'warning');
        }

        include_once(ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php');
        $pay_obj = new $payment['pay_code'];
        $order['pay_desc'] = $payment['pay_desc'];
        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
        $this->assign('pay_online',$pay_online);
        $order['order_amount'] = price_format($order['order_amount']);
        $this->assign('order', $order);
        $this->assign('payment_list', $payment_list);
        $this->assign('page_title', '收银台');
        $this->display();
    }

    /**
     * 切换支付方式
     */
    public function actionChangePayment()
    {
        $payment_id = I('pay_id', 0, 'intval');
        $order_id = I('order_id', 0, 'intval');

        if (empty($payment_id)) {
            show_message('非法操作', '', url('/'), 'warning');
        }
        if (IS_AJAX) {
            $payment = payment_info($payment_id);
            /* 订单详情 */
            $order = $this->db->getRow("SELECT * FROM {pre}order_info WHERE order_id='" . $order_id. "' LIMIT 1");
            //获取log_id
            $order['log_id'] = $GLOBALS['db']->getOne(" SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id = '" . $order_id . "' LIMIT 1 ");
            //改变订单的支付名称和支付id
            $sql="UPDATE {pre}order_info set pay_id='" .$payment_id. "',pay_name='" .$payment['pay_name']. "' WHERE order_id = '" .$order_id. "'";
            $this->db->query($sql);
            $sql = "SELECT order_id FROM ". $GLOBALS['ecs']->table('order_info') ." WHERE main_order_id = '$order_id'";
            $child_order_id_arr = $GLOBALS['db']->getAll($sql);
            if ($order['main_order_id'] == 0 && count($child_order_id_arr) > 0 && $order_id > 0) {
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                    " SET pay_id = '" . $payment_id . "', " .
                    " pay_name = '" .$payment['pay_name'] . "', " .
                    "WHERE main_order_id = '$order_id'";
                $GLOBALS['db']->query($sql);
            }
            include_once(ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php');
            $pay_obj = new $payment['pay_code'];
            $order['pay_desc'] = $payment['pay_desc'];
            $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
            exit($pay_online);
        }
    }

    /**
     * 验证是否登录
     */
    public function check_login()
    {
        if (!$_SESSION['user_id']) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            if (IS_AJAX) {
                $this->ajaxReturn(['error' => 1, 'message' => L('yet_login'), 'url' => url('user/login/index', ['back_act' => urlencode($back_act)])]);
            }
            $this->redirect('user/login/index', ['back_act' => urlencode($back_act)]);
        }
    }

}
