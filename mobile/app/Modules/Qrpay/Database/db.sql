
-- 表的结构 `收款码优惠管理表 dsc_qrpay_discounts`
--
DROP TABLE IF EXISTS `dsc_qrpay_discounts`;
CREATE TABLE IF NOT EXISTS `dsc_qrpay_discounts` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`ru_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT  '商家ID',
	`min_amount` DECIMAL(10, 2) NOT NULL DEFAULT  '0.00' COMMENT '满金额',
	`discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT  '0.00' COMMENT '优惠金额',
	`max_discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT  '0.00' COMMENT '最高优惠金额',
	`status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '优惠状态(0 关闭，1 开启)',
	`add_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- 表的结构 `收款码标签管理 dsc_qrpay_tag`
--
DROP TABLE IF EXISTS `dsc_qrpay_tag`;
CREATE TABLE IF NOT EXISTS `dsc_qrpay_tag` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`ru_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT  '商家ID',
	`tag_name` varchar(120) NOT NULL DEFAULT  '' COMMENT '标签名称',
	`self_qrpay_num` int(10) unsigned DEFAULT '0' COMMENT '相关自助收款码数量',
	`fixed_qrpay_num` int(10) unsigned DEFAULT '0' COMMENT '相关指定金额收款码数量',
	`add_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- 表的结构 `收款码管理 dsc_qrpay_manage`
--
DROP TABLE IF EXISTS `dsc_qrpay_manage`;
CREATE TABLE IF NOT EXISTS `dsc_qrpay_manage` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`qrpay_name` varchar(120) NOT NULL DEFAULT  '' COMMENT '收款码名称',
	`type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '收款码类型(0自助、1 指定)',
	`amount` DECIMAL(10, 2) NOT NULL DEFAULT  '0.00' COMMENT '收款码金额',
	`discount_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联优惠类型id',
	`tag_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT  '关联标签id',
	`qrpay_status` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '收款状况',
	`ru_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT  '商家ID',
	`qrpay_code` varchar(255) NOT NULL DEFAULT '' COMMENT '二维码链接',
	`add_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- 表的结构 `收款记录 dsc_qrpay_log`
--
DROP TABLE IF EXISTS `dsc_qrpay_log`;
CREATE TABLE IF NOT EXISTS `dsc_qrpay_log` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`pay_order_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '收款订单号',
	`pay_amount` DECIMAL(10, 2) NOT NULL DEFAULT  '0.00' COMMENT '收款金额',
	`qrpay_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '关联收款码id',
	`ru_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT  '商家ID',
	`pay_user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '支付用户id',
	`openid` varchar(255) NOT NULL DEFAULT '' COMMENT '微信用户openid',
	`payment_code` varchar(255) NOT NULL DEFAULT '' COMMENT '支付方式',
	`trade_no` varchar(255) NOT NULL DEFAULT '' COMMENT '支付交易号',
	`notify_data` text COMMENT '交易数据',
	`pay_status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否支付：0未支付 1已支付',
	`is_settlement` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否结算：0未结算 1已结算',
	`pay_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
	`add_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '记录时间',
	PRIMARY KEY (`id`),
	UNIQUE (`pay_order_sn`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
