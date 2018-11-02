<?php

/*
 * 取得购物车条件
 */
function sess()
{
    //ecmoban模板堂 --zhuo start
    if (!empty($_SESSION['user_id'])) {
        $info['sess_id'] = " user_id = '" . $_SESSION['user_id'] . "' ";

        $info['a_sess'] = " a.user_id = '" . $_SESSION['user_id'] . "' ";
        $info['b_sess'] = " b.user_id = '" . $_SESSION['user_id'] . "' ";
        $info['c_sess'] = " c.user_id = '" . $_SESSION['user_id'] . "' ";

        $info['sess_cart'] = "";
    } else {
        $info['sess_id'] = " session_id = '" . real_cart_mac_ip() . "' ";

        $info['a_sess'] = " a.session_id = '" . real_cart_mac_ip() . "' ";
        $info['b_sess'] = " b.session_id = '" . real_cart_mac_ip() . "' ";
        $info['c_sess'] = " c.session_id = '" . real_cart_mac_ip() . "' ";

        $info['sess_cart'] = real_cart_mac_ip();
    }
    //ecmoban模板堂 --zhuo ends
    return $info;
}

/**
 * 删除购物车中的商品
 *
 * @access public
 * @param integer $id
 * @return void
 */
function flow_drop_cart_goods($id)
{
    $sess = sess();
    /* 取得商品id */
    $sql = "SELECT * FROM {pre}cart WHERE rec_id = '$id'";
    $row = $GLOBALS['db']->getRow($sql);
    //删除购物车中不能单独销售的商品
    flow_clear_cart_alone();
    if ($row) {
        // 如果是超值礼包
        if ($row ['extension_code'] == 'package_buy') {
            $sql = "DELETE FROM {pre}cart WHERE " . $sess['sess_id'] . " AND rec_id = '$id' LIMIT 1";
        } // 如果是普通商品，同时删除所有赠品及其配件
        elseif ($row ['parent_id'] == 0 && $row ['is_gift'] == 0) {
            /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
            $sql = "SELECT c.rec_id
				FROM {pre}cart AS c, {pre}group_goods AS gg, {pre}goods AS g
				WHERE gg.parent_id = '" . $row ['goods_id'] . "'
				AND c.goods_id = gg.goods_id
				AND c.parent_id = '" . $row ['goods_id'] . "'
				AND c.extension_code <> 'package_buy'
				AND gg.goods_id = g.goods_id
				AND g.is_alone_sale = 0";
            $res = $GLOBALS['db']->getAll($sql);
            $_del_str = $id . ',';
            foreach ($res as $id_alone_sale_goods) {
                $_del_str .= $id_alone_sale_goods ['rec_id'] . ',';
            }
            $_del_str = trim($_del_str, ',');

            $sql = "DELETE FROM {pre}cart WHERE " . $sess['sess_id'] . " AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
        } // 如果不是普通商品，只删除该商品即可
        else {
            $sql = "DELETE FROM {pre}cart WHERE " . $sess['sess_id'] . " AND rec_id = '$id' LIMIT 1";
        }
        $result = $GLOBALS['db']->query($sql);
    }
    return $result ? $result : false;
}


/**
 * 删除购物车中不能单独销售的商品
 *
 * @access public
 * @return void
 */
