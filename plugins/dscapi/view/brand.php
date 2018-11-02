<?php

/**
 * DSC 品牌接口入口
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: brand.php zhuo $
 */
/* 获取传值 */
$brand_id = isset($_REQUEST['brand_id']) ? $base->get_intval($_REQUEST['brand_id']) : -1;                  //品牌ID
$brand_name = isset($_REQUEST['brand_name']) ? $base->get_addslashes($_REQUEST['brand_name']) : -1;        //品牌名称

$val = array(
    'brand_id' => $brand_id,
    'brand_name' => $brand_name,
    'brand_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$brand = new app\controller\brand($val);

switch ($method) {

    /**
     * 获取仓库地区列表
     */
    case 'dsc.brand.list.get':
        
        $table = array(
            'brand' => 'brand'
        );

        $result = $brand->get_brand_list($table);

        die($result);
        break;

    /**
     * 获取单条仓库地区信息
     */
    case 'dsc.brand.info.get':
        
        $table = array(
            'brand' => 'brand'
        );

        $result = $brand->get_brand_info($table);

        die($result);
        break;

    /**
     * 插入仓库地区信息
     */
    case 'dsc.brand.insert.post':
        
        $table = array(
            'brand' => 'brand'
        );

        $result = $brand->get_brand_insert($table);

        die($result);
        break;

    /**
     * 更新仓库地区信息
     */
    case 'dsc.brand.update.post':
        
        $table = array(
            'brand' => 'brand'
        );

        $result = $brand->get_brand_update($table);

        die($result);
        break;
    
    /**
     * 删除仓库地区信息
     */
    case 'dsc.brand.del.get':
        
        $table = array(
            'brand' => 'brand'
        );

        $result = $brand->get_brand_delete($table);

        die($result);
        break;

    default :

        echo "非法接口连接";
        break;
}