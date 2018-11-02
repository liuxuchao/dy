<?php

namespace App\Modules\Seckill\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    protected $region_id = 0;
    protected $area_id = 0;
    protected $area_city = 0;

    public function __construct()
    {
        parent::__construct();
        L(require(LANG_PATH . C('shop.lang') . '/other.php'));
        $this->assign('lang', array_change_key_case(L()));

        //初始化位置信息
        $this->init_params();

        $this->area_id = !empty($this->area_info['region_id']) ? $this->area_info['region_id'] : 0;
        $this->assign('region_id', $this->region_id); //商品仓库region_id
        $this->assign('area_id', $this->area_id); //地区ID
        $this->assign('area_city', $this->area_city); // 城市ID
    }

    /**
     * 首页信息
     */
    public function actionIndex()
    {
        $time_list = get_seckill_time();
        $frist_time = array_slice($time_list, 0, 1);
        foreach ($frist_time as $v) {
            $frist_time_id = $v['id'];
        }
        $i = 0;
        foreach ($time_list as $key => $value) {
            if ($i == 0) {
                $time_list[$key]['active'] = 1;
            }
            $i++;
        }
        if (IS_AJAX) {
            $tmr = 86400;
            $id = I('status', $frist_time_id, 'intval');
            $tomorrow = I('tomorrow', 0, 'intval');
            $sql = "select begin_time ,end_time from {pre}seckill_time_bucket where id=" . $id;
            $times = $this->db->getRow($sql);
            $begin_time = local_date('Y-m-d H:i:s', local_strtotime($times['begin_time']));
            $end_time = local_date('Y-m-d H:i:s', local_strtotime($times['end_time']));
            $local_begin_time = local_strtotime($times['begin_time']);
            $local_end_time = local_strtotime($times['end_time']);
            if ($tomorrow == 1) {
                $begin_time = local_date('Y-m-d H:i:s', local_strtotime($times['begin_time']) + $tmr);
                $end_time = local_date('Y-m-d H:i:s', local_strtotime($times['end_time']) + $tmr);
                $local_begin_time = local_strtotime($times['begin_time']) + $tmr;
                $local_end_time = local_strtotime($times['end_time']) + $tmr;
            }
            $seckill_status = seckill_status($local_begin_time, $local_end_time);//获取当前活动的状态
            if (!$seckill_status['is_end'] && !$seckill_status['status']) {
                $end_time = $begin_time;//当活动没有开始传的时间是开始时间不是结束时间
            }
            $page = I('page', 1, 'intval');
            $this->size = I('size', 10, 'intval');
            $list = seckill_goods_results($id, $page, $this->size, $tomorrow);
            exit(json_encode(['gb_list' => $list, 'totalPage' => $list['totalPage'], 'page' => $page, 'end_time' => $end_time, 'seckill_status' => $seckill_status, 'tomorrow' => $tomorrow]));
        }

        // banner广告位
        $position = dao('touch_ad_position')->where(['ad_type' => 'seckill', 'tc_type' => 'banner'])->order('position_id desc')->find();
        if (!empty($position)) {
            $banner_ads = insert_ads(['id' => $position['position_id'], 'num' => 6]);
            $this->assign('banner_ads', $banner_ads);
        }

        $this->assign('time_list', $time_list);
        $this->assign('page_title', L('限时秒杀'));
        $this->display();
    }

    /**
     * 详情页面
     */
    public function actionDetail()
    {
        $this->seckill_id = I('seckill_id', 0, 'intval');
        $this->goods_id = I('id', 0, 'intval');

        $tomorrow = I('tmr', 0, 'intval');
        if (!empty($this->seckill_id)) {
            $this->goods_id = $this->seckill_id;
        }
        $goods_info = seckill_info($this->goods_id, '', '', $tomorrow);
        $this->assign('pictures', get_goods_gallery($goods_info['goods_id'])); // 商品相册
        $goods_id = $goods_info['goods_id'];
        $start_date = local_strtotime($goods_info['begin_time']);
        $end_date = local_strtotime($goods_info['end_time']);
        $order_goods = get_for_purchasing_goods($start_date, $end_date, $goods_info['goods_id'], $_SESSION['user_id']);
        //评分 start
        $mc_all = ments_count_all($goods_info['goods_id']);       //总条数
        $mc_one = ments_count_rank_num($goods_info['goods_id'], 1);        //一颗星
        $mc_two = ments_count_rank_num($goods_info['goods_id'], 2);        //两颗星
        $mc_three = ments_count_rank_num($goods_info['goods_id'], 3);    //三颗星
        $mc_four = ments_count_rank_num($goods_info['goods_id'], 4);        //四颗星
        $mc_five = ments_count_rank_num($goods_info['goods_id'], 5);        //五颗星
        $comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
        if ($goods_info['user_id'] > 0) {
            //商家所有商品评分类型汇总
            $merchants_goods_comment = get_merchants_goods_comment($goods_info['user_id']);
            $this->assign('merch_cmt', $merchants_goods_comment);
        }
        $this->assign('comment_all', $comment_all);
        $good_comment = get_good_comment($goods_info['goods_id'], 4, 1, 0, 1);
        $this->assign('good_comment', $good_comment);
        $sql = "select a.kf_im_switch, b.is_IM,a.ru_id,a.province, a.city, a.kf_type, a.kf_ww, a.kf_qq, a.meiqia, a.shop_name, a.kf_appkey,kf_secretkey from {pre}seller_shopinfo as a left join {pre}merchants_shop_information as b on a.ru_id=b.user_id where a.ru_id='" . $goods_info['user_id'] . "' ";
        $basic_info = $this->db->getRow($sql);

        $info_ww = $basic_info['kf_ww'] ? explode("\r\n", $basic_info['kf_ww']) : '';
        $info_qq = $basic_info['kf_qq'] ? explode("\r\n", $basic_info['kf_qq']) : '';
        $kf_ww = $info_ww ? $info_ww[0] : '';
        $kf_qq = $info_qq ? $info_qq[0] : '';
        $basic_ww = $kf_ww ? explode('|', $kf_ww) : '';
        $basic_qq = $kf_qq ? explode('|', $kf_qq) : '';
        $basic_info['kf_ww'] = $basic_ww ? $basic_ww[1] : '';
        $basic_info['kf_qq'] = $basic_qq ? $basic_qq[1] : '';
        $customer_service = dao('shop_config')->where(['code' => 'customer_service'])->getField('value');
        $zkf = dao('seller_shopinfo')->field('kf_type, kf_qq, kf_ww')->where(['ru_id' => '0'])->find();
        $this->assign('customer_service', $customer_service);
        if($customer_service == 0){
            $basic_info['kf_ww'] = preg_replace('/^[^\-]*\|/is', '', $zkf['kf_ww']);
            $basic_info['kf_qq'] = preg_replace('/^[^\-]*\|/is', '', $zkf['kf_qq']);
        }

        //判断平台是否开启了IM在线客服
        $kf_im_switch = dao('seller_shopinfo')->where(['ru_id' => 0])->getField('kf_im_switch');

        /** 判断客服目录 */
        $im_dialog = M()->query('SHOW TABLES LIKE "{pre}im_dialog"');
        if($kf_im_switch == 1 && $im_dialog){
            $this->assign('kefu', $kf_im_switch);
        }

        if (($basic_info['is_im'] == 1 || $basic_info['ru_id'] == 0) && !empty($basic_info['kf_appkey'])) {
            $basic_info['kf_appkey'] = $basic_info['kf_appkey'];
        } else {
            $basic_info['kf_appkey'] = '';
        }

        $basic_date = ['region_name'];
        $basic_info['province'] = get_table_date('region', "region_id = '" . $basic_info['province'] . "'", $basic_date, 2);
        $basic_info['city'] = get_table_date('region', "region_id= '" . $basic_info['city'] . "'", $basic_date, 2) . "市";

        $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性

        if (!empty($goods_info['desc_mobile'])) {
            $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $goods_info['desc_mobile']);
        }
        if (empty($goods_info['desc_mobile']) && !empty($goods_info['goods_desc'])) {
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $goods_info['goods_desc']);
            } else {
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . __STATIC__ . '/images/upload', $goods_info['goods_desc']);
            }
        }
        if (empty($goods_info['desc_mobile']) && empty($goods_info['goods_desc'])) {
            // 查询关联商品描述
            $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
            $link_desc = $this->db->getOne($sql);
            $goods_desc = $link_desc;
        }
        // $goods_desc = preg_replace("/height\=\"[0-9]+?\"/", "", $goods_desc);
        // $goods_desc = preg_replace("/width\=\"[0-9]+?\"/", "", $goods_desc);
        // $goods_desc = preg_replace("/style=.+?[*|\"]/i", "", $goods_desc);
        $this->assign('goods_desc', $goods_desc);
        // 模板赋值
        $this->assign('properties', $properties['pro']);                              // 商品属性
        $this->assign('specification', $properties['spe']);                              // 商品规格

        $this->assign('basic_info', $basic_info);
        $this->assign('seckill_id', $this->goods_id);
        $this->assign('goods', $goods_info);
        $this->assign('tmr', $tomorrow);

        /* 记录浏览历史 */
        if (!empty($_COOKIE['ECS']['history_goods'])) {
            $history = explode(',', $_COOKIE['ECS']['history_goods']);
            array_unshift($history, $goods_id);
            $history = array_unique($history);
            while (count($history) > C('shop.history_number')) {
                array_pop($history);
            }
            cookie('ECS[history_goods]', implode(',', $history));
        } else {
            cookie('ECS[history_goods]', $this->goods_id);
        }

        // 微信JSSDK分享
        $share_data = [
            'title' => '秒杀商品_' . $goods_info['goods_name'],
            'desc' => $goods_info['acti_title'],
            'link' => '',
            'img' => $goods_info['goods_img'],
        ];
        $this->assign('share_data', $this->get_wechat_share_content($share_data));
        $this->assign('order_number', $order_goods['goods_number']);

        $page_title = $goods_info['goods_name'];
        $keywords = $goods_info['keywords'];
        $description = $goods_info['acti_title'];
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);
        $this->assign('page_title', $share_data['title']);
        $this->display();
    }

    /**
     * 商品评论
     */
    public function actionComment($rank = '')
    {
        $this->seckill_id = I('seckill_id', 0, 'intval');
        $this->goods_id = I('id', 0, 'intval');
        $tmr = I('tmr', 0, 'intval');
        if (IS_AJAX) {
            $rank = I('rank', 'all', ['htmlspecialchars','trim']);
            $page = I('page', 0, 'intval');
            $start = $page > 0 ? ($page - 1) * $this->size : 1;
            $goods_id = I('id', 0, 'intval');
            $arr = get_good_comment_as($goods_id, $rank, 1, $start, 10);
            $comments = $arr['arr'];
            $totalPage = $arr['max'];
            if ($rank == 'img') {
                foreach ($comments as $key => $val) {
                    if ($val['thumb'] == '') {
                        unset($comments[$key]);
                    }
                }
                $totalPage = $arr['img_max'];
            }
            $reset = ($start > 0) ? 0 : 1;
            die(json_encode(['comments' => $comments, 'rank' => $rank, 'reset' => $reset, 'totalPage' => $totalPage, 'top' => 1]));
        }
        // 兼容原有图评论
        if ($rank == 'img') {
            $rank = $rank;
        } else {
            $rank = I('rank', 'all', ['htmlspecialchars','trim']);
        }
        $this->assign('rank', $rank); // 评论
        $this->assign('comment_count', commentCol($this->goods_id)); // 评论数量
        $this->assign('goods_id', $this->goods_id);
        $this->assign('tmr', $tmr);
        $this->assign('page_title', '商品评论');
        $this->display('comment');
    }

    /**
     * 商品详情
     */
    public function actionInfo()
    {
        $goods_id = I('id', 0, 'intval');
        $seckill_id = I('seckill_id', 0, 'intval');
        $tmr = I('tmr', 0, 'intval');
        $info = $this->db->table('goods')->field('goods_desc,desc_mobile')->where(['goods_id' => $goods_id])->find();
        $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性
        if (!empty($info['desc_mobile'])) {
            $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $info['desc_mobile']);
        }
        if (empty($info['desc_mobile']) && !empty($info['goods_desc'])) {
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $info['goods_desc']);
            } else {
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . __STATIC__ . '/images/upload', $info['goods_desc']);
            }
        }
        if (empty($info['desc_mobile']) && empty($info['goods_desc'])) {
            // 查询关联商品描述
            $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
            $link_desc = $this->db->getOne($sql);
            $goods_desc = $link_desc;
        }
        $goods_desc = preg_replace("/height\=\"[0-9]+?\"/", "", $goods_desc);
        $goods_desc = preg_replace("/width\=\"[0-9]+?\"/", "", $goods_desc);
        $goods_desc = preg_replace("/style=.+?[*|\"]/i", "", $goods_desc);
        $this->assign('goods_desc', $goods_desc);
        $this->assign('goods_id', $goods_id);
        $this->assign('seckill_id', $seckill_id);
        $this->assign('tmr', $tmr);
        $this->assign('properties', $properties['pro']); // 商品属性
        $this->assign('page_title', '商品详情');
        $this->display();
    }

    /**
     * 改变属性、数量时重新计算商品价格
     */
    public function actionPrice()
    {
        $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];
        $attr = I('attr');
        $number = I('number', 1, 'intval');
        $goods_id = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
        $attr_id = !empty($attr) ? explode(',', $attr) : [];
        $warehouse_id = I('request.warehouse_id', 0, 'intval');
        $area_id = I('request.area_id', 0, 'intval'); //仓库管理的地区ID
        $onload = I('request.onload', '', ['htmlspecialchars','trim']);
        //仓库管理的地区ID
        $goods = seckill_info($goods_id);
        if ($goods_id == 0) {
            $res['err_msg'] = L('err_change_attr');
            $res['err_no'] = 1;
        } else {
            if ($number == 0) {
                $res['qty'] = $number = 1;
            } else {
                $res['qty'] = $number;
            }
            $res['attr_number'] = $goods['sec_num'];
        }
        die(json_encode($res));
    }

    /**
     * 购买
     */
    public function actionBuy()
    {
        /* 查询：判断是否登录 */
        $this->check_login();

        $warehouse_id = (isset($_REQUEST['warehouse_id'])) ? intval($_REQUEST['warehouse_id']) : 0;
        $area_id = (isset($_REQUEST['area_id'])) ? intval($_REQUEST['area_id']) : 0; //仓库管理的地区ID
        $area_city = (isset($_REQUEST['area_city'])) ? intval($_REQUEST['area_city']) : 0;
        /* 查询：取得参数：秒杀活动id */
        $sec_goods_id = isset($_POST['sec_goods_id']) ? intval($_POST['sec_goods_id']) : 0;
        if ($sec_goods_id <= 0) {
            ecs_header("Location: ./\n");
            exit;
        }
        /* 查询：取得数量 */
        $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
        $number = ($number < 1) ? 1 : $number;
        /* 查询：取得秒杀活动信息 */
        $seckill = seckill_info($sec_goods_id, $number);
        if (empty($seckill)) {
            ecs_header("Location: ./\n");
            exit;
        }
        if ($number > $seckill['sec_limit']) {
            show_message('购买数量不能超过限购数量', '', '', 'error');
            exit;
        }
        if ($seckill['sec_num'] == 0) {
            show_message('此商品已售罄', '', '', 'error');
            exit;
        }
        $start_date = local_strtotime($seckill['begin_time']);
        $end_date = local_strtotime($seckill['end_time']);
        $order_goods = get_for_purchasing_goods($start_date, $end_date, $seckill['goods_id'], $_SESSION['user_id'],'seckill');

        if (($order_goods['goods_number'] == $seckill['sec_limit']) || $number > ($seckill['sec_limit'] - $order_goods['goods_number'])) {
            show_message('购买数量已经达到限购数量', '', '', 'error');
            exit;
        }
        /* 查询：检查秒杀活动是否是进行中 */
        if (!$seckill['status']) {
            show_message(L('gb_error_status'), '', '', 'error');
        }
        
        $sql = "DELETE FROM {pre}solve_dealconcurrent WHERE user_id = '" .$_SESSION['user_id']. "' AND flow_type = '" .CART_SECKILL_GOODS. "'";
        $this->db->query($sql);

        /* 查询：取得规格 */
        $specs = isset($_POST['goods_spec']) ? htmlspecialchars(trim($_POST['goods_spec'])) : '';

        $products = [];
        if ($specs) {
            $products = get_warehouse_id_attr_number($seckill['goods_id'], $specs, $seckill['user_id'], $this->region_id, $this->area_id, $this->area_city);
        }
    
        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = [];
        $sql = "SELECT a.attr_name, g.attr_value " .
            "FROM {pre}goods_attr AS g,{pre}attribute AS a " .
            "WHERE g.attr_id = a.attr_id " .
            "AND g.goods_attr_id " . db_create_in($specs) . " ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
        $res = $this->db->getAll($sql);
        foreach ($res as $row) {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        $restrict_amount = $number + $order_goods['goods_number']; // ?

        if (!empty($_SESSION['user_id'])) {
            $sess = "";
        } else {
            $sess = real_cart_mac_ip();
        }
        /* 更新：清空购物车中所有秒杀商品 */
        clear_cart(CART_SECKILL_GOODS);
        /* 更新：加入购物车 */
        $goods_price = isset($seckill['sec_price']) && $seckill['sec_price'] > 0 ? $seckill['sec_price'] : $seckill['shop_price'];
        $cart = [
            'user_id' => $_SESSION['user_id'],
            'session_id' => $sess,
            'goods_id' => $seckill['goods_id'],
            'product_id' => isset($products['product_id']) ? $products['product_id'] : 0,
            'goods_sn' => addslashes($seckill['goods_sn']),
            'goods_name' => addslashes($seckill['goods_name']),
            'market_price' => $seckill['market_price'],
            'goods_price' => $goods_price,
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            'ru_id' => $seckill['user_id'],
            'warehouse_id' => $this->region_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'is_real' => $seckill['is_real'],
            'extension_code' => 'seckill' . $seckill['id'],
            'parent_id' => 0,
            'rec_type' => CART_SECKILL_GOODS,
            'is_gift' => 0
        ];

        $this->db->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');
        /* 更新：记录购物流程类型：团购 */
        $_SESSION['flow_type'] = CART_SECKILL_GOODS;
        $_SESSION['extension_code'] = 'seckill';
        $_SESSION['extension_id'] = $seckill['id'];

        /* 进入收货人页面 */
        $_SESSION['browse_trace'] = "seckill";
        $this->redirect('flow/index/index', ['direct_shopping' => 4]);
        exit;
    }

    /**
     * 提醒功能
     */
    public function actionCollect()
    {
        $seckill_id = I('seckill_id', 0, 'intval');
        $user_id = empty($_SESSION['user_id']) ? 0: $_SESSION['user_id'];
        $info = array();
        if($user_id){
            $sql = "SELECT COUNT(*) FROM {pre}seckill_goods_remind WHERE user_id = '$user_id' AND sec_goods_id = '$seckill_id'";
            $count = $this->db->getOne($sql);
            if($count == 0){
                //添加提醒
                $time = gmtime();
                $sql = "INSERT INTO {pre}seckill_goods_remind (user_id, sec_goods_id, add_time)" .
                    "VALUES ('$user_id', '$seckill_id', '$time')";
                $this->db->query($sql);
                $info['error'] = 0;
                die(json_encode($info));
            }else{
                //取消提醒
                $sql = "DELETE FROM {pre}seckill_goods_remind WHERE sec_goods_id = '$seckill_id' AND user_id = '$user_id' ";
                $this->db->query($sql);
                $info['error'] = 1;
                die(json_encode($info));
            }
        }else{
            $info['error'] = 2;
            die(json_encode($info));
        }
    }

    /**
     * 验证是否登录
     */
    private function check_login()
    {
        if (!$_SESSION['user_id']) {
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('user/login/index', ['back_act' => urlencode($back_act)]);
        }
    }

    /**
     * 初始化参数
     */
    private function init_params()
    {
        #需要查询的IP start
        if (!isset($_COOKIE['province'])) {
            $area_array = get_ip_area_name();
            if ($area_array['county_level'] == 2) {
                $date = ['region_id', 'parent_id', 'region_name'];
                $where = "region_name = '" . $area_array['area_name'] . "' AND region_type = 2";
                $city_info = get_table_date('region', $where, $date, 1);

                $date = ['region_id', 'region_name'];
                $where = "region_id = '" . $city_info[0]['parent_id'] . "'";
                $province_info = get_table_date('region', $where, $date);

                $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $district_info = get_table_date('region', $where, $date, 1);
            } elseif ($area_array['county_level'] == 1) {
                $area_name = $area_array['area_name'];
                $date = ['region_id', 'region_name'];
                $where = "region_name = '$area_name'";
                $province_info = get_table_date('region', $where, $date);
                $where = "parent_id = '" . $province_info['region_id'] . "' order by region_id asc limit 0, 1";
                $city_info = get_table_date('region', $where, $date, 1);
                $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
                $district_info = get_table_date('region', $where, $date, 1);
            }
        }
        #需要查询的IP end
        $order_area = get_user_order_area($this->user_id);
        $user_area = get_user_area_reg($this->user_id); //2014-02-25
        if ($order_area['province'] && $this->user_id > 0) {
            $this->province_id = $order_area['province'];
            $this->city_id = $order_area['city'];
            $this->district_id = $order_area['district'];
        } else {
            //省
            if ($user_area['province'] > 0) {
                $this->province_id = $user_area['province'];
                cookie('province', $user_area['province']);
                $this->region_id = get_province_id_warehouse($this->province_id);
            } else {
                $sql = "select region_name from " . $this->ecs->table('region_warehouse') . " where regionId = '" . $province_info['region_id'] . "'";
                $warehouse_name = $this->db->getOne($sql);

                $this->province_id = $province_info['region_id'];
                $cangku_name = $warehouse_name;
                $this->region_id = get_warehouse_name_id(0, $cangku_name);
            }
            //市
            if ($user_area['city'] > 0) {
                $this->city_id = $user_area['city'];
                cookie('city', $user_area['city']);
            } else {
                $this->city_id = $city_info[0]['region_id'];
            }
            //区
            if ($user_area['district'] > 0) {
                $this->district_id = $user_area['district'];
                cookie('district', $user_area['district']);
            } else {
                $this->district_id = $district_info[0]['region_id'];
            }
        }
        $this->province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : $this->province_id;
        $child_num = get_region_child_num($this->province_id);
        if ($child_num > 0) {
            $this->city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : $this->city_id;
        } else {
            $this->city_id = '';
        }
        $child_num = get_region_child_num($this->city_id);
        if ($child_num > 0) {
            $this->district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : $this->district_id;
        } else {
            $this->district_id = '';
        }
        $this->region_id = !isset($_COOKIE['region_id']) ? $this->region_id : $_COOKIE['region_id'];
        $goods_warehouse = get_warehouse_goods_region($this->province_id); //查询用户选择的配送地址所属仓库
        if ($goods_warehouse) {
            $this->regionId = $goods_warehouse['region_id'];
            if ($_COOKIE['region_id'] && $_COOKIE['regionid']) {
                $gw = 0;
            } else {
                $gw = 1;
            }
        }
        if ($gw) {
            $this->region_id = $this->regionId;
            cookie('area_region', $this->region_id);
        }
        cookie('goodsId', $this->goods_id);
        $sellerInfo = get_seller_info_area();
        if (empty($this->province_id)) {
            $this->province_id = $sellerInfo['province'];
            $this->city_id = $sellerInfo['city'];
            $this->district_id = 0;
            cookie('province', $this->province_id);
            cookie('city', $this->city_id);
            cookie('district', $this->district_id);

            $this->region_id = get_warehouse_goods_region($this->province_id);
        }
        $other = [
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
        ];
        $warehouse_area_info = get_warehouse_area_info($other);
        $this->area_city = $warehouse_area_info['city_id'];
        //ecmoban模板堂 --zhuo end 仓库
        $this->area_info = get_area_info($this->province_id);
    }
}
