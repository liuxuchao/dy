<?php

namespace App\Modules\Purchase\Controllers;

use App\Modules\Purchase\Models\Purchase;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{

    public function __construct()
    {
        parent::__construct();

        // 必须是商家帐号
        if ($GLOBALS['_CFG']['wholesale_user_rank'] == 0 && !$this->isSeller()) {
            $this->redirect(U('/'));
        }
    }

    /**
     * 批发首页
     */
    public function actionIndex()
    {
        $this->assign('page_title', '批发首页');
        $this->assign('action', 'index');

        /** 轮播图 */
        $banners = Purchase::get_banner(256, 3);
        $this->assign('banners', $banners);

        /** 批发分类 */
        $wholesale_cat = Purchase::get_wholesale_child_cat();
        $this->assign('wholesale_cat', $wholesale_cat);

        /** 限时采购 */
        $wholesale_limit = Purchase::get_wholesale_limit();
        $this->assign('wholesale_limit', $wholesale_limit);

        /** 批发商品 */
        $goodsList = Purchase::get_wholesale_cat();
        $this->assign('get_wholesale_cat', $goodsList);

        $this->display();
    }

    /**
     * 批发列表
     */
    public function actionList()
    {
        $page = I('page', 1, 'intval');
        $page = ($page > 0) ? $page : 1;

        $size = !empty($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

        $this->assign('page_title', '批发列表');
        $this->assign('action', 'list');

        /**
         * 分类名
         */
        $cat_id = I('id', 0, 'intval');
        if ($cat_id) {
            $this->assign('cat_name', Purchase::getCatName($cat_id));
        }

        /**
         * 分类列表
         */
        $wholesale_cat = Purchase::get_wholesale_child_cat();
        $this->assign('wholesale_cat', $wholesale_cat);
        $this->assign('cat_id', $cat_id);

        $this->display();
    }

    /**
     * 根据商品分类  商品列表
     */
    public function actionGoodsList()
    {
        // 根据批发ID获取商品信息
        $act_id = I('id', 0, 'intval');
        $page = I('page', 1, 'intval');
        $size = I('size', 10, 'intval');

        $result = Purchase::get_wholesale_list($act_id, $size, $page);

        $this->ajaxReturn($result);
    }

    /**
     * 增加搜索
     */
    public function actionSearch()
    {
        $this->assign('page_title', '搜索页面');

        // 获取参数
        $_REQUEST['keywords'] = !empty($_REQUEST['keywords']) ? strip_tags(htmlspecialchars(trim($_REQUEST['keywords']))) : '';
        $_REQUEST['keywords'] = !empty($_REQUEST['keywords']) ? addslashes_deep(trim($_REQUEST['keywords'])) : '';

        // end
        $this->assign('keyword', $_REQUEST['keywords']);
        $this->display();
    }

    /**
     * 异步获取 搜索的商品
     */
    public function actionAsyncSearchList()
    {
        $page = !empty($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        $size = !empty($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

        $list = Purchase::get_search_goods_list($_REQUEST['keywords'], $page, $size);

        $this->ajaxReturn($list);
    }

    /**
     * 批发商品页面
     */
    public function actionGoods()
    {
        $this->assign('page_title', '批发详情');
        $this->assign('action', 'goods');

        // 根据批发ID获取商品信息
        $act_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $goods = Purchase::get_wholesale_goods_info($act_id);

        // 地区
        $area_info = get_area_info($this->province_id);
        $area_id = $area_info['region_id'];

        $where = "regionId = '$this->province_id'";
        $date = ['parent_id'];
        $region_id = get_table_date('region_warehouse', $where, $date, 2);
        //

        /** 商品相册 */
        $pictures = get_goods_gallery($goods['goods_id']);
        $this->assign('pictures', $pictures);                    // 商品相册
		
		 /** 商品信息 */
        $info = $this->db->table('goods')->field('goods_desc,desc_mobile')->where(['goods_id' => $goods['goods_id']])->find();
        // 查询关联商品描述
        $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = $goods[goods_id]  AND dg.d_id = ld.id AND ld.review_status > 2";
        $link_desc = $this->db->getOne($sql);
        if (!empty($info['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $info['desc_mobile'], 'desc_mobile');
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $desc_preg['desc_mobile']);
            } else {
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $info['desc_mobile']);
            }
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
            $goods_desc = $link_desc;
        }
        // Img lazyload
        $goods['goods_desc'] = preg_replace("/<img(.*?)src=/i", '<img${1}class="lazy" src=', $goods_desc);
        $this->assign('goods', $goods);

        // 最小起订量
        $min = 0;
        foreach ($goods['volume_price'] as $list) {

            if ($min == 0 || $min > $list['volume_number']) {
                $min = $list['volume_number'];
            }
        }
        $this->assign('min', $min);

        /** 商品属性 */
        $properties = Purchase::get_wholesale_goods_properties($goods['goods_id'], $region_id, $area_id);  // 获得商品的规格和属性
        $this->assign('specification', $properties['spe']);      // 商品属性

        $main_attr_list = Purchase::get_wholesale_main_attr_list($goods['goods_id']);
        $this->assign('main_attr_list', $main_attr_list);

        $this->assign('properties', $properties['pro']);      // 商品规格

        /** 判断用户是否有权购买 */
        $is_jurisdiction = Purchase::isJurisdiction($goods);
        //$this->assign('is_jurisdiction', $is_jurisdiction);暂时隐藏权限判断，pc端权限暂时去掉了
        $this->assign('is_jurisdiction', 1);

        /** 购物车信息 */
        $cartInfo = Purchase::get_wholesale_cart_info();
        $this->assign('cart_number', $cartInfo['number']);

        /** 登录返回地址 */

        $back_url = url('user/login/index', ['back_act' => urlencode(__SELF__)]);

        $this->assign('is_login', empty($_SESSION['user_id']) ? 0 : 1);
        $this->assign('back_url', $back_url);

        $this->display();
    }

    /**
     * 添加到购物车
     */
    public function actionAddToCart()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //处理数据
        $goods_id = I('goods_id', 0, 'intval');
        //判断商品是否设置属性
        $goods_type = get_table_date('wholesale', "goods_id='$goods_id'", ['goods_type'], 2);

        if ($goods_type > 0) {
            $attr_array = empty($_REQUEST['attr_array']) ? [] : $_REQUEST['attr_array'];
            $num_array = empty($_REQUEST['num_array']) ? [] : $_REQUEST['num_array'];
            $total_number = array_sum($num_array);
        } else {
            $goods_number = empty($_REQUEST['num_array']) ? 0 : $_REQUEST['num_array'];
            $goods_number = $goods_number[0];
            $total_number = $goods_number;
        }

        $rank_ids = get_table_date('wholesale', "goods_id='$goods_id'", ['rank_ids'], 2);
        $is_jurisdiction = 0;
        if ($_SESSION['user_id'] > 0) {
            //判断是否是商家
            $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE ru_id = '" . $_SESSION['user_id'] . "'";
            $seller_id = $GLOBALS['db']->getOne($sql, true);
            if ($seller_id > 0) {
                $is_jurisdiction = 1;
            } else {
                //判断是否设置了普通会员
                if ($rank_ids) {
                    $rank_arr = explode(',', $rank_ids);
                    if (in_array($_SESSION['user_rank'], $rank_arr)) {
                        $is_jurisdiction = 1;
                    }
                }
            }
        } else {
            //提示登陆
            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];

            $result['error'] = 2;
            $result['content'] = url('user/login/index', ['back_act' => urlencode($back_act)]);
            $result['message'] = '登陆过期，请重新登陆！';
            $this->ajaxReturn($result);
        }
//        if ($is_jurisdiction == 0) {
//            //提示没有权限
//            $back_act = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
//
//            $result['error'] = 1;
//            $result['content'] = url('user/login/index', ['back_act' => urlencode($back_act)]);
//            $result['message'] = '此商品您暂无购买权限！';
//            $this->ajaxReturn($result);
//
//        }
        //计算价格
        $price_info = calculate_goods_price($goods_id, $total_number);
        //商品信息
        $goods_info = get_table_date('goods', "goods_id='$goods_id'", ['goods_name, goods_sn, user_id']);
        //通用数据
        $common_data = [];
        $common_data['user_id'] = $_SESSION['user_id'];
        $common_data['session_id'] = SESS_ID;
        $common_data['goods_id'] = $goods_id;
        $common_data['goods_sn'] = $goods_info['goods_sn'];
        $common_data['goods_name'] = $goods_info['goods_name'];
        $common_data['market_price'] = $price_info['market_price'];
        $common_data['goods_price'] = $price_info['unit_price'];
        $common_data['goods_number'] = 0;
        $common_data['goods_attr_id'] = '';
        $common_data['ru_id'] = $goods_info['user_id'];
        $common_data['add_time'] = gmtime();

        //加入购物车
        if ($_SESSION['user_id']) {
            $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
        } else {
            $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
        }
        if ($goods_type > 0) {
            foreach ($attr_array as $key => $val) {
                //货品信息
                $attr = explode(',', $val);
                //处理数据
                $data = $common_data;
                $gooda_attr = get_goods_attr_array($val);
                foreach ($gooda_attr as $v) {
                    $data['goods_attr'] .= $v['attr_name'] . ":" . $v['attr_value'] . "\n";
                }
                $data['goods_attr_id'] = $val;
                $data['goods_number'] = $num_array[$key];
                //货品数据
                $set = get_find_in_set($attr, 'goods_attr', ',');
                $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_products') . " WHERE goods_id = '$goods_id' $set ";
                $product_info = $GLOBALS['db']->getRow($sql);
                $data['goods_sn'] = $product_info['product_sn'];
                //判断是更新还是插入
                $set = get_find_in_set($attr, 'goods_attr_id', ',');

                $sql = " SELECT rec_id FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " WHERE {$sess_id} AND goods_id = '$goods_id' $set ";

                $rec_id = $GLOBALS['db']->getOne($sql);

                if (!empty($rec_id)) {
                    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_cart'), $data, 'UPDATE', "rec_id='$rec_id'");
                } else {
                    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_cart'), $data, 'INSERT');
                }
            }
        } else {
            $data = $common_data;
            $data['goods_number'] = $goods_number;
            //判断是更新还是插入
            $sql = " SELECT rec_id FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " WHERE {$sess_id} AND goods_id = '$goods_id' ";
            $rec_id = $GLOBALS['db']->getOne($sql);
            if (!empty($rec_id)) {
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_cart'), $data, 'UPDATE', "rec_id='$rec_id'");
            } else {
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_cart'), $data, 'INSERT');
            }
        }

        //重新计算价格并更新价格
        calculate_cart_goods_price($goods_id);
        $goods_data = Purchase::get_count_cart();


        // 获取购物车商品数量
        $result['message'] = '商品已添加';
        $result['content'] = $goods_data;

        $this->ajaxReturn($result);

    }

    /**
     * 改变单价
     */
    public function actionChangePrice(){
        $num= I('num');
        $goods_id = I('goods_id');
        $price_info = calculate_goods_price($goods_id, $num);
        $price_info['unit_price'] = price_format($price_info['unit_price']);
        $this->ajaxReturn($price_info);
    }

    /**
     * 提交批发订单
     */
    public function actionDown()
    {
        //公共数据
        $common_data['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $common_data['mobile'] = empty($_REQUEST['mobile']) ? '' : trim($_REQUEST['mobile']);
        $common_data['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $common_data['inv_type'] = empty($_REQUEST['inv_type']) ? 0 : intval($_REQUEST['inv_type']);
        $common_data['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
        $common_data['postscript'] = empty($_REQUEST['postscript']) ? '' : trim($_REQUEST['postscript']);
        $common_data['inv_payee'] = empty($_REQUEST['inv_payee']) ? '' : trim($_REQUEST['inv_payee']);
        $common_data['tax_id'] = empty($_REQUEST['tax_id']) ? '' : trim($_REQUEST['tax_id']);
        //内部数据
        $main_order = $common_data;
        $main_order['order_sn'] = get_order_sn(); //获取订单号
        $main_order['main_order_id'] = 0; //主订单
        $main_order['user_id'] = $_SESSION['user_id'];
        $main_order['add_time'] = gmtime();
        $main_order['order_amount'] = 0;
        //插入主订单
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_order_info'), $main_order, 'INSERT');
        $main_order_id = $GLOBALS['db']->getLastInsID(); //主订单id
        //开始分单 start
        $rec_ids = empty($_REQUEST['rec_ids']) ? '' : implode(',', $_REQUEST['rec_ids']);
        $where = " WHERE user_id = '$_SESSION[user_id]' AND rec_id IN ($rec_ids) ";
        if (empty($rec_ids)) {
            //报错
        }
        $sql = " SELECT DISTINCT ru_id FROM " . $GLOBALS['ecs']->table('wholesale_cart') . $where;
        $ru_ids = $GLOBALS['db']->getCol($sql);
        foreach ($ru_ids as $key => $val) {
            //内部数据
            $child_order = $common_data;
            $child_order['order_sn'] = get_order_sn(); //获取订单号
            $child_order['main_order_id'] = $main_order_id; //主订单
            $child_order['user_id'] = $_SESSION['user_id'];
            $child_order['add_time'] = gmtime();
            $child_order['order_amount'] = 0;
            //插入子订单
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_order_info'), $child_order, 'INSERT');
            $child_order_id = $GLOBALS['db']->getLastInsID(); //子订单id
            //购物车商品数据
            $sql = " SELECT goods_id, goods_name, goods_sn, goods_number, goods_price, goods_attr, goods_attr_id, ru_id FROM " .
                $GLOBALS['ecs']->table('wholesale_cart') . $where . " AND ru_id = '$val' ";
            $cart_goods = $GLOBALS['db']->getAll($sql);
            foreach ($cart_goods as $k => $v) {
                //插入订单商品表
                $v['order_id'] = $child_order_id;
                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_order_goods'), $v, 'INSERT');
                //统计子订单金额
                $child_order['order_amount'] += $v['goods_price'] * $v['goods_number'];
            }
            //更新子订单数据
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_order_info'), $child_order, 'update', "order_id ='$child_order_id'");
            //统计主订单金额
            $main_order['order_amount'] += $child_order['order_amount'];
            $log_id = $this->insert_pay_log($child_order_id, $child_order['order_amount'], PAY_WHOLESALE);
        }
        //更新主订单数据
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('wholesale_order_info'), $main_order, 'update', "order_id ='$main_order_id'");

        $sql = "SELECT order_amount FROM".$GLOBALS['ecs']->table('wholesale_order_info')."WHERE order_id ='$main_order_id'";
        $order_amount = $GLOBALS['db']->getOne($sql);
        $log_id = $this->insert_pay_log($main_order_id, $order_amount, PAY_WHOLESALE);//更新主订单支付日志
        //开始分单 end

        //插入数据完成后删除购物车订单
        $sql = " DELETE FROM " . $GLOBALS['ecs']->table('wholesale_cart') . $where;
        $GLOBALS['db']->query($sql);

        $result = [
            'code' => 0,
            'message' => '提交成功'
        ];
        $this->ajaxReturn($result);
    }

    /**
     * 进货单（批发购物车）
     */
    public function actionCart()
    {
        $this->assign('page_title', '进货单');
        $this->assign('action', 'cart');

        $goods_id = empty($_REQUEST['goods_id']) ? 0 : trim($_REQUEST['goods_id']);
        $rec_ids = empty($_REQUEST['rec_ids']) ? '' : trim($_REQUEST['rec_ids']);


        $goods_data = Purchase::wholesale_cart_goods($goods_id, $rec_ids);
        $this->assign('goods_data', $goods_data);

        $this->display();
    }

    /**
     * 更新购物车数量
     */
    public function actionUpdateCartGoods()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $rec_id = empty($_REQUEST['rec_id']) ? 0 : intval($_REQUEST['rec_id']);
        $rec_num = empty($_REQUEST['rec_num']) ? 0 : intval($_REQUEST['rec_num']);
        $rec_ids = I('rec_ids','');
        $rec_ids = implode(',', $rec_ids);

        //查询库存
        $cart_info = get_table_date('wholesale_cart', "rec_id='$rec_id'", ['goods_id', 'goods_attr_id']);
        if (empty($cart_info['goods_attr_id'])) {
            $goods_number = get_table_date('wholesale', "goods_id='$cart_info[goods_id]'", ['goods_number'], 2);
        } else {
            $set = get_find_in_set(explode(',', $cart_info['goods_attr_id']));
            $goods_number = get_table_date('wholesale_products', "goods_id='$cart_info[goods_id]' $set", ['product_number'], 2);
        }
        $result['goods_number'] = $goods_number;

        if ($goods_number < $rec_num) {
            $result['error'] = 1;
            $result['message'] = "该商品库存只有{$goods_number}个";
            $rec_num = $goods_number;
        }
        $sql = " UPDATE " . $GLOBALS['ecs']->table('wholesale_cart') . " SET goods_number = '$rec_num' WHERE rec_id = '$rec_id' ";
        $GLOBALS['db']->query($sql);

        // 返回商品数量、价格
        $cart_goods = Purchase:: wholesale_cart_goods(0, $rec_ids);
        $goods_list = array();
        foreach($cart_goods as $key=>$val){
            foreach($val['goods_list'] as $k=>$g){
                //处理阶梯价格
                //商品数据
                $goods_list[$g['goods_id']] = $g;
            }
        }
        $result['goods_list'] = $goods_list;
        //订单信息

        $cart_info = Purchase::wholesale_cart_info(0, $rec_ids);

        $result['cart_info'] = $cart_info;

        $result['goods'] = Purchase::cartInfo($rec_id);
        $this->ajaxReturn($result);
    }

    /**
     * 删除购物车商品
     */
    public function actionRemove()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        if ($_SESSION['user_id']) {
            $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
        } else {
            $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
        }

        $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
        if (!empty($goods_id)) {
            $sess_id .= " AND goods_id = '$goods_id' ";
            $sql = " DELETE FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " WHERE $sess_id ";
            $GLOBALS['db']->query($sql);
        }
        $this->ajaxReturn($result);
    }

    /**
     * 联系方式
     * 提交 联系方式等信息
     * 在cart页面（弹层）
     * 异步处理提交
     */
    public function actionInfo()
    {
        $this->assign('title', '批发首页');
        $this->assign('action', 'info');
        $result = [];

        $this->ajaxReturn($result);
    }

    /**
     * 求购信息列表（求购单列表）
     * 目前只做显示 ********
     */
    public function actionShow()
    {
        $this->assign('page_title', '求购信息');
        $this->assign('action', 'show');
        $is_finished = isset($_REQUEST['is_finished']) ? intval($_REQUEST['is_finished']) : -1;
        $keyword = isset($_REQUEST['keyword']) ? htmlspecialchars(stripcslashes($_REQUEST['keyword'])) : '';

        $filter_array = [];
        $filter_array['review_status'] = 1;
        $query_array = [];
        $query_array['act'] = 'list';
        if ($is_finished != -1) {
            $query_array['is_finished'] = $is_finished;
            $filter_array['is_finished'] = $is_finished;
        }
        if ($keyword) {
            $filter_array['keyword'] = $keyword;
            $query_array['keyword'] = $keyword;
        }

        $page = I('page', 1, 'intval');
        $size = 10;
        if (IS_AJAX) {
            $purchase_list = Purchase::get_purchase_list($filter_array, $size, $page);
            exit(json_encode(['list' => array_values($purchase_list['purchase_list']), 'totalPage' => $purchase_list['page_count']]));
        }

        $this->assign('is_finished', $is_finished);
        $this->display();
    }

    /**
     * 求购详细信息
     */
    public function actionShowDetail()
    {
        $this->assign('page_title', '求购详情');

        $purchase_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
        $purchase_info = Purchase::get_purchase_info($purchase_id);
        $isSeller = $this->isSeller();
        if($isSeller == 0){
            $purchase_info['contact_phone'] = '******';
            $purchase_info['contact_email'] = '******';
            $purchase_info['contact_name'] = '******';
        }
        $this->assign('purchase_info', $purchase_info);
        $this->assign('isseller', $isSeller);
        $this->display();
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

    private function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0){
        if ($id) {
            $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
                    " VALUES  ('$id', '$amount', '$type', '$is_paid')";
            $GLOBALS['db']->query($sql);
            $log_id = $GLOBALS['db']->getLastInsID();
        } else {
            $log_id = 0;
        }

        return $log_id;
    }
}
