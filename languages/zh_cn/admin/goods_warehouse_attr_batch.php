<?php

/**
 * ECSHOP 商品批量上传、修改语言文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods_batch.php 17217 2011-01-19 06:29:08Z liubo $
 */

$_LANG['csv_file'] = '上传批量csv文件：';
$_LANG['notice_file'] = '（CSV文件中一次上传商品数量最好不要超过40，CSV文件大小最好不要超过500K.）';
$_LANG['file_charset'] = '文件编码：';
$_LANG['download_file'] = '下载批量CSV文件（%s）';
$_LANG['use_help'] = '<ul>' .
          '<li>根据使用习惯，下载相应语言的csv文件，例如中国内地用户下载简体中文语言的文件，港台用户下载繁体语言的文件；</li>' .
          '<li>选择所上传商品的分类以及文件编码，上传csv文件</li>' .
        '</ul>';

$_LANG['goods_name'] = '商品名称：';
$_LANG['js_languages']['please_select_goods'] = '请您选择商品';
$_LANG['js_languages']['please_input_sn'] = '请您输入商品货号';
$_LANG['js_languages']['goods_cat_not_leaf'] = '请选择底级分类';
$_LANG['js_languages']['please_select_cat'] = '请您选择所属分类';
$_LANG['js_languages']['please_upload_file'] = '请您上传批量csv文件';

// 批量上传商品的字段
$_LANG['upload_warehouse_attr']['goods_id'] = '商品编号';
$_LANG['upload_warehouse_attr']['goods_name'] = '商品名称';
$_LANG['upload_warehouse_attr']['shop_name'] = '商家名称';
$_LANG['upload_warehouse_attr']['seller_id'] = '商家ID';
$_LANG['upload_warehouse_attr']['region_name'] = '仓库名称';
$_LANG['upload_warehouse_attr']['attr_name'] = '属性名称';
$_LANG['upload_warehouse_attr']['attr_price'] = '属性价格';
$_LANG['upload_warehouse_attr']['state'] = '状态';

$_LANG['save_products'] = '保存商品仓库属性成功';
$_LANG['add_area_batch'] = '继续批量添加商品仓库属性';

$_LANG['13_batch_add'] = '仓库属性价格批量上传';
$_LANG['02_cat_and_goods'] = '商品';

$_LANG['current_modification_data'] = '当前需要修改的数据有';
?>