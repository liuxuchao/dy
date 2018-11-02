<?php

/**
 * ECSHOP 投票管理
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: vote.php 17217 2011-01-19 06:29:08Z liubo $
*/

/* 在线调查字段 */
$_LANG['vote_id'] = '编号';
$_LANG['vote_name'] = '调查主题';
$_LANG['show_position'] = '显示位置';
$_LANG['begin_date'] = '开始日期';
$_LANG['end_date'] = '截止日期';
$_LANG['begin_end_date'] = '起止日期';
$_LANG['can_multi'] = '能否多选';
$_LANG['is_multi'] = '能';
$_LANG['no_multi'] = '不能';
$_LANG['show_index'] = '首页';
$_LANG['goodslist'] = '商品列表页';
$_LANG['add'] = '添加';

$_LANG['no_vote_name'] = '您还没有添加在线调查';
$_LANG['no_option_name'] = '该调查暂无选项';

$_LANG['list_vote'] = '在线调查列表';
$_LANG['add_vote'] = '添加调查主题';
$_LANG['edit_vote'] = '编辑调查';
$_LANG['list_vote_option'] = '调查选项列表';
$_LANG['add_vote_option'] = '添加调查选项';
$_LANG['vote_option'] = '调查选项';
$_LANG['vote_name_empty'] = '调查主题不能为空！';
$_LANG['date_error'] = '开始日期不能晚于结束日期!';
$_LANG['back_list'] = '返回调查列表';
$_LANG['continue_add_option'] = '继续添加调查选项';
$_LANG['continue_add_vote'] = '继续添加调查';
$_LANG['edit_option_order'] = '修改调查选项排序';


/* 提示信息 */
$_LANG['vote_name_exist'] = '此调查主题已经存在!';
$_LANG['vote_option_exist'] = '此调查选项已经存在!';
$_LANG['add_option_error'] = '操作失败!';

/* JS语言项 */
$_LANG['js_languages']['vote_name_empty'] = '调查标题不能为空!';
$_LANG['js_languages']['option_name_empty'] = '必须输入调查选项名称!';
$_LANG['js_languages']['drop_confirm'] = '您确定要删除这条记录吗?';
$_LANG['js_languages']['drop'] = '删除';

/* 调查选项字段 */
$_LANG['option_id'] = '编号';
$_LANG['vote_id'] = '调查ID';
$_LANG['option_name'] = '调查选项';
$_LANG['vote_count'] = '投票数';
$_LANG['option_order'] = '选项排序';
$_LANG['option_name_empty'] = '调查选项不能为空！';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '请注意设置调查主题的日期。';

$_LANG['operation_prompt_content']['list'][0] = '在线调查列表展示所有的在线调查问题。';
$_LANG['operation_prompt_content']['list'][1] = '在线调查是添加调查问题在前台展示，用户对相应的问题提出见解，平台收集相应信息。';

$_LANG['operation_prompt_content']['option'][0] = '调查选项为调查主题配置，请合理设置。';
$_LANG['operation_prompt_content']['option'][1] = '调查主题可根据关键字搜索。';

?>