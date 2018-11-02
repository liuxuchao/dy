<?php

/**
 * DSC 地区接口控制类
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: Region.php 2017-01-11 zhuo $
 */

namespace app\controller;

use app\func\common;
use app\func\base;
use app\model\productModel;
use languages\productLang;

class product extends productModel {

    private $table;                             //表名称
    private $alias;                             //表别名
    private $product_select = array();          //查询字段数组
    private $select;                            //查询字段字符串组
    private $product_id = 0;                    //货品ID
    private $goods_id = 0;                      //商品ID
    private $product_sn = '';                   //货品编码
    private $bar_code = 0;                      //条形码
    private $warehouse_id = 0;                  //仓库ID
    private $area_id = 0;                       //地区ID
    private $format = 'json';                   //返回格式（json, xml, array）
    private $page_size = 10;                    //每页条数
    private $page = 1;                          //当前页
    private $wehre_val;                         //查询条件
    private $productLangList;                   //语言包
    private $sort_by;                           //排序字段
    private $sort_order;                        //排序升降

    public function __construct($where = array()) {
        $this->product($where);

        $this->wehre_val = array(
            'product_id' => $this->product_id,
            'goods_id' => $this->goods_id,
            'product_sn' => $this->product_sn,
            'bar_code' => $this->bar_code,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
        );
        
        $this->where = productModel::get_where($this->wehre_val);
        $this->select = base::get_select_field($this->product_select);
    }

    public function product($where = array()) {

        /* 初始查询条件值 */
        $this->product_id = $where['product_id'];
        $this->goods_id = $where['goods_id'];
        $this->product_sn = $where['product_sn'];
        $this->bar_code = $where['bar_code'];
        $this->warehouse_id = $where['warehouse_id'];
        $this->area_id = $where['area_id'];
        $this->product_select = $where['product_select'];
        $this->format = $where['format'];
        $this->page_size = $where['page_size'];
        $this->page = $where['page'];
        $this->sort_by = $where['sort_by'];
        $this->sort_order = $where['sort_order'];
        
        $this->productLangList = productLang::lang_product_request();
    }

    /**
     * 多条商品货品信息
     *
     * @access  public
     * @return  array
     */
    public function get_product_list($table) {
        
        $this->table = $table['product'];
        $result = productModel::get_select_list($this->table, $this->select, $this->where, $this->page_size, $this->page, $this->sort_by, $this->sort_order);
        $result = productModel::get_list_common_data($result, $this->page_size, $this->page, $this->productLangList, $this->format);
        
        return $result;
    }

    /**
     * 单条商品货品信息
     *
     * @access  public
     * @return  array
     */
    public function get_product_info($table) {

        $this->table = $table['product'];
        $result = productModel::get_select_info($this->table, $this->select, $this->where);
        
        if (strlen($this->where) != 1) {
            $result = productModel::get_info_common_data_fs($result, $this->productLangList, $this->format);
        } else {
            $result = productModel::get_info_common_data_f($this->productLangList, $this->format);
        }
        
        return $result;
    }

