<?php

/**
 * ECSHOP 地区列表管理语言文件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: area_manage.php 17217 2011-01-19 06:29:08Z liubo $
*/

$_LANG['create_region_initial'] = '生成地区首字母';

/* 字段信息 */
$_LANG['region_id'] = '地区编号';
$_LANG['region_name'] = '地区名称';
$_LANG['region_type'] = '地区类型';
$_LANG['region_hierarchy'] = '所在层级';
$_LANG['region_belonged'] = '所属地区';

$_LANG['city_region_id'] = '市级地区ID';
$_LANG['city_region_name'] = '市级地区名称';
$_LANG['city_region_initial'] = '首字母';

$_LANG['area'] = '地区';
$_LANG['area_next'] = '以下';
$_LANG['country'] = '一级地区';
$_LANG['province'] = '二级地区';
$_LANG['city'] = '三级地区';
$_LANG['cantonal'] = '四级地区';
$_LANG['street'] = '五级地区';
$_LANG['back_page'] = '返回上一级';
$_LANG['manage_area'] = '管理';
$_LANG['region_name_empty'] = '区域名称不能为空！';
$_LANG['add_country'] = '新增一级地区';
$_LANG['add_province'] = '新增二级地区';
$_LANG['add_city'] = '增加三级地区';
$_LANG['add_cantonal'] = '增加四级地区';
$_LANG['restore_default_set'] = '恢复默认设置';
$_LANG['region_name_placeholder'] = '请输入地区名称';
$_LANG['add_region'] = '新增地区';
$_LANG['confirm_set'] = '你确定要恢复默认设置吗？';

/* JS语言项 */
$_LANG['js_languages']['region_name_empty'] = '您必须输入地区的名称!';
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

/* 恢复默认地区 */
$_LANG['restore_region'] = '恢复默认地区';
$_LANG['restore_success'] = '恢复默认地区成功';
$_LANG['restore_failure'] = '恢复默认地区失败';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['initial'][0] = '地区首字母是所有二级市区生成的字母。';
$_LANG['operation_prompt_content']['initial'][1] = '把每个城市按首字母归类，便于前台查找;注意生成地区首字母是城市不会出现县级。';

$_LANG['operation_prompt_content']['list'][0] = '在新增一级地区点击管理进入下一级地区，可进行删除和编辑。';
$_LANG['operation_prompt_content']['list'][1] = '地区用于商城定位，请根据商城实际情况谨慎设置。';
$_LANG['operation_prompt_content']['list'][2] = '生成地区首字母是方便根据地区首字母搜索相对应的地区。';
$_LANG['operation_prompt_content']['list'][3] = '地区层级关系必须为中国→省/直辖市→市→县，地区暂只支持到四级地区其后不显示，暂不支持国外。';

?>