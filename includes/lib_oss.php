<?php

/**
 * ECSHOP 基础函数库
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_base.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

//OSS添加图片
function get_oss_add_file($file = array()) {
    //OSS文件存储ecmoban模板堂 --zhuo start
    if ($file) {
        
        if (!isset($GLOBALS['_CFG']['open_oss'])) {
            $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'open_oss'";
            $is_oss = $GLOBALS['db']->getOne($sql, true);
        }else{
            $is_oss = $GLOBALS['_CFG']['open_oss'];
        }
        
        if ($is_oss == 1) {
            $bucket_info = get_bucket_info();
            $url = $GLOBALS['ecs']->url();

            $self = explode("/", substr(PHP_SELF, 1));
            $count = count($self);
            if ($count > 1) {
                $real_path = $self[$count - 2];
                if ($real_path == SELLER_PATH) {
                    $str_len = -(str_len(SELLER_PATH) + 1);
                    $url = substr($GLOBALS['ecs']->url(), 0, $str_len);
                }
            }

            $urlip = get_ip_url($url);
            $url = $urlip . "oss.php?act=upload";

            $Http = new Http();
            $post_data = array(
                'bucket' => $bucket_info['bucket'],
                'keyid' => $bucket_info['keyid'],
                'keysecret' => $bucket_info['keysecret'],
                'is_cname' => $bucket_info['is_cname'],
                'endpoint' => $bucket_info['outside_site'],
                'is_delimg' => $bucket_info['is_delimg'],
                'object' => $file
            );
            
            $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }
}

//OSS删除图片
function get_oss_del_file($file = array()) {
    //OSS文件存储ecmoban模板堂 --zhuo start
    if ($file) {
        //OSS文件存储ecmoban模板堂 --zhuo start
        
        if (!isset($GLOBALS['_CFG']['open_oss'])) {
            $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'open_oss'";
            $is_oss = $GLOBALS['db']->getOne($sql, true);
        }else{
            $is_oss = $GLOBALS['_CFG']['open_oss'];
        }
        
        if ($is_oss == 1) {
            $bucket_info = get_bucket_info();
            $url = $GLOBALS['ecs']->url();

            $self = explode("/", substr(PHP_SELF, 1));
            $count = count($self);
            if ($count > 1) {
                $real_path = $self[$count - 2];
                if ($real_path == SELLER_PATH) {
                    $str_len = -(str_len(SELLER_PATH) + 1);
                    $url = substr($GLOBALS['ecs']->url(), 0, $str_len);
                }
            }

            $urlip = get_ip_url($url);
            $url = $urlip . "oss.php?act=del_file";
            $Http = new Http();
            $post_data = array(
                'bucket' => $bucket_info['bucket'],
                'keyid' => $bucket_info['keyid'],
                'keysecret' => $bucket_info['keysecret'],
                'is_cname' => $bucket_info['is_cname'],
                'endpoint' => $bucket_info['outside_site'],
                'object' => $file
            );

            $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }
}

//单个或批量删除图片
function get_del_batch($checkboxs = '', $val_id = '', $select = '', $id = '', $table = '', $del = 0, $fileDir = '') {

    $select = implode(',', $select);

    $is = true;
    if (!empty($checkboxs)) {
        $where = db_create_in($checkboxs, $id);
    } else if (!empty($val_id)) {
        $where = " $id = '$val_id'";
    } else {
        $is = false;
    }

    if ($is) {
        $sql = "SELECT $select  FROM " . $GLOBALS['ecs']->table($table) . " WHERE " . $where;
        $list = $GLOBALS['db']->getAll($sql);

        $arr = array();
        $select = explode(',', $select);
        $val = '';
        foreach ($list as $key => $row) {
            $arr[] = $row;
            foreach ($select as $ks => $rows) {
                if ($del == 1) {
                    $val .= $row[$rows] . ",";
                    @unlink(ROOT_PATH . $row[$rows]);
                } else {
                    $val .= $fileDir . $row[$rows] . ",";
                    @unlink(ROOT_PATH . $fileDir . $row[$rows]);
                }
            }
            $arr['list'] .= $val;
        }

        if ($arr) {
            $str_list = substr($arr['list'], 0, -1);
            $str_list = explode(',', $str_list);
        } else {
            $str_list = array();
        }
        
        if (!isset($GLOBALS['_CFG']['open_oss'])) {
            $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'open_oss'";
            $is_oss = $GLOBALS['db']->getOne($sql, true);
        }else{
            $is_oss = $GLOBALS['_CFG']['open_oss'];
        }

        //OSS文件存储ecmoban模板堂 --zhuo start
        if ($is_oss == 1) {
            $bucket_info = get_bucket_info();
            $url = $GLOBALS['ecs']->url();

            $self = explode("/", substr(PHP_SELF, 1));
            $count = count($self);
            if ($count > 1) {
                $real_path = $self[$count - 2];
                if ($real_path == SELLER_PATH) {
                    $str_len = -(str_len(SELLER_PATH) + 1);
                    $url = substr($GLOBALS['ecs']->url(), 0, $str_len);
                }
            }

            $urlip = get_ip_url($url);
            $url = $urlip . "oss.php?act=del_file";
            $Http = new Http();
            $post_data = array(
                'bucket' => $bucket_info['bucket'],
                'keyid' => $bucket_info['keyid'],
                'keysecret' => $bucket_info['keysecret'],
                'is_cname' => $bucket_info['is_cname'],
                'endpoint' => $bucket_info['outside_site'],
                'object' => $str_list
            );

            $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }
}

/**
 * 删除可视化模板OSS标识文件
 */ 
