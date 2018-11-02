<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

function C($key)
{
    $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
    if (strpos($key, '.')) {
        list($item, $key) = explode('.', $key, 2);
    }
    return $shopconfig->getShopConfigByCode($key);
}

//获取OSS Bucket信息
function get_bucket_info()
{
    $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
    $res = $shopconfig->getOssConfig();

    if ($res) {
        $regional = substr($res['regional'], 0, 2);
        if ($regional == 'us' || $regional == 'ap') {
            $res['outside_site'] = "https://" . $res['bucket'] . ".oss-" . $res['regional'] . ".aliyuncs.com";
            $res['inside_site'] = "https://" . $res['bucket'] . ".oss-" . $res['regional'] . "-internal.aliyuncs.com";
        } else {
            $res['outside_site'] = "https://" . $res['bucket'] . ".oss-cn-" . $res['regional'] . ".aliyuncs.com";
            $res['inside_site'] = "https://" . $res['bucket'] . ".oss-cn-" . $res['regional'] . "-internal.aliyuncs.com";
        }
        $res['endpoint'] = str_replace('http://', 'https://', $res['endpoint']);
    }

    return $res;
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
if (!function_exists("get_image_path")) {
    function get_image_path($image = '', $path = '')
    {
        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';
        $no_picture = $rootPath . 'mobile/public/img/no_image.jpg';

        if (strtolower(substr($image, 0, 4)) == 'http') {
            $url = $image;
        } else {
            $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
            $open_oss = $shopconfig->getShopConfigByCode('open_oss');
            if ($open_oss == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $url = empty($image) ? $no_picture : rtrim($bucket_info['endpoint'], '/') . '/' . $path . $image;
            } else {
                $path = empty($path) ? '' : rtrim($path, '/') . '/';
                $img_path = $path . $image;
                if (empty($image)) {
                    $url = $no_picture;
                } else {
                    $url = $rootPath . $img_path;
                }
            }
        }

        return $url;
    }
}


/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float $price 商品价格
 * @return  string
 */
if (!function_exists('price_format')) {
    function price_format($price, $change_price = true)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $priceFormat = $shopconfig->getShopConfigByCode('price_format');
        $currencyFormat = strip_tags($shopconfig->getShopConfigByCode('currency_format'));

        if ($price === '') {
            $price = 0;
        }
        if ($change_price && defined('ECS_ADMIN') === false) {
            switch ($priceFormat) {
                case 0:
                    $price = number_format($price, 2, '.', '');
                    break;
                case 1: // 保留不为 0 的尾数
                    $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                    if (substr($price, -1) == '.') {
                        $price = substr($price, 0, -1);
                    }
                    break;
                case 2: // 不四舍五入，保留1位
                    $price = substr(number_format($price, 2, '.', ''), 0, -1);
                    break;
                case 3: // 直接取整
                    $price = intval($price);
                    break;
                case 4: // 四舍五入，保留 1 位
                    $price = number_format($price, 1, '.', '');
                    break;
                case 5: // 先四舍五入，不保留小数
                    $price = round($price);
                    break;
            }
        } else {
            @$price = number_format($price, 2, '.', '');
        }

        return sprintf($currencyFormat, $price);
    }
}


/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string $str 待转换字串
 *
 * @return  string       $str         处理后字串
 */
if (!function_exists('make_semiangle')) {
    function make_semiangle($str)
    {
        $arr = ['０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
            '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
            '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
            '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
            '　' => ' '];

        return strtr($str, $arr);
    }
}

/**
 *  生成一个用户自定义时区日期的GMT时间戳
 *
 * @access  public
 * @param   int $hour
 * @param   int $minute
 * @param   int $second
 * @param   int $month
 * @param   int $day
 * @param   int $year
 *
 * @return void
 */
if (!function_exists('local_mktime()')) {
    function local_mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timezone = $shopconfig->getShopConfigByCode('timezone');
        /**
         * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
         * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
         **/
        $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;

        return $time;
    }
}

/**
 * 获得用户所在时区指定的日期和时间信息
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
if (!function_exists('local_getdate()')) {
    function local_getdate($timestamp = null)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timezone = $shopconfig->getShopConfigByCode('timezone');

        /* 如果时间戳为空，则获得服务器的当前时间 */
        if ($timestamp === null) {
            $timestamp = time();
        }

        $gmt = $timestamp - date('Z');       // 得到该时间的格林威治时间
        $local_time = $gmt + ($timezone * 3600);    // 转换为用户所在时区的时间戳

        return getdate($local_time);
    }
}

/**
 * 获得用户所在时区指定的时间戳
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
if (!function_exists('local_gettime()')) {
    function local_gettime($timestamp = null)
    {
        $tmp = local_getdate($timestamp);
        return $tmp[0];
    }
}

/**
 * 获得当前格林威治时间的时间戳
 *
 * @return  integer
 */
