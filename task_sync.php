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
			}elseif($row['table_name'] == 'user_address'){
				$syncResult = syncUserAddress($actionType,$tableId,'address_id');  //同步地址表
			}elseif($row['table_name'] == 'delivery_goods'){
				$syncResult = syncDelivery($actionType,$tableId,'rec_id');		//同步物流表
			}elseif($row['table_name'] == 'order_return'){
				$syncResult = syncOrderReturn($actionType,$tableId,'ret_id');		//同步退货表
			}elseif($row['table_name'] == 'order_invoice'){
				$syncResult = syncInvoice($actionType,$tableId,'invoice_id');		//同步发票表
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
	*@param $actionType	操作类型  1：新增 2：修改
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
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $userInfo['user_id'],
			"user_name"  => $userInfo['user_name'],
			"email"  => $userInfo['email'],
			"nick_name" => $userInfo['nick_name'],
			"mobile_phone"  => $userInfo['mobile_phone'],
			"reg_time"  => $userInfo['reg_time'],
		);
		if($actionType==1){
			$file_type ="/userAdd";
		}elseif($actionType==2){
			$file_type = "/userUpdate";
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
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $userAddressInfo['user_id'],
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
		$sql = "SELECT * FROM " .$ecs->table('order_info'). " where ".$tableValue."=".$tableId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $Info['user_id'],
			"user_name"  => $Info['user_name'],
			"email"  => $Info['email'],
			"nick_name" => $Info['nick_name'],
			"mobile_phone"  => $Info['mobile_phone'],
			"reg_time"  => $Info['reg_time'],
		);
		if($actionType==1){
			$file_type ="/orderyAdd";
		}elseif($actionType==2){
			$file_type = "/orderUpdate";
		}
		$buildResult = buildFile($actionType,json_encode($jsonData),$file_type);		//创建文件
		return false;
	}
	
	function syncDelivery($actionType,$tableId,$tableValue)
	{
		$Info = array();
		global $db, $ecs;
		$sql = "SELECT * FROM " .$ecs->table('delivery_goods'). " where ".$tableValue."=".$tableId." limit 1";
		$Info = $db->getRow($sql);
		if(empty($Info)){
			return false;
		}
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"goods_id" => $Info['goods_id'],
			"goods_sn" => $Info['goods_sn'],
		);
		if($actionType==1){
			$file_type ="/deliveryAdd";
		}elseif($actionType==2){
			$file_type = "/deliveryUpdate";
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
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $Info['user_id'],
			"goods_id"  => $Info['goods_id'],
		);
		if($actionType==1){
			$file_type ="/orderReturnAdd";
		}elseif($actionType==2){
			$file_type = "/orderReturnUpdate";
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
		$jsonData = array("log_time"=>time(),"log_info"=>array());
		$jsonData['log_info'] = array(
			"user_id" => $Info['user_id'],
			"invoice_id"  => $Info['invoice_id'],
			"inv_payee"  => $Info['inv_payee'],
			"tax_id" => $Info['tax_id'],
		);
		if($actionType==1){
			$file_type ="/invoiceAdd";
		}elseif($actionType==2){
			$file_type = "/invoiceUpdate";
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
		$nowTime = time()- strtotime(date("Y-m-d",time()));

		$fullPathFileName = $path. $file_type.'_'.date("Y-m-d",time()).$nowTime."_".mt_rand(10000,99999).".json";
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