function del_visual_templates($ip = array(), $suffix = '', $act = 'del_hometemplates', $seller_id = 0) {
    if ($ip) {

        $where = '';
        if ($seller_id) {
            $where .= "&seller_id=" . $seller_id;
        }

        $Http = new Http();
        if (count($ip) > 1) {
            foreach ($ip as $key => $row) {
                $url = $GLOBALS['ecs']->http() . $row . "/" . "ajax_dialog.php?act=" . $act . "&suffix=" . $suffix . $where;
                $Http->doGet($url);
            }
        } else {
            $url = $GLOBALS['ecs']->http() . $ip . "/" . "ajax_dialog.php?act=" . $act . "&suffix=" . $suffix . $where;
            $Http->doGet($url);
        }
    }
}

/*
 * 获取IP域名
 */
function get_ip_url($url, $type = 0){
    
    $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'server_model'";
    $server_model = $GLOBALS['db']->getOne($sql, true);
    
    if($server_model){

        $http = $GLOBALS['ecs']->http();
        $file = ROOT_PATH . DATA_DIR . "/urlip.txt";
        $file = file_get_contents($file);
        $file = trim($file);
        
        if ($type == 1) {
            return $http . $file;
        } else {
            return $http . $file . "/";
        }
    }else{
        return $url;
    }
}

//OSS下载文件
function get_oss_list_file($file = array()) {
    //OSS文件存储ecmoban模板堂 --zhuo start
    if ($file) {
        //OSS文件存储ecmoban模板堂 --zhuo start
        
        if (!isset($GLOBALS['_CFG']['open_oss'])) {
            $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'open_oss'";
            $is_oss = $GLOBALS['db']->getOne($sql, true);
        }else{
            $is_oss = $GLOBALS['_CFG']['open_oss'];
        }
        
        if ($is_oss == 1) {
            $bucket_info = get_bucket_info();
            $url = $GLOBALS['ecs']->url();

            $self = explode("/", substr(PHP_SELF, 1));
            $count = count($self);
            if ($count > 1) {
                $real_path = $self[$count - 2];
                if ($real_path == SELLER_PATH) {
                    $str_len = -(str_len(SELLER_PATH) + 1);
                    $url = substr($GLOBALS['ecs']->url(), 0, $str_len);
                }
            }

            $urlip = get_ip_url($url);
            $url = $urlip . "oss.php?act=list_file";
            $Http = new Http();
            $post_data = array(
                'bucket' => $bucket_info['bucket'],
                'keyid' => $bucket_info['keyid'],
                'keysecret' => $bucket_info['keysecret'],
                'is_cname' => $bucket_info['is_cname'],
                'endpoint' => $bucket_info['outside_site'],
                'object' => $file
            );

            return $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }
}