<?php
    header("Content-Type:text/html;charset=UTF-8");
    date_default_timezone_set("PRC");
    $showapi_appid = 'xxxxxx';  //替换此值,在官网的"我的应用"中找到相关值
    $showapi_secret = 'xxxxxxxxx';  //替换此值,在官网的"我的应用"中找到相关值
    $paramArr = array(
         'showapi_appid'=> $showapi_appid,
         'code'=> ""
         //添加其他参数
    );
     
    //创建参数(包括签名的处理)
    function createParam ($paramArr,$showapi_secret) {
         $paraStr = "";
         $signStr = "";
         ksort($paramArr);
         foreach ($paramArr as $key => $val) {
             if ($key != '' && $val != '') {
                 $signStr .= $key.$val;
                 $paraStr .= $key.'='.urlencode($val).'&';
             }
         }
         $signStr .= $showapi_secret;//排好序的参数加上secret,进行md5
         $sign = strtolower(md5($signStr));
         $paraStr .= 'showapi_sign='.$sign;//将md5后的值作为参数,便于服务器的效验
         echo $_LANG['sort_parameter'] .$signStr."<br>\r\n";
         return $paraStr;
    }
     
    $param = createParam($paramArr,$showapi_secret);
    $url = 'http://route.showapi.com/66-22?'.$param; 
    echo $_LANG['code_url'].$url."<br>\r\n";
    $result = file_get_contents($url);
    echo $_LANG['code_json'];
    print $result.'<br>\r\n';
    $result = json_decode($result);
    echo $_LANG['code_value'];
    print_r($result->showapi_res_code);
    echo "<br>\r\n";
    ?>