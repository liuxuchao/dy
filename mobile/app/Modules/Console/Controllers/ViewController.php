<?php

namespace App\Modules\Console\Controllers;

use App\Modules\Base\Controllers\FrontendController;

class ViewController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: X-HTTP-Method-Override, Content-Type, x-requested-with, Authorization');
        $this->load_helper(['function', 'ecmoban']);
        $this->init_params();
    }

    /**
     * 编辑控制台
     */
    public function actionIndex()
    {
        $init_data = [];
        $init_data['app'] = C('shop.wap_app');
        $this->response(['init_data' => $init_data]);
    }

    /**
     * 公告
     */
    public function actionArticle()
    {
        if (IS_POST) {
            $article_msg = S('article_msg0');
            if (empty($article_msg)) {
                $cid = input('cat_id', 0, 'intval');
                ['cat_id' => $cid];
                $num = input('num', 10, 'intval');
                if ($num == 0) {
                    $limit = [];
                } else {
                    $limit = $num;
                }
                if ($cid == 0) {
                    $cid = $cid;
                } else {
                    $list = article_tree($cid);
                    foreach($list as $k => $val){
                        $res[$k] = isset($val['cat_id']) ? $val['cat_id'] : $val;
                    }
                    if ($res) {
                        array_unshift($res, $cid);
                        $cid = implode(',', $res);
                    } else {
                        $cid = $cid;
                    }
                }

                $sql = "SELECT article_id, link, title, add_time from ". $GLOBALS['ecs']->table('article') .
                    " where cat_id in (".$cid.") and is_open = 1 order by article_id DESC limit ". $num ." ";
                $article_msg = $GLOBALS['db']->getAll($sql);

                foreach ($article_msg as $key => $value) {
                    $article_msg[$key]['title'] = $value['title'];
                    $article_msg[$key]['url'] = url('article/index/detail', ['id' => $value['article_id']]);
                    $article_msg[$key]['date'] = local_date('Y-m-d H:i:s', $value['add_time']);
                }
                S('article_msg0', $article_msg);
            }
            $this->response(['error' => 0, 'article_msg' => $article_msg]);
        }
    }

    /**
     * 保存模块配置
     * post: /index.php?m=console&c=view&a=default
     * param $type  页面类型
     * param $id   页面ID
     * param $ru_id   商家ID
     * return int $id   返回默认页面ID
     */
    public function actionDefault()
    {
        if (IS_POST) {
            $type = input('type');
            $id = input('id');
            $ru_id = input('ruid');
            if ($ru_id) {
                $index = dao('touch_page_view')->where(['ru_id' => $ru_id, 'type' => $type])->getField('id');
                $this->response(['index' => $index]);
            } else {
                $index = dao('touch_page_view')->where(['ru_id' => 0, 'type' => 'index', 'default' => 1])->getField('id');
                if ($index) {
                    $this->response(['index' => $index]);
                } else {
                    $index = unserialize(str_replace('<?php exit("no access");', '', file_get_contents(ROOT_PATH . 'storage/app/diy/index.php')));
                    if (!empty($index)) {
                        $index[0]['data']['headerStyle']['bgStyle'] = "#f2f2f2";
                        $keep = [
                            'ru_id' => 0,
                            'type' => "old",
                            'page_id' => 0,
                            'title' => "old_index",
                            'data' => json_encode($index),
                            'default' => 3,
                            'review_status' => 3,
                            'is_show' => 1,
                        ];
                        //dao('touch_page_view')->add($keep);
                        $this->response(['type' => 'old', 'index' => $keep['data']]);
                    } else {
                        $data = str_replace('<?php exit("no access");', '', file_get_contents(ROOT_PATH . 'storage/app/diy/default.php'));
                        $keep = [
                            'ru_id' => 0,
                            'type' => 'index',
                            'title' => '首页',
                            'data' => $data,
                            'default' => 1,
                        ];
                        dao('touch_page_view')->add($keep);
                        $index = dao('touch_page_view')->where(['ru_id' => 0, 'type' => 'index', 'default' => 1])->getField('id');
                        $this->response(['index' => $index]);
                    }
                }
            }
        }
    }


    /**
     * 商品列表模块
     * post: /index.php?m=console&c=view&a=product
     * @param int cat_id  商品分类
     * @param string goods_id  商品ID 1,2,3
     * @param string type  推荐分类 best、new、hot
     */
    public function actionProduct()
    {
        if (IS_POST) {
            $number = input('number', 10);
            $user_id = input('ruid', 0, 'intval');
            $type = input('type');
            $cat_id = input('cat_id', 0, 'intval');
            $brand = input('brand_id', 0, 'intval');
            $warehouse_id = $this->region_id;
            $area_id = $this->area_info['region_id'];
            $goods_id = input('goods_id');
            // 商品模式
            if (!empty($goods_id)) {
                $goods_id = explode(',', $goods_id);
                $goods_cache = md5('goods0'.$goods_id.$number.serialize($_REQUEST));
                $goods = S($goods_cache);
                if ($goods === false) {
                    foreach ($goods_id as $key => $val) {
                        $row = dao('goods')->field('goods_id ,  goods_name , model_attr, product_promote_price, promote_start_date, promote_end_date,  sales_volume ,market_price , shop_price, goods_thumb, goods_img, goods_number ')->where(array('goods_id' => $val, 'is_on_sale' => 1, 'is_delete' => 0))->find();
                        if($row){
                            if ($row['promote_price'] > 0) {
                                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                            } else {
                                $promote_price = 0;
                            }
                            $price_info = get_goods_one_attr_price($row, $warehouse_id, $area_id, $promote_price);
                            $row = !empty($row) ? array_merge($row, $price_info) : $row;
                            $promote_price = empty($row['promote_price'])? $row['shop_price'] : $row['promote_price'] ;
                            $goods[$key]['shop_price'] = price_format($promote_price);
                            $goods[$key]['goods_number'] = $row['goods_number'];
                            $goods[$key]['goods_id'] = $row['goods_id'];
                            $goods[$key]['title'] = $row['goods_name'];
                            $goods[$key]['sale'] = $row['sales_volume'];
                            $goods[$key]['marketPrice'] = price_format($row['market_price']);
                            $goods[$key]['img'] = get_image_path($row['goods_thumb']);
                            $goods[$key]['goods_img'] = get_image_path($row['goods_img']);
                            $goods[$key]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                        }else{
                            $this->response(['error' => 0, 'product' => $row]);
                        }
                    }
                    S($goods_cache, $goods);
                }
                $this->response(['error' => 0, 'product' => $goods]);
            } else {
                // 分类模式
                $product_cache = md5('cat0'.$cat_id.$user_id.$type.$brand.$number.serialize($_REQUEST));
                $product = S($product_cache);
                if ($product === false) {
                    if ($cat_id == 0) {
                        $children = 0;
                    } else {
                        $children = get_children($cat_id);
                    }
                    $product = category_get_goods($children, $type, $brand, $user_id, '', $warehouse_id, $area_id, $number,$this->area_city);
                    if(empty($product) && $user_id > 0){
                        $product = category_get_goods(0, '', 0, $user_id, '', $warehouse_id, $area_id, $number,$this->area_city);
                    }
                    S($product_cache, $product);
                }

                $this->response(['error' => 0, 'product' => $product, 'type' => $type]);
            }
        }
    }

    /**
     * 已选则商品列表模块
     * post: /index.php?m=console&c=view&a=checked
     * @param string goods_id  商品ID 1,2,3
     */
    public function actionChecked()
    {
        if (IS_POST) {
            $goods_id = input('goods_id');
            // 商品模式
            if (!empty($goods_id)) {
                $goods_cache = md5('goods0'.$goods_id);
                $goods_id = explode(',', $goods_id);
                $goods = S($goods_cache);
                if ($goods === false) {
                    foreach ($goods_id as $key => $val) {
                        $row = dao('goods')->field('goods_id ,  goods_name , model_attr, product_promote_price, promote_start_date, promote_end_date,  sales_volume ,market_price , shop_price, goods_thumb, goods_img, goods_number ')->where(array('goods_id' => $val, 'is_on_sale' => 1, 'is_delete' => 0))->find();
                        if ($row) {
                            if ($row['promote_price'] > 0) {
                                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                            } else {
                                $promote_price = 0;
                            }
                            $price_info = get_goods_one_attr_price($row, $warehouse_id, $area_id, $promote_price);
                            $row = !empty($row) ? array_merge($row, $price_info) : $row;
                            $promote_price = empty($row['promote_price'])? $row['shop_price'] : $row['promote_price'] ;
                            $goods[$key]['shop_price'] = price_format($promote_price);
                            $goods[$key]['goods_number'] = $row['goods_number'];
                            $goods[$key]['goods_id'] = $row['goods_id'];
                            $goods[$key]['title'] = $row['goods_name'];
                            $goods[$key]['sale'] = $row['sales_volume'];
                            $goods[$key]['marketPrice'] = price_format($row['market_price']);
                            $goods[$key]['shop_price'] = price_format($row['shop_price']);
                            $goods[$key]['img'] = get_wechat_image_path($row['goods_thumb']);
                            $goods[$key]['goods_img'] = get_wechat_image_path($row['goods_img']);
                            $goods[$key]['url'] = build_uri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
                        }
                    }
                    S($goods_cache, $goods);
                }

                $this->response(['error' => 0, 'product' => $goods]);
            }
            $this->response(['error' => 1]);
        }
    }

    /**
     * 秒杀模块
     * post: /index.php?m=console&c=view&a=seckill
     * @return arr
     */
    public function actionSeckill()
    {
        $now = gmtime() + 28800;
        $number = input('num', 10, 'intval');
        $sql = "SELECT *  FROM " . $GLOBALS['ecs']->table('seckill_time_bucket') . " ORDER BY begin_time ASC ";
        $sec = $GLOBALS['db']->getall($sql);
        if (!empty($sec)) {
            foreach ($sec as $key => $val) {
                $sql = "SELECT stb.* " . " FROM " . $GLOBALS['ecs']->table('seckill_time_bucket') . " AS stb " . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill_goods') . " AS sg " . "ON stb.id = sg.tb_id " . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . " AS s " . "ON sg.sec_id = s.sec_id " . " WHERE s.is_putaway = 1 AND s.review_status = 3 AND s.begin_time <= $now AND s.acti_time > $now AND stb.id = " . $val['id'];
                $sec[$key] = $GLOBALS['db']->getrow($sql);
            }
            if (empty($sec)) {
                $this->response(['error' => 0, 'seckill' => '']);
            }
        } else {
            $this->response(['error' => 0, 'seckill' => '']);
        }

        foreach ($sec as $key => $val) {
            $sec[$key]['begin_time'] = local_strtotime($val['begin_time']) + 28800;
            $sec[$key]['end_time'] = local_strtotime($val['end_time']) + 28800;
            if ($now > $sec[$key]['begin_time'] && $now < $sec[$key]['end_time']) {
                $arr['id'] = $val['id'];
                $arr['begin_time'] = $sec[$key]['begin_time'];
                $arr['end_time'] = $sec[$key]['end_time'];
                $arr['type'] = 1;
            } elseif ($now < $sec[$key]['begin_time']) {
                $all[$key]['id'] = $val['id'];
                $all[$key]['begin_time'] = $sec[$key]['begin_time'];
                $all[$key]['end_time'] = $sec[$key]['end_time'];
                $all[$key]['type'] = 0;
            }
        }
        if (!empty($all)) {
            $allsec = array_values($all);
        }
        if (empty($arr['type'])) {
            $arr = '';
            $len = count($allsec);
            for ($i = 0; $i < $len; $i++) {
                if ($i == 0) {
                    $arr = $allsec[$i];
                    continue;
                }
                if ($allsec[$i]['begin_time'] < $arr['begin_time']) {
                    $arr = $allsec[$i];
                }
            }
        }
        if (empty($arr['id'])) {
            $this->response(['error' => 0, 'seckill' => '']);
            exit;
        }
        $sql = "SELECT sg.id, sg.tb_id, sg.goods_id, sg.sec_price, sg.sec_num " . " FROM " . $GLOBALS['ecs']->table('seckill_goods') . " AS sg " . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . " AS s " . "ON sg.sec_id = s.sec_id " . " WHERE s.is_putaway = 1 AND s.review_status = 3 AND s.begin_time <= $now AND s.acti_time > $now AND sg.tb_id = " . $arr['id'] . " limit $number";
        $secgoods = $GLOBALS['db']->getall($sql);
        foreach ($secgoods as $key => $value) {
            $arr['goods'][$key]['goods_id'] = $value['goods_id'];
            $arr['goods'][$key]['price'] = $value['sec_price'];
            $arr['goods'][$key]['stock'] = $value['sec_num'];
            $goods = dao('goods')->field('goods_name,market_price, goods_thumb')->where(['goods_id' => $value['goods_id']])->find();
            $arr['goods'][$key]['marketPrice'] = $goods['market_price'];
            $arr['goods'][$key]['title'] = $goods['goods_name'];
            $arr['goods'][$key]['img'] = get_image_path($goods['goods_thumb']);
            $arr['goods'][$key]['url'] = url('seckill/index/detail', ['id' => $value['id'], 'tmr' => 0]);
        }
        $this->response(['error' => 0, 'seckill' => $arr]);
    }

    /**
     * 店铺街
     */
    public function actionStore()
    {
        if (IS_POST) {
            $number = input('number', 10);
            $childrenNumber = input('childrenNumber', 3, 'intval');
            $cache_id = md5('store0'.$number.$childrenNumber);
            $store = S($cache_id);
            if ($store === false) {
                $sql = "SELECT ms.shop_id,ms.user_id, ms.rz_shopName, ss.logo_thumb, ss.street_thumb " .
                    " FROM " . $GLOBALS['ecs']->table('merchants_shop_information') . " AS ms " .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('seller_shopinfo') . " AS ss " .
                    " ON ms.user_id = ss.ru_id " .
                    " WHERE ss.shop_close = 1 AND ms.is_street = 1 " .
                    " order by ms.sort_order ASC " .
                    " limit  0, $number";
                $store = $GLOBALS['db']->getAll($sql);
                foreach ($store as $key => $value) {
                    $sql = "SELECT goods_name, goods_thumb " .
                        " FROM " . $GLOBALS['ecs']->table('goods') .
                        " WHERE user_id = '" . $value['user_id'] . "' " .
                        " limit 0, $childrenNumber";
                    $goods = $GLOBALS['db']->getAll($sql);
                    foreach ($goods as $a => $val) {
                        $goods[$a]['goods_thumb'] = get_image_path($val['goods_thumb']);
                    }
                    $store[$key]['goods'] = $goods;
                    $store[$key]['total'] = count($goods);
                    $store[$key]['logo_thumb'] = get_image_path(ltrim($value['logo_thumb'], "../"));
                    $store[$key]['street_thumb'] = get_image_path($value['street_thumb']);
                }
                S($cache_id, $store);
            }
            $this->response(['error' => 0, 'store' => $store, 'total' => count($store)]);
        }
    }

    /**
     * 店铺街详情
     */
    public function actionStoreIn()
    {
        if (IS_POST) {
            $ru_id = input('ruid');
            $time = gmtime();

                $sql = "SELECT ms.shop_id, ms.user_id, ms.rz_shopName, ss.logo_thumb, ss.street_thumb " .
                    " FROM " . $GLOBALS['ecs']->table('merchants_shop_information') . " AS ms " .
                    " LEFT JOIN " . $GLOBALS['ecs']->table('seller_shopinfo') . " AS ss " .
                    " ON ms.user_id = ss.ru_id " .
                    " WHERE ms.user_id = $ru_id ";
                $store = $GLOBALS['db']->getAll($sql);
                foreach ($store as $key => $value) {
                    $sql = "SELECT count(*) " .
                        " FROM " . $GLOBALS['ecs']->table('goods') .
                        " WHERE user_id = '" . $value['user_id'] . "' AND is_on_sale = 1 AND is_delete = 0 and is_alone_sale = 1 AND review_status >2 ";
                    $goods = $GLOBALS['db']->getOne($sql);

                    $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('goods') .
                        " where user_id = '" .$value['user_id']. "' AND is_on_sale = 1 AND is_delete = 0 AND is_alone_sale = 1 AND review_status > 2 AND store_new = 1 ";
                    $new = $GLOBALS['db']->getOne($sql);

                    $sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('goods') .
                        " where user_id = '" .$value['user_id']. "' AND is_on_sale = 1 AND is_delete = 0 AND is_alone_sale = 1 AND review_status > 2 AND is_promote = 1 AND promote_start_date < '".$time."' AND promote_end_date > '".$time."' ";
                    $promote = $GLOBALS['db']->getOne($sql);
                    $store[$key]['total'] = $goods;
                    $store[$key]['new'] = $new;
                    $store[$key]['promote'] = $promote;
                    $store[$key]['logo_thumb'] = get_image_path(ltrim($value['logo_thumb'], "../"));
                    $store[$key]['street_thumb'] = get_image_path($value['street_thumb']);

                    $sql = "SELECT count(user_id) as a FROM {pre}collect_store WHERE ru_id = " . $value['user_id'] . " AND user_id = " . $_SESSION['user_id'];
                    $follow = $this->db->getOne($sql);
                    $store[$key]['count_gaze'] = empty($follow) ? 0 : 1;
                    $sql = "SELECT count(ru_id) as a FROM {pre}collect_store WHERE ru_id = " . $value['user_id'];
                    $like_num = $this->db->getOne($sql);
                    $store[$key]['like_num'] = empty($like_num) ? 0 : $like_num;
                }

            $this->response(['store' => $store]);
        }
    }

    /**
     * 店铺街详情底部
     */
    public function actionStoreDown()
    {
        if (IS_POST) {
            $ru_id = input('ruid');
            $sql = "SELECT ms.shop_id, ms.user_id, ms.is_IM, ms.rz_shopName, ss.kf_qq, ss.kf_ww, ss.meiqia " .
                " FROM " . $GLOBALS['ecs']->table('merchants_shop_information') . " AS ms " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('seller_shopinfo') . " AS ss " .
                " ON ms.user_id = ss.ru_id  " .
                " WHERE ms.user_id = $ru_id  ";
            $shop = $GLOBALS['db']->getAll($sql);
            //判断平台是否开启了IM在线客服
            $kf_im_switch = dao('seller_shopinfo')->where(['ru_id' => 0])->getField('kf_im_switch');
            $customer_service = dao('shop_config')->where(['code' => 'customer_service'])->getField('value');
            $im_dialog = M()->query('SHOW TABLES LIKE "{pre}im_dialog"');
            foreach ($shop as $key => $value) {
                $store[$key]['shop_id'] = $value['shop_id'];
                $store[$key]['user_id'] = $value['user_id'];
                $store[$key]['rz_shopName'] = $value['rz_shopName'];
                $store[$key]['shop_category'] = get_user_store_category($value['user_id']);
                $store[$key]['shop_about'] = url('store/index/shop_about', ['ru_id' => $value['user_id']]);
                if($customer_service == 0){
                    if($kf_im_switch == 1 && $im_dialog){
                        $store[$key]['kf'] = url('chat/index/index', array('ru_id' => 0));
                    }else{
                        if($value['is_im'] == 1 ){
                            $store[$key]['kf'] = url('chat/yunwang/index', array('ru_id'=> $ru_id));
                        }elseif($value['meiqia']){
                            $store[$key]['kf'] = "javascript:meiqia_chat();";
                            $store[$key]['meiqia'] = $value['meiqia'];
                        }else{
                            $zkf = dao('seller_shopinfo')->field('kf_type, kf_qq, kf_ww')->where(['ru_id' => '0'])->find();
                            if ($zkf['kf_type'] == 1) {
                                $store[$key]['kf'] ="https://www.taobao.com/webww/ww.php?ver=3&touid=".preg_replace('/^[^\-]*\|/is', '', $zkf['kf_ww'])."&siteid=cntaobao&status=1&charset=utf-8" ;
                            } else {
                                if ($value['kf_qq']) {
                                    $store[$key]['kf'] = "https://wpa.qq.com/msgrd?v=3&uin=".preg_replace('/^[^\-]*\|/is', '', $zkf['kf_qq'])."&site=qq&menu=yes" ;
                                }
                            }
                        }
                    }
                }else{
                    if($kf_im_switch == 1 && $im_dialog){
                        $store[$key]['kf'] = url('chat/index/index', array('ru_id'=> $ru_id));
                    }else{
                        if($value['is_im'] == 1 ){
                            $store[$key]['kf'] = url('chat/yunwang/index', array('ru_id'=> $ru_id));
                        }elseif($value['meiqia']){
                            $store[$key]['kf'] = "javascript:meiqia_chat();";
                            $store[$key]['meiqia'] = $value['meiqia'];
                        }else{
                            if ($value['kf_ww']) {
                                $store[$key]['kf'] ="https://www.taobao.com/webww/ww.php?ver=3&touid=".preg_replace('/^[^\-]*\|/is', '', $value['kf_ww'])."&siteid=cntaobao&status=1&charset=utf-8" ;
                            } else {
                                if ($value['kf_qq']) {
                                    $store[$key]['kf'] = "https://wpa.qq.com/msgrd?v=3&uin=".preg_replace('/^[^\-]*\|/is', '', $value['kf_qq'])."&site=qq&menu=yes" ;
                                }
                            }
                        }
                    }
                }
            }
            $this->response(['store' => $store]);
        }
    }

    /**
     * 红包
     */
    public function actionStoreBonus()
    {
        if (IS_POST) {
            $ru_id = input('ruid');
            $sql = "SELECT * FROM {pre}coupons WHERE (`cou_type` = 3 OR `cou_type` = 4 ) AND `cou_end_time` > $time AND (( instr(`cou_ok_user`, $_SESSION[user_rank]) ) or (`cou_ok_user`=0)) AND review_status = 3 AND ru_id='" . $ru_id . "' ";
            $info = $this->db->getAll($sql);
            foreach ($info as $key => $val) {
                $info[$key]['cou_man'] = intval($val['cou_man']);
                $info[$key]['cou_money'] = intval($val['cou_money']);
            }
            $bonus = $info;
            $this->response(['store' => $bonus]);
        }
    }

    /**
     * 关注店铺
     */
    public function actionAddCollect()
    {
        $time = gmtime();
        $shopid = input('ruid', 0, 'intval');
        if (!empty($shopid) && $_SESSION['user_id'] > 0) {
            $status = dao('collect_store')->field('user_id, rec_id')->where(['ru_id' => $shopid, 'user_id' => $_SESSION['user_id']])->find();
            if (count($status) > 0) {
                dao('collect_store')->where(['rec_id' => $status['rec_id']])->delete();
                die(json_encode(['error' => 2, 'msg' => L('cancel_attention')]));
            } else {
                dao('collect_store')->data(['user_id' => $_SESSION['user_id'], 'ru_id' => $shopid, 'add_time' => $time, 'is_attention' => '1'])->add();
                die(json_encode(['error' => 1, 'msg' => L('attentioned')]));
            }
        } else {
            die(json_encode(['error' => 0, 'msg' => L('please_login')]));
        }
    }

    /**
     * 显示页面
     * param: $id 页面ID
     * param: $type 页面类型
     * return:
     */
    public function actionView()
    {
        if (IS_POST) {
            $default = input('default');
            $id = input('id');
            $type = input('type');
            $ru_id = input('ruid', 0, 'intval');
            $number = input('number', 10);
            $page_id = input('page_id', 0, 'intval');
            if ($id) {
                $view = dao('touch_page_view')->field('type, title, thumb_pic, data, default')->where(['id' => $id])->find();
            } elseif ($default < 2) {
                if ($number == 0) {
                    $view = dao('touch_page_view')->field('id , type ,  title ,  pic ,thumb_pic , default ')->where(['default' => $default, 'ru_id' => $ru_id, 'page_id' => $page_id])->order('update_at DESC')->select();
                } elseif ($number > 0) {
                    $view = dao('touch_page_view')->field('id , type ,  title ,  pic ,thumb_pic , default ')->where(['default' => $default, 'ru_id' => $ru_id, 'page_id' => $page_id])->order('update_at DESC')->limit($number)->select();
                }
            } elseif ($default == 3) {
                if ($number == 0) {
                    $view = dao('touch_page_view')->field('id , type ,  title ,  pic ,thumb_pic , default ')->order('update_at DESC')->select();
                } elseif ($number > 0) {
                    $view = dao('touch_page_view')->field('id , type ,  title ,  pic ,thumb_pic , default ')->where(['ru_id' => $ru_id])->order('update_at DESC')->limit($number)->select();
                }
            } else {
                $view = dao('touch_page_view')->field('id , type ,  title , data,  pic ,thumb_pic , default ')->where(['ru_id' => $ru_id, 'type' => $type])->order('update_at DESC')->select();
            }
            $view['data'] = str_replace('7ee458', 'f2f2f2', $view['data']);
            $view['data'] = str_replace('../data/gallery_album/original_img/5951ceab15b33.jpg', get_image_path(ltrim('../data/gallery_album/original_img/5951ceab15b33.jpg', "../")), $view['data']);
            $navigation = file_get_contents(DATA_PATH . '/navigation.php');
            if (empty($navigation)) {
                $navigation = str_replace('<?php', '', file_get_contents(ROOT_PATH . 'config/navigation.php'));
            }
            $this->response(['error' => 0, 'view' => $view, 'navigation' => $navigation]);
        }
    }

    /**
     * 搜索
     */
    public function actionSearch()
    {
        if (IS_POST) {
            $title = input('title');
            $ru_id = input('ruid', 0, 'intval');
            $default = input('default', 0, 'intval');
            $view = dao('touch_page_view')->field('id, pic, title, default, type')->where(['title' => $title, 'ru_id' => $ru_id, 'default' => $default])->order('update_at DESC')->select();

            $this->response(['error' => 0, 'view' => $view]);
        }
    }

    /*
     *商品列表
     * post: /index.php?m=console&c=view&a=goodslist
     */
    public function actionGoodsList()
    {
        if (IS_POST) {
            $goods_id = input('goods_id');
            $pageSize = input('pageSize', 10, 'intval');
            $currentPage = input('currentPage', 1, 'intval');
            if ($currentPage == 1) {
                $current = 0;
            } else {
                $current = ($currentPage - 1) * $pageSize;
            }
            $wherenum = "  LIMIT $current , $pageSize ";
            $sql = "SELECT * from ". $GLOBALS['ecs']->table('goods') .
                    " where goods_id in (".$goods_id.") $wherenum ";
            $goodslist = $GLOBALS['db']->getAll($sql);
            foreach ($goodslist as $key => $val) {
                $goodslist[$key]['url'] = url('goods/index/index', ['id' => $val['goods_id'], 'u' => $_SESSION['user_id']]);
                $goodslist[$key]['goods_img'] = get_image_path($val['goods_img']);
                $goodslist[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
            }
            $this->response([ 'goodslist' => $goodslist]);
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

            $goods_warehouse = get_warehouse_goods_region($this->province_id);
            $this->region_id = $goods_warehouse['region_id'];
        }
        $other = [
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
        ];
        $warehouse_area_info = get_warehouse_area_info($other);
        $this->area_city = $warehouse_area_info['city_id'];
        cookie('area_city', $this->area_city);
        //ecmoban模板堂 --zhuo end 仓库
        $this->area_info = get_area_info($this->province_id);
    }
}
