<?php

/**
 * ECSHOP 权限名称语言文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: priv_action.php 17217 2011-01-19 06:29:08Z liubo $
*/
/* 权限管理的一级分组 */
$_LANG['goods'] = '商品管理';
$_LANG['goods_storage'] = '库存管理';
$_LANG['cms_manage'] = '文章管理';
$_LANG['users_manage'] = '会员管理';
$_LANG['priv_manage'] = '权限管理';
$_LANG['sys_manage'] = '系统设置';
$_LANG['order_manage'] = '订单管理';
$_LANG['promotion'] = '促销管理';
$_LANG['email'] = '邮件管理';
$_LANG['templates_manage'] = '模板管理';
$_LANG['db_manage'] = '数据库管理';
$_LANG['sms_manage'] = '短信管理';
$_LANG['stats'] = '报表统计'; //ecmoban模板堂 --zhuo
$_LANG['index_manage'] = '首页管理';//liu


//商品管理部分的权限
$_LANG['goods_manage'] = '商品添加/编辑';
$_LANG['remove_back'] = '商品删除/恢复';
$_LANG['cat_manage'] = '分类添加/编辑';
$_LANG['cat_drop'] = '分类转移/删除';
$_LANG['attr_manage'] = '商品属性管理';
$_LANG['brand_manage'] = '自营品牌管理';
$_LANG['comment_priv'] = '用户评论管理';
$_LANG['goods_type'] = '商品类型';
$_LANG['tag_manage'] = '标签管理';
$_LANG['goods_auto'] = '商品自动上下架';
$_LANG['discuss_circle'] = '网友讨论圈'; //ecmoban模板堂 --zhuo
$_LANG['single_manage'] = '用户晒单管理'; //ecmoban模板堂 --zhuo
$_LANG['single_edit_delete'] = '用户晒单编辑/删除'; //ecmoban模板堂 --zhuo
$_LANG['topic_manage'] = '专题管理';
$_LANG['virualcard'] = '虚拟卡管理';
$_LANG['picture_batch'] = '图片批量处理';
$_LANG['goods_export'] = '商品批量导出';
$_LANG['goods_batch'] = '商品批量上传/修改';
$_LANG['gen_goods_script'] = '生成商品代码';
$_LANG['suppliers_goods'] = '供货商商品管理';
$_LANG['review_status'] = '商品审核';
$_LANG['value_card'] = '储值卡';

//商品库存管理部分的权限
$_LANG['storage_put'] = '库存入库';
$_LANG['storage_out'] = '库存出库';

//文章管理部分的权限
$_LANG['article_cat'] = '文章分类管理';
$_LANG['article_manage'] = '文章内容管理';
$_LANG['shopinfo_manage'] = '网店信息管理';
$_LANG['shophelp_manage'] = '网店帮助管理';
$_LANG['vote_priv'] = '在线调查管理';
$_LANG['article_auto'] = '文章自动发布';
$_LANG['visualnews'] = 'CMS可视化';

//会员信息管理
$_LANG['integrate_users'] = '会员数据整合';
$_LANG['sync_users'] = '同步会员数据';
$_LANG['users_manages'] = '会员添加/编辑';
$_LANG['users_drop'] = '会员删除';
$_LANG['user_rank'] = '会员等级管理';
$_LANG['feedback_priv'] = '会员留言管理';
$_LANG['surplus_manage'] = '会员余额管理';
$_LANG['account_manage'] = '会员账户管理';
$_LANG['baitiao_manage'] = '会员白条管理';//@author bylu 权限语言-会员白条管理;
$_LANG['user_vat_manage'] = '用户增票资质审核';
$_LANG['users_real_manage'] = '用户实名管理';

//权限管理部分的权限
$_LANG['admin_drop'] = '删除管理员';
$_LANG['allot_priv'] = '分派权限';
$_LANG['logs_manage'] = '管理日志列表';
$_LANG['logs_drop'] = '删除管理日志';
$_LANG['message_manage'] = '管理员留言';
$_LANG['template_manage'] = '模板管理';
$_LANG['agency_manage'] = '办事处管理';
$_LANG['suppliers_manage'] = '供货商管理';
$_LANG['role_manage'] = '角色管理';
$_LANG['admin_message'] = '管理员留言';