function flow_clear_cart_alone()
{
    $sess = sess();
    /* 查询：购物车中所有不可以单独销售的配件 */
    $sql = "SELECT c.rec_id, gg.parent_id
		FROM {pre}cart AS c
		LEFT JOIN {pre}group_goods AS gg ON c.goods_id = gg.goods_id
		LEFT JOIN {pre}goods AS g ON c.goods_id = g.goods_id
		WHERE " . $sess['c_sess'] . "
		AND c.extension_code <> 'package_buy'
		AND gg.parent_id > 0
		AND g.is_alone_sale = 0";
    $res = $GLOBALS['db']->query($sql);
    $rec_id = [];
    foreach ($res as $row) {
        $rec_id [$row ['rec_id']] [] = $row ['parent_id'];
    }
    if (empty($rec_id)) {
        return;
    }

    /* 查询：购物车中所有商品 */
    $sql = "SELECT DISTINCT goods_id
		FROM {pre}cart WHERE " . $sess['sess_id'] . "
		AND extension_code <> 'package_buy'";
    $res = $GLOBALS['db']->query($sql);
    $cart_good = [];
    foreach ($res as $row) {
        $cart_good [] = $row ['goods_id'];
    }
    if (empty($cart_good)) {
        return;
    }

    /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
    $del_rec_id = '';
    foreach ($rec_id as $key => $value) {
        foreach ($value as $v) {
            if (in_array($v, $cart_good)) {
                continue 2;
            }
        }

        $del_rec_id = $key . ',';
    }
    $del_rec_id = trim($del_rec_id, ',');

    if ($del_rec_id == '') {
        return;
    }

    /* 删除 */
    $sql = "DELETE FROM {pre}cart WHERE " . $sess['sess_id'] . "
    AND rec_id IN ($del_rec_id)";
    $GLOBALS['db']->query($sql);
}


/**
 * 取得某用户等级当前时间可以享受的优惠活动  - 凑单列表
 * @param   int     $user_rank      用户等级id，0表示非会员
 * @param int $user_id 商家id
 * @return  array
 */
function favourable_goods_list($user_rank, $favourable_id, $sort = '', $order = '', $size, $page, $warehouse_id = 0, $area_id = 0)
{
    if ($sort) {
        $sort = " ORDER BY g.$sort ";
    }
    /* 当前用户可享受的优惠活动 */
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();

    $select = '';
    if ($GLOBALS['_CFG']['region_store_enabled']) {
        $select .= " userFav_type_ext, rs_id, ";
    }

    $sql = "SELECT act_range_ext, act_range, userFav_type, $select user_id " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND review_status = 3 AND start_time <= '$now' AND end_time >= '$now' AND act_id = '$favourable_id' ";
    $favourable = $GLOBALS['db']->getRow($sql);
    $arr = [];
    $totalpage = 0;
    $where = '';
    if (!empty($favourable)) {
        if ($favourable['act_range'] == FAR_ALL) {
            $ext = true;
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                /* 设置的使用范围 卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if($mer_ids){
                    $where .= " AND g.user_id ".db_create_in($mer_ids);
                }
                if ($favourable['userFav_type_ext']) {
                   $ext = false;
                }
            }
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            $ext = true;
            // 按分类
            $id_list = [];
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {

                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = get_array_keys_cat(intval($id));

                $id_list = array_merge($id_list, $cat_keys);
            }
            $where .= " AND g.cat_id " . db_create_in($id_list);
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            // 按品牌
            $id_list = explode(',', $favourable['act_range_ext']);
            $where .= " AND g.brand_id " . db_create_in($id_list);
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            $ext = true;
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                /* 设置的使用范围 msj卖场优惠活动 liu */
                $mer_ids = get_favourable_merchants($favourable['userFav_type'], $favourable['userFav_type_ext'], $favourable['rs_id']);
                if($mer_ids){
                    $where .= " AND g.user_id ".db_create_in($mer_ids);
                }
                if ($favourable['userFav_type_ext']) {
                   $ext = false;
                }
            }
            // 按商品分类
            $id_list = explode(',', $favourable['act_range_ext']);
            $where .= " AND g.goods_id " . db_create_in($id_list);
        }

        if ($favourable['userFav_type'] == 0 && $ext) {
            $where .= " AND g.user_id = '" .$favourable['user_id']. "'";
        }

        $sql = "SELECT g.goods_id, " . "IFNULL(IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]'), g.shop_price * '$_SESSION[discount]')  AS shop_price," .
            " g.goods_name, g.goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "AS g " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
            " ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('warehouse_goods') . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' " .
            " LEFT JOIN " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' " .
            " WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 $where $sort $order";

        $total_query = $GLOBALS['db']->query($sql);
        $total = is_array($total_query) ? count($total_query) : 0;
        $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

        if ($res && $total > 0) {
            $totalpage = ceil($total / $size);
            foreach ($res as $key => $row) {
                $arr[$key]['goods_id'] = $row['goods_id'];
                $arr[$key]['goods_name'] = $row['goods_name'];
                $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
                $arr[$key]['format_shop_price'] = price_format($row['shop_price']);
                $arr[$key]['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
            }
        }
    }

    return ['list' => array_values($arr), 'totalpage' => $totalpage];
}

