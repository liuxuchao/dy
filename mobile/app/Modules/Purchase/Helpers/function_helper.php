<?php

function get_seller_domain_url($ru_id = 0, $build_uri = []) {

    $build_uri['cid'] = isset($build_uri['cid']) ? $build_uri['cid'] : 0;
    $build_uri['urid'] = isset($build_uri['urid']) ? $build_uri['urid'] : 0;
    $append = isset($build_uri['append']) ? $build_uri['append'] : '';
    unset($build_uri['append']);

    $res = get_seller_domain_info($ru_id);

    $res['seller_url'] = $res['domain_name'];

    if ($res['domain_name'] && $res['is_enable']) {
        if ($build_uri['cid']) {
            $build_uri['domain_name'] = $res['domain_name'];
            $res['domain_name'] = get_return_store_url($build_uri, $append);
        } else {
            $res['domain_name'] = $res['domain_name'];
        }

        $res['domain_name'] = $res['domain_name'];
    } else {
        $res['domain_name'] = get_return_store_url($build_uri, $append);
    }

    return $res;
}


/**
 * 获取店铺二级域名信息
 * @param int $ru_id
 * @return mixed
 */
function get_seller_domain_info($ru_id = 0) {
    $sql = "SELECT domain_name, is_enable, validity_time FROM " . $GLOBALS['ecs']->table('seller_domain') . " WHERE ru_id = '$ru_id' LIMIT 1";
    $row = $GLOBALS['db']->getRow($sql);

    if (!$row) {
        $row['domain_name'] = '';
        $row['is_enable'] = '';
        $row['validity_time'] = '';
    }

    return $row;
}

/**
 * 店铺地址
 * @param string $params
 * @param string $append
 */
function get_return_store_url($params = '', $append = ''){
    $url = build_uri('merchants_store', $params, $append);
    return $url;
}

/**
 * 货品查询语句处理
 * @param array $attr
 * @param string $col
 * @param string $sign
 * @return string
 */
function get_find_in_set($attr = [], $col = 'goods_attr', $sign = '|') {
    $set = "";
    foreach ($attr as $key => $val) {
        $set .= " AND FIND_IN_SET('$val', REPLACE($col, '$sign', ',')) ";
    }
    return $set;
}


/**
 * 获取成交量
 * @param int $goods_id
 * @return mixed
 */
function get_sale($goods_id = 0) {
    $sql = "SELECT SUM(og.goods_number) FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS oi "
        . " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_order_goods') . " AS og ON og.order_id = oi.order_id "
        . " WHERE oi.main_order_id > 0 AND oi.is_delete = 0 AND oi.main_order_id > 0 AND og.goods_id=" . $goods_id;
    $count = $GLOBALS['db']->getOne($sql);
    return $count;
}

/**
 * 获取批发商品标识
 */
function get_wholesale_extend($goods_id) {
    //获得商品的扩展信息by wang
    $extend_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_extend') . " WHERE goods_id = '$goods_id'";
    return $GLOBALS['db']->getRow($extend_sql);
}

/**
 * 判断商家或者会员是否通过实名认证
 * user_id 会员编号
 * user_type 会员类型：0普通会员，1商家
 * return bool
 */

function check_users_real($user_id = 0, $user_type = 0) {
    $data = get_table_date('users_real', "user_id='$user_id' AND user_type='$user_type' AND review_status=1", ['real_id'], 2);
    if ($data) {
        return true;
    } else {
        return false;
    }
}

/**
 * 返回商家组合地区信息，如：江苏 南京
 * type 类型（暂时无用，后期可根据不同类型返回对应组合）
 */

function get_seller_area_info($ru_id = 0, $type = 0) {
    $data = [];
    switch ($type) {
        case 0:$data = ['province', 'city'];
            break;
        default:$data = ['country', 'province', 'city'];
            break;
    }
    $area_info = get_table_date('seller_shopinfo', "ru_id='$ru_id'", $data);
    if ($area_info) {
        $area_info = implode(',', $area_info);
        $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ($area_info) ";
        $region_name = $GLOBALS['db']->getCol($sql);
        if ($region_name) {
            return implode(' ', $region_name);
        }
    }
    return '';
}

