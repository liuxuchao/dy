<?php

namespace App\Modules\Groupbuy\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    protected $user_id = 0;
    protected $goods_id = 0;
    protected $groupbuyid = 0;
    protected $region_id = 0;
    protected $area_info = [];
    protected $size = 10;

    public function __construct()
    {
        parent::__construct();
        //初始化位置信息
        $this->init_params();
        L(require(LANG_PATH . C('shop.lang') . '/other.php'));

        $this->area_id = $this->area_info['region_id'];
    }

    /**
     * 团购列表
     * @return
     */
    public function actionIndex()
    {
        $default_sort_order_method = C('sort_order_method') == '0' ? 'ASC' : 'DESC';
        if ($_REQUEST['sort'] == 'comments_number') {
            $default_sort_order_type = C('sort_order_type') == '0' ? 'start_time' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');
        } else {
            $default_sort_order_type = 'act_id';
        }
        if (IS_AJAX) {
            $this->sort = (isset($_REQUEST['sort']) && in_array(trim(strtolower($_REQUEST['sort'])), ['act_id', 'start_time', 'sales_volume', 'comments_number'])) ? trim($_REQUEST['sort']) : $default_sort_order_type;
            $this->order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), ['ASC', 'DESC'])) ? trim($_REQUEST['order']) : $default_sort_order_method;

            $page = I('post.page', 1, 'intval');
            $keywords = I('keyword');

            /* 取得团购活动总数 */
            $count = group_buy_count($keywords);
            $max_page = ($count > 0) ? ceil($count / $this->size) : 1;
            if ($page > $max_page) {
                $page = $max_page;
            }
            $gb_list = group_buy_list($this->size, $page, $keywords, $this->sort, $this->order);
            exit(json_encode(['gb_list' => $gb_list, 'totalPage' => ceil($count / $this->size)]));
        }

        // SEO标题
        $seo = get_seo_words('group');
        foreach ($seo as $key => $value) {
            $seo[$key] = html_in(str_replace(['{sitename}', '{key}', '{description}'], [C('shop.shop_name'), C('shop.shop_keywords'), C('shop.shop_desc')], $value));
        }

        $page_title = !empty($seo['title']) ? $seo['title'] : L('group_purchase_index');
        $keywords = !empty($seo['keywords']) ? $seo['keywords'] : C('shop.shop_keywords');
        $description = !empty($seo['description']) ? $seo['description'] : C('shop.shop_desc');

        $share_data = [
            'title' => $page_title,
            'desc' => $description,
            'link' => '',
            'img' => '',
        ];
        $this->assign('share_data', $this->get_wechat_share_content($share_data));
        $this->assign('page_title', $page_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);
        $this->display();
    }

    /**
     * 团购详情
     * @return
     */
    public function actionDetail()
    {
        $this->groupbuyid = I('id');
        if (!$this->groupbuyid) {
            ecs_header("Location: ./\n");
        }
        $group_buy = group_buy_info($this->groupbuyid);
        if (empty($group_buy)) {
            ecs_header("Location: ./\n");
            exit;
        }
        $group_buy['gmt_end_date'] = $group_buy['end_date'];
        $this->assign('group_buy', $group_buy);
        $this->assign('group_buy_id', $this->groupbuyid);
        $first_month_day = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
        $last_month_day = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
        $group_list = get_month_day_start_end_goods($this->groupbuyid, $first_month_day, $last_month_day);
        $this->assign('group_list', $group_list);

        // 该商铺的其他团购
        $merchant_group = get_merchant_group_goods($this->groupbuyid);
        $this->assign('merchant_group_goods', $merchant_group);
        $this->assign('look_top', get_top_group_goods('click_count'));
        $this->assign('buy_top', get_top_group_goods('sales_volume'));

        /* 取得团购商品信息 */
        $this->goods_id = $group_buy['goods_id'];
        $goods = goods_info($this->goods_id, $this->region_id, $this->area_id,$this->area_city);
        if (empty($goods)) {
            ecs_header("Location: ./\n");
            exit;
        }
        $sql = "SELECT count(*) FROM " . $this->ecs->table('collect_store') . " WHERE ru_id = " . $goods['user_id'];
        $collect_number = $this->db->getOne($sql);
        $this->assign('collect_number', $collect_number ? $collect_number : 0);
        //评分 start
        $mc_all = ments_count_all($this->goods_id);       //总条数
        $mc_one = ments_count_rank_num($this->goods_id, 1);        //一颗星
        $mc_two = ments_count_rank_num($this->goods_id, 2);        //两颗星
        $mc_three = ments_count_rank_num($this->goods_id, 3);    //三颗星
        $mc_four = ments_count_rank_num($this->goods_id, 4);        //四颗星
        $mc_five = ments_count_rank_num($this->goods_id, 5);        //五颗星
        $comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
        if ($goods['user_id'] > 0) {
            //商家所有商品评分类型汇总
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']);
            $this->assign('merch_cmt', $merchants_goods_comment);
        }
        $this->assign('comment_all', $comment_all);
        //客服start

        $sql = "select a.kf_im_switch, b.is_IM,a.ru_id,a.province, a.city, a.kf_type, a.kf_ww, a.kf_qq, a.meiqia, a.shop_name, a.kf_appkey,kf_secretkey from {pre}seller_shopinfo as a left join {pre}merchants_shop_information as b on a.ru_id=b.user_id where a.ru_id='" . $goods['user_id'] . "' ";
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
        if ($goods['user_id'] == 0) {
            //判断平台是否开启了IM在线客服
            if ($this->db->getOne("SELECT kf_im_switch FROM {pre}seller_shopinfo WHERE ru_id = 0")) {
                $basic_info['is_dsc'] = true;
                $im_dialog = M()->query('SHOW TABLES LIKE "{pre}im_dialog"');
                if($im_dialog){
                   $this->assign('kefu', 1);
                }
            } else {
                $basic_info['is_dsc'] = false;
            }
        } else {
            $basic_info['is_dsc'] = false;
        }
        $this->assign('basic_info', $basic_info);
        //客服end
        //查询一条好评
        $good_comment = get_good_comment($this->goods_id, 4, 1, 0, 1);
        $this->assign('good_comment', $good_comment);
        $this->assign('goods_id', $this->goods_id); //商品ID
        //新品
        $new_goods = get_recommend_goods('new', '', $this->region_id, $this->area_info['region_id'],$this->area_city, $goods['user_id']);
        $this->assign('new_goods', $new_goods);
        /* 读评论信息 */
        $this->assign('type', 0);
        $goods['url'] = build_uri('goods', ['gid' => $this->goods_id], $goods['goods_name']);
        $this->assign('goods', $goods);
        //获取商品的相册
        $sql = "SELECT * FROM {pre}goods_gallery WHERE goods_id = " . $this->goods_id;
        $goods_img = $this->db->query($sql);
        foreach ($goods_img as $key => $val) {
            $goods_img[$key]['img_url'] = get_image_path($val['img_url']);
        }
        $this->assign('goods_img', $goods_img);
        // 检查是否已经存在于用户的收藏夹
        if ($_SESSION ['user_id']) {
            $where['user_id'] = $_SESSION ['user_id'];
            $where['goods_id'] = $this->goods_id;
            $rs = $this->db->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $this->assign('goods_collect', 1);
            }
        }
        $warehouse_list = get_warehouse_list_goods();
        $this->assign('warehouse_list', $warehouse_list); //仓库列
        $this->assign('area_id', $this->area_info['region_id']); //地区ID
        $this->assign('warehouse_id', $this->region_id); //仓库名称
        $this->assign('region_id', $this->region_id); //商品仓库region_id
        $properties = get_goods_properties($this->goods_id, $this->region_id, $this->area_id);  // 获得商品的规格和属性
        $this->assign('properties', $properties['pro']);                              // 商品属性
        $default_spe = '';
        if ($properties['spe']) {
            foreach ($properties['spe'] as $k => $v) {
                if ($v['attr_type'] == 1) {
                    if ($v['is_checked'] > 0) {
                        foreach ($v['values'] as $key => $val) {
                            $default_spe .= $val['checked'] ? $val['label'] . '、' : '';
                        }
                    } else {
                        foreach ($v['values'] as $key => $val) {
                            if ($key == 0) {
                                $default_spe .= $val['label'] . '、';
                            }
                        }
                    }
                }
            }
        }
        $this->assign('specification', $properties['spe']); // 商品规格

        $position = assign_ur_here(0, $goods['goods_name']);

        // SEO标题
        $seo = get_seo_words('group_content');
        foreach ($seo as $key => $value) {
            $seo[$key] = html_in(str_replace(['{name}', '{description}'], [$group_buy['act_name'], $group_buy['act_desc']], $value));
        }

        $page_title = !empty($seo['title']) ? $seo['title'] : $position['title'];
        $keywords = !empty($seo['keywords']) ? $seo['keywords'] : (!empty($goods['keywords']) ? $goods['keywords'] : C('shop.shop_keywords'));
        $description = !empty($seo['description']) ? $seo['description'] : (!empty($goods['goods_brief']) ? $goods['goods_brief'] : C('shop.shop_desc'));
        // 微信JSSDK分享
        $share_data = [
            'title' => $page_title,
            'desc' => $description,
            'link' => '',
            'img' => $goods['goods_img'],
        ];
        $this->assign('share_data', $this->get_wechat_share_content($share_data));
        $this->assign('page_title', $page_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);

        // 查询关联商品描述
        $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$this->goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
        $link_desc = $this->db->getOne($sql);
        if (!empty($goods['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $goods['desc_mobile'], 'desc_mobile');
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $desc_preg['desc_mobile']);
            } else {
                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $goods['desc_mobile']);
            }
        }

        if (empty($goods['desc_mobile']) && !empty($goods['goods_desc'])) {
            if (C('shop.open_oss') == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $goods['goods_desc']);
            } else {
                $goods_desc = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . __STATIC__ . '/images/upload', $info['goods_desc']);
            }
        }
        if (empty($goods['desc_mobile']) && empty($goods['goods_desc'])) {
            $goods_desc = $link_desc;
        }
        if (!empty($goods_desc)) {
            $goods_desc = preg_replace("/height\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/width\=\"[0-9]+?\"/", "", $goods_desc);
            $goods_desc = preg_replace("/style=.+?[*|\"]/i", "", $goods_desc);
        }
        $this->assign('goods_desc', $goods_desc);

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
        $this->goods_id = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
        $attr_id = !empty($attr) ? explode(',', $attr) : [];
        $warehouse_id = I('request.warehouse_id', 0, 'intval');
        $this->area_id = I('request.area_id', 0, 'intval'); //仓库管理的地区ID
        $onload = I('request.onload', '', 'trim'); // 首次加载标识

        $goods = get_goods_info($this->goods_id, $warehouse_id, $this->area_id, $this->area_city);
        if ($this->goods_id == 0) {
            $res['err_msg'] = L('err_change_attr');
            $res['err_no'] = 1;
        } else {
            if ($number == 0) {
                $res['qty'] = $number = 1;
            } else {
                $res['qty'] = $number;
            }
            //ecmoban模板堂 --zhuo start
            $products = get_warehouse_id_attr_number($this->goods_id, $_REQUEST['attr'], $goods['user_id'], $warehouse_id, $this->area_id, $area_city);
            $attr_number = $products['product_number'];

            //贡云商品 获取库存
            if ($goods['cloud_id'] > 0) {
                $sql = "SELECT product_number,cloud_product_id FROM" . $GLOBALS['ecs']->table('products') . "WHERE product_id = '" . $products['product_id'] . "'";
                $product = $GLOBALS['db']->getRow($sql);
                $attr_number = get_jigon_products_stock($product);

            } else {
                if ($goods['model_attr'] == 1) {
                    $table_products = "products_warehouse";
                    $type_files = " AND warehouse_id = '$warehouse_id'";
                } elseif ($goods['model_attr'] == 2) {
                    $table_products = "products_area";
                    $type_files = " AND area_id = '$this->area_id'";
                    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                        $type_files .= " AND city_id = '$area_city' ";
                    }
                } else {
                    $table_products = "products";
                    $type_files = "";
                }
            }

            $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '$this->goods_id'" . $type_files . " LIMIT 0, 1";
            $prod = $GLOBALS['db']->getRow($sql);

            if (empty($prod)) { //当商品没有属性库存时
                $attr_number = $goods['goods_number'];
            }

            $attr_number = !empty($attr_number) ? $attr_number : 0;
            $res['attr_number'] = $attr_number;
        }
        die(json_encode($res));
    }

    /**
     * 立即团
     * @return
     */
    public function actionBuy()
    {
        $this->check_login();

        $warehouse_id = I('request.warehouse_id', 0, 'intval');
        $this->area_id = (isset($_REQUEST['area_id'])) ? intval($_REQUEST['area_id']) : 0; //仓库管理的地区ID

        /* 查询：取得参数：团购活动id */
        $this->groupbuyid = I('request.group_buy_id', 0, 'intval');
        if ($this->groupbuyid <= 0) {
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查询：取得数量 */
        $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
        $number = $number < 1 ? 1 : $number;

        /* 查询：取得团购活动信息 */
        $group_buy = group_buy_info($this->groupbuyid, $number);
        if (empty($group_buy)) {
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查询：检查团购活动是否是进行中 */
        if ($group_buy['status'] != GBS_UNDER_WAY) {
            show_message(L('gb_error_status'), '', '', 'error');
        }

        /* 查询：取得团购商品信息 */
        $goods = goods_info($group_buy['goods_id'], $this->region_id, $this->area_id,$this->area_city);
        if (empty($goods)) {
            ecs_header("Location: ./\n");
            exit;
        }

        $start_date = $group_buy['xiangou_start_date'];
        $end_date = $group_buy['xiangou_end_date'];
        $order_goods = get_for_purchasing_goods($start_date, $end_date, $group_buy['goods_id'], $_SESSION['user_id'], 'group_buy');
        $restrict_amount = $number + $order_goods['goods_number'];

        /* 查询：判断数量是否足够 */
        if ($group_buy['restrict_amount'] > 0 && $restrict_amount > $group_buy['restrict_amount']) {
            show_message(L('gb_error_restrict_amount'), '', '', 'error');
        } elseif ($group_buy['restrict_amount'] > 0 && ($number > ($group_buy['restrict_amount'] - $group_buy['valid_goods']))) {
            show_message(L('gb_error_goods_lacking'), '', '', 'error');
        }

        /* 查询：取得规格 */
        $specs = isset($_POST['goods_spec']) ? htmlspecialchars(trim($_POST['goods_spec'])) : '';

        /* 查询：如果商品有规格则取规格商品信息 配件除外 */
        if ($specs) {
            $_specs = explode(',', $specs);
            $product_info = get_products_info($goods['goods_id'], $_specs, $warehouse_id, $this->area_id, $this->area_city);
        }

        empty($product_info) ? $product_info = ['product_number' => 0, 'product_id' => 0] : '';

        if ($goods['model_attr'] == 1) {
            $table_products = "products_warehouse";
            $type_files = " and warehouse_id = '$warehouse_id'";
        } elseif ($goods['model_attr'] == 2) {
            $table_products = "products_area";
            $type_files = " and area_id = '$this->area_id'";
            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $type_files .= " AND city_id = '$area_city'";
            }
        } else {
            $table_products = "products";
            $type_files = "";
        }

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '" . $goods['goods_id'] . "'" . $type_files . " LIMIT 0, 1";
        $prod = $GLOBALS['db']->getRow($sql);

        /* 检查：库存 */
        if ($GLOBALS['_CFG']['use_storage'] == 1) {
            /* 查询：判断指定规格的货品数量是否足够 */
            if ($prod && $number > $product_info['product_number']) {
                show_message(L('gb_error_goods_lacking'), '', '', 'error');
            } else {
                /* 查询：判断数量是否足够 */
                if ($number > $goods['goods_number']) {
                    show_message(L('gb_error_goods_lacking'), '', '', 'error');
                }
            }
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = [];
        $sql = "SELECT a.attr_name, g.attr_value " .
            "FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS g, " .
            $GLOBALS['ecs']->table('attribute') . " AS a " .
            "WHERE g.attr_id = a.attr_id " .
            "AND g.goods_attr_id " . db_create_in($specs);
        $res = $GLOBALS['db']->query($sql);
        foreach ($res as $row) {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        /* 更新：清空购物车中所有团购商品 */
        clear_cart(CART_GROUP_BUY_GOODS);

        //ecmoban模板堂 --zhuo start

        $area_info = get_area_info($this->province_id);
        $this->area_id = $area_info['region_id'];

        $where = "regionId = '$this->province_id'";
        $date = ['parent_id'];
        $this->region_id = get_table_date('region_warehouse', $where, $date, 2);

        if (!empty($_SESSION['user_id'])) {
            $sess = "";
        } else {
            $sess = real_cart_mac_ip();
        }
        //ecmoban模板堂 --zhuo end

        /* 更新：加入购物车 */
        $goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];
        $cart = [
            'user_id' => $_SESSION['user_id'],
            'session_id' => $sess,
            'goods_id' => $group_buy['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $goods_price,
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            //ecmoban模板堂 --zhuo start
            'ru_id' => $goods['user_id'],
            'warehouse_id' => $this->region_id,
            'area_id' => $this->area_id,
            //ecmoban模板堂 --zhuo end
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_GROUP_BUY_GOODS,
            'is_gift' => 0
        ];
        $this->db->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');

        /* 更新：记录购物流程类型：团购 */
        $_SESSION['flow_type'] = CART_GROUP_BUY_GOODS;
        $_SESSION['extension_code'] = 'group_buy';
        $_SESSION['cart_value'] = '';
        $_SESSION['extension_id'] = $this->groupbuyid;
        $_SESSION['direct_shopping'] = 5;

        /* 进入收货人页面 */
        $_SESSION['browse_trace'] = "group_buy";
        $this->redirect('flow/index/index', ['direct_shopping' => 5]);
        exit;
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
        cookie('area_city ', $this->area_city);
        //ecmoban模板堂 --zhuo end 仓库
        $this->area_info = get_area_info($this->province_id);
    }
}
