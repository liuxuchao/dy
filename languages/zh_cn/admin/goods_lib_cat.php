<?php

/**
 * DSC 管理中心起始页语言文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liu $
 * $Id: goods_lib_cat.php 17217 2017-07-13 09:29:08Z liu $
*/

$_LANG['is_show'] = '是否显示';
$_LANG['parent_id'] = '上级分类';
$_LANG['cat_name'] = '分类名称';
$_LANG['catadd_succed'] = '商品库商品分类添加成功！';
$_LANG['category_edit'] = '编辑商品分类';
$_LANG['catedit_succed'] = '商品库商品分类编辑成功！';
$_LANG['continue_add'] = '继续添加分类';
$_LANG['back_list'] = '返回分类列表';
$_LANG['top_cat'] = '顶级分类';
$_LANG['move_goods'] = '转移商品';
$_LANG['start_move_cat'] = '开始转移';
$_LANG['level'] = '级';
$_LANG['level_alt'] = '级别';
$_LANG['goods_number'] = '商品数量';
$_LANG['cat_isleaf'] = '分类已存在';

$_LANG['select_default_topcat_notic'] = '不选择分类默认为顶级分类';

$_LANG['catname_empty'] = '分类名称不能为空!';
$_LANG['catname_exist'] = '已存在相同的分类名称!';
$_LANG["parent_isleaf"] = '所选分类不能是末级分类!';
$_LANG["cat_isleaf"] = '不是末级分类,您不能删除!';
$_LANG["cat_noleaf"] = '底下还有其它子分类,不能修改为末级分类!';
$_LANG["is_leaf_error"] = '所选择的上级分类不能是当前分类或者当前分类的下级分类!';

$_LANG['js_languages']['cat_name_not_null'] = '分类名称不能为空';
$_LANG['js_languages']['not_ts_zf'] = '不能包含特殊字符';
$_LANG['js_languages']['confirm_zy_cat'] = '执行此操作时，当前分类所有下级分类也同时转移，确定执行吗？';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '请按提示信息填写每一个字段。';

$_LANG['operation_prompt_content']['list'][0] = '展示了平台商品库的所有分类。';
$_LANG['operation_prompt_content']['list'][1] = '可在列表直接增加下一级分类。';
$_LANG['operation_prompt_content']['list'][2] = '鼠标移动“设置”位置，可新增、查看下一级分类。';
?>