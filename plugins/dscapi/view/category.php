<?php

/**
 * DSC 类目接口入口
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: category.php zhuo $
 */
/* 获取传值 */
$seller_id = isset($_REQUEST['seller_id']) ? $base->get_intval($_REQUEST['seller_id']) : -1;                    //商家ID
$cat_id = isset($_REQUEST['cat_id']) ? $base->get_intval($_REQUEST['cat_id']) : -1;                             //分类ID
$parent_id = isset($_REQUEST['parent_id']) ? $base->get_intval($_REQUEST['parent_id']) : -1;         		//分类父级ID
$cat_name = isset($_REQUEST['cat_name']) ? $base->get_addslashes($_REQUEST['cat_name']) : -1;        		//分类名称

$val = array(
    'seller_id' => $seller_id,
    'cat_id' => $cat_id,
    'parent_id' => $parent_id,
    'cat_name' => $cat_name,
    'category_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$category = new app\controller\category($val);

switch ($method) {

    /**
     * 获取分类列表
     */
    case 'dsc.category.list.get':
        
        $table = array(
            'category' => 'category'
        );

        $result = $category->get_category_list($table);

        die($result);
        break;

    /**
     * 获取单条分类信息
     */
    case 'dsc.category.info.get':
        
        $table = array(
            'category' => 'category'
        );

        $result = $category->get_category_info($table);

        die($result);
        break;

    /**
     * 插入分类信息
     */
    case 'dsc.category.insert.post':
        
        $table = array(
            'category' => 'category'
        );

        $result = $category->get_category_insert($table);

        die($result);
        break;

    /**
     * 更新分类信息
     */
    case 'dsc.category.update.post':
        
        $table = array(
            'category' => 'category'
        );

        $result = $category->get_category_update($table);

        die($result);
        break;
    
    /**
     * 删除分类信息
     */
    case 'dsc.category.del.get':
        
        $table = array(
            'category' => 'category'
        );

        $result = $category->get_category_delete($table);

        die($result);
        break;
    
    /**
     * 获取商家分类列表
     */
    case 'dsc.category.seller.list.get':
        
        $table = array(
            'seller' => 'merchants_category'
        );

        $result = $category->get_category_seller_list($table);

        die($result);
        break;

    /**
     * 获取单条商家分类信息
     */
    case 'dsc.category.seller.info.get':
        
        $table = array(
            'seller' => 'merchants_category'
        );

        $result = $category->get_category_seller_info($table);

        die($result);
        break;

    /**
     * 插入商家分类信息
     */
    case 'dsc.category.seller.insert.post':
        
        $table = array(
            'seller' => 'merchants_category'
        );

        $result = $category->get_category_seller_insert($table);

        die($result);
        break;

    /**
     * 更新商家分类信息
     */
    case 'dsc.category.seller.update.post':
        
        $table = array(
            'seller' => 'merchants_category'
        );

        $result = $category->get_category_seller_update($table);

        die($result);
        break;
    
    /**
     * 删除商家分类信息
     */
    case 'dsc.category.seller.del.get':
        
        $table = array(
            'seller' => 'merchants_category'
        );

        $result = $category->get_category_seller_delete($table);

        die($result);
        break;

    default :

        echo "非法接口连接";
        break;
}