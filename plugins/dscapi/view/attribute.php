<?php

/**
 * DSC 仓库仓库地区接口入口
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: attribute.php zhuo $
 */
/* 获取传值 */
$seller_type = isset($_REQUEST['seller_type']) ? $base->get_intval($_REQUEST['seller_type']) : -1;              //商家ID字段类型
$seller_id = isset($_REQUEST['seller_id']) ? $base->get_intval($_REQUEST['seller_id']) : -1;                    //商家ID
$attr_id = isset($_REQUEST['attr_id']) ? $base->get_intval($_REQUEST['attr_id']) : -1;                  	//属性ID
$cat_id = isset($_REQUEST['cat_id']) ? $base->get_intval($_REQUEST['cat_id']) : -1;                  		//属性组ID
$attr_name = isset($_REQUEST['attr_name']) ? $base->get_addslashes($_REQUEST['attr_name']) : -1;        	//属性名称
$attr_type = isset($_REQUEST['attr_type']) ? $base->get_intval($_REQUEST['attr_type']) : -1;            	//唯一属性标记

$val = array(
    'seller_type' => $seller_type,
    'seller_id' => $seller_id,
    'attr_id' => $attr_id,
    'cat_id' => $cat_id,
    'attr_name' => $attr_name,
    'attr_type' => $attr_type,
    'attribute_select' => $data,
    'page_size' => $page_size,
    'page' => $page,
    'sort_by' => $sort_by,
    'sort_order' => $sort_order,
    'format' => $format
);

/* 初始化商品类 */
$attribute = new app\controller\attribute($val);

switch ($method) {

    /**
     * 获取属性类型列表
     */
    case 'dsc.goodstype.list.get':
        
        $table = array(
            'goodstype' => 'goods_type'
        );

        $result = $attribute->get_goodstype_list($table);

        die($result);
        break;

    /**
     * 获取单条属性类型信息
     */
    case 'dsc.goodstype.info.get':
        
        $table = array(
            'goodstype' => 'goods_type'
        );

        $result = $attribute->get_goodstype_info($table);

        die($result);
        break;

    /**
     * 插入属性类型信息
     */
    case 'dsc.goodstype.insert.post':
        
        $table = array(
            'goodstype' => 'goods_type'
        );

        $result = $attribute->get_goodstype_insert($table);

        die($result);
        break;

    /**
     * 更新属性信息
     */
    case 'dsc.goodstype.update.post':
        
        $table = array(
            'goodstype' => 'goods_type'
        );

        $result = $attribute->get_goodstype_update($table);

        die($result);
        break;
    
    /**
     * 删除属性信息
     */
    case 'dsc.goodstype.del.get':
        
        $table = array(
            'goodstype' => 'goods_type'
        );

        $result = $attribute->get_goodstype_delete($table);

        die($result);
        break;
    
    /**
     * 获取属性列表
     */
    case 'dsc.attribute.list.get':
        
        $table = array(
            'attribute' => 'attribute'
        );

        $result = $attribute->get_attribute_list($table);

        die($result);
        break;

    /**
     * 获取单条属性信息
     */
    case 'dsc.attribute.info.get':
        
        $table = array(
            'attribute' => 'attribute'
        );

        $result = $attribute->get_attribute_info($table);

        die($result);
        break;

    /**
     * 插入属性信息
     */
    case 'dsc.attribute.insert.post':
        
        $table = array(
            'attribute' => 'attribute'
        );

        $result = $attribute->get_attribute_insert($table);

        die($result);
        break;

    /**
     * 更新属性信息
     */
    case 'dsc.attribute.update.post':
        
        $table = array(
            'attribute' => 'attribute'
        );

        $result = $attribute->get_attribute_update($table);

        die($result);
        break;
    
    /**
     * 删除属性信息
     */
    case 'dsc.attribute.del.get':
        
        $table = array(
            'attribute' => 'attribute'
        );

        $result = $attribute->get_attribute_delete($table);

        die($result);
        break;

    default :

        echo "非法接口连接";
        break;
}