    /**
     * 插入商品货品信息
     *
     * @access  public
     * @param   integer $table     表名称
     * @param   integer $product_select     字段信息
     * @return  array
     */
    function get_product_insert($table) {

        $this->table = $table['product'];

        $productLang = productLang::lang_product_insert();
        
        $select = $this->product_select;

        if ($select) {
            if (!isset($select['product_id'])) {

                if (isset($select['goods_id']) && !empty($select['goods_id']) && isset($select['product_sn']) && !empty($select['product_sn'])) {
                    
                    $where = '';
                    if(isset($select['user_id'])){
                        if(!empty($select['user_id'])){
                            $user_id = $select['user_id'];
                        }else{
                            $user_id = 0;
                        }
                        
                        $where = " AND (SELECT user_id FROM " .$GLOBALS['ecs']->table($table['goods']). " WHERE goods_id = '" .$select['goods_id']. "') = '$user_id'";
                    }
                    
                    if (isset($select['warehouse_id']) && !empty($select['warehouse_id'])) {
                        $where .= " AND warehouse_id = '" .$select['warehouse_id']. "'";
                    } elseif (isset($select['area_id']) && !empty($select['area_id'])) {
                        $where .= " AND area_id = '" .$select['area_id']. "'";
                    }

                    $where = "product_sn = '" . $select['product_sn'] . "' $where";
                    $info = $this->get_select_info($this->table, "*", $where);

                    if (!$info) {
                        return productModel::get_insert($this->table, $this->product_select, $this->format);
                    } else {
                        $error = productLang::INSERT_DATA_EXIST_FAILURE;
                        $info = $select;
                    }
                } else {
                    $error = productLang::INSERT_KEY_PARAM_FAILURE;
                    $string = 'goods_id, product_sn';
                }
            } else {
                $info = $select;
                $error = productLang::INSERT_CANNOT_PARAM_FAILURE;
                $string = 'product_id';
            }
        }else{
            $error = productLang::INSERT_NOT_PARAM_FAILURE;
        }

        if(in_array($error, [productLang::INSERT_CANNOT_PARAM_FAILURE,productLang::INSERT_KEY_PARAM_FAILURE])){
            $productLang['msg_failure'][$error]['failure'] = sprintf($productLang['msg_failure'][$error]['failure'], $string);
        }
        
        $common_data = array(
            'result' => "failure",
            'msg' => $productLang['msg_failure'][$error]['failure'],
            'error' => $productLang['msg_failure'][$error]['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新商品货品信息
     *
     * @access  public
     * @param   integer $table     表名称
     * @param   integer $product_select     商品字段信息
     * @return  array
     */
    function get_product_update($table) {

        $this->table = $table['product'];

        $productLang = productLang::lang_goods_update();
        
        $select = $this->goods_select;
        
        if ($select) {
            if (!isset($select['goods_id'])) {
                if (strlen($this->where) != 1) {

                    $info = $this->get_select_info($this->table, "*", $this->where);
                    if (!$info) {
                        $error = productLang::UPDATE_DATA_NULL_FAILURE;
                    } else {
                        
                        $where = '';
                        if (isset($select['user_id'])) {
                            if (!empty($select['user_id'])) {
                                $user_id = $select['user_id'];
                            } else {
                                $user_id = 0;
                            }

                            $where = " AND (SELECT user_id FROM " . $GLOBALS['ecs']->table($table['goods']) . " WHERE goods_id = '" . $select['goods_id'] . "') = '$user_id'";
                        }

                        if (isset($select['warehouse_id']) && !empty($select['warehouse_id'])) {
                            $where .= " AND warehouse_id = '" . $select['warehouse_id'] . "'";
                        } elseif (isset($select['area_id']) && !empty($select['area_id'])) {
                            $where .= " AND area_id = '" . $select['area_id'] . "'";
                        }

                        $where = "product_sn = '" . $select['product_sn'] . "' $where";
                        $info = $this->get_select_info($this->table, "*", $where);
                        
                        if($info){
                            $error = productLang::UPDATE_DATA_EXIST_FAILURE;
                            $info = $select;
                        }else{
                            return productModel::get_update($this->table, $this->product_select, $this->where, $this->format, $info);
                        }
                    }
                } else {
                    $error = productLang::UPDATE_NULL_PARAM_FAILURE;
                }
            } else {
                $error = productLang::UPDATE_CANNOT_PARAM_FAILURE;
                $string = 'goods_id';
            }
        } else {
            $error = productLang::UPDATE_NOT_PARAM_FAILURE;
        }

        if (in_array($error, [productLang::UPDATE_CANNOT_PARAM_FAILURE])) {
            $productLang['msg_failure'][$error]['failure'] = sprintf($productLang['msg_failure'][$error]['failure'], $string);
        }

        $common_data = array(
            'result' => "failure",
            'msg' => $productLang['msg_failure'][$error]['failure'],
            'error' => $productLang['msg_failure'][$error]['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 删除商品货品信息
     *
     * @access  public
     * @param   string where 查询条件
     * @return  array
     */
    function get_product_delete($table) {

        $this->table = $table['product'];
        return productModel::get_delete($this->table, $this->where, $this->format);
    }
}