/**
 * 获得商品分类的所有信息
 *
 * @param   integer     $cat_id     指定的分类ID
 *
 * @return  mix
 */
function get_cat_info($cat_id = 0, $select = [], $table = 'category')
{
    if($select){
        $select = implode(",", $select);
    }else{
        $select = "*";
    }

    $sql = "SELECT $select FROM " .$GLOBALS['ecs']->table($table). " WHERE cat_id = '$cat_id' LIMIT 1";
    $row = $GLOBALS['db']->getRow($sql);

    return $row;
}

/**
 * 返回地区逐级名称，如：中国 上海 上海 普陀区 中山北路
 */

function get_every_region_name($region_id = 0) {
    $arr = [];
    $arr[] = $region_id;
    $parent_id = get_table_date('region', "region_id='$region_id'", ['parent_id'], 2);
    while ($parent_id) {
        $arr[] = $parent_id;
        $parent_id = get_table_date('region', "region_id='$parent_id'", ['parent_id'], 2);
    }
    krsort($arr);
    //获取地区
    $area_info = implode(',', $arr);
    $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ($area_info) ";
    $region_name = $GLOBALS['db']->getCol($sql);
    if ($region_name) {
        return implode(' ', $region_name);
    }
    return '';
}

/**
 * 处理属性，返回数组
 * goods_attr_id 字符串，如 1,2,3
 * 返回数组
 */
function get_wholesale_attr_array($goods_attr_id = ''){
    if(empty($goods_attr_id)){
        return false;
    }
    $sort_order = " ORDER BY a.sort_order ASC, a.attr_id ASC ";
    $sql = " SELECT a.attr_name, ga.attr_value FROM ".$GLOBALS['ecs']->table('wholesale_goods_attr')." AS ga ".
        " LEFT JOIN ".$GLOBALS['ecs']->table('attribute')." AS a ON a.attr_id = ga.attr_id ".
        " WHERE ga.goods_attr_id IN ($goods_attr_id) ".$sort_order;
    $res = $GLOBALS['db']->getAll($sql);

    return $res;
}

/**
 * 刷新购物车商品价格
 * @param int $goods_id
 * @param string $rec_ids
 * @return bool
 */
