<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require('mc_function.php'); 

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/* 检查权限 */
admin_priv('batch_add_order');

/*------------------------------------------------------ */
//-- 批量写入
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'mc_add')
{
    
   $link[] = array('text' => $_LANG['go_back'], 'href' => 'mc_order.php');

   //获取订单添加时间段  并转换为时间戳
   $start_time = !empty($_POST['start_time']) ? local_strtotime($_POST['start_time']) : 0;
   $end_time   = !empty($_POST['end_time']) ? local_strtotime($_POST['end_time']) : 0;

   $order_status = !empty($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : 0;//订单状态
   $shipping_status = !empty($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : 0;//配送状态
   $pay_status = !empty($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : 0;//支付状态

   $goods = isset($_REQUEST['comment_id']) ? trim($_REQUEST['comment_id']) : '';

   $goods_number = isset($_REQUEST['goods_number']) ? intval($_REQUEST['goods_number']) : 1;
   $_REQUEST['comment_num'] = trim($_REQUEST['comment_num']);
   
   $comment_num = intval($_REQUEST['comment_num']);
   if($comment_num < 1){
	   $comment_num = 1;
   }

   $goods = preg_replace("/\r\n/",",",$goods); //替换空格回车换行符 为 英文逗号
   $goods = explode(',', $goods);
   
   if(count($goods) < 0){
	   sys_msg($_LANG['mc_add_notic'], 0, $link);
	}
	
	if(!$_FILES['upfile']){
	     sys_msg($_LANG['not_upload_file'], 0, $link);
	}
	
	//文件上传 == 批量上传 的文件做了..备份保存;
    $path = "../mc_upfile/".date("Ym")."/";
	//上传,备份;
	$file_chk = uploadfile("upfile",$path,'mc_order.php',1024000,'txt');
	/* 读取用户名 */
	if($file_chk){
		$filename = $path.$file_chk[0];
		//读取内容;
		$user_str = mc_read_txt($filename);	
		//截取字符,返加数组
		if(!empty($user_str)){
		  mc_new_order($user_str, $goods, $goods_number, $comment_num,$start_time,$end_time,$order_status,$shipping_status,$pay_status);
		}else{
			sys_msg($_LANG['read_user_name_file_error'], 0, $link);
		}
		
	 }else{
       sys_msg($_LANG['file_not_uplaod_success'], 0, $link);	
	 }
	 
   sys_msg($_LANG['batch_add_order_success'], 0, $link);
}

/*------------------------------------------------------ */
//-- 操作界面
/*------------------------------------------------------ */
else
{
	require_once(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/' . ADMIN_PATH . '/order.php');
	$smarty->assign('lang',$_LANG);
	/* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ss_list', get_status_list('shipping'));
    $smarty->assign('ps_list', get_status_list('payment'));

    $smarty->assign('ur_here',      $_LANG['batch_add_order']);
    $smarty->display('mc_order.dwt');
}

function mc_new_order($str = '', $goods, $goods_number, $comment_num,$start_time=0,$end_time = 0 , $order_status = 0 , $shipping_status = 0 , $pay_status = 0){
	if(!$str) return false;
	$str = preg_replace("/\r\n/","*",$str); //替换空格回车换行符 为 英文逗号

	$str_arr = array_filter(explode('*', $str));
	$goodsCnt = get_goods_amount($goods, $goods_number);
	$arr = array();
	$other = array();
	if($comment_num > 1){
		$str_arr = get_array_rand_return($str_arr); //随机用户（数组形式）
	}
	
	for($i=0; $i<$comment_num; $i++){		
		$array_goods[$i] = $goods; 
		if($comment_num > 1){
			$array_goods[$i] = get_array_rand_return($array_goods[$i]); //随机商品（数组形式）
		}
		$rand_num = rand(1, $comment_num);
		for($k=0; $k<$rand_num; $k++){
			// $arr[$i] = str_iconv($str_arr[$i]);
			$arr[$i] = explode("|", trim($str_arr[$i]));
			
			
			if(!empty($arr[$i][2])){
				$region = explode('--', $arr[$i][2]);
				$region_name = explode(',', $region['0']);
			}
			
			$user_id = get_infoCnt('users', 'user_id', "user_name = '" . $arr[$i][0] . "'");
			$province = get_infoCnt('region', 'region_id', "region_name = '" . $region_name[0] . "'");
			$city = get_infoCnt('region', 'region_id', "region_name = '" . $region_name[1] . "'");
			$district = get_infoCnt('region', 'region_id', "region_name = '" . $region_name[2] . "'");
			$shipping_id = get_infoCnt('shipping', 'shipping_id', "shipping_name = '" . $arr[$i][7] . "'");
			$pay_id = get_infoCnt('payment', 'pay_id', "pay_name = '" . $arr[$i][8] . "'");
			
			//如果指定订单下单时间段 则在时间段内获得随机时间
			if($start_time > 0 && $end_time > 0){
				$time = rand($start_time,$end_time);
			}else{
				$rand_time = rand(1,1000000);
				$nowTime = gmtime();
				$time = $nowTime - $rand_time;
			}
			
			$other = array(
				'user_id' => $user_id,
				'order_sn' => mc_get_order_sn(),
				'consignee' => $arr[$i][1],
				'country' => 1,
				'province' => $province,
				'city' => $city,
				'district' => $district,
				'address' => $region[1],
				'zipcode' => $arr[$i][6], //邮政编码
				'tel' => $arr[$i][3], //电话
				'mobile' => $arr[$i][4], //手机
				'email' => $arr[$i][5],
				'shipping_id' => $shipping_id,
				'shipping_name' => $arr[$i][7],
				'pay_id' => $pay_id,
				'pay_name' => $arr[$i][8],
				'goods_amount' => $goodsCnt['goods_amount'], //商品总价
				'shipping_fee' => $arr[$i][9], //运费
				'order_amount' =>  $goodsCnt['goods_amount'] + $arr[$i][9], //订单总金额
				'add_time' => $time, //下单时间
				'order_status' => $order_status,//订单状态
				'shipping_status' => $shipping_status,//配送状态
				'pay_status' => $pay_status//支付状态
			);
			if($comment_num > 0){
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $other, "INSERT");
				$order_id = $GLOBALS['db']->insert_id();
				for($j=0; $j<count($array_goods[$i]); $j++){
					if(!empty($array_goods[$i][$j])){
						$goodsText = explode('-', $array_goods[$i][$j]);
						$goods_id = $goodsText[0];
						$attr_price = $goodsText[1];
						
						$goodsFiles = 'goods_id, goods_sn, goods_name, shop_price, promote_price, promote_start_date, promote_end_date, is_promote, market_price, is_real';	
						$goods_info = get_infoCnt('goods', $goodsFiles, "goods_id = '$goods_id'", 2);
						
						$time = gmtime();
						if($goods_info['is_promote'] == 1){
							if($goods_info['promote_start_date'] <= $time && $goods_info['promote_end_date'] >= $time){
								$goods_info['goods_price'] = ($goods_info['promote_price'] + $attr_price);
							}else{
								$goods_info['goods_price'] = ($goods_info['shop_price'] + $attr_price);
							}
						}else{
							$goods_info['goods_price'] = ($goods_info['shop_price'] + $attr_price);
						}
						
						$goods_other = array(
							'order_id' => $order_id,
							'goods_id' => $goods_info['goods_id'],
							'goods_sn' => $goods_info['goods_sn'],
							'goods_name' => $goods_info['goods_name'],
							'goods_number' => $goods_number,
							'goods_price' => $goods_info['goods_price'],
							'market_price' => $goods_info['market_price'],
							'is_real' => $goods_info['is_real'],
						);
						
						if(count($goods[$j]) > 0){
							$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_goods'), $goods_other, "INSERT");
						}
					}
				}
			}
		}
		
	}
}

/**
 * 取得状态列表
 * @param   string  $type   类型：all | order | shipping | payment
 */
function get_status_list($type = 'all')
{
    global $_LANG;

    $list = array();

    if ($type == 'all' || $type == 'order')
    {
        $pre = $type == 'all' ? 'os_' : '';
        foreach ($_LANG['os'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'shipping')
    {
        $pre = $type == 'all' ? 'ss_' : '';
        foreach ($_LANG['ss'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'payment')
    {
        $pre = $type == 'all' ? 'ps_' : '';
        foreach ($_LANG['ps'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }
    return $list;
}
function mc_get_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

//计算商品价格以及总价金额
function get_goods_amount($goods, $goods_number){
	
	$time = gmtime();
	$price = '';
	$arr = array();
	
	for($i=0; $i<count($goods); $i++){
		$goods[$i] = explode('-', $goods[$i]);
		$goods_id = $goods[$i][0];
		$attr_price = $goods[$i][1];
		
		$goodsCnt = 'goods_id, goods_sn, goods_name, shop_price, promote_price, promote_start_date, promote_end_date, is_promote';
		
		$goods_info = get_infoCnt('goods', $goodsCnt, "goods_id = '$goods_id'", 2);

		if($goods_info['is_promote'] == 1){
			if($goods_info['promote_start_date'] <= $time && $goods_info['promote_end_date'] >= $time){
				$arr[$i]['goods_price'] = ($goods_info['promote_price'] + $attr_price) * $goods_number;
			}else{
				$arr[$i]['goods_price'] = ($goods_info['shop_price'] + $attr_price)  * $goods_number;
			}
		}else{
			$arr[$i]['goods_price'] = ($goods_info['shop_price'] + $attr_price) * $goods_number;
		}
		
		$arr['goods_amount'] += $arr[$i]['goods_price'];
	}

	return $arr;
}

?>