//系统设置部分权限
$_LANG['shop_config'] = '商店设置';
$_LANG['shop_authorized'] = '授权证书';
$_LANG['webcollect_manage'] = '网罗天下管理';
$_LANG['ship_manage'] = '配送方式管理';
$_LANG['payment'] = '支付方式管理';
$_LANG['shiparea_manage'] = '配送区域管理';
$_LANG['area_list'] = '地区列表管理';
$_LANG['friendlink'] = '友情链接管理';
$_LANG['partnerlink'] = '合作伙伴管理';
$_LANG['db_backup'] = '数据库备份';
$_LANG['db_renew'] = '数据库恢复';
$_LANG['flash_manage'] = '首页主广告管理'; //Flash 播放器管理
$_LANG['navigator'] = '自定义导航栏';
$_LANG['cron'] = '计划任务';
$_LANG['affiliate'] = '推荐设置';
$_LANG['affiliate_ck'] = '分成管理';
$_LANG['sitemap'] = '站点地图管理';
$_LANG['file_check'] = '文件校验';
$_LANG['file_priv'] = '文件权限检验';
$_LANG['reg_fields'] = '会员注册项管理';
$_LANG['website'] = '第三方登录插件管理';
$_LANG['oss_configure'] = '阿里云OSS配置';
$_LANG['open_api'] = '开发接口API配置';
$_LANG['api'] = '接口对接';
$_LANG['upgrade'] = '在线升级';

//订单管理部分权限
$_LANG['order_os_remove'] = '订单删除';
$_LANG['order_os_edit'] = '编辑订单状态';
$_LANG['order_ps_edit'] = '编辑付款状态';
$_LANG['order_ss_edit'] = '编辑发货状态';
$_LANG['order_edit'] = '添加编辑订单';
$_LANG['order_view'] = '查看未完成订单';
$_LANG['order_view_finished'] = '查看已完成订单';
$_LANG['repay_manage'] = '退款申请管理';
$_LANG['booking'] = '缺货登记管理';
$_LANG['sale_order_stats'] = '订单销售统计';
$_LANG['client_flow_stats'] = '客户流量统计';
$_LANG['delivery_view'] = '查看发货单';
$_LANG['back_view'] = '查看退货单';
$_LANG['order_detection'] = '检测已发货订单';
$_LANG['complaint'] = "交易投诉";
$_LANG['exchange'] = "积分明细";
$_LANG['order_delayed'] = "延迟收货";

//ecmoban模板堂 --zhuo start
$_LANG['batch_add_order'] = '批量添加订单';
$_LANG['order_back_cause'] = '退货原因列表';
$_LANG['order_back_apply'] = '退换货申请列表';
$_LANG['order_print'] = '订单打印';
$_LANG['comment_edit_delete'] = '用户评论编辑/删除';
//ecmoban模板堂 --zhuo end

//促销管理
$_LANG['snatch_manage'] = '夺宝奇兵';
$_LANG['bonus_manage'] = '红包管理';
$_LANG['coupons_manage'] = '优惠券管理'; //bylu
$_LANG['card_manage'] = '祝福贺卡';
$_LANG['goods_pack'] = '商品包装';
$_LANG['ad_manage'] = '广告管理';
$_LANG['gift_manage'] = '赠品管理';
$_LANG['auction'] = '拍卖活动';
$_LANG['group_by'] = '团购活动';
$_LANG['favourable'] = '优惠活动';
$_LANG['whole_sale'] = '批发管理';
$_LANG['package_manage'] = '超值礼包';
$_LANG['exchange_goods'] = '积分商城商品';
$_LANG['presale'] = '预售活动';
$_LANG['seckill_manage'] = '秒杀管理';

// 拼团
$_LANG['team_manage'] = '拼团管理';
// 砍价
$_LANG['bargain_manage'] = '砍价管理';

//邮件管理
$_LANG['attention_list'] = '关注管理';
$_LANG['email_list'] = '邮件订阅管理';
$_LANG['magazine_list'] = '杂志管理';
$_LANG['view_sendlist'] = '邮件队列管理';
$_LANG['mail_template']  = '邮件消息模板';
$_LANG['mail_settings']  = '邮件服务器设置';

//模板管理
$_LANG['template_select'] = '模板选择';
$_LANG['template_setup']  = '模板设置';
$_LANG['library_manage']  = '库项目管理';
$_LANG['lang_edit']       = '语言项编辑';
$_LANG['backup_setting']  = '模板设置备份';
$_LANG['visualhome']  = '首页可视化';

