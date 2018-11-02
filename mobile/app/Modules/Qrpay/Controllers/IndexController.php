<?php

namespace App\Modules\Qrpay\Controllers;

use Payment\Config;
use App\Extensions\Http;
use App\Extensions\Wechat;
use Payment\Client\Query;
use Payment\Client\Charge;
use Payment\Client\Notify;
use Payment\Common\PayException;
use Payment\Notify\PayNotifyInterface;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    private $pay_code = '';
    private $qrpay_id = 0;
    private $qrpay_info = '';

    public function __construct()
    {
        parent::__construct();
        C('URL_MODEL', 0);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: X-HTTP-Method-Override, Content-Type, x-requested-with, Authorization');
        // 加载helper文件
        $helper_list = ['order'];
        $this->load_helper($helper_list);
        // 判断是支付宝或微信
        $this->pay_code = IsWeixinOrAlipay();

        $this->qrpay_id = input('id', 0, 'intval');
    }

    /**
     * 返回商家和二维码信息
     * mobile/index.php?m=qrpay&a=index&id=5
     * @return
     */
    public function actionIndex()
    {
        $this->qrpay_info = get_qrpay_info($this->qrpay_id);

        if ($this->pay_code == 'wxpay') {
            if (!empty($_SESSION['openid'])) {
                $_SESSION['openid_base'] = $_SESSION['openid'];
            } else {
                $_SESSION['openid_base'] = (isset($_SESSION['openid_base']) && !empty($_SESSION['openid_base'])) ? $_SESSION['openid_base'] : $this->getOpenid();
            }            
        }

        if (IS_AJAX) {
            if (!empty($this->qrpay_info)) {
                // 收款码类型 指定金额
                if ($this->qrpay_info['type'] == 1) {
                    $pay_amount = $this->qrpay_info['amount'];
                } else {
                    $pay_amount = 0;
                }
                // 商店名称
                if ($this->qrpay_info['ru_id'] > 0) {
                    $shop_name = dao('merchants_shop_information')->alias('a')
                        ->join(C('DB_PREFIX') . 'seller_shopinfo b on a.user_id = b.ru_id')
                        ->where(['ru_id' => $this->qrpay_info['ru_id'], 'b.shop_close' => 1])
                        ->getField('shop_title');
                } else {
                    $shop_name = C('shop.shop_name');
                }

                $detail = [
                    'seller' => $shop_name,
                    'qrcode' => [
                        'type' => $this->qrpay_info['type'],
                        'amount' => $pay_amount,
                        'qrpay_name' => $this->qrpay_info['qrpay_name'],
                    ]
                ];
                $this->response($detail);
            } else {
                $this->response(['error' => 1, 'message' => '收款码不存在']);
            }
        }

        if (empty($this->qrpay_info)) {
            show_message('收款码不存在', L('msg_go_back'), '');
        }

        $this->assign('qrpay_info', $this->qrpay_info);
        $this->display();
    }

    /**
     * 发起支付
     * mobile/index.php?m=qrpay&a=pay&id=5
     * @return
     */
    public function actionPay()
    {
        if (IS_AJAX) {
            $self_amount = input('amount', 0, 'floatval');
            if ($self_amount <= 0) {
                $this->response(['error' => 1, 'message' => '请输入支付金额']);
            }

            $this->qrpay_info = get_qrpay_info($this->qrpay_id);

            if (!empty($this->qrpay_info)) {
                // 收款码类型 指定金额
                if ($this->qrpay_info['type'] == 1) {
                    $pay_amount = $this->qrpay_info['amount'];
                } else {
                    $pay_amount = $self_amount;
                }
                // 商店名称
                if ($this->qrpay_info['ru_id'] > 0) {
                    $shop_name = dao('merchants_shop_information')->alias('a')
                        ->join(C('DB_PREFIX') . 'seller_shopinfo b on a.user_id = b.ru_id')
                        ->where(['ru_id' => $this->qrpay_info['ru_id'], 'b.shop_close' => 1])
                        ->getField('shop_title');
                } else {
                    $shop_name = C('shop.shop_name');
                }

                // 下单流程
                $order = [];
                $order['pay_order_sn'] = get_order_sn();

                //计算此收款码满减优惠后的金额
                if ($pay_amount > 0) {
                    $discount_fee = do_discount_fee($this->qrpay_info['id'], $pay_amount);
                    $pay_amount = $pay_amount - $discount_fee;
                    $pay_amount = number_format($pay_amount, 2, '.', '');
                }
                $order['pay_amount'] = $pay_amount;

                $order['pay_user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                $order['openid'] = (isset($_SESSION['openid_base']) && !empty($_SESSION['openid_base'])) ? $_SESSION['openid_base'] : '';
                $order['add_time'] = gmtime();
                $order['qrpay_id'] = $this->qrpay_info['id'];
                $order['pay_desc'] = (isset($discount_fee) && $discount_fee > 0) ? get_discounts_name($this->qrpay_info['discount_id']) : ''; // 备注
                $order['ru_id'] = !empty($this->qrpay_info['ru_id']) ? $this->qrpay_info['ru_id'] : 0; // 商家id
                $order['payment_code'] = $this->pay_code; // 支付方式

                $insert = false; // 是否插入订单
                if ($this->pay_code == 'wxpay') {
                    if (isset($_SESSION['openid_base']) && !empty($_SESSION['openid_base'])) {
                        $insert = true;
                    }
                } else {
                    $insert = true;
                }

                if ($insert == true) {
                    /* 插入收款记录表 */
                    $error_no = 0;
                    do {
                        $order['pay_order_sn'] = get_order_sn(); //获取新订单号
                        $new_order = $this->db->filter_field('qrpay_log', $order);
                        try {
                            $new_order_id = dao('qrpay_log')->data($new_order)->add();
                        } catch (\Exception $e) {
                            $error_no = (int)substr($e->getMessage(), 0, 4);
                        }

                        if ($error_no > 0 && $error_no != 1062) {
                            die($e->getMessage());
                        }
                    } while ($error_no == 1062); //如果是订单号重复则重新提交数据

                    $order['id'] = $new_order_id;
                }

                /* 取得支付信息，生成支付代码 */
                $payment = get_payment_info($this->pay_code);

                if (!empty($order['id']) && $payment && $pay_amount > 0) {
                    // 业务请求参数
                    $payData = $this->getPayData($order);

                    try {
                        $trade_type = $this->pay_code == 'wxpay' ? Config::WX_CHANNEL_PUB : Config::ALI_CHANNEL_WAP;
                        $ret = Charge::run($trade_type, $this->getConfig(), $payData);
                        // 微信转json数据
                        $ret = $this->pay_code == 'wxpay' ? json_encode($ret, JSON_UNESCAPED_UNICODE) : $ret;
                    } catch (PayException $e) {
                        // 异常处理
                        exit($e->getMessage());
                    }
                }

                $detail = [
                    'seller' => $shop_name,
                    'qrcode' => [
                        'type' => $this->qrpay_info['type'],
                        'amount' => $pay_amount,
                        'qrpay_name' => $this->qrpay_info['qrpay_name'],
                    ],
                    'paycode' => $this->pay_code,
                    'payment' => $ret
                ];
                $this->response($detail);
            } else {
                $this->response(['error' => 1, 'message' => '收款码不存在']);
            }
        }
    }

    /**
     * 支付同步通知
     * @return
     */
    public function actionCallback()
    {
        // 提示类型
        $msg_type = 2;
        $payment = get_payment_info($this->pay_code);
        if ($payment === false) {
            $msg = L('pay_disabled');
        } else {
            if (!empty($_GET)) {
                try {
                    if ($this->pay_code == 'alipay') {
                        $order = [];
                        list($order['pay_order_sn'], $order['id']) = explode('Q', $_GET['out_trade_no']);
                        $res = $this->query($order);
                        if ($res === true) {
                            $msg = L('pay_success');
                            $msg_type = 0;
                        } else {
                            $msg = L('pay_fail');
                            $msg_type = 1;
                        }
                    } elseif ($this->pay_code == 'wxpay') {
                        $status = input('get.status', 0, 'intval');
                        if ($status == 1) {
                            $msg = L('pay_success');
                            $msg_type = 0;
                        } else {
                            $msg = L('pay_fail');
                            $msg_type = 1;
                        }
                    }
                } catch (PayException $e) {
                    logResult($e->getMessage());
                }
            } else {
                $msg = L('pay_fail');
                $msg_type = 1;
            }
        }

        // 显示页面
        $id = isset($order['id']) ? dao('qrpay_log')->where(['id' => $order['id']])->getField('qrpay_id') : input('get.id', 0, 'intval');
        $this->assign('id', $id);
        $this->assign('message', $msg);
        $this->assign('msg_type', $msg_type);
        $this->assign('page_title', L('pay_status'));
        $this->display();
    }

    /**
     * 支付异步通知
     * @return
     */
    public function actionNotify()
    {
        // 获取code参数
        $this->pay_code = str_replace('_qrpay', '', input('get.code'));
        // logResult('==========pay_code-q1=========');
        // logResult($this->pay_code);
        if (isset($_GET['code'])) {
            unset($_GET['code']);
        }

        // if ($this->pay_code == 'wxpay') {
        //     $postStr = file_get_contents("php://input");
        //     logResult('==========postStr-q1=========');
        //     logResult($postStr);
        // } else {
        //     // $_POST = str_replace('\\', '', $_POST);

        //     logResult('==========POST-q1=========');
        //     logResult($_POST);
        // }

        $config = $this->getConfig();
        $config['notify_url'] = preg_replace('/\/public\/notify/', '', $config['notify_url'], 1);
        if (isset($config['return_url'])) {
            $config['return_url'] = preg_replace('/\/public\/notify/', '', $config['return_url'], 1);
        }

        try {
            $callback = new OrderPaidNotify();
            $trade_type = $this->pay_code == 'wxpay' ? Config::WX_CHARGE : Config::ALI_CHARGE;
            $ret = Notify::run($trade_type, $config, $callback);// 处理回调，内部进行了签名检查
            exit($ret);
        } catch (PayException $e) {
            logResult($e->getMessage());
            exit('fail');
        }
    }

    /**
     * 订单查询
     * @return mixed
     */
    public function query($order)
    {
        $data = [
            'out_trade_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
        ];

        try {
            $trade_type = $this->pay_code == 'wxpay' ? Config::WX_CHARGE : Config::ALI_CHARGE;
            $ret = Query::run($trade_type, $this->getConfig(), $data);
            if ($ret['response']['trade_state'] === Config::TRADE_STATUS_SUCC) {
                qrpay_order_paid($order['id'], 1);
                return true;
            }
        } catch (PayException $e) {
            logResult($e->getMessage());
        }

        return false;
    }

    /**
     * 业务请求参数
     * @param  $order
     * @return
     */
    private function getPayData($order)
    {
        $payData = [];
        if ($this->pay_code == 'alipay') {
            $payData = [
                'body' => $order['pay_order_sn'],
                'subject' => !empty($order['pay_desc']) ? "【" . $order['pay_desc'] . "】" . $order['pay_order_sn'] : $order['pay_order_sn'],
                'order_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
                'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
                'amount' => $order['pay_amount'],// 单位为元 ,最小为0.01
                'return_param' => 'qr' . $order['id'],// 一定不要传入汉字，只能是 字母 数字组合
                'client_ip' => $this->get_client_ip(),// 客户地址
                'goods_type' => 1,
                'store_id' => '',
            ];
        }

        if ($this->pay_code == 'wxpay') {
            $payData = [
                'body' => $order['pay_order_sn'],
                'subject' => !empty($order['pay_desc']) ? "【" . $order['pay_desc'] . "】" . $order['pay_order_sn'] : $order['pay_order_sn'],
                'order_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
                'timeout_express' => time() + 3600 * 24,// 表示必须 24h 内付款
                'amount' => $order['pay_amount'],// 单位为元 接口已转换为分
                'return_param' => 'qr' . $order['id'],// 一定不要传入汉字，只能是 字母 数字组合
                'client_ip' => $this->get_client_ip(),// 客户地址
                'openid' => $order['openid'],
            ];
        }

        return $payData;
    }

    /**
     * 获取配置
     * @return array
     */
    protected function getConfig()
    {
        $payment = get_payment_info($this->pay_code);
        $config = [];
        if ($this->pay_code == 'alipay') {
            $config = [
                'use_sandbox' => (bool)$payment['use_sandbox'],
                'partner' => $payment['alipay_partner'],
                'app_id' => $payment['app_id'],
                'sign_type' => $payment['sign_type'],
                // 可以填写文件路径，或者密钥字符串  当前字符串是 rsa2 的支付宝公钥(开放平台获取)
                'ali_public_key' => $payment['ali_public_key'],
                // 可以填写文件路径，或者密钥字符串  我的沙箱模式，rsa与rsa2的私钥相同，为了方便测试
                'rsa_private_key' => $payment['rsa_private_key'],
                'notify_url' => __URL__ . '/public/notify/' . $this->pay_code . '_qrpay.php',
                'return_url' => url('qrpay/index/callback', ['id' => $this->qrpay_id], 0, true),
                'return_raw' => false,
            ];
        }

        if ($this->pay_code == 'wxpay') {
            $config = [
                'use_sandbox' => (bool)$payment['use_sandbox'],
                'app_id' => $payment['wxpay_appid'], // 公众账号ID
                'mch_id' => $payment['wxpay_mchid'], // 商户id
                'md5_key' => $payment['wxpay_key'], // md5 秘钥
                // 'app_cert_pem' => ROOT_PATH . 'storage/app/certs/apiclient_cert.pem',
                // 'app_key_pem' => ROOT_PATH . 'storage/app/certs/apiclient_key.pem',
                'sign_type' => 'MD5', // MD5  HMAC-SHA256
                'fee_type' => 'CNY', // 货币类型  当前仅支持该字段
                'notify_url' => __URL__ . '/public/notify/' . $this->pay_code . '_qrpay.php',
                // 'redirect_url' => url('qrpay/index/callback', 0, 0, true), // h5支付跳转地址
                'return_raw' => false,
            ];
        }

        return $config;
    }

    /**
     * 获取当前服务器的IP
     */
    private function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "127.0.0.1";
        }
        return $cip;
    }


    /**
     * 获取openid
     * @return bool
     */
    protected function getOpenid()
    {
        if (empty($_SESSION['openid_base'])) {
            $payment = get_payment_info($this->pay_code);
            $options = [
                'appid' => $payment['wxpay_appid'],
                'appsecret' => $payment['wxpay_appsecret'],
            ];
            $obj = new Wechat($options);
            if (isset($_GET['code']) && $_GET['state'] == 'qrrepeat') {
                $token = $obj->getOauthAccessToken();
                $_SESSION['openid_base'] = $token['openid'];
                return $_SESSION['openid_base'];
            }
            $callback = __HOST__ . $_SERVER['REQUEST_URI'];
            $url = $obj->getOauthRedirect($callback, 'qrrepeat', 'snsapi_base');
            redirect($url);
        }
    }

    /**
     * 验证是否登录
     * @return
     */
    protected function checklogin()
    {
        if (empty($_SESSION['user_id'])) {
            $back_act = __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('user/login/index', ['back_act' => urlencode($back_act)]);
        }
    }

}

/**
 * 客户端需要继承该接口，并实现这个方法，在其中实现对应的业务逻辑
 * Class OrderPaidNotify
 */
class OrderPaidNotify implements PayNotifyInterface
{
    public function notifyProcess(array $data)
    {
        /**
         * 改变支付状态
         *
         */
        // logResult('notify_data:');
        // logResult($data);
        $out_trade_no = explode('Q', $data['order_no']);
        $log_id = $out_trade_no['1']; // 订单号log_id
        qrpay_order_paid($log_id, 1);

        // 保存交易信息
        update_trade_data($log_id, $data);

        // 自动结算 商家账户
        insert_seller_account_log($log_id);

        return true;
    }
}
