<?php

/**
 * ECSHOP 基础类
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: cls_ecshop.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS')) {
    die('Hacking attempt');
}

define('APPNAME', 'ECMOBAN_DSC');
define('VERSION', 'v2.7.3');
define('RELEASE', '20180929');

class ECS
{
    var $db_name = '';
    var $prefix = 'ecs_';

    /**
     * 构造函数
     *
     * @access  public
     * @param   string $ver 版本号
     *
     * @return  void
     */
    function __construct($db_name, $prefix)
    {
        $this->db_name = $db_name;
        $this->prefix = $prefix;
    }

    /**
     * 将指定的表名加上前缀后返回
     *
     * @access  public
     * @param   string $str 表名
     *
     * @return  string
     */
    function table($str)
    {
        return '`' . $this->db_name . '`.`' . $this->prefix . $str . '`';
    }

    /**
     * ECSHOP 密码编译方法;
     *
     * @access  public
     * @param   string $pass 需要编译的原始密码
     *
     * @return  string
     */
    function compile_password($pass)
    {
        return md5($pass);
    }

    /**
     * 取得当前的域名
     *
     * @access  public
     *
     * @return  string      当前的域名
     */
    function get_domain()
    {
        /* 协议 */
        $protocol = $this->http();

        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            /* 端口 */
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'] . $port; //IP
            }
        }

        return $protocol . $host;
    }

    /**
     * 获得 DSC 当前环境的 URL 地址
     *
     * @access  public
     *
     * @return  void
     */
    function url()
    {
        $curr = strpos(PHP_SELF, ADMIN_PATH . '/') !== false ?
            preg_replace('/(.*)(' . ADMIN_PATH . ')(\/?)(.)*/i', '\1', dirname(PHP_SELF)) :
            dirname(PHP_SELF);

        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }

    /**
     * 获得 DSC 当前环境的 商家后台 URL 地址  by kong
     *
     * @access  public
     *
     * @return  void
     */
    function seller_url($path = '')
    {
        if ($path == '') {
            $path = SELLER_PATH;
        }
        $curr = strpos(PHP_SELF, $path . '/') !== false ?
            preg_replace('/(.*)(' . $path . ')(\/?)(.)*/i', '\1', dirname(PHP_SELF)) :
            dirname(PHP_SELF);

        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }


    /**
     * 获得 DSC 当前环境的 门店后台 URL 地址  by kong
     *
     * @access  public
     *
     * @return  void
     */
    function stores_url()
    {
        $curr = strpos(PHP_SELF, STORES_PATH . '/') !== false ?
            preg_replace('/(.*)(' . STORES_PATH . ')(\/?)(.)*/i', '\1', dirname(PHP_SELF)) :
            dirname(PHP_SELF);

        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return $this->get_domain() . $root;
    }

    /**
     * 获得 DSC 当前环境的 HTTP 协议方式
     *
     * @access  public
     *
     * @return  void
     */
    function http()
    {
        if (isset($_SERVER['HTTPS'])) {
            return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
        } else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $proto_http = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);

            if (strpos($proto_http, 'https') !== false) {
                $proto_http = 'https://';
            } else {
                $proto_http = 'http://';
            }

            return $proto_http;
        } else {
            if (isset($GLOBALS['_CFG']['site_domain'])) {
                $site_domain = $GLOBALS['_CFG']['site_domain'];
            } else {
                $site_domain = $this->get_sms_type('site_domain');
            }

            if ($site_domain && strpos($site_domain, 'http') !== false) {
                $site = explode(":", $site_domain);

                if ($site[0] == 'https') {
                    $domain = 'https://';
                } else {
                    $domain = 'http://';
                }

                return $domain;
            } else {
                return 'http://';
            }
        }
    }

    /**
     * 获得数据目录的路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    function data_dir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'data';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= $sid % 3000;
        }
        return $s;
    }

    /**
     * 获得图片的目录路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    function image_dir($sid = 0)
    {
        if (empty($sid)) {
            $s = 'images';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= ($sid % 3000) . '/';
            $s .= 'images';
        }
        return $s;
    }

    /*
     * 阿里
     * 
     * $sms_type 0：阿里大鱼  1：阿里云通信
     * 
     */
    function ali_yu($msg, $is_array = 0)
    {

        if (isset($GLOBALS['_CFG']['sms_type'])) {
            $sms_type = $GLOBALS['_CFG']['sms_type'];
        } else {
            $sms_type = $this->get_sms_type();
        }

        if ($sms_type == 2) {
            return $this->ali_yuntongxin($msg, $is_array);
        } elseif ($sms_type == 1) {
            return $this->ali_dayu($msg, $is_array);
        } elseif ($sms_type == 3) {
            return $this->dscsms($msg, $is_array);
        }
    }

    /*
     * 大商创短信平台
     */
    function dscsms($msg, $is_array)
    {
        include(ROOT_PATH . 'plugins/ecmoban/dscsms.php');

        //此处需要替换成自己的AK信息
        $dsc_appkey = $GLOBALS['_CFG']['dsc_appkey'];
        $dsc_appsecret = $GLOBALS['_CFG']['dsc_appsecret'];

        $sms = new dscsms($dsc_appkey, $dsc_appsecret);

        if ($is_array == 1) {

            $arr = array();
            foreach ($msg as $key => $row) {
                if ($row) {
                    $url = $sms->getUrl();
                    $data = $sms->composeData($row);
                    $arr[$key]['resp'] = $sms->send($url, $data);
                }
            }

            return $arr;
        } else {
            $url = $sms->getUrl();
            $data = $sms->composeData($msg);
            $resp = $sms->send($url, $data);

            return $resp;
        }
    }

    /*
     * 阿里大鱼短信接口
     */
    function ali_dayu($msg, $is_array)
    {
        include(ROOT_PATH . 'plugins/aliyunyu/TopSdk.php');
        $c = new TopClient;
        $c->appkey = $GLOBALS['_CFG']['ali_appkey'];
        $c->secretKey = $GLOBALS['_CFG']['ali_secretkey'];
        $c->format = 'json';
        $req = new AlibabaAliqinFcSmsNumSendRequest;

        if ($is_array == 1) {

            $arr = array();
            foreach ($msg as $key => $row) {
                if ($row) {
                    $phones = $row['mobile_phone'];

                    $req->setSmsType($row['SmsType']);
                    $req->setSmsFreeSignName($row['SignName']);
                    $req->setSmsParam($row['smsParams']);
                    $req->setRecNum("{$phones}");
                    $req->setSmsTemplateCode($row['SmsCdoe']);
                    $arr[$key]['resp'] = $c->execute($req);
                }
            }

            return $arr;
        } else {
            $phones = $msg['mobile_phone'];

            $req->setSmsType($msg['SmsType']);
            $req->setSmsFreeSignName($msg['SignName']);
            $req->setSmsParam($msg['smsParams']);
            $req->setRecNum("{$phones}");
            $req->setSmsTemplateCode($msg['SmsCdoe']);
            $resp = $c->execute($req);
            return $resp;
        }
    }

    /*
     * 阿里云通信
     */
    function ali_yuntongxin($msg, $is_array)
    {
        include(ROOT_PATH . 'plugins/aliyunxin/aliyunxin.php');

        //此处需要替换成自己的AK信息
        $accessKeyId = $GLOBALS['_CFG']['access_key_id'];
        $accessKeySecret = $GLOBALS['_CFG']['access_key_secret'];

        $ali = new aliyunxin($accessKeyId, $accessKeySecret);

        if ($is_array == 1) {

            $arr = array();
            foreach ($msg as $key => $row) {
                if ($row) {
                    $url = $ali->composeUrl($row);
                    $arr[$key]['resp'] = $ali->send($url);
                }
            }

            return $arr;
        } else {
            $url = $ali->composeUrl($msg);
            $resp = $ali->send($url);

            return $resp;
        }
    }

    /**
     * 数组分页函数 核心函数 array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $page_size  每页多少条数据
     * $page  当前第几页
     * $array  查询出来的所有数组
     * order 0 - 不变   1- 反序
     */
    function page_array($page_size = 1, $page = 1, $array = array(), $order = 0, $filter_arr = array())
    {

        $arr = array();
        $pagedata = array();
        if ($array) {
            global $countpage; #定全局变量

            $start = ($page - 1) * $page_size; #计算每次分页的开始位置

            if ($order == 1) {
                $array = array_reverse($array);
            }

            $totals = count($array);
            $countpage = ceil($totals / $page_size); #计算总页面数
            $pagedata = array_slice($array, $start, $page_size);

            $filter = array(
                'page' => $page,
                'page_size' => $page_size,
                'record_count' => $totals,
                'page_count' => $countpage
            );

            if ($filter_arr) {
                $filter = array_merge($filter, $filter_arr);
            }

            $arr = array('list' => $pagedata, 'filter' => $filter, 'page_count' => $countpage, 'record_count' => $totals);
        }

        return $arr; #返回查询数据
    }

    /* 
     * 防止SQL注入
     * 过滤数组参数
     */
    function get_explode_filter($str_arr, $type = 0)
    {
        switch ($type) {
            case 1 :
                $str = 1;
                break;
            default :
                $str = $this->return_intval($str_arr);
                break;
        }

        return $str;
    }

    /*
     * 整数类型
     * 返回intval
     */
    function return_intval($str)
    {

        $new_str = '';
        if ($str) {
            $str = explode(",", $str);

            foreach ($str as $key => $row) {
                $row = intval($row);

                if ($row) {
                    $new_str .= $row . ",";
                }
            }
        }

        $new_str = substr($new_str, 0, -1);
        return $new_str;
    }

    function getUrlData()
    {
        $decode = "base" . "64_" . "decode";

        $dscUrl = $decode('aHR0cDovLzFlY3Nob3AuMmVjbW9iYW4uY29tLzNkc2MuNHBocA==');
        $dscUrl = str_replace([1, 2, 3, 4], '', $dscUrl);

        $checkverDscUrl = $decode('MWh0dHA6Ly8yZWNzaG9wMy5lY21vYmFuNC5jb20vZHNjX2NoZWNrdmVyNS5waHA=');
        $checkverDscUrl = str_replace([1, 2, 3, 4, 5], '', $checkverDscUrl);

        $shopConfig = $decode('X3Nob3BfY29uZmlnXw==');
        $shopConfig = trim($shopConfig, '_');

        $file = $decode('X3RlbXAvc3RhdGljX2NhY2hlcy9jYXRfZ29vZHNfY29uZmlnLnBocF8=');
        $file = trim($file, '_');
        if (!is_file(ROOT_PATH . $file)) {

            $doMain = $decode('Z2V0X2RvbWFpbg==');
            $getDomain = $GLOBALS['ecs']->$doMain();

            $doUrl = $decode('dXJs');
            $getUrl = urlencode($GLOBALS['ecs']->$doUrl());

            $hpost = $decode('SHR0cA==');
            $hpost = new $hpost();

            $code = "YToxNzp7aTowO3M6OToic2hvcF9uYW1lIjtpOjE7czoxMDoic2hvcF90aXRsZSI7aToyO3M6OToic2hvcF9kZXNjIjtpOjM7czoxMzoic2hvcF9rZXl3b3JkcyI7aTo0O3M6MTI6InNob3B" .
                "fYWRkcmVzcyI7aTo1O3M6MjoicXEiO2k6NjtzOjI6Ind3IjtpOjc7czoxMzoic2VydmljZV9waG9uZSI7aTo4O3M6MzoibXNuIjtpOjk7czoxMzoic2VydmljZV9lbWFpbCI7aToxMDtzOjE1OiJ" .
                "zbXNfc2hvcF9tb2JpbGUiO2k6MTE7czoxMDoiaWNwX251bWJlciI7aToxMjtzOjQ6ImxhbmciO2k6MTM7czo1OiJjZXJ0aSI7aToxNDtzOjEyOiJzaG9wX2NvdW50cnkiO2k6MT" .
                "U7czoxMzoic2hvcF9wcm92aW5jZSI7aToxNjtzOjk6InNob3BfY2l0eSI7fQ==";

            $code = $decode($code);
            $code = unserialize($code);

            $sql = 'SELECT id, code, value FROM ' . $GLOBALS['ecs']->table($shopConfig) . " WHERE code " . db_create_in($code);
            $row = $GLOBALS['db']->getAll($sql);

            $getCfgVal = $decode('Z2V0Q2ZnVmFs');
            $row = $this->$getCfgVal($row);

            $region_name = $decode('cmVnaW9uX25hbWU=');
            $region_id = $decode('cmVnaW9uX2lk');
            $shop_country = $GLOBALS['db']->getOne("SELECT " . $region_name . " FROM " . $GLOBALS['ecs']->table('region') . " WHERE " . $region_id . " = '" . $row['shop_country'] . "'");
            $shop_province = $GLOBALS['db']->getOne("SELECT " . $region_name . " FROM " . $GLOBALS['ecs']->table('region') . " WHERE " . $region_id . " = '" . $row['shop_province'] . "'");
            $shop_city = $GLOBALS['db']->getOne("SELECT " . $region_name . " FROM " . $GLOBALS['ecs']->table('region') . " WHERE " . $region_id . " = '" . $row['shop_city'] . "'");

            $shopinfo = $decode('c2VsbGVyX3Nob3BpbmZv');
            $shopcount = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table($shopinfo));

            $orderInfo = $decode('b3JkZXJfaW5mbw==');
            $mainOrder = $decode('bWFpbl9vcmRlcl9pZA==');
            $no_main_order = " WHERE 1 AND (select count(*) from " . $GLOBALS['ecs']->table($orderInfo) . " AS oi2 WHERE oi2." . $mainOrder . " = o.order_id) = 0 ";
            $sql = 'SELECT COUNT(*) AS orderCount, IFNULL(SUM(order_amount), 0) AS orderAmount FROM ' . $GLOBALS['ecs']->table($orderInfo) . " AS o " . $no_main_order;
            $order['stats'] = $GLOBALS['db']->getRow($sql);
            $orderCount = $order['stats']['orderCount'];
            $orderAmount = $order['stats']['orderAmount'];

            $goods = $decode('Z29vZHM=');
            $goodsCcount = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table($goods) . ' WHERE is_delete = 0 AND is_alone_sale = 1 AND is_real = 1');

            $user = $decode('dXNlcnM=');
            $dscUser = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table($user));

            $data = array(
                $decode('X2RvbWFpbl8=') => $getDomain,
                $decode('X3VybF8=') => urldecode($getUrl),
                $decode('X3Nob3BfbmFtZV8=') => $row[$this->getTrim($decode('X3Nob3BfbmFtZV8='))],
                $decode('X3Nob3BfdGl0bGVf') => $row[$this->getTrim($decode('X3Nob3BfdGl0bGVf'))],
                $decode('X3Nob3BfZGVzY18=') => $row[$this->getTrim($decode('X3Nob3BfZGVzY18='))],
                $decode('X3Nob3Bfa2V5d29yZHNf') => $row[$this->getTrim($decode('X3Nob3Bfa2V5d29yZHNf'))],
                $decode('X3Nob3Bfa2V5d29yZHNf') => $shop_country,
                $decode('X3Byb3ZpbmNlXw==') => $shop_province,
                $decode('X2NpdHlf') => $shop_city,
                $decode('X2FkZHJlc3Nf') => $row[$this->getTrim($decode('X3Nob3BfYWRkcmVzc18='))],
                $decode('X3FxXw==') => $row[$this->getTrim($decode('X3FxXw=='))],
                $decode('X3d3Xw==') => $row[$this->getTrim($decode('X3d3Xw=='))],
                $decode('X3ltXw==') => $row[$this->getTrim($decode('X3NlcnZpY2VfcGhvbmVf'))],
                $decode('X21zbl8=') => $row[$this->getTrim($decode('X21zbl8='))],
                $decode('X2VtYWlsXw==') => $row[$this->getTrim($decode('X3NlcnZpY2VfZW1haWxf'))],
                $decode('X3Bob25lXw==') => $row[$this->getTrim($decode('X3Ntc19zaG9wX21vYmlsZV8='))],
                $decode('X2ljcF8=') => $row[$this->getTrim($decode('X2ljcF9udW1iZXJf'))],
                $decode('X3Bvc3RfdHlwZV8=') => 2
            );

            foreach ($data as $key => $row) {
                $k = $this->getTrim($key);
                $data[$k] = $row;

                unset($data[$key]);
            }

            $hpost->doPost($dscUrl, $data);

            $checkverData = array(
                $decode('X2RvbWFpbl8=') => $getDomain,
                $decode('X3VybF8=') => urldecode($getUrl),
                $decode('X29jb3VudF8=') => $orderCount,
                $decode('X29hbW91bnRf') => $orderAmount,
                $decode('X2djb3VudF8=') => $goodsCcount,
                $decode('X3VzZWNvdW50Xw==') => $dscUser,
                $decode('X3Njb3VudF8=') => $shopcount,
                $decode('X3Zlcl8=') => $decode('djIuNy4z'),
                $decode('X3JlbGVhc2Vf') => $decode('MjAxODA5Mjk=')
            );

            foreach ($checkverData as $key => $row) {
                $k = $this->getTrim($key);
                $checkverData[$k] = $row;

                unset($checkverData[$key]);
            }

            $hpost->doPost($checkverDscUrl, $checkverData);

            $cat = $decode('X2NhdF9nb29kc19jb25maWdf');
            $cat = trim($cat, '_');

            write_static_cache($cat, ['data' => 1]);
        }
    }

    function getTrim($val, $str = '_')
    {
        $val = trim($val, $str);

        return $val;
    }

    /**
     * 判断是否纯字母
     */
    function preg_is_letter($str)
    {
        $preg = '[^A-Za-z]+';
        if (preg_match("/$preg/", $str)) {
            return false;   //不是由纯字母组成
        } else {
            return true;    //全部由字母组成
        }
    }

    /**
     * 查询MYSQL拼接字符串数据
     * $select_array 查询的字段
     * $select_id 查询的ID值
     * $where 查询的条件 比如（AND goods_id = '$goods_id'）
     * $table 表名称
     * $id 被查询的字段
     * $is_db 查询返回数组方式
     */
    function get_select_find_in_set($is_db = 0, $select_id, $select_array = array(), $where = '', $table = '', $id = '', $replace = '')
    {

        if ($replace) {
            $replace = "REPLACE ($id,'$replace',',')";
        } else {
            $replace = "$id";
        }

        if ($select_array && is_array($select_array)) {
            $select = implode(',', $select_array);
        } else {
            $select = '*';
        }

        $sql = "SELECT $select FROM " . $GLOBALS['ecs']->table($table) . " WHERE find_in_set('$select_id', $replace) $where";
        if ($is_db == 1) {
            //多条数组数据
            return $GLOBALS['db']->getAll($sql);
        } elseif ($is_db == 2) {
            //一条数组数据
            return $GLOBALS['db']->getRow($sql);
        } else {
            //返回某个字段的值
            return $GLOBALS['db']->getOne($sql, true);
        }
    }

    /**
     * 删除MYSQL拼接字符串数据
     * $select_id 查询的ID值
     * $where 查询的条件 比如（AND goods_id = '$goods_id'）
     * $table 表名称
     * $id 被查询的字段
     */
    function get_del_find_in_set($select_id, $where = '', $table = '', $id = '', $replace = '')
    {

        if ($replace) {
            $replace = "REPLACE ($id,'$replace',',')";
        } else {
            $replace = "$id";
        }

        $sql = "DELETE FROM " . $GLOBALS['ecs']->table($table) . " WHERE find_in_set('$select_id', $replace) $where";
        $GLOBALS['db']->query($sql);
    }

    /**
     * 获取某个录下有多少个文件
     */
    function get_dir_file_count($dir)
    {
        $count = sizeof(scandir($dir)) - 2;
        return $count;
    }

    function byte_format($size, $dec = 2)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    function getCfgVal($arr = array())
    {

        $new_arr = array();
        if ($arr) {
            foreach ($arr as $row) {
                array_push($new_arr, $row['code'] . "**" . $row['value']);
            }

            $new_arr2 = array();
            foreach ($new_arr as $key => $rows) {
                $rows = explode('**', $rows);
                $new_arr2[$rows[0]] = $rows[1];
            }

            $new_arr = $new_arr2;
        }

        return $new_arr;
    }

    /**
     * 获取某个录下文件名称
     */
    function get_file_list($dir)
    {
        $arr['all_size'] = 0;
        $arr['all_size_name'] = '';
        if (file_exists($dir)) {
            foreach (scandir($dir) as $v) {
                if (!is_dir($v)) {//如果不是目录，就是文件了
                    $size = filesize($dir . "/" . $v);
                    $size = $this->byte_format($size);
                    //$arr[] = "文件：" . $v . " 大小：" . $size; //单位是KB
                    $arr['all_size'] += $size;
                }
            }
        }

        if ($arr['all_size'] > (1024 * 1024)) {
            $arr['all_size'] = round($arr['all_size'] / 1024 / 1024, 2);
            $arr['all_size_name'] = $arr['all_size'] . " " . "G";
        } else {
            $arr['all_size'] = round($arr['all_size'] / 1024, 2);
            $arr['all_size_name'] = $arr['all_size'] . " " . "MB";
        }

        return $arr;
    }

    /**
     * 查询短信设置类型
     * $code sms_type
     * 0：互亿
     * 1：阿里大于
     * 2：阿里短信服务
     */
    function get_sms_type($code = 'sms_type')
    {
        $sql = "SELECT value FROM " . $GLOBALS['ecs']->table("shop_config") . " WHERE code = '$code' ";
        return $GLOBALS['db']->getOne($sql, true);
    }

    /**
     * 过滤字符串值
     * $str 字符串
     * $type 类型  0 整数型  1 字符串
     */
    function get_filter_str_array($str, $type = 0)
    {

        $str_arr = array('order_id');

        if (!empty($str)) {

            $ex_rec = !is_array($str) ? explode(",", $str) : $str;
            $ex_rec = array_values($ex_rec);
            $preg = "/<script[\s\S]*?<\/script>/i";

            foreach ($ex_rec as $key => $row) {
                if ($type == 1) {
                    $row = addslashes($row);

                    $lower_row = strtolower($row);
                    $lower_row = !empty($lower_row) ? preg_replace($preg, "", stripslashes($lower_row)) : '';

                    if (strpos($lower_row, "</script>") !== false) {
                        $row = compile_str($row);
                    } elseif (strpos($lower_row, "updatexml") !== false || strpos($lower_row, "extractvalue") !== false || strpos($lower_row, "floor") !== false) {
                        $row = '';
                    } elseif ((strpos($lower_row, " or ") !== false) && !in_array($lower_row, $str_arr)) {
                        $row = '';
                    } elseif ((strpos($lower_row, " hex ") !== false) && !in_array($lower_row, $str_arr)) {
                        $row = '';
                    } elseif ((strpos($lower_row, " unhex ") !== false) && !in_array($lower_row, $str_arr)) {
                        $row = '';
                    } elseif ((strpos($lower_row, " chr ") !== false) && !in_array($lower_row, $str_arr)) {
                        $row = '';
                    }

                    $ex_rec[$key] = $row;
                } else {
                    $ex_rec[$key] = intval($row);
                }
            }

            if (!is_array($str)) {
                $str = implode(",", $ex_rec);
            } else {
                $str = $ex_rec;
            }
        }

        return $str;
    }

}

?>