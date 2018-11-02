<?php

/**
 * ECSHOP 地区列表管理语言文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: area_manage.php 17217 2011-01-19 06:29:08Z liubo $
*/

/* 字段信息 */
$_LANG['region_id'] = '地区编号';
$_LANG['region_name'] = '地区名称';
$_LANG['region_type'] = '地区类型';
$_LANG['belonged_to_region'] = '所属地区';
$_LANG['region_code'] = '地区编码';
$_LANG['belonged_to_warehouse'] = '所在仓库';
$_LANG['warehouse_list'] = '仓库列表';
$_LANG['code'] = '编码';
$_LANG['region_list'] = '地区列表';
$_LANG['region_manage'] = '区域管理';

$_LANG['05_area_list_01'] = '仓库管理';
$_LANG['warehouse_freight_template'] = '仓库运费模板';
$_LANG['data_list'] = '自提时间段';

$_LANG['area'] = '地区';
$_LANG['area_next'] = '以下';
$_LANG['country'] = '一级地区';
$_LANG['province'] = '二级地区';
$_LANG['city'] = '三级地区';
$_LANG['cantonal'] = '四级地区';
$_LANG['back_page'] = '返回上一级';
$_LANG['manage_area'] = '管理';
$_LANG['region_name_empty'] = '区域名称不能为空！';
$_LANG['add_country'] = '新增一级地区';
$_LANG['add_province'] = '新增二级地区';
$_LANG['add_city'] = '增加三级地区';
$_LANG['add_cantonal'] = '增加四级地区';
$_LANG['class_one'] = '一级';

/* JS语言项 */
$_LANG['js_languages']['region_name_empty'] = '您必须输入地区的名称!';
$_LANG['js_languages']['warehouse_name_empty'] = '您必须输入仓库的名称!';
$_LANG['js_languages']['select_region_name_empty'] = '请选择地区名称';
$_LANG['js_languages']['option_name_empty'] = '必须输入调查选项名称!';
$_LANG['js_languages']['drop_confirm'] = '您确定要删除这条记录吗?';
$_LANG['js_languages']['drop'] = '删除';
$_LANG['js_languages']['country'] = '一级地区';
$_LANG['js_languages']['province'] = '二级地区';
$_LANG['js_languages']['city'] = '三级地区';
$_LANG['js_languages']['cantonal'] = '四级地区';

/* 提示信息 */
$_LANG['add_area_error'] = '添加新地区失败!';
$_LANG['region_name_exist'] = '已经有相同的地区名称存在!';
$_LANG['parent_id_exist'] = '该区域下有其它下级地区存在, 不能删除!';
$_LANG['form_notic'] = '点击查看下级地区';
$_LANG['area_drop_confirm'] = '如果订单或用户默认配送方式中使用以下地区，这些地区信息将显示为空。您确认要删除这条记录吗?';
$_LANG['region_code_exist'] = '已经有相同的编码存在!';

//运费
$_LANG['fee_compute_mode'] = '费用计算方式';
$_LANG['fee_by_weight'] = '按重量计算';
$_LANG['fee_by_number'] = '按商品件数计算';
$_LANG['free_money'] = '免费额度';
$_LANG['pay_fee'] = '货到付款支付费用';

$_LANG['not_find_plugin'] = '没有找到指定的配送方式的插件。';

$_LANG['originating_place'] = '始发地';
$_LANG['reach_the_destination'] = '到达目的地';
$_LANG['logistics_distribution'] = '物流配送';
$_LANG['logistics_info'] = '物流信息';
$_LANG['select_logistics_company'] = '已选择物流公司';
$_LANG['freight'] = '运费';
$_LANG['new_add_warehouse'] = '新增仓库';
$_LANG['warehouse_new_add_region'] = '仓库新增地区';
$_LANG['add_region'] = '新增地区';
$_LANG['freight_guanli'] = '运费管理';
$_LANG['distribution_mode'] = '配送方式';
$_LANG['distribution_mode_desc'] = '配送方式描述';
$_LANG['not_distribution_mode'] = '未添加配送方式';
$_LANG['set_distribution_mode'] = '设置仓库运费模板';

$_LANG['warehouse_confirm'] = '确定要移除选定的运费模板么？';
$_LANG['freight_template_name'] = '运费模板名称';
$_LANG['originating_warehouse'] = '始发仓库';
$_LANG['reach_region'] = '抵达地区';
$_LANG['warehouse_confirm_code'] = '确定删除编号为';
$_LANG['warehouse_confirm_code_2'] = '的模板吗？';

$_LANG['remove_success'] = '移除成功';
$_LANG['remove_fail'] = '移除失败';

$_LANG['warehouse_freight_formwork_list'] = '仓库运费模板列表 - ';
$_LANG['edit_warehouse_freight_formwork'] = '仓库运费模板编辑 - ';
$_LANG['warehouse_freight_formwork'] = '仓库运费模板_';
$_LANG['add_freight_formwork'] = '新增运费模板';
$_LANG['back_dispatching_llist'] = '返回配送列表';
$_LANG['back_template_llist'] = '返回模板列表';

$_LANG['info_fillin_complete'] = '请将信息填写完整';
$_LANG['add_freight_success'] = '运费添加成功';
$_LANG['template_arrive_region'] = '模板抵达地区已存在！';

$_LANG['template_add_success'] = '模板添加成功';
$_LANG['template_edit_success'] = '模板修改成功';

$_LANG['freight_add_success'] = '运费添加成功';
$_LANG['freight_edit_success'] = '运费编辑成功';

$_LANG['select_dispatching_mode'] = '请选择配送方式';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['freight'][0] = '可添加多个配送方式。';

$_LANG['operation_prompt_content']['list'][0] = '仓库下的地区，展示一级仓库下的所有地区。';
$_LANG['operation_prompt_content']['list'][1] = '仓库地区最多可添加到四级地区。';

$_LANG['operation_prompt_content']['list_stair'][0] = '可在输入框输入仓库名称进行添加新仓库。';
$_LANG['operation_prompt_content']['list_stair'][1] = '一级仓库管理可新增该仓库的地区。';
$_LANG['operation_prompt_content']['list_stair'][2] = '仓库会在添加商品选择仓库模式时会使用到，在前台商品详情页配送也会用到，请谨慎添加仓库。';

$_LANG['operation_prompt_content']['shopping_list'][0] = '展示所有仓库配送方式。';
$_LANG['operation_prompt_content']['shopping_list'][1] = '给仓库配送方式设置运费模板，如果仓库单个地区没有设置仓库运费时，系统会默认调用仓库运费模板。';

$_LANG['operation_prompt_content']['tpl_info'][0] = '当目的地不选择地区添加地区时会添加“<em>全国</em>”';

$_LANG['operation_prompt_content']['tpl_list'][0] = '展示所有仓库配送方式运费模板列表。”';
$_LANG['operation_prompt_content']['tpl_list'][1] = '可以设置仓库到不同地区的运费，根据实际需求谨慎设置。';

// 商家后台
$_LANG['select_warehouse'] = '请选择仓库';
$_LANG['select_deliver'] = '请选择物流配送';
$_LANG['delet_tpl_id_1'] = '确定删除编号为';
$_LANG['delet_tpl_id_2'] = '的模板吗？';
$_LANG['label_express_deliver'] = '物流配送：';
$_LANG['label_start_send_area'] = '始发地：';
$_LANG['label_terminus_ad_quem'] = '到达目的地：';


?>