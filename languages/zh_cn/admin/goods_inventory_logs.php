<?php

/**
 * ECSHOP 管理中心管理员操作内容语言文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: admin_logs.php 17217 2011-01-19 06:29:08Z liubo $
*/
/* 字段信息 */
$_LANG['log_id'] = '编号';
$_LANG['drop_logs'] = '删除日志';
$_LANG['goods_name'] = '商品名称';
$_LANG['goods_attr'] = '商品属性';
$_LANG['order_sn'] = '订单号';
$_LANG['order_operation_type'] = '操作类型';
$_LANG['operation_admin'] = '操作人员';
$_LANG['inventory_type'] = '库存类型';
$_LANG['inventory'] = '库存';
$_LANG['operation_time'] = '操作时间';
$_LANG['comfrom'] = '搜索';
$_LANG['operation_type'] = '操作类型';
$_LANG['operation_info'] = '操作信息';

/* 提示信息 */
$_LANG['drop_sueeccud'] = '操作成功!';
$_LANG['batch_drop_success'] = '成功删除了 %d 个日志记录';

/*大商创1.5版本新增 sunle*/
$_LANG['region'] = '地区';
$_LANG['delivery_time'] = '发货时'; //值:0
$_LANG['order_time'] = '下单时'; //值:1
$_LANG['order_invalid'] = '订单无效'; //值:2
$_LANG['order_cancel'] = '订单取消'; //值:3
$_LANG['order_confirm_receipt'] = '订单确认收货'; //值:4
$_LANG['order_not_shipped'] = '订单设为未发货'; //值:5
$_LANG['order_return'] = '订单退货'; //值:6
$_LANG['add_goods'] = '添加商品'; //值:7
$_LANG['edit_goods'] = '编辑商品'; //值:8
$_LANG['add_goods_product'] = '添加商品货品'; //值:9
$_LANG['edit_goods_product'] = '编辑商品货品'; //值:10
$_LANG['goods_attr_stock'] = '商品货品库存'; 
$_LANG['goods_stock'] = '商品库存';
$_LANG['paid_time'] = '付款时'; //值:15

$_LANG['confirm_batch_delete'] = '确定批量删除？';
$_LANG['time_end_not_null'] = '结束时间不能为空';
$_LANG['time_start_not_null'] = '开始时间不能为空';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '展示了商城商品入库/出库的操作日志。';
$_LANG['operation_prompt_content']['list'][1] = '可以按照时间段筛选、商品名称关键字搜索，查看具体商品入库/出库日志。';
$_LANG['operation_prompt_content']['list'][2] = '侧边栏可进行高级搜索。';
?>