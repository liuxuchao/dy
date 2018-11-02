<?php

/**
 * ECSHOP 会员预付款管理语言项
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user_account.php 17217 2011-01-19 06:29:08Z liubo $
*/

$_LANG['please_take_handle'] = '请选择操作项';
$_LANG['through'] = '通过';

$_LANG['user_name'] = "会员";

$_LANG['real_name'] = '银行卡用户名';
$_LANG['bank_name'] = '银行名称';
$_LANG['bank_card'] = '银行卡账号';
$_LANG['self_num'] = '用户身份证';

$_LANG['user_surplus'] = '预付款';
$_LANG['surplus_id'] = '编号';
$_LANG['user_id'] = '会员名称';
$_LANG['surplus_amount'] = '金额';
$_LANG['add_date'] = '操作日期';
$_LANG['pay_mothed'] = '支付方式';
$_LANG['pay_state'] = '支付状态';
$_LANG['process_type'] = '类型';
$_LANG['confirm_date'] = '到款日期';
$_LANG['surplus_notic'] = '管理员备注';
$_LANG['surplus_desc'] = '会员描述';
$_LANG['surplus_type'] = '操作类型';
//by wang
$_LANG['real_name'] = '真实姓名';
$_LANG['bank_number'] = '银行账号';
$_LANG['contact'] = '联系方式';

$_LANG['no_user'] = '匿名购买';

$_LANG['surplus_type_0'] = '充值';
$_LANG['surplus_type_1'] = '提现';
$_LANG['admin_user'] = '操作员';
$_LANG['offline_transfer'] = '线下转账';
$_LANG['surplus_time'] = '提现时间';

$_LANG['status'] = '到款状态';
$_LANG['is_confirm'] = '已完成';
$_LANG['confirm'] = '已完成，到账余额';
$_LANG['unconfirm'] = '未确认';
$_LANG['cancel'] = '取消';
$_LANG['confirm_nopay'] = '已完成，不处理到账余额';
$_LANG['complaint_details'] = '申诉说明';
$_LANG['complaint_imges'] = '申诉凭据';

$_LANG['please_select'] = '请选择...';
$_LANG['surplus_info'] = '会员金额信息';
$_LANG['add_info'] = '操作信息';
$_LANG['check'] = '到款审核';
$_LANG['deposit_fee'] = '手续费';

$_LANG['money_type'] = '币种';
$_LANG['surplus_add'] = '添加申请';
$_LANG['surplus_edit'] = '编辑申请';
$_LANG['attradd_succed'] = '您此次操作已成功！';
$_LANG['username_not_exist'] = '您输入的会员名称不存在！';
$_LANG['cancel_surplus'] = '您确定要取消这条记录吗?';
$_LANG['surplus_frozen_error'] = '要提现的金额超过了此会员的帐户冻结余额，此操作将不可进行！';
$_LANG['surplus_amount_error'] = '要提现的金额超过了此会员的帐户余额，此操作将不可进行！';
$_LANG['edit_surplus_notic'] = '现在的状态已经是 已完成，如果您要修改，请先将之设置为 未确认';
$_LANG['back_list'] = '返回充值和提现申请';
$_LANG['continue_add'] = '继续添加申请';
$_LANG['recharge_apply'] = '充值申请';
$_LANG['put_forward_apply'] = '提现申请';
$_LANG['keywords_notic'] = '会员名称/手机号/邮箱';
$_LANG['cannot_del_no_handle'] = '未处理的订单无法删除';
$_LANG['Application_withdrawal'] = "申请提现";
$_LANG['dismiss_application'] = "驳回申请";
$_LANG['dismiss'] = "已驳回";


/* JS语言项 */
$_LANG['js_languages']['user_id_empty'] = '会员名称不能为空！';
$_LANG['js_languages']['deposit_amount_empty'] = '请输入充值的金额！';
$_LANG['js_languages']['pay_code_empty'] = '请选择支付方式';
$_LANG['js_languages']['deposit_amount_error'] = '请按正确的格式输入充值的金额！';
$_LANG['js_languages']['deposit_type_empty'] = '请填写类型！';
$_LANG['js_languages']['deposit_notic_empty'] = '请填写管理员备注！';
$_LANG['js_languages']['deposit_desc_empty'] = '请填写会员描述！';
$_LANG['user_account_confirm'] = '确定全部完成么？此操作不可逆转，请谨慎操作！';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['check'][0] = '请仔细核对会员充值或提现资金信息。';
$_LANG['operation_prompt_content']['check'][1] = '勾选到款状态后请填写管理员备注。';

$_LANG['operation_prompt_content']['info'][0] = '会员充值编辑类型和到款状态是不可以修改的。';

$_LANG['operation_prompt_content']['list'][0] = '该页面展示所有充值和提现的会员信息列表。';
$_LANG['operation_prompt_content']['list'][0] = '可以进行手动添加申请、编辑申请、到款审核操作。';
$_LANG['operation_prompt_content']['list'][0] = '可以输入会员名称关键字进行搜索，侧边栏可进行高级搜索。';

?>