if (!function_exists('gmtime()')) {
    function gmtime()
    {
        return (time() - date('Z'));
    }
}

/**
 * 将GMT时间戳格式化为用户自定义时区日期
 *
 * @param  string $format
 * @param  integer $time 该参数必须是一个GMT的时间戳
 *
 * @return  string
 */
if (!function_exists('local_date()')) {
    function local_date($format, $time = null)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timezone = $shopconfig->getShopConfigByCode('timezone');

        if ($time === null) {
            $time = gmtime();
        } elseif ($time <= 0) {
            return '';
        }

        $time += ($timezone * 3600);

        return date($format, $time);
    }
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}


/**
 * 去除字符串中首尾逗号
 * 去除字符串中出现两个连续逗号
 */
function get_del_str_comma($str = '')
{

    if ($str && is_array($str)) {
        return $str;
    } else {
        if ($str) {
            $str = str_replace(",,", ",", $str);

            $str1 = substr($str, 0, 1);
            $str2 = substr($str, str_len($str) - 1);

            if ($str1 === "," && $str2 !== ",") {
                $str = substr($str, 1);
            } elseif ($str1 !== "," && $str2 === ",") {
                $str = substr($str, 0, -1);
            } elseif ($str1 === "," && $str2 === ",") {
                $str = substr($str, 1);
                $str = substr($str, 0, -1);
            }
        }

        return $str;
    }
}

/**
 * 计算字符串的长度（汉字按照两个字符计算）
 *
 * @param   string $str 字符串
 *
 * @return  int
 */
function str_len($str)
{
    $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));

    if ($length) {
        return strlen($str) - $length + intval($length / 3) * 2;
    } else {
        return strlen($str);
    }
}


/**
 * 数组转换
 * 三维数组转换成二维数组
 */
function get_three_to_two_array($list = array()) {

    $new_list = array();
    if ($list) {
        foreach ($list as $lkey => $lrow) {
            foreach ($lrow as $ckey => $crow) {
                $new_list[] = $crow;
            }
        }
    }

    return $new_list;
}


//数组排序--根据键的值的数值排序
function get_array_sort($arr, $keys, $type = 'asc') {

    $new_array = array();
    if (is_array($arr) && !empty($arr)) {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
    }

    return $new_array;
}

/**
 * 运算商品详情设置非按区域运费模式价格
 */
function get_order_transport($goods_list, $consignee = array(), $shipping_id = 0, $shipping_code = ''){

    $sprice = 0;
    $type_left = array();
    $freight = 0;

    if($goods_list && $shipping_code != 'cac'){

        /**
         * 商品运费模板
         * 地区运费
         */
        $area_shipping = get_goods_area_shipping($goods_list, $shipping_id, $shipping_code, $consignee);

        foreach( $goods_list as $key => $row ){
            if($row['freight'] && $row['is_shipping'] == 0){
                if($row['freight'] == 1){
                    /**
                     * 商品
                     * 固定运费
                     */
                    $sprice += $row['shipping_fee'] * $row['goods_number'];
                }else{

                    $trow = app('App\Repositories\Goods\GoodsRepository')->getGoodsTransport($row['tid']);

                    if(isset($trow['freight_type']) && $trow['freight_type'] == 0){
                        /**
                         * 商品
                         * 运费模板
                         * 区域运费
                         */
                        $transport = array('top_area_id', 'area_id', 'tid', 'ru_id', 'sprice');
                        $transport_where = " AND ru_id = '" . $row['ru_id'] . "' AND tid = '" . $row['tid'] . "'";
                        $goods_transport = app('App\Repositories\Shop\ShopRepository')->get_select_find_in_set(2, $consignee['city'], $transport, $transport_where, 'goods_transport_extend', 'area_id');

                        if ($goods_transport) {
                            $ship_transport = array('tid', 'ru_id', 'shipping_fee');
                            $ship_transport_where = " AND ru_id = '" . $row['ru_id'] . "' AND tid = '" . $row['tid'] . "'";
                            $goods_ship_transport = app('App\Repositories\Shop\ShopRepository')->get_select_find_in_set(2, $shipping_id, $ship_transport, $ship_transport_where, 'goods_transport_express', 'shipping_id');
                        }

                        $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                        $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                        if ($trow['type'] == 1) {
                            $sprice += $goods_transport['sprice'] * $row['goods_number'] + $goods_ship_transport['shipping_fee'] * $row['goods_number'];
                        } else {
                            $type_left[$row['tid']] = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                        }
                    }
                }
            }else{
                $freight += 1;
            }
        }

        $unified_total = get_cart_unified_freight_total($type_left);

        $arr = array(
            'sprice' => $area_shipping['shipping_fee'] + $sprice + $unified_total, //固定运费 + 运费模板
            'freight' => $freight //是否有按配送区域计算运费的商品
        );

    }else{
        $arr = array(
            'sprice' => 0, //上门取货运费为0
            'freight' => $freight //是否有按配送区域计算运费的商品
        );
    }

    return $arr;
}

