<?php

namespace App\Modules\Respond\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    private $data = [];

    public function __construct()
    {
        parent::__construct();
        C('URL_MODEL', 0);
        // 获取参数
        $this->data = [
            'code' => I('get.code')
        ];
        if (isset($_GET['code'])) {
            unset($_GET['code']);
        }
    }

    /**
     * 处理支付同步通知
     */
    public function actionIndex()
    {
        // 提示类型
        $msg_type = 2;
        $payment = $this->getPayment();
        if ($payment === false) {
            $msg = L('pay_disabled');
        } else {
            // 微信h5中间页面
            if (isset($_GET['type']) && $this->data['code'] == 'wxpay' && $_GET['type'] == 'wxh5') {
                $log_id = intval($_GET['log_id']);
                $this->redirect('respond/index/wxh5', ['code' => 'wxpay', 'log_id' => $log_id]);
            }

            if ($payment->callback($this->data)) {
                $msg = L('pay_success');
                $msg_type = 0;
            } else {
                $msg = L('pay_fail');
                $msg_type = 1;
            }
        }

        // 根据不同订单类型（普通、充值） 跳转
        if (isset($_GET['log_id']) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = dao('pay_log')->field('order_type, order_id')->where(['log_id' => $log_id])->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order/detail', ['order_id' => $pay_log['order_id']]);
            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account/detail');
            } elseif ($pay_log['order_type'] == 2) { //分销购买
                $order_url = url('drp/user/index');
            } elseif ($pay_log['order_type'] == 3) { //拼团
                $order_url = url('team/user/index');
            }
			
        } else {
            $order_url = url('user/order/index'); // 订单列表
        }
        $order_url = str_replace('respond', 'index', $order_url);
        // 显示页面
        $this->assign('message', $msg);
        $this->assign('msg_type', $msg_type);
        $this->assign('order_url', $order_url);
        $this->assign('page_title', L('pay_status'));
        $this->display();
    }

    /**
     * 处理支付异步通知
     */
    public function actionNotify()
    {
        $payment = $this->getPayment();
        if ($payment === false) {
            exit('plugin load fail');
        }
        $payment->notify($this->data);
    }

    /**
     * 获得支付信息
     */
    private function getPayment()
    {
        /* 判断启用状态 */
        $condition = [
            'pay_code' => $this->data['code'],
            'enabled' => 1
        ];
        $enabled = $this->db->table('payment')->where($condition)->count();
        $plugin = ADDONS_PATH . 'payment/' . $this->data['code'] . '.php';
        if (!is_file($plugin) || $enabled == 0) {
            return false;
        }
        /* 实例化插件 */
        require_cache($plugin);
        $payment = new $this->data['code']();
        return $payment;
    }

    /**
     * 微信支付h5同步通知中间页面
     * @return
     */
    public function actionWxh5()
    {
        //显示页面
        if (isset($_GET) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = dao('pay_log')->field('order_type, order_id')->where(['log_id' => $log_id])->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order/detail', ['order_id' => $pay_log['order_id']]);
            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account/detail');
            } elseif ($pay_log['order_type'] == 2) { //分销购买
                $order_url = url('drp/user/index');
            } elseif ($pay_log['order_type'] == 3) { //拼团
                $order_url = url('team/user/index');
            }
            $order_url = str_replace('respond', 'index', $order_url);
            $repond_url = __URL__ . "/respond.php?code=" .$this->data['code']. "&status=1&log_id=".$log_id;
        } else {
            $repond_url = __URL__ . "/respond.php?code=" .$this->data['code']. "&status=0";
        }
        $is_wxh5 = ($this->data['code'] == 'wxpay' && !is_wechat_browser()) ? 1 : 0;
        $this->assign('is_wxh5', $is_wxh5);
        $this->assign('repond_url', $repond_url);
        $this->assign('order_url', $order_url);
        $this->assign('page_title', '确认支付');
        $this->display();
    }
}