// 取得当前活动 已经加入购物车的商品
function cart_favourable_goods($user_rank, $favourable_id, $warehouse_id = 0, $area_id = 0)
{
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();

    $sql = "SELECT act_range_ext, act_range, userFav_type, user_id " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND review_status = 3 AND start_time <= '$now' AND end_time >= '$now' AND act_id = '$favourable_id' ";
    $favourable = $GLOBALS['db']->getRow($sql);

    // 增加查询条件
    if (!empty($_SESSION['user_id'])) {
        $c_sess = " c.user_id = '" . $_SESSION['user_id'] . "' ";
    } else {
        $c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
    }
    $cart_favourable_goods = [];

    if (!empty($favourable)) {

        /* 查询优惠范围内购物车的商品 */
        $sql = "SELECT c.rec_id, c.goods_number, g.goods_id, g.goods_thumb, g.goods_name, c.goods_price AS shop_price" .
                " FROM " . $GLOBALS['ecs']->table('cart') . " AS c JOIN " . $GLOBALS['ecs']->table('goods') . " AS g " .
                " ON c.goods_id = g.goods_id ".
                " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                " LEFT JOIN " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' " .
                " LEFT JOIN " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' " .
                " WHERE " .$c_sess. " AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
                " AND c.is_gift = 0 " .
                " AND c.parent_id = 0 " .
                " AND c.is_invalid = 0 " .
                " AND c.goods_id > 0 " ; //ecmoban模板堂 --zhuo

        /* 根据优惠范围修正sql */
        if ($favourable['act_range'] == FAR_ALL) {
            $ext = true;
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                if ($favourable['userFav_type_ext']) {
                   $ext = false;
                }
            }
        } elseif ($favourable['act_range'] == FAR_CATEGORY) {
            $ext = true;
            /* 取得优惠范围分类的所有下级分类 */
            $id_list = array();
            $cat_list = explode(',', $favourable['act_range_ext']);
            foreach ($cat_list as $id) {

                /**
                 * 当前分类下的所有子分类
                 * 返回一维数组
                 */
                $cat_keys = get_array_keys_cat(intval($id));
                $id_list = array_merge($id_list, $cat_keys);
            }

            $sql .= " AND g.cat_id " . db_create_in($id_list);
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $id_list = explode(',', $favourable['act_range_ext']);

            $sql .= " AND g.brand_id " . db_create_in($id_list);
        } elseif ($favourable['act_range'] == FAR_GOODS) {
            $ext = true;
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                if ($favourable['userFav_type_ext']) {
                   $ext = false;
                }
            }
            $id_list = explode(',', $favourable['act_range_ext']);

            $sql .= " AND g.goods_id " . db_create_in($id_list);
        }

        if ($favourable['userFav_type'] == 0 && $ext) {
            $sql .= " AND g.user_id = '" . $favourable['user_id']. "' ";
        }

        /* 优惠范围内的商品总额 */
        $res =  $GLOBALS['db']->query($sql);

        foreach ($res as $key => $row) {
            $cart_favourable_goods[$key]['rec_id'] = $row['rec_id'];
            $cart_favourable_goods[$key]['goods_id'] = $row['goods_id'];
            $cart_favourable_goods[$key]['goods_name'] = $row['goods_name'];
            $cart_favourable_goods[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
            $cart_favourable_goods[$key]['shop_price'] = number_format($row['shop_price'], 2, '.', '');
            $cart_favourable_goods[$key]['goods_number'] = $row['goods_number'];
            $cart_favourable_goods[$key]['goods_url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
        }
    }

    return $cart_favourable_goods;
}