/**
 * 商品运费模板
 * 地区运费
 *
 * $goods_list 购物车商品
 */
function get_goods_area_shipping($goods_list, $shipping_id = 0, $shipping_code = '', $consignee){
    $tid_arr1 = array();
    $prefix = Config::get('database.connections.mysql.prefix');

    foreach ($goods_list as $key => $row) {
        $tid_arr1[$row['tid']][$key] = $row;
    }

    $tid_arr2 = array();
    foreach ($tid_arr1 as $key => $row) {
        $row = !empty($row) ? array_values($row) : $row;

        $tid_arr2[$key]['weight'] = 0;
        $tid_arr2[$key]['number'] = 0;
        $tid_arr2[$key]['amount'] = 0;
        foreach ($row as $gkey => $grow) {
//            $tid_arr2[$key]['weight'] += $grow['goodsweight'] * $grow['goods_number'];
            $tid_arr2[$key]['number'] += $grow['goods_number'];
            $tid_arr2[$key]['amount'] += $grow['goods_price'] * $grow['goods_number'];
        }
    }

    if(empty($shipping_id)){
        $select = array('shipping_code' => $shipping_code);
        $shipping_info = shipping_info($select, array('shipping_id'));
        $shipping_id = $shipping_info['shipping_id'];
    }

    if(empty($shipping_code)){
        $shipping_info = shipping_info($shipping_id, array('shipping_code'));
        $shipping_code = $shipping_info['shipping_code'];
    }

    $shipping_fee = 0;
    $region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']);

    foreach($tid_arr2 as $key=>$row){
        $trow = get_goods_transport($key);
        if($trow && $trow['freight_type'] == 1){
        $sql = "SELECT * FROM {$prefix}goods_transport_tpl WHERE tid = '$key' AND shipping_id = '$shipping_id'" .
                " AND ((FIND_IN_SET('" . $region[1] . "', region_id)) OR (FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id)))" .
                " LIMIT 1";
        $transport_tpl = DB::select($sql);
        $transport_tpl = isset($transport_tpl[0]) ? json_decode(json_encode($transport_tpl[0]), 1) : array();

        $configure = !empty($transport_tpl) && $transport_tpl['configure'] ? unserialize($transport_tpl['configure']) : '';

        if(!empty($configure)){
            $tid_arr2[$key]['shipping_fee'] = shipping_fee($shipping_code, $configure, $row['weight'], $row['amount'], $row['number']);
        }else{
            $tid_arr2[$key]['shipping_fee'] = 0;
        }

        $shipping_fee += $tid_arr2[$key]['shipping_fee'];
    }
    }
    $arr = array('tid_list' => $tid_arr2, 'shipping_fee' => $shipping_fee);
    return $arr;
}

function get_goods_transport($tid = 0)
{
    $prefix = Config::get('database.connections.mysql.prefix');
    $sql = "SELECT * FROM " . $prefix."goods_transport WHERE tid = '$tid' LIMIT 1";
    $transport = DB::select($sql);
    $transport = !empty($transport) ? get_object_vars($transport[0]) : '';
    return $transport;
}

/**
 * 购物车商品统一运费
 */
function get_cart_unified_freight_total($total){

    $sprice = 0;

    if($total){
        foreach($total as $key=>$row){
            $sprice += $row;
        }
    }

    return $sprice;
}

/**
 * 计算运费
 * @param   string $shipping_code 配送方式代码
 * @param   mix $shipping_config 配送方式配置信息
 * @param   float $goods_weight 商品重量
 * @param   float $goods_amount 商品金额
 * @param   float $goods_number 商品数量
 * @return  float   运费
 */
function shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount, $goods_number = '')
{
    if (!is_array($shipping_config)) {
        $shipping_config = unserialize($shipping_config);
    }

    $filename = base_path() . '/app/Plugins/shipping/' . $shipping_code . '.php';
    if (file_exists($filename)) {
        include_once($filename);

        $obj = new $shipping_code($shipping_config);

        return $obj->calculate($goods_weight, $goods_amount, $goods_number);
    } else {
        return 0;
    }
}


/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param   string $cfg
 * @return  void
 */
function unserialize_config($cfg)
{
    if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
        $config = array();

        foreach ($arr as $key => $val) {
            $config[$val['name']] = $val['value'];
        }

        return $config;
    } else {
        return false;
    }
}

/**
 * 写入日志文件
 *
 * @param string $word
 * @param string $type
 */
function apiLog($word = '', $type = 'api')
{
    $word = is_array($word) ? var_export($word, true) : $word;
    $suffix = '_' . substr(md5(__DIR__), 0, 6);
    $fp = fopen(base_path('storage/logs/' . $type . $suffix . '.log'), "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . date("Y-m-d H:i:s", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}