function calculate_cart_goods_price($goods_id = 0, $rec_ids = '') {
    if (!empty($_SESSION['user_id'])) {
        $sess_id = " c.user_id = '" . $_SESSION['user_id'] . "' ";
        $sess = "";
    } else {
        $sess_id = " c.session_id = '" . real_cart_mac_ip() . "' ";
        $sess = real_cart_mac_ip();
    }

    if (!empty($goods_id)) {
        $sess_id .= " AND c.goods_id = '$goods_id' ";
    }

    if (!empty($rec_ids)) {
        $sess_id .= " AND c.rec_id IN ($rec_ids) ";
    }

    //重新计算价格并更新价格
    $sql = " SELECT SUM(c.goods_number) FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c WHERE $sess_id ";
    $total_number = $GLOBALS['db']->getOne($sql);
    $price_info = calculate_goods_price($goods_id, $total_number);
    $sql = " UPDATE " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c SET c.goods_price = '" . $price_info['unit_price'] . "' WHERE $sess_id ";
    if ($GLOBALS['db']->query($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 计算价格
 * @param int $goods_id
 * @param int $goods_number
 * @return array
 */
function calculate_goods_price($goods_id = 0, $goods_number = 0) {
    $goods = get_table_date('goods', "goods_id='$goods_id'", ['market_price']);
    $data = get_table_date('wholesale', "goods_id='$goods_id'", ['price_model', 'goods_price']);
    //合并
    $data = array_merge($data, $goods);
    if ($data['price_model'] == 0) {
        $unit_price = $data['goods_price'];
    } elseif ($data['price_model'] == 1) {
        $sql = " SELECT MIN(volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' AND volume_number <= $goods_number ";
        $unit_price = $GLOBALS['db']->getOne($sql);
        //如果不满足阶梯价的最小数量要求，则依然展示最大价格
        if (empty($unit_price)) {
            $sql = " SELECT MAX(volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' ";
            $unit_price = $GLOBALS['db']->getOne($sql);
        }
    }
    $data['total_number'] = $goods_number;
    $data['unit_price'] = $unit_price;
    $data['unit_price_formatted'] = price_format($data['unit_price']);
    $data['total_price'] = $unit_price * $goods_number;
    //$data['total_price_formatted'] = price_format($data['total_price']);
    $data['total_price_formatted'] = sprintf('%0.2f', $data['total_price']);
    return $data;
}


/**
 * 处理属性，返回数组
 * goods_attr_id 字符串，如 1,2,3
 * 返回数组
 */

function get_goods_attr_array($goods_attr_id = '') {
    if (empty($goods_attr_id)) {
        return false;
    }
    $sort_order = " ORDER BY a.sort_order ASC, a.attr_id ASC ";
    $sql = " SELECT a.attr_name, ga.attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS ga " .
        " LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a ON a.attr_id = ga.attr_id " .
        " WHERE ga.goods_attr_id IN ($goods_attr_id) " . $sort_order;
    $res = $GLOBALS['db']->getAll($sql);

    return $res;
}


/*
 * 获取批发阶梯价
 */

function get_wholesale_volume_price($goods_id = 0, $goods_number = 0) {
    $sql = " SELECT price_model, goods_price FROM " . $GLOBALS['ecs']->table('wholesale') . " WHERE goods_id = '$goods_id' ";
    $res = $GLOBALS['db']->getRow($sql);
    if ($res['price_model']) {
        //按数量排序
        $sql = " SELECT volume_number, volume_price FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' ORDER BY volume_number ASC ";
        $res['volume_price'] = $GLOBALS['db']->getAll($sql);
        //按数量排序
        /* usort($res['volume_price'], function($a, $b){
          $a1 = $a['volume_number'];
          $b1 = $a['volume_number'];
          if($a1 == $b1) return 0;
          return ($a1 < $b1)? -1:1;
          }); */
        //设置数量阶段
        foreach ($res['volume_price'] as $key => $val) {
            if ($key < count($res['volume_price']) - 1) {
                $range_number = $res['volume_price'][$key + 1]['volume_number'] - 1;
                $res['volume_price'][$key]['range_number'] = $range_number;
            }
            if ($goods_number >= $val['volume_number']) {
                $res['volume_price'][$key]['is_reached'] = 1; //当前达成
                if (isset($res['volume_price'][$key - 1]['is_reached'])) {
                    unset($res['volume_price'][$key - 1]['is_reached']);
                }
            }
        }
    }

    return $res['volume_price'];
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn()
{
    $time = explode(" ", microtime());
    $time = $time[1] . ($time[0] * 1000);
    $time = explode(".", $time);
    $time = isset($time[1]) ? $time[1] : 0;
    $time = date('YmdHis') + $time;

    /* 选择一个随机的方案 */
    mt_srand((double)microtime() * 1000000);
    return $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}



/**
 *  获取用户采购单订单列表
 *
 * @access  public
 * @param   int $user_id 用户ID号
 * @param   int $num 列表最大数量
 * @param   int $start 列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_purchase_orders($user_id, $num = 10, $page = 1)
{
    $where = "";
    //总条数
    $total_arr = $GLOBALS['db']->getAll("SELECT oi.order_id FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi" .
        " left join " . $GLOBALS['ecs']->table('wholesale_order_goods') . " as og on oi.order_id = og.order_id" .
        " WHERE oi.user_id = '$user_id' and oi.is_delete = '0' " .
        " and (select count(*) from " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi2 where oi2.main_order_id = oi.order_id) = 0 " .  //主订单下有子订单时，则主订单不显示
        " group by oi.order_id ORDER BY oi.add_time DESC");

    $total = is_array($total_arr) ? count($total_arr) : 0;
    $start = ($page - 1) * $num;
    /* 取得订单列表 */
    $arr = [];

    $sql = "SELECT og.ru_id, oi.order_id,oi.main_order_id, oi.order_sn, oi.consignee,oi.order_status,oi.address,oi.mobile,oi.order_amount, oi.add_time,oi.postscript, oi.is_delete, oi.inv_payee, oi.inv_type, oi.invoice_type, oi.vat_id, oi.tax_id,oi.extension_code,oi.pay_id " .
        " FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi" .
        " left join " . $GLOBALS['ecs']->table('wholesale_order_goods') . " as og on oi.order_id = og.order_id" .
        " WHERE oi.user_id = '$user_id' and oi.is_delete = '0' " .
        " and (select count(*) from " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi2 where oi2.main_order_id = oi.order_id) = 0 " .  //主订单下有子订单时，则主订单不显示
        " group by oi.order_id ORDER BY oi.add_time DESC LIMIT $start, $num";

    $res = $GLOBALS['db']->query($sql);
    foreach ($res as $key => $row) {
        $row['order_goods'] = get_purchase_order_goods($row['order_id']);
        $order_id = $row['order_id'];
        //商家ru_id
        /*$sql = "select user_id from " . $GLOBALS['ecs']->table('goods') . " where goods_id = '" . $row['goods_id'] . "'";
        $ru_id = $GLOBALS['db']->getOne($sql);*/
        $pay = dao('payment')->where(['pay_id' => $row['pay_id']])->find();
        $ru_id = $row['ru_id'];
        $province = get_order_region_name($row['province']);
        $city = get_order_region_name($row['city']);
        $district = get_order_region_name($row['district']);

        $district_name = !empty($district['region_name']) ? $district['region_name'] : '';
        $address_detail = $province['region_name'] . "&nbsp;" . $city['region_name'] . "市" . "&nbsp;" . $district_name;

        $arr[] = [
            'order_id' => $row['order_id'],
            'order_sn' => $row['order_sn'],
            'order_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
            'order_status' => $row['order_status'],
            'order_del' => $row['order_del'],
            'order_amount' => price_format($row['order_amount'], false),
            'status' => $row['status'],
            'consignee' => $row['consignee'],
            'mobile' => $row['mobile'],
            'main_order_id' => $row['main_order_id'],
            'user_name' => get_shop_name($ru_id, 1), //店铺名称 ,
            'order_goods' => $row['order_goods'],
            'order_goods_num' => count($row['order_goods']),
            'no_picture' => $GLOBALS['_CFG']['no_picture'],
            'delete_yes' => $row['delete_yes'],
            'invoice_no' => $row['invoice_no'],
            'address_detail' => $row['address_detail'],
            'address' => $row['address'],
            'postscript' => $row['postscript'],
            'address_detail' => $address_detail,
            'tel' => $row['mobile'],
            'order_count' => $order_count,
            'handler_return' => $row['handler_return'],
            'pay_name' => $pay['pay_name'],
            'pay_status' => $row['pay_status'],
            'inv_payee' => $row['inv_payee'],
            'inv_type' => $row['inv_type'],
            'tax_id' => $row['tax_id'],
            'invoice_type' => $row['invoice_type'],
            'handler' => $row['handler'],
            'extension_code' => $row['extension_code']
        ];
    }

    $order_list = ['list' => $arr, 'totalpage' => ceil($total / $num)];

    return $order_list;
}



//根据订单商品查询商品信息
function get_purchase_order_goods($order_id = 0)
{
    $sql = "SELECT w.act_id,g.goods_id, g.goods_name, g.goods_thumb, og.goods_number, og.goods_attr, og.goods_price, og.extension_code, og.goods_name AS extension_name, oi.order_sn FROM " . $GLOBALS['ecs']->table('wholesale_order_goods') . " as og " .
        " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS oi ON og.order_id = oi.order_id " .
        "left join " . $GLOBALS['ecs']->table('goods') . " as g on og.goods_id = g.goods_id " .
        "left join " . $GLOBALS['ecs']->table('wholesale') . " as w on g.goods_id = w.goods_id " .
        "WHERE og.order_id = '$order_id' order by og.goods_id";
    $res = $GLOBALS['db']->getAll($sql);
    $arr = [];
    foreach ($res as $key => $row) {
        $arr[$key]['goods_id'] = $row['goods_id'];
        $arr[$key]['goods_name'] = $row['goods_name'];
        $arr[$key]['goods_number'] = $row['goods_number'];
        $arr[$key]['extension_code'] = $row['extension_code'];
        $arr[$key]['goods_attr'] = $row['goods_attr'];
        $arr[$key]['goods_price'] = price_format($row['goods_price'], false);
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
        $arr[$key]['url'] = url('purchase/index/goods', ['id' => $row['act_id']]);
    }
    return $arr;
}