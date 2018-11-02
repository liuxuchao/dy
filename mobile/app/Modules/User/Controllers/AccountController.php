<?php

namespace App\Modules\User\Controllers;

use Think\Verify;
use App\Modules\Base\Controllers\FrontendController;

class AccountController extends FrontendController
{
    protected $user_id = 0; // 用户id
    protected $size = 10;

    /**
     * 构造，加载文件语言包和helper文件
     */
    public function __construct()
    {
        parent::__construct();
        $this->user_id = $_SESSION['user_id'];
        $this->actionchecklogin();
        L(require(LANG_PATH . C('shop.lang') . '/user.php'));
        L(require(LANG_PATH . C('shop.lang') . '/flow.php'));
        $this->assign('lang', array_change_key_case(L()));
        $files = [
            'order',
            'clips',
            'payment',
            'transaction',
        ];
        $this->load_helper($files);
    }

    /**
     * 频道页
     */
    public function actionIndex()
    {
        // 当前余额
        $surplus_amount = get_user_surplus($this->user_id);

        $this->assign('surplus_amount', $surplus_amount ? $surplus_amount : 0);
        // 当前冻结资金
        $frozen_money = get_user_frozen($this->user_id);
        $this->assign('frozen_money', $frozen_money ? $frozen_money : 0);
        // 红包数量
        $this->assign('record_count', my_bonus($this->user_id));
        // 银行卡数量 已废弃
        // $drp_card = $this->db->getOne("SELECT COUNT(*) FROM {pre}user_bank WHERE user_id = '$this->user_id'");
        // $this->assign('drp_card', $drp_card ? $drp_card : 0);
        //储值卡 liu
        $sql = " SELECT COUNT(*) AS num, SUM(card_money) AS money FROM {pre}value_card WHERE user_id = '$this->user_id' ";
        $vc = $this->db->getRow($sql);
        $vc['money'] = price_format($vc['money']);
        $this->assign('value_card', $vc);
        // 积分数量
        $pay_points = $this->db->getOne("SELECT  pay_points FROM {pre}users WHERE user_id='$this->user_id'");
        $this->assign('pay_points', $pay_points ? $pay_points : 0);

        $this->assign('page_title', L('label_user_surplus'));
        $this->display();
    }

    /**
     * 个人积分明细
     */
    public function actionPayPoints()
    {
        if (IS_AJAX) {
            $account_type = 'pay_points';
            $page = input('page', 1, 'intval');
            $this->size = 15;
            $log_list = get_user_accountlog_count($this->user_id, $account_type, $page, $this->size);
            exit(json_encode(['list' => $log_list['list'], 'totalPage' => $log_list['totalpage']]));
        }
        $this->assign('page_title', L('user_pay_points'));
        $this->display();
    }


    /**
     * 资金明细
     */
    public function actionDetail()
    {
        if (IS_AJAX) {
            $account_type = 'user_money';
            $page = input('page', 1, 'intval');
            $this->size = 15;
            $log_list = get_user_accountlog_count($this->user_id, $account_type, $page, $this->size);
            exit(json_encode(['list' => $log_list['list'], 'totalPage' => $log_list['totalpage']]));
        }
        $this->assign('page_title', L('account_detail'));
        $this->display();
    }

    /**
     * 用户充值
     */
    public function actionDeposit()
    {
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 2;
        $account = get_surplus_info($surplus_id);
        $payment_list = get_online_payment_list(false);
        foreach ($payment_list as $key => $val) {
            if (!file_exists(ADDONS_PATH . 'payment/' . $val['pay_code'] . '.php')) {
                unset($payment_list[$key]);
            }
            if ($val['pay_code'] == 'onlinepay') {
                unset($payment_list[$key]);
            }
        }

        $buyer_recharge = floatval(C('shop.buyer_recharge')); // 买家充值最低金额，0表示不限
        $this->assign('buyer_recharge', $buyer_recharge);

        $this->assign('payment', $payment_list);
        $this->assign('order', $account);
        $this->assign('process_type', $surplus_id);
        $this->assign('page_title', L('account_user_charge'));
        $this->display();
    }

    /**
     *  会员退款申请界面  提现
     */
    public function actionAccountRaply()
    {
        // 检测是否实名认证
        $user_real = dao('users_real')->where(['user_id' => $this->user_id, 'user_type' => 0])->find();
        if (empty($user_real)){
            show_message(L('user_real'), '', '', 'fail');
        }
        if ($user_real['review_status'] != 1) {
            show_message(L('user_real_review'), '', '', 'warning');
        }

        // 获取剩余余额
        $surplus_amount = get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }

        $buyer_cash = floatval(C('shop.buyer_cash')); // 买家提现最低金额，0表示不限
        $this->assign('buyer_cash', $buyer_cash);

        // 组装提现卡号，二维数组便于模板循环
        $bank = [
            [
                'bank_name' => $user_real['bank_name'],
                'bank_card' => substr($user_real['bank_card'], 0, 4) . '******' . substr($user_real['bank_card'], -4),
                'bank_region' => $user_real['bank_name'],
                'bank_user_name' => $user_real['real_name'],
                'bank_card_org' => $user_real['bank_card'],
                'bank_mobile' => $user_real['bank_mobile'],
            ]
        ];
        $this->assign('bank', $bank);
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('deposit_fee', C('shop.deposit_fee'));
        $this->assign('page_title', L('account_user_repay'));
        $this->display();
    }

    /**
     * 申请提现页面   对会员余额申请的处理
     */
    public function actionAccount()
    {
        $surplus_type = input('surplus_type', 0, 'intval'); // 0 充值, 1 提现
        $amount = input('amount', 0, 'floatval');  // 充值、提现金额

        if ($amount <= 0) {
            show_message(L('amount_gt_zero'), '', '', 'warning');
        }

        // 提现验证实名
        if ($surplus_type == 1) {
            $user_real = dao('users_real')->where(['user_id' => $this->user_id, 'user_type' => 0])->count();
            if (empty($user_real)) {
                show_message(L('user_real'), '', '', 'fail');
            }
            $buyer_cash = floatval(C('shop.buyer_cash')); // 买家提现最低金额，0表示不限
            if (!empty($buyer_cash) && $amount < $buyer_cash) {
                show_message(L('amount_gt_little').$buyer_cash.'元', '', '', 'warning');
            }
        }
        // 充值
        if ($surplus_type == 0) {
            $buyer_recharge = floatval(C('shop.buyer_recharge')); // 买家充值最低金额，0表示不限
            if (!empty($buyer_recharge) && $amount < $buyer_recharge) {
                show_message(L('amount_gt_pay').$buyer_recharge.'元', '', '', 'warning');
            }
        }

        $rec_id = input('rec_id', 0, 'intval');
        $payment_id = input('payment_id', 0, 'intval');
        $user_note = input('user_note', '', ['html_in', 'trim']);

        /* 变量初始化 */
        $surplus = [
            'user_id' => $this->user_id,
            'rec_id' => $rec_id,
            'process_type' => $surplus_type,
            'payment_id' => $payment_id,
            'user_note' => $user_note,
            'amount' => $amount
        ];

        /* 退款申请的处理 */
        if ($surplus['process_type'] == 1) {
            // 资金提现增加短信验证码
            if (C('shop.sms_signin') == 1) {
                $mobile = input('mobile', '', ['html_in', 'trim']);
                $mobile_code = input('mobile_code', '', ['html_in', 'trim']);
                if ($mobile != $_SESSION['sms_mobile'] || $mobile_code != $_SESSION['sms_mobile_code']) {
                    show_message(L('mobile_code_fail'), L('back_input_code'), '', 'error');
                }
            }
            /* 判断是否有足够的余额的进行退款的操作 */
            $sur_amount = get_user_surplus($this->user_id);
            if ($amount > $sur_amount) {
                show_message(L('surplus_amount_error'), L('back_page_up'), '', 'warning');
            }
            $bank_number = input('bank_number', '', ['html_in', 'trim']);
            $real_name = input('real_name', '', ['html_in', 'trim']);
            if (empty($bank_number) || empty($real_name)) {
                show_message(L('account_withdraw_deposit'), L('account_submit_information'), '', 'warning');
            }
            $deposit_fee = !empty(C('shop.deposit_fee')) ? intval(C('shop.deposit_fee')) : 0; // 提现手续费比例
            $deposit_money = 0;
            if ($deposit_fee > 0) {
                $deposit_money = $amount * $deposit_fee / 100;
            }
            //判断手续费扣除模式，余额充足则从余额中扣除手续费，不足则在提现金额中扣除
            if(($amount+$deposit_money) > $sur_amount){
                $amount = $amount - $deposit_money;
            }
            //插入会员账目明细
            $surplus['deposit_fee'] = '-'.$deposit_money;

            //提现金额
            $frozen_money = $amount + $deposit_money;
            $amount = '-'.$amount;
            $surplus['payment'] = '';
            $surplus['rec_id']  = insert_user_account($surplus, $amount);

            /* 如果成功提交 */
            if ($surplus['rec_id'] > 0) {
                //by wang提现记录扩展信息start
                $user_account_fields = [
                    'user_id' => $surplus['user_id'],
                    'account_id' => $surplus['rec_id'],
                    'bank_number' => $bank_number,
                    'real_name' => $$real_name
                ];

                insert_user_account_fields($user_account_fields);
                //by wang提现记录扩展信息end

                /* 申请提现的资金进入冻结状态 */
                log_account_change($this->user_id, $amount, $frozen_money, 0, 0, "【" . L('application_withdrawal') . "】" . $surplus['user_note'], ACT_ADJUSTING,0,$surplus['deposit_fee']);

                // remove session
                unset($_SESSION['sms_mobile']);
                unset($_SESSION['sms_mobile_code']);

                show_message(L('surplus_appl_submit'), L('back_account_log'), url('log'), 'success');
            } else {
                show_message(L('process_false'), L('back_page_up'), '', 'fail');
            }
        } else {
            /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
            if ($surplus['payment_id'] <= 0) {
                show_message(L('select_payment_pls'), '', '', 'warning');
            }

            //获取支付方式名称
            $payment_info = [];
            $payment_info = payment_info($surplus['payment_id']);
            $surplus['payment'] = $payment_info['pay_name'];

            if ($surplus['rec_id'] > 0) {
                //更新会员账目明细
                $surplus['rec_id'] = update_user_account($surplus);
            } else {
                //插入会员账目明细
                $surplus['rec_id'] = insert_user_account($surplus, $amount);
            }
            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);

            //生成伪订单号, 不足的时候补0
            $order = [];
            $order['order_sn'] = $surplus['rec_id'];
            $order['user_name'] = $_SESSION['user_name'];
            $order['surplus_amount'] = $amount;

            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

            //计算此次预付款需要支付的总金额
            $order['order_amount'] = $amount + $payment_info['pay_fee'];

            //记录支付log
            $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type = PAY_SURPLUS, 0);
            if (!file_exists(ADDONS_PATH . 'payment/' . $payment_info['pay_code'] . '.php')) {
                unset($payment_info['pay_code']);
                ecs_header("Location: " . url('user/account/log'));
            } else {
                /* 调用相应的支付方式文件 */
                include_once(ADDONS_PATH . 'payment/' . $payment_info['pay_code'] . '.php');

                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $payment_info['pay_code'];

                $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
                $pay_fee = !empty($payment_info['pay_fee']) ? price_format($payment_info['pay_fee'], false) : 0;

                /* 模板赋值 */
                $this->assign('payment', $payment_info);
                $this->assign('pay_fee', $pay_fee);
                $this->assign('amount', price_format($amount, false));
                $this->assign('order', $order);
                $this->assign('type', 1);
                $this->assign('page_title', L('account_charge'));
                $this->assign('but', $payment_info['pay_button']);
                $this->display();
            }
        }
    }

    /**
     * 申请记录
     * @param 充值或提现记录
     */
    public function actionLog()
    {
        if (IS_AJAX) {
            $page = input('page', 1, 'intval');
            $this->size = 15;
            $log_list = get_account_log($this->user_id, $page, $this->size);
            exit(json_encode(['log_list' => $log_list['log_list'], 'totalPage' => $log_list['totalpage']]));
        }

        $this->assign('page_title', L('account_apply_record'));
        $this->display();
    }

    /**
     * 账户详情Log  详情
     * @param type 0 充值 1 提现
     */
    public function actionAccountDetail()
    {
        $log_id = input('id', 0, 'intval');
        //获取记录详情
        $log_detail = get_account_log_info($this->user_id, $log_id);
        if (!$log_detail) {
            $this->redirect('user/account/log');
        }

        $log_detail['pay_fee'] = empty($log_detail['pay_fee']) ? 0 : price_format($log_detail['pay_fee']);

        $log_title = ($log_detail['process_type'] == 0) ? L('surplus_type_0') : L('surplus_type_1');

        $this->assign('log_detail', $log_detail);
        $this->assign('page_title', $log_title . L('account_details'));
        $this->display();
    }

    /**
     * 操作取消
     */
    public function actionCancel()
    {
        if (IS_POST) {
            $id = input('request.id', 0, 'intval');

            if ($id == 0 || $this->user_id == 0) {
                exit(json_encode(['error' => 1, 'msg' => L('取消失败')]));
            }

            $result = del_user_account($id, $this->user_id);
            if ($result == true) {
                del_user_account_fields($id, $this->user_id); // 删除扩展信息表
                exit(json_encode(['error' => 0, 'msg' => L('取消成功'), 'url' => url('user/account/log') ]));
            }
        }
    }

    /**
     * 用户红包列表
     * status 0 未使用 1 已使用 2 已过期
     */
    public function actionBonus()
    {
        if (IS_AJAX) {
            $page = input('page', 1, 'intval');
            $size = input('size', 10, 'intval');
            $type = input('type', 0, 'intval'); // 0 未使用 1 已使用 2 已过期

            $bonus_list = get_user_bouns_list($this->user_id, $type, $size, $page);
            exit(json_encode(['bonus_list' => $bonus_list['list'], 'totalPage' => $bonus_list['totalpage']]));
        }
        // 红包类型数量
        $bonus1 = get_user_conut_bonus($this->user_id, 0);
        $bonus2 = get_user_conut_bonus($this->user_id, 1);
        $bonus3 = get_user_conut_bonus($this->user_id, 2);
        $status = [
            'one' => $bonus1,
            'two' => $bonus2,
            'three' => $bonus3,
        ];
        $this->assign('status', $status);
        $this->assign('page_title', L('account_discount_list'));
        $this->display();
    }

    /**
     * 个人中心优惠券
     * 显示优惠券
     */
    public function actionCoupont()
    {
        $page = I('page', 1, 'intval');
        $status = I('status', 0, 'intval'); // status 0 未使用 1 已使用 2 已过期
        if (IS_AJAX) {
            $coupons_list = get_coupons_lists($this->size, $page, $status);
            exit(json_encode(['coupons_list' => $coupons_list, 'totalPage' => $coupons_list['totalpage']]));
        }
        $this->assign('status', $status);
        $this->assign('page_title', L('coupont_list'));
        $this->display();
    }

    /**
     * 添加红包
     */
    public function actionAddbonus()
    {
        if (IS_POST) {
            $bouns_sn = input('bonus_sn', 0, 'intval');
            $bouns_password = input('bouns_password', '', ['htmlspecialchars', 'trim']);
            if (empty($bouns_sn)) {
                show_message('红包口令不能为空', L('back_up_page'), url('user/account/bonus'));
            }
            if (empty($bouns_password)) {
                show_message('红包密码不能为空', L('back_up_page'), url('user/account/bonus'));
            }

            if (add_bonus($this->user_id, $bouns_sn, $bouns_password)) {
                show_message(L('add_bonus_sucess'), L('back_up_page'), url('user/account/bonus'), 'info');
            } else {
                show_message(L('add_bonus_false'), L('back_up_page'), url('user/account/bonus'));
            }
        }
        $this->assign('page_title', L('add_bonus'));
        $this->display();
    }

    /**
     *
     */
    public function actionExchange()
    {
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

        $account_type = 'pay_points';

        /* 获取记录条数 */
        $sql = "SELECT COUNT(*) FROM {pre}account_log  WHERE user_id = '$this->user_id'  AND $account_type <> 0 ";
        $record_count = $this->db->getOne($sql);

        //分页函数
        $pager = get_pager(url('user/account/exchange'), [], $record_count, $page);

        //获取剩余余额
        $pay_points = $this->db->getOne("SELECT  pay_points FROM {pre}users WHERE user_id='$this->user_id'");

        if (empty($pay_points)) {
            $pay_points = 0;
        }

        //获取余额记录
        $account_log = [];
        $sql = "SELECT * FROM {pre}account_log  WHERE user_id = '$this->user_id'  AND $account_type <> 0   ORDER BY log_id DESC";
        $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
        foreach ($res as $row) {
            $row['change_time'] = local_date(C('shop.date_format'), $row['change_time']);
            $row['type'] = $row[$account_type] > 0 ? L('account_inc') : L('account_dec');
            $row['user_money'] = price_format(abs($row['user_money']), false);
            $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
            $row['rank_points'] = abs($row['rank_points']);
            $row['pay_points'] = abs($row['pay_points']);
            $row['short_change_desc'] = sub_str($row['change_desc'], 60);
            $row['amount'] = $row[$account_type];
            $account_log[] = $row;
        }
        //模板赋值
        $this->assign('pay_points', $pay_points);
        $this->assign('account_log', $account_log);
        $this->assign('pager', $pager);
        $this->display();
    }

    /**
     * 验证是否登录
     */
    public function actionchecklogin()
    {
        if (!$this->user_id) {
            $url = urlencode(__HOST__ . $_SERVER['REQUEST_URI']);
            if (IS_POST) {
                $url = urlencode($_SERVER['HTTP_REFERER']);
            }
            ecs_header("Location: " . url('user/login/index', ['back_act' => $url]));
            exit;
        }
    }

    /**
     * 会员通过帐目明细列表进行再付款的操作
     */
    public function actionPay()
    {
        //变量初始化
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
        if ($surplus_id == 0) {
            ecs_header("Location: " . url('user/account_log'));
            exit;
        }

        //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
        if ($payment_id == 0) {
            ecs_header("Location: " . url('user/account_deposit', ['id' => $surplus_id]));
            exit;
        }

        //获取单条会员帐目信息
        $order = [];
        $order = get_surplus_info($surplus_id);

        //支付方式的信息
        $payment_info = [];
        $payment_info = payment_info($payment_id);

        /* 如果当前支付方式没有被禁用，进行支付的操作 */
        if (!empty($payment_info)) {
            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);

            //生成伪订单号
            $order['order_sn'] = $surplus_id;

            //获取需要支付的log_id
            $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

            $order['user_name'] = $_SESSION['user_name'];
            $order['surplus_amount'] = $order['amount'];

            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

            //计算此次预付款需要支付的总金额
            $order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];

            //如果支付费用改变了，也要相应的更改pay_log表的order_amount
            $order_amount = $this->db->getOne("SELECT order_amount FROM {pre}pay_log WHERE log_id = '$order[log_id]'");
            $this->db->getOne("SELECT COUNT(*) FROM {pre}order_goods WHERE order_id='$order[order_id]'AND is_real = 1");
            if ($order_amount <> $order['order_amount']) {
                $this->db->query("UPDATE {pre}pay_log SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
            }
            if (!file_exists(ADDONS_PATH . 'payment/' . $payment_info ['pay_code'] . '.php')) {
                unset($payment_info ['pay_code']);
            } else {
                /* 调用相应的支付方式文件 */
                include_once(ADDONS_PATH . 'payment/' . $payment_info ['pay_code'] . '.php');
                /* 取得在线支付方式的支付按钮 */
                $pay_obj = new $payment_info['pay_code']();
                $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
            }
        }
    }

    /**
     * 银行卡列表 后续会废弃
     */
    public function actionCardList()
    {
        if (IS_AJAX) {
            $id = I('id');
            if (empty($id)) {
                exit();
            }
            $this->model->table('user_bank')->where(['id' => $id])->delete();
            exit();
        }
        $card_list = get_card_list($this->user_id);
        $this->assign('card_list', $card_list);
        $this->assign('page_title', L('account_card_list'));
        $this->display();
    }

    /**
     * 添加银行卡 后续会废弃
     */
    public function actionAddCard()
    {
        if (IS_POST) {
            $bank_card = I('bank_card', '');
            $pre = '/^\d*$/';
            if (!preg_match($pre, $bank_card)) {
                show_message("请输入正确的卡号");
            }
            $bank_region = I('bank_region', '');
            $bank_name = I('bank_name', '');
            $bank_user_name = I('bank_user_name', '');
            $user_id = $this->user_id;
            if ($this->user_id < 0) {
                show_message('请重新登录');
            }
            $sql = "INSERT INTO {pre}user_bank (bank_name,bank_region,bank_card,bank_user_name,user_id)
                    value('$bank_name','$bank_region',$bank_card,'$bank_user_name',$user_id)";
            if ($this->db->query($sql)) {
                //               show_message('添加成功','返回列表',url('card_list'),'success');
                show_message(L('account_add_success'), L('account_back_list'), url('card_list'), 'success');
            } else {
                //               show_message('添加失败','继续添加',url('add_card'),'fail');
                show_message(L('account_add_error'), L('account_add_continue'), url('add_card'), 'fail');
            }
        }
        //$this->assign('page_title', '添加银行卡');
        $this->assign('page_title', L('account_add_card'));
        $this->display();
    }

    /***
     * 获取用户拥有的储值卡
     */
    public function actionValueCard()
    {
        if (IS_AJAX) {
            $page = input('page', 1, 'intval');
            $bind_vc = get_user_bind_vc_list($this->user_id, $page, 0, '', 1, $this->size);
            exit(json_encode(['list' => $bind_vc['list'], 'totalPage' => $bind_vc['totalpage']]));
        }
        $this->assign('page_title', L('vc_list'));
        $this->display();
    }

    /***
     * 获取用户拥有的储值卡的使用详情
     */
    public function actionValueCardInfo()
    {
        $vid = input('vid', 0, 'intval');
        $info = value_cart_info($vid);
        if ($info['user_id'] != $this->user_id) {
            ecs_header("Location: " . url('user/account/value_card'));
            exit;
        }
        if (IS_AJAX) {
            $page = input('page', 1, 'intval');
            $value_card_info = value_card_use_info($vid, $page, $this->size);
            exit(json_encode(['list' => $value_card_info['list'], 'totalPage' => $value_card_info['totalpage']]));
        }
        // 是否可充值
        if ($info['is_rec'] == 1) {
            $pay_url = url('user/account/pay_value_card', ['vid' => $vid]);
            $this->assign('pay_url', $pay_url);
        }

        $this->assign('vid', $vid);
        $this->assign('page_title', L('vc_info'));
        $this->display();
    }

    /***
     * 绑定储值卡
     */
    public function actionAddValueCard()
    {
        if (IS_POST) {
            $value_card_sn = trim(I('post.value_card_sn'));
            $password = compile_str(I('post.password'));
            /* 验证码验证 */
            if (gd_version() > 0) {
                if (empty($_POST['captcha'])) {
                    exit(json_encode(['status' => 'n', 'info' => L('invalid_captcha')]));
                }
                $validator = new Verify();
                if (!$validator->check($_POST['captcha'])) {
                    exit(json_encode(['status' => 'n', 'info' => L('invalid_captcha')]));
                }
            }
            $result = add_value_card($this->user_id, $value_card_sn, $password);
            if ($result == 1) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_use_expire')]));
            }
            if ($result == 2) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_is_used')]));
            }
            if ($result == 3) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_is_used_by_other')]));
            }
            if ($result == 4) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_not_exist')]));
            }
            if ($result == 5) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_limit_expire')]));
            }
            if ($result == 0) {
                exit(json_encode(['status' => 'y', 'info' => L('add_value_card_sucess'), 'url' => url('user/account/value_card')]));
            }
        }
        $this->assign('page_title', L('add_vc'));
        $this->display();
    }

    /***
     * 储值卡充值--充值卡
     */
    public function actionPayValueCard()
    {
        $vid = I('vid', 0, 'intval');
        if (empty($vid)) {
            exit(json_encode(['status' => 'y', 'url' => url('user/account/value_card')]));
        }
        if (IS_POST) {
            $pay_card_sn = trim(I('post.pay_card_sn'));
            $password = compile_str(I('post.password'));
            $vid = I('post.vid');
            /* 验证码验证 */
            if (gd_version() > 0) {
                if (empty($_POST['captcha'])) {
                    exit(json_encode(['status' => 'n', 'info' => L('invalid_captcha')]));
                }
                $validator = new Verify();
                if (!$validator->check($_POST['captcha'])) {
                    exit(json_encode(['status' => 'n', 'info' => L('invalid_captcha')]));
                }
            }
            $result = use_pay_card($this->user_id, $vid, $pay_card_sn, $password);
            if ($result == 0) {
                exit(json_encode(['status' => 'y', 'info' => L('use_pay_card_sucess'), 'url' => url('user/account/value_card_info', ['vid' => $vid])]));
            }
            if ($result == 1) {
                exit(json_encode(['status' => 'n', 'info' => L('pc_not_exist')]));
            }
            if ($result == 2) {
                exit(json_encode(['status' => 'n', 'info' => L('pc_is_used')]));
            }
            if ($result == 3) {
                exit(json_encode(['status' => 'n', 'info' => L('vc_use_expire')]));
            }
        }
        $this->assign('vid', $vid);
        $this->assign('page_title', L('pay_vc'));
        $this->display();
    }
}
