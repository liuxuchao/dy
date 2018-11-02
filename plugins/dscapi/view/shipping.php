<?php

/**
 * DSC 快递方式接口入口
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: shipping.php zhuo $
 */
/* 获取传值 */
$shipping_id = isset($_REQUEST['shipping_id']) ? $base->get_intval($_REQUEST['shipping_id']) : -1;                  //快递方式ID
$shipping_name = isset($_REQUEST['shipping_name']) ? $base->get_addslashes($_REQUEST['shipping_name']) : -1;                  //快递名称
$shipping_code = isset($_REQUEST['shipping_code']) ? $base->get_addslashes($_REQUEST['shipping_code']) : -1;            //快递编码

$val = array(
    'shipping_id' => $shipping_id,
    'shipping_code' => $shipping_code,
    'shipping_name' => $shipping_name,
    'shipping_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$shipping = new app\controller\shipping($val);

switch ($method) {

    /**
     * 获取快递方式列表
     */
    case 'dsc.shipping.list.get':
        
        $table = array(
            'shipping' => 'shipping'
        );

        $result = $shipping->get_shipping_list($table);

        die($result);
        break;

    /**
     * 获取单条快递方式信息
     */
    case 'dsc.shipping.info.get':
        
        $table = array(
            'shipping' => 'shipping'
        );

        $result = $shipping->get_shipping_details($table);

        die($result);
        break;

    /**
     * 插入快递方式信息
     */
    case 'dsc.shipping.insert.post':
        
        $table = array(
            'shipping' => 'shipping'
        );

        $result = $shipping->get_shipping_insert($table);

        die($result);
        break;

    /**
     * 更新快递方式信息
     */
    case 'dsc.shipping.update.post':
        
        $table = array(
            'shipping' => 'shipping'
        );

        $result = $shipping->get_shipping_update($table);

        die($result);
        break;
    
    /**
     * 删除快递方式信息
     */
    case 'dsc.shipping.del.get':
        
        $table = array(
            'shipping' => 'shipping'
        );

        $result = $shipping->get_shipping_delete($table);

        die($result);
        break;

    default :

        echo "非法接口连接";
        break;
}