//数据库管理
$_LANG['db_backup']    = '数据备份';
$_LANG['db_renew']     = '数据恢复';
$_LANG['db_optimize']  = '数据表优化';
$_LANG['sql_query']    = 'SQL查询';
$_LANG['convert']      = '转换数据';
$_LANG['table_prefix'] = '修改表前缀';
$_LANG['transfer'] = "数据迁移";

//短信管理
$_LANG['my_info']         = '账号信息';
$_LANG['sms_send']        = '发送短信';
$_LANG['sms_charge']      = '短信充值';
$_LANG['send_history']    = '发送记录';
$_LANG['charge_history']  = '充值记录 ';

//商家入驻管理部分的权限 ecmoban模板堂 --zhuo start
$_LANG['merchants'] = '商家入驻';
$_LANG['merchants_setps']         = '商家申请流程管理';
$_LANG['merchants_setps_drop']    = '商家流程信息删除';
$_LANG['users_merchants']         = '商家列表管理';
$_LANG['users_merchants_drop']    = '商家信息删除';
$_LANG['users_merchants_priv']    = '入驻商家默认权限管理';
$_LANG['client_searchengine']    = '搜索引擎';
$_LANG['client_report_guest']    = '客户统计';
$_LANG['users_flow_stats']    = '流量分析';
$_LANG['warehouse_manage']    = '仓库管理';
$_LANG['region_area']    = '区域管理';
$_LANG['merchants_commission']    = '店铺佣金结算';
$_LANG['merchants_percent']    = '设置商家佣金';
$_LANG['merchants_brand']    = '商家品牌管理';
$_LANG['merch_virualcard']    = '更改加密串';
$_LANG['seller_dimain']    = '二级域名管理';//by kong
$_LANG['seller_grade']    = '商家等级/标准管理';
$_LANG['seller_apply']    = '等级入驻管理';
$_LANG['seller_account']    = '商家账户管理';
$_LANG['comment_seller']    = '商家满意度';
$_LANG['seller_users_real'] = '商家实名认证';

$_LANG['shipping_date_list']    = '指定配送时间';
$_LANG['create_seller_grade']    = '入驻商家评分';

$_LANG['cloud'] = '云服务中心';
$_LANG['cloud_services'] = '资源专区';
//ecmoban模板堂 --zhuo end

/*by kong start*/

$_LANG['admin_manage'] = '管理员添加/编辑';
$_LANG['seller_manage'] = '商家管理员添加/编辑';
$_LANG['seller_allot'] = '商家权限分配';
$_LANG['seller_drop'] = ' 删除商家管理员';

//店铺设置管理
$_LANG['seller_store_setup'] = '店铺设置管理';
$_LANG['seller_store_informa']='店铺基本信息设置';
$_LANG['seller_store_other']='店铺其他设置';
$_LANG['10_visual_editing']  = '店铺可视化装修';
$_LANG['11_touch_dashboard'] = '手机可视化装修';
/*by kong end*/

//众筹权限 by wu
$_LANG['zc_manage'] = '众筹管理';
$_LANG['zc_project_manage'] = '众筹项目管理';
$_LANG['zc_category_manage'] = '众筹分类管理';
$_LANG['zc_initiator_manage'] = '众筹发起人管理';
$_LANG['zc_topic_manage'] = '众筹话题管理';

/*门店 by kong*/
$_LANG['offline_store'] = "门店列表";
$_LANG['stores'] = "门店管理";

// 手机端菜单管理权限
$_LANG['ectouch']    = '手机端管理';
$_LANG['oauth_admin'] = '授权登录';
$_LANG['touch_nav_admin'] = '导航管理';
$_LANG['touch_ad'] = '广告管理';
$_LANG['touch_ad_position'] = '广告位管理';
$_LANG['touch_dashboard'] = '可视化装修';

// ecjia APP权限管理 qin
$_LANG['ecjia'] = 'APP';
$_LANG['ecjia_app'] = 'APP移动应用';
$_LANG['ecjia_shipping'] = 'APP配送方式';
$_LANG['ecjia_sms'] = 'APP短息管理';
$_LANG['ecjia_feedback'] = 'APP留言反馈';
$_LANG['ecjia_marketing'] = 'APP营销顾问';

