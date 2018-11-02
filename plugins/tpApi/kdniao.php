<?php
/**
 *
 * 快递鸟电子面单接口
 *
 * @技术QQ群: 340378554
 * @see: http://kdniao.com/api-eorder
 * @copyright: 深圳市快金数据技术服务有限公司
 * 
 * ID和Key请到官网申请：http://kdniao.com/reg
 */

//电商ID
defined('EBusinessID') or define('EBusinessID', $GLOBALS['_CFG']['kdniao_client_id']);
//电商加密私钥，快递鸟提供，注意保管，不要泄漏
defined('AppKey') or define('AppKey', $GLOBALS['_CFG']['kdniao_appkey']);
//请求url，正式环境地址：http://api.kdniao.cc/api/Eorderservice    测试环境地址：http://testapi.kdniao.cc:8081/api/EOrderService
defined('ReqURL') or define('ReqURL', 'http://api.kdniao.cc/api/Eorderservice');

//-------------------------------------------------------------

/**
 * Json方式 调用电子面单接口
 */
function submitEOrder($requestData){
	$datas = array(
        'EBusinessID' => EBusinessID,
        'RequestType' => '1007',
        'RequestData' => urlencode($requestData) ,
        'DataType' => '2',
    );
    $datas['DataSign'] = encrypt($requestData, AppKey);
	$result=sendPost(ReqURL, $datas);	
	
	//根据公司业务处理返回的信息......
	
	return $result;
}

 
/**
 *  post提交数据 
 * @param  string $url 请求Url
 * @param  array $datas 提交的数据 
 * @return url响应返回的html
 */
function sendPost($url, $datas) {
    $temps = array();	
    foreach ($datas as $key => $value) {
        $temps[] = sprintf('%s=%s', $key, $value);		
    }	
    $post_data = implode('&', $temps);
    $url_info = parse_url($url);
	if(empty($url_info['port']))
	{
		$url_info['port']=80;	
	}
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader.= "Host:" . $url_info['host'] . "\r\n";
    $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
    $httpheader.= "Connection:close\r\n\r\n";
    $httpheader.= $post_data;
    $fd = fsockopen($url_info['host'], $url_info['port']);
    fwrite($fd, $httpheader);
    $gets = "";
	$headerFlag = true;
	while (!feof($fd)) {
		if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
			break;
		}
	}
    while (!feof($fd)) {
		$gets.= fread($fd, 128);
    }
    fclose($fd);  
    
    return $gets;
}

/**
 * 电商Sign签名生成
 * @param data 内容   
 * @param appkey Appkey
 * @return DataSign签名
 */
function encrypt($data, $appkey) {
    return urlencode(base64_encode(md5($data.$appkey)));
}
/************************************************************** 
 * 
 *  使用特定function对数组中所有元素做处理 
 *  @param  string  &$array     要处理的字符串 
 *  @param  string  $function   要执行的函数 
 *  @return boolean $apply_to_keys_also     是否也应用到key上 
 *  @access public 
 * 
 *************************************************************/  
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)  
{  
    static $recursive_counter = 0;  
    if (++$recursive_counter > 1000) {  
        die('possible deep recursion attack');  
    }  
    foreach ($array as $key => $value) {  
        if (is_array($value)) {  
            arrayRecursive($array[$key], $function, $apply_to_keys_also);  
        } else {  
            $array[$key] = $function($value);  
        }  
   
        if ($apply_to_keys_also && is_string($key)) {  
            $new_key = $function($key);  
            if ($new_key != $key) {  
                $array[$new_key] = $array[$key];  
                unset($array[$key]);  
            }  
        }  
    }  
    $recursive_counter--;  
}  


/************************************************************** 
 * 
 *  将数组转换为JSON字符串（兼容中文） 
 *  @param  array   $array      要转换的数组 
 *  @return string      转换得到的json字符串 
 *  @access public 
 * 
 *************************************************************/  
function JSON($array) {  
    arrayRecursive($array, 'urlencode', true);  
    $json = json_encode($array);  
    return urldecode($json);  
}  
?>