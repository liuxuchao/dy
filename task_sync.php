<?php

	/**
	* 计划任务 监听 触发器表的新增操作  
	* dsc_trigger_log
	**/
	define('IN_ECS', true);
	//const CONS = '常量值';
	require(dirname(__FILE__) . '/includes/init.php');

	$sql = "SELECT * FROM " .$ecs->table('trigger_log'). " where status=0";
	$res = $db->query($sql);

	if($res){
		while ($row = $db->fetchRow($res))
		{
			$syncResult = false;
			$syncId = $row['id'];
			$actionType = $row['action_type'];
			$tableId = $row['tables_id'];
			if($row['table_name'] == 'user'){
				$syncResult = syncUserInfo($actionType,$tableId,'user_id');		//同步用户表
			}elseif($row['table_name'] == 'order_info'){
				$syncResult = syncOrder($actionType,$tableId,'order_id');		//同步订单表
			}elseif($row['table_name'] == 'delivery_order'){
				$syncResult = syncDelivery($actionType,$tableId,'delivery_id');		//同步物流表
			}elseif($row['table_name'] == 'order_return'){
				$syncResult = syncOrderReturn($actionType,$tableId,'ret_id');		//同步退货表
			}elseif($row['table_name'] == 'order_invoice'){
				$syncResult = syncInvoice($actionType,$tableId,'invoice_id');		//同步发票表
			}elseif($row['table_name'] == 'user_address'){
				//$syncResult = syncUserAddress($actionType,$tableId,'address_id');  //同步地址表
			}
			if($syncResult == true){
				updateSyncStatus($syncId,1);		//成功
			}else{
				updateSyncStatus($syncId,2);	//失败
			}
			
		}
	}else{
		return;
	}
	echo "end task!";
	
	
	/**
	* 同步用户表
	*@param $actionType	操作类型   2：修改
	*@param $tableId		表的id
	*@param $tableValue	字段名称
	*@return bool 
	**/
	function syncUserInfo($actionType,$tableId,$tableValue)
	{
		
		$userInfo = array();
		global $db, $ecs;
		$sql = "SELECT user_id,user_name,email,nick_name,mobile_phone,reg_time FROM " .$ecs->table('users'). " where ".$tableValue."=".$tableId." limit 1";
		$userInfo = $db->getRow($sql);
		if(empty($userInfo)){
			return false;
		}
		$userId = $userInfo['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => hd_user_id,
			"user_name"  => $userInfo['user_name'],
			"email"  => $userInfo['email'],
			"nick_name" => $userInfo['nick_name'],
			"mobile_phone"  => $userInfo['mobile_phone'],
			"reg_time"  => $userInfo['reg_time'],
		);
		if($actionType==2){
			$file_type ="/update_user";
		}else{
			return;
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		if($buildResult){
			return checkFile($buildResult);		//检查文件是否创建
		}
		return false;
	}
	
	
	function syncUserAddress($actionType,$tableId,$tableValue)
	{
		$userAddressInfo = array();
		global $db, $ecs;
		$sql = "SELECT address_name,consignee,user_id,address,mobile,province,city,district,street FROM " .$ecs->table('user_address'). " where ".$tableValue."=".$tableId." limit 1";
		$userAddressInfo = $db->getRow($sql);
		if(empty($userAddressInfo)){
			return false;
		}
		$userId = $userAddressInfo['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $hd_user_id,
			"address_name"  => $userAddressInfo['address_name'],
			"consignee"  => $userAddressInfo['consignee'],
			"address" => $userAddressInfo['address'],
			"mobile"  => $userAddressInfo['mobile'],
			"province"  => $userAddressInfo['province'],
			"city"  => $userAddressInfo['city'],
			"district"  => $userAddressInfo['district'],
			"street"  => $userAddressInfo['street']
		);
		if($actionType==1){
			$file_type ="/userAddressAdd";
		}elseif($actionType==2){
			$file_type = "/userAddressUpdate";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		if($buildResult){
			return checkFile($buildResult);		//检查文件是否创建
		}
		return false;
	}
	function syncOrder($actionType,$tableId,$tableValue)
	{
		$Info = array();
		global $db, $ecs;
		$select_field = "
			`order_id`,`main_order_id`,`order_sn`,`user_id`,`order_status`,`shipping_status`,`pay_status`,`consignee`,
			`country`,`province`,`city`,`district`,`street`,`address`,`zipcode`,`tel`,`mobile`,`email`,`best_time`,
			`sign_building`,`postscript`,`shipping_id`,`shipping_name`,`shipping_code`,`shipping_type`,`pay_id`,`pay_name`,
			`how_oos`,`card_message`,`inv_payee`,`inv_content`,`goods_amount`,`cost_amount`,`shipping_fee`,`insure_fee`,
			`pay_fee`,`pack_fee`,`card_fee`,`money_paid`,`surplus`,`integral`,`integral_money`,`bonus`,`order_amount`,
			`return_amount`,`from_ad`,`referer`,`add_time`,`confirm_time`,`pay_time`,`shipping_time`,`confirm_take_time`,
			`auto_delivery_time`,`pack_id`,`card_id`,`bonus_id`,`invoice_no`,`extension_code`,`extension_id`,`agency_id`,
			`inv_type`,`tax`,`parent_id`,`discount`,`point_id`,`shipping_dateStr`,`coupons`,`uc_id`,`invoice_type`,`tax_id`
		";
		$sql = "SELECT ".$select_field." FROM " .$ecs->table('order_info'). " where is_delete=0 and ".$tableValue."=".$tableId." limit 1";

		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}
		
		$userId = $Info['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		$jsonData = formartOrderInfo($Info,$hd_user_id);
		if($actionType==1){
			$file_type ="/add_order";
		}elseif($actionType==2){
			$file_type = "/update_order";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		return false;
	}
	
	/**
	*格式化 订单信息
	*@param $orderInfo 订单数据
	*@param $hd_user_id 恒大用户ID
	**/
	function formartOrderInfo($orderInfo,$hd_user_id)
	{
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$main_order_id = $orderInfo['main_order_id'];
		if($orderInfo['inv_payee']){
			$orderInfo['need_inv'] = 1;
		}
		if($orderInfo['insure_fee']){
			$orderInfo['need_insure'] = 1;
		}
		unset($orderInfo['pay_id']);
		unset($orderInfo['pay_name']);
		unset($orderInfo['main_order_id']);
		$orderInfo['memid'] = $hd_user_id;
		$payInfo = getPayLog($orderInfo['order_id']);
		$paymentInfo = getPayMentInfo($orderInfo['pay_id']);
		$payAmountInfo = getPayAmount($orderInfo['order_id']);
		$orderInfo['logId'] = $payInfo['id'] >0 ?$payInfo['id'] : "";
		$orderInfo['logId'] = $payInfo['id'] >0 ?$payInfo['id'] : "";
		$orderInfo['payDesc'] = !empty($paymentInfo['pay_desc']) ? $paymentInfo['pay_desc'] : "";
		$orderInfo['payCode'] = !empty($paymentInfo['pay_code']) ? $paymentInfo['pay_code'] : "";
		$orderInfo['formatShippingFee'] = !empty($payAmountInfo['sum_fee']) ? $payAmountInfo['sum_fee'] : "";		//运费合计
		$orderInfo['formatOrderAmount'] = !empty($payAmountInfo['sum_price']) ? $payAmountInfo['sum_price'] : "";//总价合计
		
		
		//格式化 金额
		$orderInfo['goods_amount']  = (float)$orderInfo['goods_amount']; 
		$orderInfo['cost_amount']  = (float)$orderInfo['cost_amount']; 
		$orderInfo['shipping_fee']  = (float)$orderInfo['shipping_fee']; 
		$orderInfo['insure_fee']  = (float)$orderInfo['insure_fee']; 
		$orderInfo['pay_fee']  = (float)$orderInfo['pay_fee']; 
		$orderInfo['pack_fee']  = (float)$orderInfo['pack_fee']; 
		$orderInfo['card_fee']  = (float)$orderInfo['card_fee']; 
		$orderInfo['money_paid']  = (float)$orderInfo['money_paid']; 
		$orderInfo['surplus']  = (float)$orderInfo['surplus']; 
		$orderInfo['integral_money']  = (float)$orderInfo['integral_money']; 
		$orderInfo['order_amount']  = (float)$orderInfo['order_amount']; 
		$orderInfo['return_amount']  = (float)$orderInfo['return_amount']; 
		$orderInfo['discount']  = (float)$orderInfo['discount']; 
		$orderInfo['coupons']  = (float)$orderInfo['coupons']; 
		$orderInfo['formatShippingFee']  = (float)$orderInfo['formatShippingFee']; 
		$orderInfo['formatOrderAmount']  = (float)$orderInfo['formatOrderAmount']; 
		
		//格式化  整型 
		
		$orderInfo['order_id']  = intval($orderInfo['order_id']); 
		$orderInfo['user_id']  = intval($orderInfo['user_id']);  
		$orderInfo['order_status']  = intval($orderInfo['order_status']);  
		$orderInfo['pay_status']  = intval($orderInfo['pay_status']);  
		$orderInfo['shipping_id']  = intval($orderInfo['shipping_id']); 
		$orderInfo['shipping_type']  = intval($orderInfo['shipping_type']); 
		$orderInfo['integral']  = intval($orderInfo['integral']); 
		$orderInfo['from_ad']  = intval($orderInfo['from_ad']); 
		$orderInfo['pack_id']  = intval($orderInfo['pack_id']); 
		$orderInfo['card_id']  = intval($orderInfo['card_id']); 
		$orderInfo['bonus_id']  = intval($orderInfo['bonus_id']); 
		$orderInfo['extension_id']  = intval($orderInfo['extension_id']); 
		$orderInfo['inv_type']  = intval($orderInfo['inv_type']); 
		$orderInfo['point_id']  = intval($orderInfo['point_id']); 
		$orderInfo['invoice_type']  = intval($orderInfo['invoice_type']); 
		$orderInfo['tax_id']  = intval($orderInfo['tax_id']); 
		$orderInfo['country']  = intval($orderInfo['country']); 
		$orderInfo['province']  = intval($orderInfo['province']); 
		$orderInfo['city']  = intval($orderInfo['city']); 
		$orderInfo['district']  = intval($orderInfo['district']); 
		$orderInfo['street']  = intval($orderInfo['street']); 
		$orderInfo['confirm_take_time']  = intval($orderInfo['confirm_take_time']); 
		$orderInfo['auto_delivery_time']  = intval($orderInfo['auto_delivery_time']); 
		$orderInfo['parent_id']  = intval($orderInfo['parent_id']); 
		
		global $db, $ecs;	
		$orderSql = "SELECT * FROM " .$ecs->table('order_goods'). " where order_id=".$orderInfo['order_id'];
		$goodsInfo = $db->query($orderSql);
		$orderInfo['goods'] = array();
		if(!empty($goodsInfo)){
			
			while ($row = $db->fetchRow($goodsInfo)){
				
				$tmp = array(
					"item_id"=> $hd_user_id,
					"item_number"=> intval($row['goods_number']),
					"item_price"=>  (float)$row['goods_price'],
					"item_name"=> $row['goods_name'],
					"item_img"=>'',
				);
				if($row['goods_attr'] > 0){
					$attrsql = "SELECT * FROM " .$ecs->table('goods_attr'). " where goods_attr_id=".$row['goods_attr']." and goods_id=".$row['goods_id']." limit 1";
					$attrInfo = $db->getRow($attrsql);
					if($attrInfo){
						$tmp['goods_img'] = $_SERVER['HTTP_HOST']."/".$attrInfo['attr_img_file'];
					}
				}
				array_push($orderInfo['goods'],$tmp);
			}
		}
		
		$jsonData['log_info'] = $orderInfo;
		return $jsonData;
	}
	
	function getHdIdByUserId($userId)
	{
		if(intval($userId) ==0){
			return 0;
		}
		global $db, $ecs;
		$sql = "SELECT hd_user_id FROM " .$ecs->table('users'). " where user_id=".$userId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return 0;
		}else{
			return $Info['hd_user_id'];
		}
	}
	
	
	/*
	*支付log
	*/
	function getPayLog($orderId)
	{
		if(intval($orderId) ==0){
			return [];
		}
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('pay_log'). " where order_id=".$orderId." order by log_id desc limit 1";
		$payInfo = $db->getRow($sql);
		if(empty($payInfo)){
			return [];
		}else{
			return $payInfo;
		}
	}
	
	/*
	*支付log
	*/
	function getPayMentInfo($payId)
	{
		if(intval($payId) ==0){
			return [];
		}
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('payment'). " where pay_id=".$payId." enabled=1 order by pay_id desc limit 1";
		$paymentInfo = $db->getRow($sql);
		if(empty($paymentInfo)){
			return [];
		}else{
			return $paymentInfo;
		}
	}
	
	/* 
	*支付log
	*/
	function getPayAmount($orderId)
	{
		if(intval($orderId) ==0){
			return [];
		}
		global $db, $ecs;
		$sql = "SELECT sum(goods_number) as sum_number,sum(shipping_fee) as sum_fee,sum(goods_price) as sum_price FROM " .$ecs->table('order_goods'). " where order_id=".$orderId." limit 1";
		$paymentInfo = $db->getRow($sql);
		if(empty($paymentInfo)){
			return [];
		}else{
			return $paymentInfo;
		}
	}
	
	/**
	*物流信息 
	*
	**/
	function syncDelivery($actionType,$tableId,$tableValue)
	{
		$Info = array();
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('delivery_order'). " where ".$tableValue."=".$tableId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}

		$userId = $Info['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		foreach($Info as $key=>$val){
			if($key =="user_id"){
				$val = $hd_user_id;
			}
			$jsonData["log_info"][$key]=$val;
		}
		if($actionType==1){
			$file_type ="/add_express";
		}elseif($actionType==2){
			$file_type = "/update_express";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		if($buildResult){
			return checkFile($buildResult);		//检查文件是否创建
		}
		return false;
	}

	function syncOrderReturn($actionType,$tableId,$tableValue)
	{
		$Info = array();
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('order_return'). " where ".$tableValue."=".$tableId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}
		$userId = $Info['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		foreach($Info as $key=>$val){
			if($key =="user_id"){
				$val = $hd_user_id;
			}
			$jsonData["log_info"][$key]=$val;
		}
		if($actionType==1){
			$file_type ="/add_order_return";
		}elseif($actionType==2){
			$file_type = "/update_order_return";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		if($buildResult){
			return checkFile($buildResult);		//检查文件是否创建
		}
		return false;
	}
	
	
	function syncInvoice($actionType,$tableId,$tableValue)
	{
		$Info = array();
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('order_invoice'). " where ".$tableValue."=".$tableId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}
		$userId = $Info['user_id'];
		$hd_user_id = getHdIdByUserId($userId);
		if($hd_user_id == 0){
			return;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		foreach($Info as $key=>$val){
			if($key =="user_id"){
				$val = $hd_user_id;
			}
			$jsonData["log_info"][$key]=$val;
		}
		if($actionType==1){
			$file_type ="/add_invoice";
		}elseif($actionType==2){
			$file_type = "/update_invoice";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		if($buildResult){
			return checkFile($buildResult);		//检查文件是否创建
		}
		return false;
	}
	
	function buildFile($actionType,$jsonData,$file_type="/unkonw")
	{
		if(empty($jsonData))
		{
			return false;
		}
		
		$basePath = "/ecmoban/www/data/logs/";
		$path = $basePath.date("Y-m-d",time());
		
		if(!is_dir($path)){
			mkdirs($path);
		}
		$nowTime = date("His",time());
		$fullPathFileName = $path. $file_type.'_'.date("Ymd",time()).$nowTime.mt_rand(10000,99999).".json";		
		file_put_contents($fullPathFileName,$jsonData);
		return $fullPathFileName;
	}
	
	/**
	*检查文件是否存在
	**/
	function checkFile($file)
	{
		if(file_exists($file)){
			return true;
		}
		return false;
	}
	
	/**
	*
	*创建文件夹
	**/
	function mkdirs($dir, $mode = 0777)
	{
		if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
		if (!mkdirs(dirname($dir), $mode)) return FALSE;
		return @mkdir($dir, $mode);
	}
	
	/**
	*修改 log 状态
	**/
	function updateSyncStatus($sync_id,$statusValue)
	{
		if(intval($sync_id)==0){
			return false;
		}
		global $db, $ecs;
		$sql = "update " .$ecs->table('trigger_log'). " set status=".$statusValue.",update_status_time=".time()." where id=".$sync_id;
		$db->query($sql);
		return;
	}
	
	
	
?>