// 获取优惠活动类型 满赠-满减-打折
function get_act_type($user_rank, $favourable_id)
{
    $user_rank = ',' . $user_rank . ',';
    $now = gmtime();

    $sql = "SELECT act_name, act_type, min_amount, act_type_ext, gift " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND review_status = 3 AND start_time <= '$now' AND end_time >= '$now' AND act_id = '$favourable_id' ";

    $selected = $GLOBALS['db']->getRow($sql);

    $act_type_txt = '';
    if (!empty($selected)) {
        switch ($selected['act_type']) {
            case 0:
                $act_type_txt = "<em class='em-promotion'>". L('with_a_gift') ."</em> " . " 满 " . $selected['min_amount'] . " 元可换购赠品";
                break;
            case 1:
                $act_type_txt = "<em class='em-promotion'>". L('full_reduction') ."</em> " . " 满 " . $selected['min_amount'] . " 元可享受减免 ".$selected['act_type_ext'] . " 元 ";
                break;
            case 2:
                $act_type_txt = "<em class='em-promotion'>". L('discount') ."</em> " . " 满 " . $selected['min_amount'] . " 元可享受折扣 ";
                break;
            default:
                break;
        }
    }

    return $act_type_txt;
}

/**
 * 添加优惠活动（赠品）到购物车
 * @param   int     $act_id     优惠活动id
 * @param   int     $id         赠品id
 * @param   float   $price      赠品价格
 */
function add_gift_to_cart($act_id, $id, $price, $ru_id)
{
    //ecmoban模板堂 --zhuo start
    if(!empty($_SESSION['user_id'])){
        $sess = "";
    }else{
        $sess = real_cart_mac_ip();
    }
    //ecmoban模板堂 --zhuo end
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('cart') . " (" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, ".
                "goods_number, is_real, extension_code, parent_id, rec_type, is_gift, add_time, ru_id, act_id ) ".
            "SELECT '$_SESSION[user_id]', '" . $sess . "', goods_id, goods_sn, goods_name, market_price, ".
                "'$price', 1, is_real, extension_code, 0, '" . CART_GENERAL_GOODS . "', '$act_id', ".gmtime().", '$ru_id', '$act_id' " .
            "FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_id = '$id'";
    $GLOBALS['db']->query($sql);
}

/* 获取购物车中同一活动下的商品和赠品 -qin
 *
 * 来源flow.php 转移函数
 *
 * $favourable_id int 优惠活动id
 * $act_sel_id string 活动中选中的cart id
 */
