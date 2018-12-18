<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_code.php');
/* 过滤 XSS 攻击和SQL注入 */
get_request_filter();
$sys_legal_list = array("hd");


//参数接收
$u = base64_decode(trim($_GET['u']));
$random = base64_decode(trim($_GET['random']));

global $db, $ecs,$random_url;


/*

if($u ==false || $random == false){
	sendSms(500,"系统参数错误");
}
*/

//恒大 重定向 请求 本地址  获取 手机号，用户ID 系统类型 //查询
/*
	判断参数
	判断用户手机号是否绑定 
	未绑定 ，对用户表进行插入数据，返回用户ID
	已绑定， 重定向到用户提交登录方法；
*/



$sql = "SELECT * FROM " .$ecs->table('users'). " where hd_user_id='".$u."' limit 1";
$userResult = $db->query($sql);
foreach($userResult as $key=>$val){
	$userInfoData=$val;
}
if($userInfoData){
	$isNew = 2;
}else{
	$isNew = 1;
}

//$resultRandom = checkRandom($u,$random,$is_new);
$resultRandom =array(
	"data"=>array(
	    "memId"=>"1002065",
         "memMobile"=>"12018073101",
         "memType"=>"0"
	)
);
if(empty($resultRandom['data'])){
	header("Location:user.php?act=login");
}

if($isNew ==2){
	$login_name = $userInfoData['user_name'];
	$user->set_session($login_name);
	$user->set_cookie($login_name);
	update_user_info();
	recalculate_price();
	$redirect_url = "http://" . $_SERVER["HTTP_HOST"] . str_replace("logininfromhd.php", "index.php", $_SERVER["REQUEST_URI"]);
	header('Location: ' . $redirect_url);
	return;
}
$username = $resultRandom['data']['memId'];
$hdUserId = $resultRandom['data']['memId'];
$memMobile = $resultRandom['data']['memMobile'];
$password = '';
$email = '';
$other = '';
$register_mode = '';

if($isNew ==1 && $resultRandom){
	
	//注册用户
	$insertData = array (
		"user_name"=> $username,
		"password"=> '085d5127648344f8c9e46e874ea743d6',
		"is_hd_user"=>1,
		"hd_user_id"=> $hdUserId,
		"mobile_phone"=> $memMobile,
		"reg_time"=>time(),
	);
	$fields = array();
	$values = array();
	foreach($insertData as $key => $val){
		array_push($fields,$key);
		array_push($values,$val);
	}
	$sql = "INSERT INTO " . $ecs->table('users').
		   " (" . implode(',', $fields) . ")".
		   " VALUES ('" . implode("', '", $values) . "')";
     $result = $db->query($sql);
	if ($result)
	{
		$login_name = $insertData['user_name'];
		$user->set_session($login_name);
		$user->set_cookie($login_name);
		update_user_info();
		recalculate_price();
		$redirect_url = "http://" . $_SERVER["HTTP_HOST"] . str_replace("logininfromhd.php", "index.php", $_SERVER["REQUEST_URI"]);
		header('Location: ' . $redirect_url);
		return;
	}else{
		header("Location:user.php?act=login");
	}
}
header("Location:user.php?act=login");
return;

/**
*@param $code
*@param $msg
*@param $data
**/
function sendSms($code=500,$msg="",$data=array())
{
	$jsondata = array(
		"code"=> $code,
		"msg"=> $msg,
		"data"=>array(
			"memId"=> $data["memId"] ? $data["memId"] : "",
			"memMobile"=> $data["memMobile"] ? $data["memMobile"] : "",
			"memType"=> $data["memType"] ? $data["memType"] : 0,
		),
	);

	
	echo json_encode($jsondata);
	return;
}

/**
*@param userId
*@param $random
**/
function checkRandom($userId,$random,$is_new)
{
	$random_url = 'http://zlyswx.hengdainsurance.com/ntgs/validateRandom';
	$data = array("memId"=>$userId,"random"=>$random,"isNew"=>$is_new);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $random_url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$return = curl_exec($ch);
	curl_close($ch);
	return json_decode($return, true);
}


?>