// 微信通菜单管理权限
if (file_exists(MOBILE_WECHAT)) {
    $_LANG['wechat'] = '微信通管理';
    $_LANG['wechat_admin'] = '公众号设置';
    $_LANG['mass_message'] = '群发消息';
    $_LANG['auto_reply'] = '自动回复';
    $_LANG['menu'] = '自定义菜单';
    $_LANG['fans'] = '粉丝管理';
    $_LANG['media'] = '素材管理';
    $_LANG['qrcode'] = '二维码管理';
    $_LANG['share'] = '扫码引荐';
    $_LANG['extend'] = '功能扩展';
    $_LANG['market'] = '营销中心';
    $_LANG['template'] = '消息提醒';
    //$_LANG['wxapp_config'] = '小程序设置';
}
if (wxapp_enabled()) {
    //小程序
    $_LANG['wxapp'] = '小程序管理';
    $_LANG['wxapp_wechat_config'] = '小程序设置';
    $_LANG['wxapp_template'] = '消息提醒';

}

//微分销菜单管理权限
if (file_exists(MOBILE_DRP)) {
    $_LANG['drp'] = '分销管理';
    $_LANG['drp_config'] = '店铺设置';
    $_LANG['drp_shop'] = '分销商管理';
    $_LANG['drp_list'] = '分销排行';
    $_LANG['drp_order_list'] = '分销订单操作';
    $_LANG['drp_set_config'] = '分销比例设置';
}

$_LANG['gallery_album'] = '图片库管理';
$_LANG['privilege_seller'] = "编辑个人资料";

//首页管理部分的权限
$_LANG['index_sales_volume'] 	= '首页今日销售总额';
$_LANG['index_today_order'] 	= '首页今日订单总数';
$_LANG['index_today_comment'] 	= '首页今日评论数';
$_LANG['index_seller_num'] 		= '首页店铺销量';
$_LANG['index_order_status'] 	= '首页订单状态';
$_LANG['index_order_stats'] 	= '首页订单统计';
$_LANG['index_sales_stats'] 	= '首页销售统计';
$_LANG['index_member_info'] 	= '首页会员信息';
$_LANG['index_goods_view'] 		= '首页商品一览';
$_LANG['index_control_panel'] 	= '首页控制面板';
$_LANG['index_system_info'] 	= '首页系统信息';

//商品库
$_LANG['goods_lib'] 	= '商品库管理';
$_LANG['goods_lib_list'] 	= '商品库商品列表';
$_LANG['goods_lib_cat'] 	= '商品库分类列表';

//供应商
$_LANG['suppliers_goods'] = "商品（供应商）";
$_LANG['suppliers_goods_list'] = "商品列表";
$_LANG['suppliers_goods_type'] = "商品类型";
$_LANG['suppliers_goods_transport'] = "运费模板管理";
$_LANG['suppliers_gallery_album'] = "图片库管理";
$_LANG['suppliers_attr_list'] = "属性列表";
$_LANG['standard_goods_lib'] = "标准商品库";
$_LANG['local_goods_lib'] = "本地商品库";
//促销
//$_LANG['suppliers_promotion'] = "促销（供应商）";
//订单
$_LANG['suppliers_order_manage'] = "订单（供应商）";
$_LANG['suppliers_order_view'] = "采购订单";
$_LANG['suppliers_purchase'] = "求购订单";
$_LANG['suppliers_order_back_apply'] = "采购退货单";
$_LANG['suppliers_delivery_view'] = "采购发货单";
//商家
$_LANG['suppliers'] = "商家（供应商）";
$_LANG['suppliers_list'] = "供应商列表";
$_LANG['suppliers_commission'] = "供应商结算";
$_LANG['suppliers_account'] = "供应商账户";
//统计
$_LANG['suppliers_sale_order_stats'] = "统计（供应商）";
$_LANG['suppliers_stats'] = "订单统计";
$_LANG['suppliers_sale_list'] = "销售明细";
//权限
$_LANG['suppliers_priv_manage'] = "权限（供应商）";
$_LANG['suppliers_logs_manage'] = "管理员日志";
$_LANG['suppliers_child_manage'] = "下级管理员列表";
$_LANG['suppliers_privilege'] = "编辑个人资料";
//系统
$_LANG['suppliers_sys_manage'] = "系统（供应商）";
$_LANG['suppliers_order_print_setting'] = "打印设置";
?>