function cart_favourable_box($favourable_id, $act_sel_id = array())
{
    $fav_res = favourable_list($_SESSION['user_rank'], -1, $favourable_id, $act_sel_id);
    $favourable_activity = $fav_res[0];

    $cart_value = isset($act_sel_id['act_sel_id']) && !empty($act_sel_id['act_sel_id']) ? addslashes($act_sel_id['act_sel_id']) : 0;
    $cart_goods = get_cart_goods($cart_value, 1);
    $merchant_goods = $cart_goods['goods_list'];

    $favourable_box = [];

    if ($cart_goods['total']['goods_price']) {
        $favourable_box['goods_amount'] = $cart_goods['total']['goods_price'];
    }

    $list_array = [];
    foreach ($merchant_goods as $key => $row) {
        // 第一层 遍历商家
        $user_cart_goods = $row['goods_list'];
        //if ($row['ru_id'] == $favourable_activity['user_id']) { //判断是否商家活动
            foreach ($user_cart_goods as $key1 => $row1) {
                // 第二层 遍历购物车中商家的商品
                $row1['original_price'] = $row1['goods_price'] * $row1['goods_number'];
                if (!empty($act_sel_id)) {
                    // 用来判断同一个优惠活动前面是否全部不选
                    $row1['sel_checked'] = strstr(',' . $act_sel_id['act_sel_id'] . ',', ',' . $row1['rec_id'] . ',') ? 1 : 0; // 选中为1
                }
                $favourable_box['ru_id'] = $favourable_activity['user_id'];
                if($row1['act_id'] == $favourable_activity['act_id']){
                    // 活动-全部商品
                    if ($favourable_activity['act_range'] == 0 && $row1['extension_code'] != 'package_buy') {
                        if ($row1['is_gift'] == FAR_ALL) { // 活动商品
                            $favourable_box['act_id'] = $favourable_activity['act_id'];
                            $favourable_box['act_name'] = $favourable_activity['act_name'];
                            $favourable_box['act_type'] = $favourable_activity['act_type'];
                            // 活动类型
                            switch ($favourable_activity['act_type']) {
                                case 0:
                                    $favourable_box['act_type_txt'] = L('with_a_gift');
                                    $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                    break;
                                case 1:
                                    $favourable_box['act_type_txt'] = L('full_reduction');
                                    $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                    break;
                                case 2:
                                    $favourable_box['act_type_txt'] = L('discount');
                                    $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                    break;

                                default:
                                    break;
                            }
                            $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                            $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                            $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                            $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额

                            // 购物车中已选活动赠品数量
                            $cart_favourable = cart_favourable($row1['ru_id']);
                            $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                            $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                            $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                            // 活动赠品
                            if ($favourable_activity['gift']) {
                                $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                            }

                            // new_list->活动id->act_goods_list
                            $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                        } else { // 赠品
                            $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                        }
                        continue; // 如果活动包含全部商品，跳出循环体
                    }

                    // 活动-分类
                    if ($favourable_activity['act_range'] == FAR_CATEGORY && $row1['extension_code'] != 'package_buy') {
                        // 优惠活动关联的 分类集合
                        $get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 1); // 1表示优惠范围 按分类

                        $str_cat = '';
                        foreach ($get_act_range_ext as $id) {

                            /**
                             * 当前分类下的所有子分类
                             * 返回一维数组
                             */
                            $cat_keys = get_array_keys_cat(intval($id));

                            if($cat_keys){
                                $str_cat .= implode(",", $cat_keys);
                            }
                        }

                        if($str_cat){
                            $list_array = explode(",", $str_cat);
                        }

                        $list_array = !empty($list_array) ? array_merge($get_act_range_ext, $list_array) : $get_act_range_ext;
                        $id_list = arr_foreach($list_array);
                        $id_list = array_unique($id_list);
                        $cat_id = $row1['cat_id']; //购物车商品所属分类ID

                        // 判断商品或赠品 是否属于本优惠活动
                        if ((in_array(trim($cat_id), $id_list) && $row1['is_gift'] == 0) || ($row1['is_gift'] == $favourable_activity['act_id'])) {

                            //优惠活动关联分类集合
                            $fav_act_range_ext = !empty($favourable_activity['act_range_ext']) ? explode(',', $favourable_activity['act_range_ext']) : array();

                            // 此 优惠活动所有分类
                            foreach ($fav_act_range_ext as $id) {
                                /**
                                 * 当前分类下的所有子分类
                                 * 返回一维数组
                                 */
                                $cat_keys = get_array_keys_cat(intval($id));
                                $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                            }

                            if ($row1['is_gift'] == 0 && in_array($cat_id, $fav_act_range_ext)) { // 活动商品
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = L('with_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = L('full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = L('discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额

                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($row1['ru_id']);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                //活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                                $favourable_box['act_goods_list_num'] = count($favourable_box['act_goods_list']);
                            }
                            if ($row1['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                            }
                            continue;
                        }
                    }

                    // 活动-品牌
                    if ($favourable_activity['act_range'] == FAR_BRAND && $row1['extension_code'] != 'package_buy') {
                        // 优惠活动 品牌集合
                        $get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 2); // 2表示优惠范围 按品牌
                        $brand_id = $row1['brand_id'];

                        // 是品牌活动的商品或者赠品
                        if ((in_array(trim($brand_id), $get_act_range_ext) && $row1['is_gift'] == 0) || ($row1['is_gift'] == $favourable_activity['act_id'])) {
                            $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ',';
                            $brand_id_str = ',' . $brand_id . ',';
                            if ($row1['is_gift'] == 0 && strstr($act_range_ext_str, trim($brand_id_str))) { // 活动商品
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = L('with_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = L('full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = L('discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额
                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($row1['ru_id']);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_activity, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                //活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                            }
                            if ($row1['is_gift'] == $favourable_activity['act_id']) { // 赠品
                                $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                            }
                            continue;
                        }
                    }

                    // 活动-部分商品
                    if ($favourable_activity['act_range'] == FAR_GOODS && $row1['extension_code'] != 'package_buy') {
                        $get_act_range_ext = get_act_range_ext($_SESSION['user_rank'], $row['ru_id'], 3); // 3表示优惠范围 按商品
                        // 判断购物商品是否参加了活动  或者  该商品是赠品
                        if (in_array($row1['goods_id'], $get_act_range_ext) || ($row1['is_gift'] == $favourable_activity['act_id'])) {
                            $act_range_ext_str = ',' . $favourable_activity['act_range_ext'] . ','; // 优惠活动中的优惠商品
                            $goods_id_str = ',' . $row1['goods_id'] . ',';
                            // 如果是活动商品
                            if (strstr($act_range_ext_str, trim($goods_id_str)) && ($row1['is_gift'] == 0)) {
                                $favourable_box['act_id'] = $favourable_activity['act_id'];
                                $favourable_box['act_name'] = $favourable_activity['act_name'];
                                $favourable_box['act_type'] = $favourable_activity['act_type'];
                                // 活动类型
                                switch ($favourable_activity['act_type']) {
                                    case 0:
                                        $favourable_box['act_type_txt'] = L('with_a_gift');
                                        $favourable_box['act_type_ext_format'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                        break;
                                    case 1:
                                        $favourable_box['act_type_txt'] = L('full_reduction');
                                        $favourable_box['act_type_ext_format'] = number_format($favourable_activity['act_type_ext'], 2); // 满减金额
                                        break;
                                    case 2:
                                        $favourable_box['act_type_txt'] = L('discount');
                                        $favourable_box['act_type_ext_format'] = floatval($favourable_activity['act_type_ext'] / 10); // 折扣百分比
                                        break;

                                    default:
                                        break;
                                }
                                $favourable_box['min_amount'] = $favourable_activity['min_amount'];
                                $favourable_box['act_type_ext'] = intval($favourable_activity['act_type_ext']); // 可领取总件数
                                $favourable_box['cart_fav_amount'] = cart_favourable_amount($favourable_activity, $act_sel_id);
                                $favourable_box['available'] = favourable_available($favourable_activity, $act_sel_id); // 购物车满足活动最低金额

                                // 购物车中已选活动赠品数量
                                $cart_favourable = cart_favourable($row1['ru_id']);
                                $favourable_box['cart_favourable_gift_num'] = empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]);
                                $favourable_box['favourable_used'] = favourable_used($favourable_box, $cart_favourable);
                                $favourable_box['left_gift_num'] = intval($favourable_activity['act_type_ext']) - (empty($cart_favourable[$favourable_activity['act_id']]) ? 0 : intval($cart_favourable[$favourable_activity['act_id']]));

                                // 活动赠品
                                if ($favourable_activity['gift']) {
                                    $favourable_box['act_gift_list'] = $favourable_activity['gift'];
                                }

                                // new_list->活动id->act_goods_list
                                $favourable_box['act_goods_list'][$row1['rec_id']] = $row1;
                            }
                            // 如果是赠品
                            if ($row1['is_gift'] == $favourable_activity['act_id']) {
                                $favourable_box['act_cart_gift'][$row1['rec_id']] = $row1;
                            }
                        }
                    } else {
                        // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                        $favourable_box[$row1['rec_id']] = $row1;
                    }
                }
            }
        //}
    }

    return $favourable_box;
}