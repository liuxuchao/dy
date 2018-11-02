<?php

namespace App\Modules\User\Controllers;

use Think\Image;
use App\Extensions\QRcode;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    public $user_id;

    /**
     * 构造函数
     */
    public function __construct()
    {
        if (strtolower(ACTION_NAME) == 'addcomment') {
            $_SERVER['HTTP_USER_AGENT'] = 'AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1';
        }
        parent::__construct();
        $this->user_id = $_SESSION['user_id'];
        $this->actionchecklogin();
        L(require(LANG_PATH . C('shop.lang') . '/user.php'));
        L(require(LANG_PATH . C('shop.lang') . '/flow.php'));
        $this->assign('lang', array_change_key_case(L()));
        $files = [
            'clips',
            'transaction',
            'main'
        ];
        $this->load_helper($files);
        $this->assign('user_id', $this->user_id);
    }

    /**
     * 会员中心欢迎页
     */
    public function actionIndex()
    {
        $user_id = $this->user_id;
        // 订单信息
        $type = 0;
        $where_pay = ' AND oi.pay_status = ' . PS_UNPAYED . ' AND oi.order_status not in(' . OS_CANCELED . ',' . OS_INVALID . ',' . OS_RETURNED . ')';
        $pay_count = get_order_where_count($user_id, $type, $where_pay);
        $this->assign('pay_count', intval($pay_count));//待付款

        $where_confirmed = " AND oi.pay_status = " . PS_PAYED . " AND oi.order_status in (" . OS_CONFIRMED . ", " . OS_SPLITED . ", " . OS_SPLITING_PART . ") AND (oi.shipping_status >= " . SS_UNSHIPPED . " AND oi.shipping_status <> " . SS_RECEIVED . ")";
        $confirmed_count = get_order_where_count($user_id, $type, $where_confirmed);
        $this->assign('confirmed_count', intval($confirmed_count));//待发货

        $sql = "SELECT a.msg_id  FROM {pre}feedback AS a WHERE a.parent_id IN " .
            " (SELECT b.msg_id FROM {pre}feedback AS b WHERE b.user_id = '" . $_SESSION['user_id'] . "') ORDER BY a.msg_id DESC";
        $msg_ids = $this->db->getOne($sql);
        $this->assign('msg_ids', $msg_ids);

        //获取管理员留言
        $this->assign('admin_count', get_admin_feedback($_SESSION['user_id']));
        // 用户信息
        $this->assign('info', get_user_default($this->user_id)); // 用户信息
         // 用户等级
        $rank = get_rank_info();
        $this->assign('rank', $rank);
        if (empty($rank)) {
            $this->assign('next_rank_name', sprintf(L('next_level'), $rank['next_rank'], $rank['next_rank_name']));
        }
        //获取优惠券的个数count
        $sql = "SELECT count(*)  FROM " . $GLOBALS['ecs']->table('coupons_user') . " WHERE is_use = 0 and user_id = '$user_id'";
        $coupons_num = $GLOBALS['db']->getOne($sql);
        $this->assign("coupons_num", intval($coupons_num));

        $this->assign('msg_list', msg_lists($this->user_id)); //获取未读取消息数量
        $this->assign('goods_num', num_collection_goods($this->user_id)); //收藏数量
        $this->assign('store_num', num_collection_store($this->user_id)); //关注数量
        $this->assign('bonus', my_bonus($this->user_id)); // 红包

        $this->assign('history', historys()); //浏览记录

        $not_evaluated = get_user_order_comment_list($this->user_id, 1, 0);
        $this->assign('not_comment', intval($not_evaluated)); //待评价

        // 获取退货单总数
        $return_count = get_count_return();
        $this->assign('return_count', $return_count);

        //是否显示我的微店
        $this->assign('drp', is_dir(APP_DRP_PATH) ? 1 : 0);

        //是否显示待拼团
        $this->assign('team', is_dir(APP_TEAM_PATH) ? 1 : 0);
        // 拼团中
        if (is_dir(APP_TEAM_PATH)) {
            $team_num = team_ongoing($user_id);
            $this->assign('team_num', intval($team_num));
        }
        //是否显示我的砍价
        $this->assign('bargain', is_dir(APP_BARGAIN_PATH) ? 1 : 0);

        //是否显示我的批发
        $this->assign('purchase', is_dir(APP_PURCHASE_PATH) ? 1 : 0);

        //是否显示供求
        // 必须是商家帐号
        if ($GLOBALS['_CFG']['wholesale_user_rank'] == 0 && !$this->isSeller() || !is_dir(APP_DEMAND_PATH)) {
            $this->assign('demand', 0);
        }else{
            $this->assign('demand',1);
        }

        //获取会员竞拍的全部拍卖
        $type = " AND ga.is_finished= 0 ";
        $all_auction = get_all_auction($this->user_id,$type);

        if(cookie('all_auction')==1){
            $all_auction='';
        }
        $this->assign('auction',$all_auction);

        //是否显示推荐分成
        $share = unserialize($GLOBALS['_CFG']['affiliate']);
        if ($share['on'] == 1) {
            $this->assign('share', '1');
        }
        $this->assign('page_title', L('user'));
        $this->display();
    }

    /**
     * 浏览记录
     */
    public function actionHistory()
    {
        $arr = explode(',', $_COOKIE['ECS']['history_goods']);
        foreach($arr as $key => $val){
            $arry[$key] = explode('_', $val);
        }

        foreach ($arry as $v) {
          $a[] = $v['1'];
        }
        array_multisort($a, SORT_DESC, $arry);

        foreach($arry as $k => $v){
            if($v['0']){
                $goods_list[$k] = dao('goods')->field('goods_id, goods_name, shop_price, goods_thumb')->where(array('goods_id' => $v['0']))->find();
                if($goods_list[$k]['goods_id']){
                    $goods_list[$k]['goods_thumb'] = get_image_path($goods_list[$k]['goods_thumb']);
                    $goods_list[$k]['shop_price'] = price_format($goods_list[$k]['shop_price']);
                    $goods_list[$k]['url'] = url('goods/index/index', ['id' => $goods_list[$k]['goods_id']]);
                }
            }
        }

        $key ='goods_id';
        $goods_list = $this->arrayUnique($goods_list,$key);
        $total = count($goods_list);

        if (IS_AJAX) {
            $page = I('page', 1, 'intval');

            $offset = 10;
            $totalPage = ceil($total / $offset);

            $start = ($page - 1) * $offset;
            $new_goods_list = $this->splitArray($goods_list, $start, $totalPage);

            foreach ($new_goods_list[0] as $k => $v) {
                if (empty($v)) {
                    unset($new_goods_list[0][$k]);
                }
            }
            die(json_encode(['history' => $new_goods_list[0], 'totalPage' => $totalPage]));
        }
        $this->assign('count', $total);
        $this->assign('page_title', L('history'));
        $this->display();
    }


    /**
     *
     * 把数组按指定的个数分隔
     * @param array $array 要分割的数组
     * @param int $groupNum 分的组数
     */
    public function splitArray($array, $start = 0,  $groupNum){
        if(empty($array)) return array();

        //数组的总长度
        $allLength = count($array);

        //个数
        $groupNum = intval($groupNum);

        //开始位置
        // $start = 0;

        //分成的数组中元素的个数
        $enum = 10;//(int)($allLength/$groupNum);

        //结果集
        $result = array();

        if($enum > 0){

            //被分数组中 能整除 分成数组中元素个数 的部分
            $firstLength = $enum * $groupNum;
            $firstArray = array();
            for($i=0; $i<$firstLength; $i++){
                array_push($firstArray, $array[$i]);
                unset($array[$i]);
            }
            for($i=0; $i<$groupNum; $i++){

                //从原数组中的指定开始位置和长度 截取元素放到新的数组中
                $result[] = array_slice($firstArray, $start, $enum);

                //开始位置加上累加元素的个数
                $start += $enum;
            }
            //数组剩余部分分别加到结果集的前几项中
            $secondLength = $allLength - $firstLength;
            for($i=0; $i<$secondLength; $i++){
                array_push($result[$i], $array[$i + $firstLength]);
            }
        }else{
            for($i=0; $i<$allLength; $i++){
                $result[] = array_slice($array, $i, 1);
            }
        }
        return $result;
    }

    /**
     * 清除去除重复值
     */
    public function arrayUnique($arr, $key)
    {
        $tmp_arr = array();
        foreach($arr as $k => $v)
        {
            if(in_array($v[$key], $tmp_arr))
            {
                unset($arr[$k]);
            }
            else {
                $tmp_arr[$k] = $v[$key];
            }
        }
        return $arr;
   }

    /**
     * 清除搜索记录
     */
    public function actionClearHistory()
    {
        $status = input('status');
        if (IS_AJAX && $status == 1) {
            cookie('ECS[history_goods]', null);
            echo json_encode(['y' => 1]);
        }
    }

    /**
     * 验证是否登录
     */
    public function actionchecklogin()
    {
        if ($_SESSION['user_id']){
            // 检测用户是否被删除
            $users = dao('users')->where(['user_id' => $_SESSION['user_id']])->find();
            if (empty($users)) {
                $_SESSION['user_id'] = 0;
                $_SESSION['user_name'] = '';
                $_SESSION['email'] = '';
                $_SESSION['user_rank'] = 0;
                $_SESSION['discount'] = 1.00;
                  $_SESSION['openid'] = '';
                $_SESSION['unionid'] = '';
            }
        }
        if (!$_SESSION['user_id']) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('user/login/index', ['back_act' => urlencode($back_act)]);
        }
    }

    /**
     * 会员账号中心
     */
    public function actionProfile()
    {
        $sql = "SELECT user_name,email, birthday, sex, question, answer, rank_points, pay_points,user_money, user_rank," .
            " msn, qq, office_phone, home_phone, mobile_phone, passwd_question, passwd_answer " .
            "FROM {pre}users WHERE user_id = '$this->user_id'";
        $infos = $this->db->getRow($sql);
        if ($infos['sex'] == 0) {
            $infos['sex'] = L('secrecy');
        }
        if ($infos['sex'] == 1) {
            $infos['sex'] = L('male');
        }
        if ($infos['sex'] == 2) {
            $infos['sex'] = L('female');
        }
        $this->assign('infos', $infos);

        $this->display();
    }

    /**
     * 修改密码
     */
    public function actionEditPassword()
    {
        // 修改密码处理
        if (IS_POST) {
            $old_password = I('post.old_password');
            $new_passwords = I('post.new_password1');
            $new_password = I('post.new_password');
            if (empty($this->user_id)) {
                ecs_header("Location: " . url('user/login/index'));
                exit;
            }
            if ($new_passwords !== $new_password) {
                show_message(L('confirm_password_invalid'), L('back_retry_answer'), url('user/index/edit_password'), 'warning');
            }
            $user_info = $this->users->get_profile_by_id($this->user_id);
            if (!$this->users->check_user($user_info['user_name'], $old_password)) {
                show_message(L('first_password_error'), L('back_retry_answer'), url('user/index/edit_password'), 'warning');
            }
            if (strlen($new_password) < 6) {
                show_message(L('password_shorter'), L('back_retry_answer'), url('user/index/edit_password'), 'warning');
            }
            if ($this->users->edit_user(['username' => $user_info['user_name'], 'old_password' => $old_password, 'password' => $new_password], 0)) {
                $sql = "UPDATE {pre}users SET `ec_salt`='0' WHERE user_id= '" . $this->user_id . "'";
                $this->db->query($sql);
                unset($_SESSION['user_id']);
                unset($_SESSION['user_name']);
                $this->back_act = url('user/index/index');
                show_message(L('edit_profile_success'), L('back_login'), url('user/login/index', ['back_act' => $this->back_act]), 'success');
            }
        }
        $this->assign("page_title", L('edit_password'));
        // 显示修改密码页面
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $this->display();
        } else {
            /* 如果没有传入id则跳回到首页 */
            ecs_header("Location: " . url('user/index/edit_password'));
            exit;
        }
    }


    /**
     * 用户手机信息修改
     */
    public function actionUpdate_mobile()
    {
        //格式化返回数组
        $result = [
            'error' => 0,
            'message' => ''
        ];
        // 是否有接收值
        if (isset($_POST ['mobile_phone'])) {
            $mobile_phone = $_POST ['mobile_phone'];
            if ($mobile_phone == '') {
                $result ['error'] = 1;
                $result ['message'] = '未接收到值';
                die(json_encode($result));
            }
            $sql = "UPDATE {pre}users SET mobile_phone= '$mobile_phone' WHERE user_id='" . $this->user_id . "'";
            $query = $this->db->query($sql);
            if ($query) {
                $result ['error'] = 2;
                $result ['sucess'] = $mobile_phone;
                $result ['message'] = L('edit_sucsess');
                die(json_encode($result));
            }
        }
    }

    /**
     * 待评价
     */
    public function actionCommentList()
    {
        $sign = isset($_REQUEST['sign']) ? intval($_REQUEST['sign']) : 0; // 评论标识
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $size = 10;

        // 剔除未保存晒单图
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('comment_img') . " WHERE user_id='$_SESSION[user_id]' AND comment_id = 0";
        $GLOBALS['db']->query($sql);

        $record_count = get_user_order_comment_list($_SESSION['user_id'], 1, $sign);

        $pager = get_pager('user.php', [
            'act' => $action
        ], $record_count, $page, $size);
        $comment_list = get_user_order_comment_list($_SESSION['user_id'], 0, $sign, 0, $size, $pager['start']);

        $this->assign('comment_list', $comment_list);
        $this->assign('pager', $pager);
        $this->assign('sign', $sign);
        $this->assign('sessid', SESS_ID);
        $this->assign('page_title', L('commentList'));
        $this->display();
    }

    /**
     * 评价功能
     */
    public function actionAddComment()
    {
        if (IS_POST) {
            $user_id = $_SESSION['user_id'];
            $comment_id = I('comment_id', 0, 'intval');
            $rank = I('comment_rank', 5, 'intval');
            $rank_server = 5;
            $rank_delivery = 5;
            $content = I('content');
            $order_id = I('order_id', 0, 'intval');
            $goods_id = I('goods_id', 0, 'intval');
            $goods_tag = I('impression');
            $sign = I('sign', 0, 'intval');
            $rec_id = I('rec_id', 0, 'intval');
            $addtime = gmtime();
            $ip = real_ip();

            $desc_rank = I('desc_rank');
            $service_rank = I('service_rank');
            $delivery_rank = I('delivery_rank');
            $sender_rank = I('sender_rank');

            if (empty($content)) {
                show_message('评论内容不可为空', '返回', '', 'warning');
            }

            // 是否存在评论
            $condition = [
                'order_id' => $order_id,
                'rec_id' => $rec_id,
                'id_value' => $goods_id,
                'user_id' => $_SESSION['user_id']
            ];
            $count = $this->model->table('comment')->where($condition)->count();
            if ($count > 0) {
                show_message('已经评价过了', '', url('user/index/index'));
            }

            // 查询商品商家
            $condition = [
                'goods_id' => $goods_id
            ];
            $ru_id = $this->model->table('goods')->where($condition)->getField('user_id');

            if (is_null($ru_id)) {
                show_message('缺少商家参数', '返回', '', 'warning');
            }

            // 添加评论
            $data = [
                'comment_type' => 0,
                'id_value' => $goods_id,
                'email' => $_SESSION['email'],
                'user_name' => $_SESSION['user_name'],
                'content' => $content,
                'comment_rank' => $rank,
                'comment_server' => $rank_server,
                'comment_delivery' => $rank_delivery,
                'add_time' => $addtime,
                'ip_address' => $ip,
                'status' => (1 - C('shop.comment_check')),
                'parent_id' => 0,
                'user_id' => $user_id,
                'single_id' => 0,
                'order_id' => $order_id,
                'rec_id' => $rec_id,
                'goods_tag' => $goods_tag,
                'ru_id' => $ru_id
            ];

            $comment_id = dao('comment')->add($data);
            if ($comment_id > 0) {
                $sql = "UPDATE {pre}comment_img SET comment_id = '$comment_id' WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id' AND comment_id = 0 and order_id='$order_id' and rec_id='$rec_id'";
                $this->db->query($sql);
            }
            if($ru_id>0){
                //用户此订单对商家提交满意度
           $sql = "INSERT INTO {pre}comment_seller (user_id, ru_id, order_id, desc_rank, service_rank, delivery_rank, sender_rank, add_time )VALUES('$user_id', '$ru_id', '$order_id', ' $desc_rank', '$service_rank', '$delivery_rank', '$sender_rank', '$addtime')";
                $result = $this->db->query($sql);
                if($result){//插入店铺评分
                    $store_score = sprintf("%.2f", ($desc_rank + $service_rank + $delivery_rank) / 3);
                    $sql = "UPDATE {pre}merchants_shop_information SET store_score = '" . $store_score . "' WHERE user_id = $ru_id";
                    $res = $this->db->query($sql);
                }
            }
            show_message('商品评论成功', '返回上一页', url('user/index/comment_list'), 'success');
        }
        $rec_id = I('rec_id', 0, 'intval');

        $sql = "SELECT g.*, og.* FROM {pre}order_goods og LEFT JOIN {pre}goods g on og.goods_id = g.goods_id WHERE og.rec_id='{$rec_id}'";
        $goods_info = $this->db->getRow($sql);

        if (empty($goods_info)) {
            show_message('评论商品数据不完整', '返回', '', 'warning');
        }
        $goods_info['shop_price'] = price_format($goods_info['shop_price']);
        $goods_info['goods_thumb'] = get_image_path($goods_info['goods_thumb']);
        $goods_info['goods_img'] = get_image_path($goods_info['goods_img']);
        $goods_info['original_img'] = get_image_path($goods_info['original_img']);
        $sql = "SELECT id,comment_img FROM {pre}comment_img where rec_id = '$rec_id'";
        $img_list = $this->db->getAll($sql);
        $img = [];
        foreach ($img_list as $key => $val) {
            $img[$key]['img_id'] = $val['id'];
            $img[$key]['pic'] = get_image_path($val['comment_img']);
        }

        $sql = "select sid from {pre}comment_seller WHERE order_id =".$goods_info['order_id'];
        $sid = $this->db->getOne($sql);
        $sql = "select rz_shopName from {pre}merchants_shop_information WHERE  user_id=".$goods_info[ru_id];
        $shop_name = $this->db->getOne($sql);
        $this->assign('shop_name', $shop_name);
        $this->assign('sid', $sid);
        $this->assign('img', $img);
        $this->assign('order_id', $goods_info['order_id']);
        $this->assign('rec_id', $rec_id);
        $this->assign('goods_id', $goods_info['goods_id']);
        $this->assign('goods_info', $goods_info);
        $this->assign('page_title', '商品评论');
        $this->display();
    }


    /**
     * 评论上传图片
     */
    public function actionAddCommentImg()
    {
        $img = $_FILES['myfile']['tmp_name'];
        list($width, $height, $type) = getimagesize($img);
        if (empty($img)) {
            return;
        }
        $user_id = $_SESSION['user_id'];
        $goods_id = I('goods_id');
        $order_id = I('order_id');
        $rec_id = I('rec_id');
        $add_time = gmtime();
        //判断文件类型
        if (empty($type)) {
            exit(json_encode(['error' => 1, 'content' => '图片类型不正确']));
        }
        $img_num = dao('comment_img')->field('id')->where(['user_id' => $user_id, 'order_id' => $order_id, 'goods_id' => $goods_id])->select();
        if(count($img_num) > 4){
            exit(json_encode(['error' => 1, 'content' => '图片上传过多']));
        }
        //上传图片并 获得路径
        $result = $this->upload('data/cmt_img', false, 20, [C('shop.thumb_width'), C('shop.thumb_height')]);
        if ($result['error'] > 0) {
            exit(json_encode(['error' => 1, 'content' => $result['message']]));
        }
        $path = $result['url']['myfile']['url'];
        $sql = "INSERT INTO {pre}comment_img (goods_id,user_id,comment_img,img_thumb,order_id,comment_id,rec_id)values(" . $goods_id . "," . $user_id . ",'" . $path . "','" . $path . "'," . $order_id . ",0," . $rec_id . ")";
        $GLOBALS['db']->query($sql);

        $sql = "SELECT id, comment_img FROM {pre}comment_img WHERE order_id = " . $order_id . " and user_id = " . $user_id . " and goods_id = " . $goods_id;
        $res = $GLOBALS['db']->query($sql);
        $img = [];
        foreach ($res as $key => $val) {
            $img[$key]['img_id'] = $val['id'];
            $img[$key]['pic'] = get_image_path($val['comment_img']);
        }
        exit(json_encode($img));
    }

    /**
     * 删除评论上传的图片
     */
    public function actionClearCommentImg()
    {
        $id = I('id', 0, 'intval');
        $goods_id = I('goods_id', 0, 'intval');
        $sql = "select comment_img from {pre}comment_img where id=" . $id;
        $result = ['error' => 0, 'content' => ''];
        $img_list = $GLOBALS['db']->getAll($sql);
        foreach ($img_list as $key => $row) {
            get_oss_del_file([$row['comment_img']]);
            @unlink(get_image_path($row['comment_img']));
        }
        $sql = "delete from {pre}comment_img where user_id = '" . $_SESSION['user_id'] . "' and goods_id = '$goods_id'" . " and id=" . $id;
        $GLOBALS['db']->query($sql);
        exit(json_encode($result));
    }

    /**
     * 邮箱修改
     */
    public function actionUpdate_email()
    {
        //格式化返回数组
        $result = [
            'error' => 0,
            'message' => ''
        ];
        // 是否有接收值
        if (isset($_POST ['email'])) {
            $email = $_POST ['email'];
            if ($email == '') {
                $result ['error'] = 1;
                $result ['message'] = '未接收到值';
                die(json_encode($result));
            }
            $sql = "UPDATE {pre}users SET email= '$email' WHERE user_id='" . $this->user_id . "'";
            $query = $this->db->query($sql);
            if ($query) {
                $result ['error'] = 2;
                $result ['sucess'] = $mobile_phone;
                $result ['message'] = L('edit_sucsess');
                die(json_encode($result));
            }
        }
    }

    /**
     * 性别修改
     */
    public function actionUpdate_sex()
    {
        //格式化返回数组
        $result = [
            'error' => 0,
            'message' => ''
        ];
        // 是否有接收值
        if (isset($_POST ['sex'])) {
            $sex = $_POST ['sex'];
            if (sex == '') {
                $result ['error'] = 1;
                $result ['message'] = '未接收到值';
                die(json_encode($result));
            }
            $sql = "UPDATE {pre}users SET sex= '$sex' WHERE user_id='" . $this->user_id . "'";
            $query = $this->db->query($sql);
            if ($query) {
                $result ['error'] = 2;
                $result ['message'] = L('edit_sucsess ');
                die(json_encode($result));
            }
        }
    }

    /**
     * 收货地址列表
     */
    public function actionAddressList()
    {
        $user_id = $this->user_id;
        /* 获得用户所有的收货人信息 */
        if ($_SESSION['user_id'] > 0) {
            $consignee_list = get_consignee_list($_SESSION['user_id']);
        } else {
            if (isset($_SESSION['flow_consignee'])) {
                $consignee_list = [$_SESSION['flow_consignee']];
            } else {
                $consignee_list[] = ['country' => C('shop.shop_country')];
            }
        }
        $this->assign('name_of_region', [C('shop.name_of_region_1'), C('shop.name_of_region_2'), C('shop.name_of_region_3'), C('shop.name_of_region_4')]);
        if ($consignee_list) {
            foreach ($consignee_list as $k => $v) {
                $address = '';
                if ($v['province']) {
                    $res = get_region_name($v['province']);
                    $address .= $res['region_name'];
                }
                if ($v['city']) {
                    $ress = get_region_name($v['city']);
                    $address .= $ress['region_name'];
                }
                if ($v['district']) {
                    $resss = get_region_name($v['district']);
                    $address .= $resss['region_name'];
                }
                if ($v['street']) {
                    $resss = get_region_name($v['street']);
                    $address .= $resss['region_name'];
                }
                $consignee_list[$k]['address'] = $address . ' ' . $v['address'];
            }
        }
        /* 取得每个收货地址的省市区列表 */
        $province_list = [];
        $city_list = [];
        $district_list = [];
        foreach ($consignee_list as $region_id => $consignee) {
            $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
            $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
            $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

            $province_list[$region_id] = get_regions(1, $consignee['country']);
            $city_list[$region_id] = get_regions(2, $consignee['province']);
            $district_list[$region_id] = get_regions(3, $consignee['city']);
        }
        $address_id = $this->db->getOne("SELECT address_id FROM {pre}users WHERE user_id='$user_id'");

        $this->assign('address_id', $address_id);
        foreach ($consignee_list as $k => $v) {
            if ($v['address_id'] == $address_id) {
                $c[] = $v;
                unset($consignee_list[$k]);
            }
        }
        if (is_array($consignee_list) && is_array($c)) {
            $consignee_list = array_merge($c, $consignee_list);
        }
        $this->assign('consignee_list', $consignee_list);
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        $this->assign('page_title', '收货地址');
        $this->display();
    }

    /**
     * 添加收货地址
     */
    public function actionAddAddress()
    {
        if (IS_POST) {
            $consignee = [
                'address_id' => I('address_id'),
                'consignee' => I('consignee'),
                'country' => 1,
                'province' => I('province_region_id'),
                'city' => I('city_region_id'),
                'district' => I('district_region_id'),
                'street' => I('town_region_id'),
                'email' => I('email'),
                'address' => I('address'),
                'zipcode' => I('zipcode'),
                'tel' => I('tel'),
                'mobile' => I('mobile'),
                'sign_building' => I('sign_building'),
                'best_time' => I('best_time'),
                'user_id' => $_SESSION['user_id']
            ];
            if (empty($consignee['consignee'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_receiving_notnull')]));
            }
            if (is_mobile($consignee['mobile']) == false) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_mobile_format_error')]));
            }
            if (empty($consignee['province'])) {
                exit(json_encode(['status' => 'n', 'info' => L('请选择地区')]));
            }
            if (empty($consignee['address'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_address_notnull')]));
            }
            // 收货地址数量限制
            $limit_address = $this->db->getOne("select count(address_id) from {pre}user_address where user_id = '" . $consignee['user_id'] . "'");
            if ($limit_address > 10) {
                exit(json_encode(['status' => 'n', 'info' => sprintf(L('msg_save_address'), 10)]));
            }
            if ($_SESSION['user_id'] > 0) {
                /* 如果用户已经登录，则保存收货人信息 */
                save_consignee($consignee, ture);
            }
            /* 保存到session */
            $_SESSION['flow_consignee'] = stripslashes_deep($consignee);
            $back_act = url('user/index/address_list');
            if (isset($_SESSION['flow_consignee']) && empty($consignee['address_id'])) {
                exit(json_encode(['status' => 'y', 'info' => L('success_address'), 'url' => $back_act]));
            } elseif (isset($_SESSION['flow_consignee']) && !empty($consignee['address_id'])) {
                exit(json_encode(['status' => 'y', 'info' => L('edit_address'), 'url' => $back_act]));
            } else {
                exit(json_encode(['status' => 'n', 'info' => L('error_address')]));
            }
        }

        $this->assign('user_id', $_SESSION['user_id']);
        $this->assign('country_list', get_regions());
        $this->assign('shop_country', C('shop.shop_country'));
        $this->assign('shop_province_list', get_regions(1, C('shop.shop_country')));
        $this->assign('address_id', I('address_id'));
        $province_list = get_regions(1, C('shop.shop_country'));
        $this->assign('province_list', $province_list); //省、直辖市
        $city_list = get_region_city_county($this->province_id);
        if ($city_list) {
            foreach ($city_list as $k => $v) {
                $city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
            }
        }
        $this->assign('city_list', $city_list); //省下级市
        $district_list = get_region_city_county($this->city_id);
        $this->assign('district_list', $district_list); //市下级县
        $address_id = I('request.address_id', 0 , 'intval');
        if ($address_id) {
            $consignee_list = $this->db->getRow("SELECT * FROM {pre}user_address WHERE user_id = '".$_SESSION['user_id']."' AND address_id = '".$address_id."' ");
            if (empty($consignee_list)) {
                show_message(L('no_address'), '', '', 'error');
            }
            $province = get_region_name($consignee_list['province']);
            $city = get_region_name($consignee_list['city']);
            $district = get_region_name($consignee_list['district']);
            $town = get_region_name($consignee_list['street']);

            $consignee_list['province'] = $province['region_name'];
            $consignee_list['city'] = $city['region_name'];
            $consignee_list['district'] = $district['region_name'];
            $consignee_list['town'] = $town['region_name'];

            $consignee_list['province_id'] = $province['region_id'];
            $consignee_list['city_id'] = $city['region_id'];
            $consignee_list['district_id'] = $district['region_id'];
            $consignee_list['town_region_id'] = $town['region_id'];

            $city_list = get_region_city_county($province['region_id']);

            if ($city_list) {
                foreach ($city_list as $k => $v) {
                    $city_list[$k]['district_list'] = get_region_city_county($v['region_id']);
                }
            }
            $this->assign('city_list', $city_list); //省下级市
            $this->assign('consignee_list', $consignee_list);
            $this->assign('page_title', '修改收货地址');
            $this->display();
        } else {
            $this->assign('page_title', '添加收货地址');
            $this->display();
        }
    }

    /*
   * AJAX显示地区名称
   */
    public function actionShowRegionName()
    {
        $error['province'] = get_region_name(I('province'));
        $error['city'] = get_region_name(I('city'));
        $error['district'] = get_region_name(I('district'));
        die(json_encode($error));
    }

    /**
     * 删除收货地址
     */
    public function actionDrop()
    {
        $id = I('address_id', 0, 'intval');
        if (drop_consignee($id)) {
            ecs_header("Location: " . url('user/index/address_list'));
            exit;
        } else {
            show_message(L('del_address_false'));
        }
    }

    //异步设置默认地址
    public function actionAjaxMakeAddress()
    {
        $user_id = $this->user_id;
        $address_id = input('address_id', 0, 'intval');
        if (!empty($address_id)) {
            $sql = "UPDATE {pre}users SET address_id = '" . $address_id . "' WHERE user_id = '" . $user_id . "' ";
            $this->db->query($sql);
        }
        $res['address_id'] = $address_id;
        exit(json_encode($res));
    }

    /**
     * 收藏列表-
     */
    public function actionCollectionList()
    {
        if (IS_AJAX) {
            $user_id = $this->user_id;
            $page = I('page', '1', 'intval');
            $offset = 10;
            $sql = "SELECT count(rec_id) as max FROM {pre}collect_goods WHERE user_id=$user_id ";
            $count = $this->db->getOne($sql);
            $page_size = ceil($count / $offset);
            $limit = ' LIMIT ' . ($page - 1) * $offset . ',' . $offset;
            $collection_goods = get_collection_goods($user_id, $offset, $page);
           
            $show = $count > 0 ? 1 : 0;
            die(json_encode(['goods_list' => $collection_goods['goods_list'], 'show' => $show, 'totalPage' => $page_size]));
        }
        $this->assign('paper', $collection_goods['paper']);
        $this->assign('record_count', $collection_goods['record_count']);
        $this->assign('size', $collection_goods['size']);

        $this->assign('page_title', '我的收藏');
        $this->display();
    }

    /**
     * 添加删除收藏
     */
    public function actionAddCollection()
    {
        $result = [
            'error' => 0,
            'message' => ''
        ];
        $goods_id = intval($_GET['id']);

        if (!isset($this->user_id) || $this->user_id == 0) {
            $result['error'] = 2;
            $result['message'] = L('login_please');
            die(json_encode($result));
        } else {
            // 检查是否已经存在于用户的收藏夹
            $where['user_id'] = $this->user_id;
            $where['goods_id'] = $goods_id;
            $rs = $this->db->table('collect_goods')
                ->where($where)
                ->count();
            if ($rs > 0) {
                $this->db->table('collect_goods')
                    ->where($where)
                    ->delete();
                $result['error'] = 0;
                $result['message'] = L('collect_success');
                die(json_encode($result));
            } else {
                $data['user_id'] = $this->user_id;
                $data['goods_id'] = $goods_id;
                $data['add_time'] = gmtime();
                if ($this->db->table('collect_goods')
                        ->data($data)
                        ->add() === false
                ) {
                    $result['error'] = 1;
                    $result['message'] = M()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            }
        }
    }

    //
    /* 删除收藏的商品 */
    public function actionDelCollection()
    {
        $user_id = $this->user_id;
        $collection_id = I('rec_id');
        $sql = "SELECT count(*) FROM {pre}collect_goods WHERE rec_id='$collection_id' AND user_id ='$user_id'";
        if ($this->db->getOne($sql) > 0) {
            $this->db->query("DELETE FROM {pre}collect_goods WHERE rec_id='$collection_id' AND user_id ='$user_id'");
            ecs_header("Location: " . url('user/index/collectionlist'));
            exit;
        }
    }

    /**
     * 帮助中心
     */
    public function actionHelp()
    {
        // 获取帮助中心类别
        $sql = "SELECT cat_id, cat_name FROM " . $GLOBALS['ecs']->table('article_cat') .
            " WHERE parent_id = 3  order by sort_order asc, cat_id asc";
        $articles = $this->db->query($sql);

        foreach ($articles as $key => $value) {
            $sql = "SELECT article_id, title FROM " . $GLOBALS['ecs']->table('article') .
                ' WHERE is_open = 1 and '. get_article_children($value['cat_id']) .' order by add_time desc ';
            $articles[$key]['list'] = $this->db->query($sql);
        }

        $this->assign('articles', $articles);
        $this->assign('page_title', '帮助手册');
        $this->display();
    }

    //显示留言列表
    public function actionMessageList()
    {
        /** 判断客服文件是否存在  存在则跳转到客服聊天列表 */
        if (is_dir(dirname(ROOT_PATH) . '/kefu/')) {
            $this->redirect('chat/index/chatlist');
        }

        $sql = "SELECT msg_id,msg_time  FROM {pre}feedback AS a WHERE a.parent_id IN " .
            " (SELECT msg_id FROM {pre}feedback AS b WHERE b.user_id = '" . $_SESSION['user_id'] . "') ORDER BY a.msg_id DESC LIMIT 1";
        $msg_ids = $this->db->getRow($sql);
        S('message_' . $_SESSION['user_id'], $msg_ids['msg_id']);
        $user_id = $this->user_id;
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

        $info = get_user_default($user_id);
        $order_info = [];
        /* 获取用户留言的数量 */
        if ($order_id) {
            $sql = "SELECT COUNT(*) FROM {pre}feedback
                     WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
            $order_info = $this->db->getRow("SELECT * FROM {pre}order_info  WHERE order_id = '$order_id' AND user_id = '$user_id'");
            $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
        } else {
            $sql = "SELECT COUNT(*) FROM {pre}feedback
                     WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
        }

        $record_count = $this->db->getOne($sql);

        $act = ['act' => $action];

        if ($order_id != '') {
            $act['order_id'] = $order_id;
        }

        $pager = get_pager('user.php', $act, $record_count, $page, 5);
        $this->assign('info', $info);
        $message_list = get_message_list($user_id, $_SESSION['user_name'], $pager['size'], $pager['start'], $order_id);
        ksort($message_list);
        $this->assign('message_list', $message_list);
        $this->assign('pager', $pager);
        $this->assign('order_info', $order_info);
        $this->assign('page_title', '客户服务');
        $this->display();
    }

    /* 添加我的留言 */

    public function actionAddMessage()
    {
        if (IS_POST) {
            $message = [
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'user_email' => $_SESSION['email'],
                'msg_type' => isset($_POST['msg_type']) ? intval($_POST['msg_type']) : 0,
                'msg_title' => isset($_POST['msg_title']) ? trim($_POST['msg_title']) : '',
                'msg_time' => gmtime(),
                'msg_content' => isset($_POST['msg_title']) ? trim($_POST['msg_title']) : '',
                'order_id' => empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
                'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none') ? $_FILES['message_img'] : []
            ];
            if (empty($_POST['msg_title'])) {
                show_message("请输入点内容吧");
            }
            if (addmg($message)) {
                ecs_header("Location: " . url('user/index/messagelist'));
                exit;
            }
        }
    }

    /* 关注店铺列表 */
    public function actionStoreList()
    {
        if (IS_AJAX) {
            $page = I('page', '1', 'intval');
            $offset = 5;
            $sql = "SELECT count(rec_id) as max FROM {pre}collect_store WHERE user_id=" . $this->user_id;
            $count = $this->db->getOne($sql);
            $page_size = ceil($count / $offset);
            $limit = ' LIMIT ' . ($page - 1) * $offset . ',' . $offset;
            $res = get_collection_store_list($this->user_id, $count, $limit);
            $show = $count > 0 ? 1 : 0;
            die(json_encode(['store_list' => $res['store_list'], 'show' => $show, 'totalPage' => $page_size]));
        }
        $this->assign('page_title', '我的关注');
        $this->display();
    }

    //取消关注
    public function actionDelStore()
    {
        $user_id = $this->user_id;
        $collection_id = I('rec_id');
        if (I('rec_id') > 0) {
            $this->db->query("DELETE FROM {pre}collect_store WHERE rec_id='$collection_id' AND user_id ='$user_id'");
            ecs_header("Location: " . url('user/index/storelist'));
            exit;
        }
    }

    /**
     * 登记列表
     */
    public function actionBookingList()
    {
        if (IS_POST) {
            $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            /* 获取缺货登记的数量 */
            $sql = "SELECT COUNT(*) " . "FROM " . $GLOBALS['ecs']->table('booking_goods') . " AS bg, " . $GLOBALS['ecs']->table('goods') . " AS g " . "WHERE bg.goods_id = g.goods_id AND bg.user_id = '$this->user_id'";
            $record_count = $GLOBALS['db']->getOne($sql);
            $pager = get_pager('user.php', [
                'act' => $action
            ], $record_count, $page);

            $booking_list = get_booking_list($this->user_id, $pager['size'], $pager['start']);
            exit(json_encode(['list' => $booking_list, 'totalPage' => ceil($record_count / $pager['size'])]));
        }
        $this->assign('page_title', '缺货登记');
        $this->display();
    }

    /**
     * 缺货登记
     */
    public function actionAddBooking()
    {
        if (IS_POST) {
            $booking = [
                'goods_id' => isset($_POST['id']) ? intval($_POST['id']) : 0,
                'goods_amount' => isset($_POST['number']) ? intval($_POST['number']) : 0,
                'desc' => isset($_POST['desc']) ? trim($_POST['desc']) : '',
                'linkman' => isset($_POST['linkman']) ? trim($_POST['linkman']) : '',
                'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
                'tel' => isset($_POST['tel']) ? trim($_POST['tel']) : '',
                'booking_id' => isset($_POST['rec_id']) ? intval($_POST['rec_id']) : 0
            ];

            // 查看此商品是否已经登记过
            $rec_id = get_booking_rec($this->user_id, $booking['goods_id']);
            if ($rec_id > 0) {
                show_message('商品已经登记过啦', '返回上一页', '', 'error');
            }

            if (add_booking($booking)) {
                show_message('添加缺货登记成功', '返回登记列表', url('booking_list'), 'info');
            } else {
                $GLOBALS['err']->show('返回登记列表', url('booking_list'));
            }
            return;
        }
        $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($goods_id == 0) {
            show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
        }

        /* 根据规格属性获取货品规格信息 */
        $goods_attr = '';
        if ($_GET['spec'] != '') {
            $goods_attr_id = $_GET['spec'];

            $attr_list = [];
            $sql = "SELECT a.attr_name, g.attr_value " . "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " . $GLOBALS['ecs']->table('attribute') . " AS a " . "WHERE g.attr_id = a.attr_id " . "AND g.goods_attr_id " . db_create_in($goods_attr_id);
            $res = $GLOBALS['db']->query($sql);
            foreach ($res as $row) {
                $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
            }
            $goods_attr = join(chr(13) . chr(10), $attr_list);
        }
        $this->assign('goods_attr', $goods_attr);

        $this->assign('info', get_goodsinfo($goods_id));
        $this->assign('page_title', '缺货登记');
        $this->display();
    }

    /* 删除缺货登记 */
    public function actionDelBooking()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id == 0 || $this->user_id == 0) {
            exit(json_encode(['status' => 0]));
        }

        $result = delete_booking($id, $this->user_id);
        if ($result) {
            exit(json_encode(['status' => 1]));
        }
    }

    //我的推荐
    public function actionAffiliate()
    {
        $share = unserialize($GLOBALS['_CFG']['affiliate']);
        if ($share['on'] == 0) {
            $this->redirect('user/index/index');
        }
        $goodsid = I('request.goodsid', 0);
        if (empty($goodsid)) {
            $page = I('post.page', 1, 'intval');
            $size = 8;
            empty($share) && $share = [];
            // 推荐注册分成
            $affdb = [];
            $num = count($share['item']);
            $up_uid = "'$this->user_id'";
            $all_uid = "'$this->user_id'";
            for ($i = 1; $i <= $num; $i++) {
                $count = 0;
                if ($up_uid) {
                    $sql = "SELECT user_id FROM {pre}users WHERE parent_id IN($up_uid)";
                    $rs = $GLOBALS['db']->query($sql);
                    empty($rs) && $rs = [];
                    $up_uid = '';
                    foreach ($rs as $k => $v) {
                        $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                        if ($i < $num) {
                            $all_uid .= ", '$v[user_id]'";
                        }
                        $count++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
                $this->assign('affdb', $affdb);
            }
            if (IS_AJAX) {
                $sqladd = '';
                $sqladd .= " AND (select count(*) from " . $GLOBALS['ecs']->table('order_info') . " as oi2 where oi2.main_order_id = o.order_id) = 0 ";
                $sqladd .= " AND (SELECT og.ru_id FROM " . $GLOBALS['ecs']->table('order_goods') . " AS og WHERE og.order_id = o.order_id LIMIT 1) = 0"; //只显示平台分成订单

                // 推荐注册分成
                if ($share['config']['separate_by'] == 0) {
                    if (is_dir(APP_DRP_PATH)) {
                        $sqlcount = "SELECT count(*) as count FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND u.drp_parent_id = 0 AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd";
                        $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND u.drp_parent_id = 0 AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd" . " ORDER BY order_id DESC";
                    } else {
                        $sqlcount = "SELECT count(*) as count FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd";
                        $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd" . " ORDER BY order_id DESC";
                    }
                } else {
                    // 推荐订单分成
                    $sqlcount = "SELECT count(*) as count FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}APP_DRP_PATHiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd";
                    $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM {pre}order_info o" . " LEFT JOIN {pre}users u ON o.user_id = u.user_id" . " LEFT JOIN {pre}affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0) $sqladd" . " ORDER BY order_id DESC";
                }
                $res = $this->model->query($sqlcount);
                $count = $res[0]['count'];
                $max_page = ($count > 0) ? ceil($count / $size) : 1;
                if ($page > $max_page) {
                    $page = $max_page;
                }
                $limit = (($page - 1) * $size) . "," . $size;
                $sql = $sql . ' LIMIT ' . $limit;
                $rt = $this->model->query($sql);
                if ($rt) {
                    foreach ($rt as $k => $v) {
                        if (!empty($v['suid'])) {
                            // 在affiliate_log有记录
                            if ($v['separate_type'] == -1 || $v['separate_type'] == -2) {
                                // 已被撤销
                                $rt[$k]['is_separate'] = 3;
                            }
                        }
                        $rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], -2, 2);
                        $rt[$k]['affiliate_type'] = $share['config']['separate_by'];
                    }
                } else {
                    $rt = [];
                }
                die(json_encode(['logdb' => $rt, 'totalPage' => ceil($count / $size)]));
            }
        } else {
            // 单个商品推荐
            $this->assign('userid', $this->user_id);
            $this->assign('goodsid', $goodsid);

            $types = [
                1,
                2,
                3,
                4,
                5
            ];
            $this->assign('types', $types);

            $goods = get_goods_info($goodsid);
            $goods['goods_img'] = get_image_path($goods['goods_img']);
            $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            $goods['shop_price'] = price_format($goods['shop_price']);

            $this->assign('goods', $goods);
        }
        $type = $share['config']['expire_unit'];
        switch ($type) {
            case 'hour':
                $this->assign('expire_unit', '小时');    //时效单位
                break;
            case 'day':
                $this->assign('expire_unit', '天');    //时效单位
                break;
            case 'week':
                $this->assign('expire_unit', '周');    //时效单位
                break;
        }
        if ($share['config']['separate_by'] == 0) {
            $this->assign('separate_by', $share['config']['separate_by']);                                      //分成模式
            $this->assign('expire', $share['config']['expire']);                                           //分成时效
            $this->assign('level_register_all', $share['config']['level_register_all']);                           //注册送的积分
            $this->assign('level_register_up', $share['config']['level_register_up']);                           //注册送的积分上限
            $this->assign('level_money_all', $share['config']['level_money_all']);                                  //金额比例
            $this->assign('level_point_all', $share['config']['level_point_all']);                                  //积分比例
        }
        if ($share['config']['separate_by'] == 1) {
            $this->assign('separate_by', $share['config']['separate_by']);                                      //分成模式
            $this->assign('expire', $share['config']['expire']);                                           //分成时效
            $this->assign('level_money_all', $share['config']['level_money_all']);                                  //金额比例
            $this->assign('level_point_all', $share['config']['level_point_all']);                                  //积分比例
        }

        //二维码内容
        $url = url('/', '', true, true) . '?u=' . $this->user_id;
        // 纠错级别：L、M、Q、H
        $errorCorrectionLevel = 'M';
        // 点的大小：1到10
        $matrixPointSize = 8;
        $file = dirname(ROOT_PATH) . '/data/attached/qrcode/';
        if (!file_exists($file)) {
            make_dir($file, 0777);
        }
        $qrcode_bg = ROOT_PATH . 'public/img/affiliate.jpg';//背景
        $filename = $file . 'user_share_' . $this->user_id . $errorCorrectionLevel . $matrixPointSize . '.png';//二维码
        $share_img = $file . 'user_share_' . $this->user_id . '_bg.png'; // 输出图片
        if (!file_exists($share_img)) {
            $code = QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
            $qrcode_logo = $filename;
            $img = new Image();
            $bg_width = $img->open($qrcode_bg)->width(); // 背景图宽 640
            $bg_height = $img->open($qrcode_bg)->height(); // 背景图高 1005
            $logo_width = $img->open($qrcode_logo)->width(); // logo图宽 296
            $img->open($qrcode_bg)->water($qrcode_logo, [($bg_width - $logo_width) / 2, $bg_height / 2], 100)->save($share_img);

            // 同步OSS数据
            if (C('shop.open_oss') == 1) {
                $image_name = $this->ossMirror($share_img, 'data/attached/qrcode/');
            }
        }
        $image_name = 'data/attached/qrcode/' . basename($share_img);
        $qrcode_url = get_image_path($image_name);

        // 微信JSSDK分享
        if (is_dir(APP_WECHAT_PATH) && is_wechat_browser()) {
            $share_img = get_wechat_image_path('data/attached/qrcode/' . basename($share_img));
            $share_data = [
                'title' => '我的推荐',
                'desc' => '推荐注册有好礼，马上加入我们_' . C('shop.shop_name'),
                'link' => $url,
                'img' => $share_img,
            ];
            $this->assign('share_data', $this->get_wechat_share_content($share_data));
        }

        $this->assign('ewm', $qrcode_url);
        $this->assign('domain', __HOST__);
        $this->assign('shopdesc', C('shop.shop_desc'));
        $this->assign('share', $share);
        $this->assign('page_title', '我的推荐');
        $this->display();
    }

    /**
     * 生成推荐二维码
     */
    public function actionCreateQrcode()
    {
        $url = I('get.value');
        if ($url) {
            // 二维码
            // 纠错级别：L、M、Q、H
            $errorCorrectionLevel = 'L';
            // 点的大小：1到10
            $matrixPointSize = 8;
            QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
        }
    }

    /**
     * 商品举报列表
     */
    public function actionReportList()
    {
        //获取恶意举报冻结结束时间
        $sql = "SELECT report_time FROM {pre}users WHERE user_id = '$_SESSION[user_id]'";
        $report_time = $this->db->getOne($sql);
        $report_time = local_date('Y-m-d H:i:s', $report_time);
        if (IS_AJAX) {
            $size = 3;
            $page = I('page', 1, 'intval');
            $goods_report = get_goods_report_list($size, $page);
            exit(json_encode(['order_list' => $goods_report['list'], 'totalPage' => $goods_report['totalPage']]));
        }
        $this->assign('page_title', '商品举报列表');
        $this->assign('report_time', $report_time);
        $this->display();
    }

    /**
     * 服务中心
     */
    public function actionService()
    {
        $this->assign('page_title', '服务中心');
        $this->assign('report_time', $report_time);
        $this->display();
    }

    /**
     * 商品举报
     */
    public function actionGoodsReport()
    {
        $report_id = I('report_id', 0, 'intval');
        $goods_id = I('id', 0, 'intval');
        $where = '';
        if ($goods_id > 0) {
            //判断会员举报权限冻结时间
            $new_time = gmtime();
            $sql = "SELECT report_time FROM {pre}users WHERE user_id = '$_SESSION[user_id]'";
            $report_time = $this->db->getOne($sql);

            if ($report_time > $new_time) {
                show_message(L('malice_report'), L('back_report_list'), url('user/index/report_list'));
            }
            //判断是否重复举报 （举报，切状态为已处理的可以再次举报）
            $sql = "SELECT count(*) FROM {pre}goods_report WHERE goods_id='$goods_id' AND user_id = '$_SESSION[user_id]' AND report_state = 0";
            $goods_report_count = $this->db->getOne($sql);

            if ($goods_report_count > 0) {
                show_message(L('repeat_report'));
            }
            $goods_info = goods_info($goods_id);

            //下架商品不能举报
            if ($goods_info['is_on_sale'] == 0) {
                show_message(L('offgoods_report'));
            }
            $goods_info['goods_img'] = $goods_info['goods_thumb'];
            $goods_info['goods_thumb'] = get_image_path($goods_info['goods_thumb']);
            $goods_info['goods_price'] = price_format($goods_info['goods_price']);
            $this->assign('goods_info', $goods_info);
            //获取投诉类型列表
            $report_type = get_goods_report_type();
            $report_title = [];
            if ($report_type) {
                foreach($report_type as $key => $val){
                    $report_title[$val['type_id']] = get_goods_report_title($val['type_id']);
                }
            }

            $this->assign('report_type', $report_type);
            $this->assign('report_title',$report_title);
            $this->assign('report_title_json',json_encode($report_title));
            $where .= " AND goods_id = '$goods_id' AND report_id = 0";
        } elseif ($report_id > 0) {
            //初始化数据
            $goods_report_info = [
                'goods_id' => 0,
                'goods_name' => '',
                'goods_thumb' => ''
            ];
            $sql = "SELECT g.report_id , g.user_id , g.user_name , g.goods_id , g.goods_name , g.goods_image , g.title_id , g.type_id , "
                . "g.inform_content , g.add_time , g.report_state , g.handle_type , g.handle_message , g.handle_time , g.admin_id , "
                . "gt.type_name , gt.type_desc , ge.title_name FROM {pre}goods_report AS g "
                . "LEFT JOIN {pre}goods_report_type AS gt ON gt.type_id = g.type_id "
                . "LEFT JOIN {pre}goods_report_title AS ge ON ge.title_id=g.title_id "
                . "WHERE g.report_id = '$report_id' AND g.user_id = '$_SESSION[user_id]]' LIMIT 1";
            $goods_report_info = $this->db->getRow($sql);
            //商品赋值
            $goods_info['goods_id'] = $goods_report_info['goods_id'];
            $goods_info['goods_name'] = $goods_report_info['goods_name'];
            $goods_info['goods_thumb'] = get_image_path($goods_report_info['goods_image']);

            $sql = "SELECT user_id FROM {pre}goods WHERE goods_id = '" . $goods_report_info['goods_id'] . "' LIMIT 1";
            $basic_info = get_seller_shopinfo($GLOBALS['db']->getOne($sql));
            $goods_info['shop_name'] = $basic_info['shop_name'];

            $this->assign('goods_report_info', $goods_report_info);
            $where .= "AND report_id = '$report_id' AND goods_id = '" . $goods_report_info['goods_id'] . "'";
        }
        $goods_info['url'] = build_uri('goods', ['gid' => $goods_info['goods_id']]);

        //获取举报相册
        $sql = "SELECT img_id as id , goods_id, report_id,user_id,img_file as comment_img FROM {pre}goods_report_img WHERE user_id = '$_SESSION[user_id]' $where ORDER BY  id DESC";
        $img_list = $this->db->getAll($sql);
        $img = [];
        foreach ($img_list as $key => $val) {
            $img[$key]['img_id'] = $val['id'];
            $img[$key]['pic'] = get_image_path($val['comment_img']);
        }
        $this->assign('img', $img);
        //模板赋值

        $this->assign('report_title_json',json_encode($report_title));
        $this->assign('report_id', $report_id);
        $this->assign('img_list', $img_list);
        $this->assign("goods_info", $goods_info);
        $this->assign('page_title', L('report_goods'));  // 页面标题
        $this->display();
    }

    //切换举报状态
    public function actionCheckReportState()
    {
        $report_id = I('report_id', 0, 'intval');
        $state = I('state', 0, 'intval');
        if ($_SESSION['user_id'] > 0) {
            $sql = "UPDATE {pre}goods_report SET report_state = '$state'  WHERE report_id = '$report_id'";
            $this->db->query($sql);
            $result['error'] = 0;
            die(json_encode($result));
        }
    }

    /**
     * 上传图片
     */
    public function actionImgReturn()
    {
        $img = $_FILES['myfile']['tmp_name'];
        list($width, $height, $type) = getimagesize($img);
        if (empty($img)) {
            return;
        }
        //获取退货信息
        $user_id = $_SESSION['user_id'];
        $goods_id = I('goods_id');

        //判断文件类型
        if (empty($type)) {
            echo json_encode(['error' => 1, 'content' => '图片类型不正确']);
            return;
        }
        //上传图片并 获得路径
        $result = $this->upload('data/report_img', false, 20, [C('shop.thumb_width'), C('shop.thumb_height')]);
        $path = $result['url']['myfile']['url'];
        $add_time = gmtime();
        $sql = "INSERT INTO {pre}goods_report_img (goods_id,user_id,img_file,report_id)values(" . $goods_id . "," . $user_id . ",'" . $path . "',0)";
        $GLOBALS['db']->query($sql);
        $sql = "SELECT img_id, img_file FROM {pre}goods_report_img WHERE user_id = " . $user_id . " and goods_id = " . $goods_id;
        $res = $GLOBALS['db']->query($sql);
        $img = [];
        foreach ($res as $key => $val) {
            $img[$key]['img_id'] = $val['img_id'];
            $img[$key]['pic'] = get_image_path($val['img_file']);
        }
        echo json_encode($img);
    }

    /**
     * 清空图片
     */
    public function actionClearPictures()
    {
        $id = I('img_id', 0, 'intval');
        $rec_id = I('goods_id', 0, 'intval');
        $result = ['error' => 0, 'content' => ''];
        $sql = "select img_file from {pre}goods_report_img where user_id = '" . $_SESSION['user_id'] . "' and goods_id = '$rec_id'" . " and img_id=" . $id;
        $img_list = $GLOBALS['db']->getAll($sql);
        foreach ($img_list as $key => $row) {
            get_oss_del_file([$row['img_file']]);
            @unlink(get_image_path($row['img_file']));
        }
        $sql = "delete from {pre}goods_report_img where user_id = '" . $_SESSION['user_id'] . "' and goods_id = '$rec_id'" . " and img_id=" . $id;
        $GLOBALS['db']->query($sql);
        echo json_encode($result);
    }

    //举报入库
    public function actionGoodsReportSubmit()
    {
        $goods_id = I('goods_id', 0, 'intval');
        $goods_name = !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
        $goods_image = !empty($_REQUEST['goods_image']) ? trim($_REQUEST['goods_image']) : '';
        $title_id = I('title_id', 0, 'intval');
        $type_id = I('type_id', 0, 'intval');
        $inform_content = !empty($_REQUEST['inform_content']) ? trim($_REQUEST['inform_content']) : '';
        if ($title_id == 0) {
            show_message(L('title_null'));
        } elseif ($type_id == 0) {
            show_message(L('type_null'));
        } elseif ($inform_content == '') {
            show_message(L('inform_content_null'));
        } else {
            $time = gmtime();
            //更新数据
            $other = [
                'user_id' => $_SESSION['user_id'],
                'user_name' => $_SESSION['user_name'],
                'goods_id' => $goods_id,
                'goods_name' => $goods_name,
                'goods_image' => $goods_image,
                'title_id' => $title_id,
                'type_id' => $type_id,
                'inform_content' => $inform_content,
                'add_time' => $time,
            ];
            //入库处理
            $report_id = dao('goods_report')->add($other);
            //更新图片
            if ($report_id > 0) {
                $sql = "UPDATE {pre}goods_report_img SET report_id = '$report_id' WHERE user_id = '$_SESSION[user_id]' AND goods_id = '$goods_id' AND report_id = 0";
                $this->db->query($sql);
            }
            show_message(L('report_success'), '', url('user/index/report_list'));
        }
    }

    //增值发票
    public function actionInvForm()
    {
        $user_id = $_SESSION['user_id'];
        $id = I('id');
        if (IS_POST) {
            $content = [
                'id' => I('id'),
                'company_name' => I('company_name'),
                'user_id' => $user_id,
                'tax_id' => I('tax_id'),
                'company_address' => I('company_address'),
                'company_telephone' => I('company_telephone'),
                'bank_of_deposit' => I('bank_of_deposit'),
                'bank_account' => I('bank_account'),
                'consignee_name' => I('consignee_name'),
                'consignee_mobile_phone' => I('consignee_mobile_phone'),
                'consignee_address' => I('consignee_address'),
                'country' => I('country'),
                'province' => I('province'),
                'city' => I('city'),
                'district' => I('district'),
                'audit_status' => 0
            ];
            if (empty($content['consignee_address'])) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_receiving_notnull')]));
            }
            if (is_mobile($content['consignee_mobile_phone']) == false) {
                exit(json_encode(['status' => 'n', 'info' => L('msg_mobile_format_error')]));
            }
            if (empty($content['province'])) {
                exit(json_encode(['status' => 'n', 'info' => L('请选择地区')]));
            }

            $vat_id = $this->db->getOne(" SELECT id FROM {pre}users_vat_invoices_info  WHERE user_id = '$user_id' LIMIT 1 ");
            if ($vat_id && empty($id)) {
                exit(json_encode(['status' => 'y', 'info' => '您已提交过增票资质申请，请勿重复提交！', 'url' => url('user/index/inv_info')]));
            } elseif (empty($content['id'])) {
                $content['add_time'] = gmtime();
                dao('users_vat_invoices_info')->add($content);
                exit(json_encode(['status' => 'y', 'info' => '您的增票资质已提交，等待审核。', 'url' => url('user/index/inv_info')]));
            } else {
                dao('users_vat_invoices_info')->where(['id' => $id])->save($content);
                exit(json_encode(['status' => 'y', 'info' => '您的增票资质已提交，等待审核。', 'url' => url('user/index/inv_info')]));
            }
        }
        if (!empty($id)) {
            $vat_info = $this->db->getRow(" SELECT * FROM {pre}users_vat_invoices_info  WHERE user_id = '$user_id' and id=$id");
            $province = get_region_name($vat_info['province']);
            $city = get_region_name($vat_info['city']);
            $district = get_region_name($vat_info['district']);
            $vat_info['province_name'] = $province['region_name'];
            $vat_info['city_name'] = $city['region_name'];
            $vat_info['district_name'] = $district['region_name'];

            $this->assign('vat_info', $vat_info);
        }
        $this->assign('id', $id);
        $this->assign('page_title', '增值发票');  // 页面标题
        $this->display();
    }

    //增值发票详情
    public function actionInvInfo()
    {
        $user_id = $_SESSION['user_id'];
        $vat_info = $this->db->getRow(" SELECT * FROM {pre}users_vat_invoices_info  WHERE user_id = '$user_id'");
        if (empty($vat_info)) {
            ecs_header("Location: " . url('user/index/inv_form'));
        }
        $province = get_region_name($vat_info['province']);
        $city = get_region_name($vat_info['city']);
        $district = get_region_name($vat_info['district']);
        $vat_info['province_name'] = $province['region_name'];
        $vat_info['city_name'] = $city['region_name'];
        $vat_info['district_name'] = $district['region_name'];
        $this->assign('vat_info', $vat_info);
        $this->assign('page_title', '增值票详情');  // 页面标题
        $this->display();
    }

    //删除增值发票
    public function actionDelInv()
    {
        $vat_id = I('vat_id', 0, 'intval');
        $sql = " DELETE FROM {pre}users_vat_invoices_info WHERE id = '$vat_id' ";
        $this->db->query($sql);
        exit(json_encode(['status' => 1]));
    }

    public function actionSetAuctionCookie(){

        cookie('all_auction', 1);
        exit(json_encode(['status' => 1]));
    }
    /**
     * 当前会员是否是商家
     */
    private function isSeller()
    {
        $user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
        $is_jurisdiction = 0;
        if ($user_id > 0) {
            //判断是否是商家
            $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('seller_shopinfo') . " WHERE ru_id = '$user_id'";
            if ($GLOBALS['db']->getOne($sql, true)) {
                $is_jurisdiction = 1;
            }

            //判断是否是厂商
            $sql = "SELECT fid FROM " . $GLOBALS['ecs']->table('merchants_steps_fields') . " WHERE user_id = '$user_id' AND company_type = '厂商'";
            $is_chang = $GLOBALS['db']->getOne($sql, true);

            if ($is_chang) {
                $is_jurisdiction = 0;
            }
        }
        return $is_jurisdiction;
    }
}
