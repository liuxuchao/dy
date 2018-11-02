<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_code.php');
require(dirname(__FILE__) . '/includes/lib_wechatemoji.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

/**
 * 微信登录配置信息
  * orgid 公众号原始ID
 * appid 开发平台应用appid
 * appsecret 开发平台应用appsecret
 * redirect_uri 回调URL
 * last_url 最终跳转URL
 * @var array
 */

$act = isset($_GET['act']) ? htmlspecialchars(trim($_GET['act'])) : 'index';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
 
$config = array('orgid'=>'', 'appid'=>'', 'appsecret'=>'', 'redirect_uri'=>'http://www.xxxx.com/wechat_oauth.php?act=login', 'last_url'=>'http://xxxx.com');
$wechat = new wechatOauth($config, $user, $ecs, $db, $smarty, $_LANG, $err, $user_id);

try{
	$wechat->$act();	
} catch (Exception $e) {
	show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
}


class wechatOauth{

	private $config;
	private $user;   
	private $ecs;
	private $db;
	private $smarty;
	private $_LANG;
	private $err;
	private $wechat_id = 0;
	private $user_id = 0;

	/**
	 * 配置信息
	 */
	public function __construct($config, $user, $ecs, $db, $smarty, $_LANG, $err, $user_id){
		$this->config = $config;
		$this->user = $user;
		$this->ecs = $ecs;
		$this->db = $db;
		$this->smarty = $smarty;
		$this->_LANG = $_LANG;
		$this->err = $err;
		$this->user_id = $user_id;
		$sql = 'SELECT id FROM '.$this->ecs->table('wechat').' where orgid = "'.$this->config['orgid'].'"';
		$this->wechat_id = $this->db->getOne($sql);
	}

	/**
	 * 授权登录
	 */
	public function index($user_callblock = ''){
		
		$user_where  = '';
		if($this->user_id){
			$user_where .=  "&user_id=" . $this->user_id;
		}
		
		if($user_callblock){
			$user_where .=  "&user_callblock=" . $user_callblock;
		}
		
		$redirect_uri = $this->getOauthRedirect($this->config['appid'], $this->config['redirect_uri'] . $user_where);
		header('Location:'.$redirect_uri, true, 302);
	}
	
	//会员绑定
	public function user_wechat($userinfo){
		
		$user_callblock = isset($userinfo['user_callblock']) && !empty($userinfo['user_callblock']) ? trim($userinfo['user_callblock']) : '';
		if(isset($userinfo['user_callblock'])){
			unset($userinfo['user_callblock']);
		}

		$userinfo['type'] = "wechat";
		$userinfo = serialize($userinfo);
		$_SESSION['wechatoath'] = $userinfo;
		
		$redirect_uri = "user.php?act=oath_weixin_login";
		
		if(!empty($user_callblock)){
			$redirect_uri .= "&user_callblock=" . $user_callblock;
		}
		
		header('Location:'.$redirect_uri, true, 302);
	}

	/**
	 * 登录操作
	 */
	public function login(){
		
		$user_callblock = isset($_GET['user_callblock']) && !empty($_GET['user_callblock']) ? urlencode(trim($_GET['user_callblock'])) : '';
		
		if(isset($_GET['code']) && !empty($_GET['code'])){
			//token
			$access_token = $this->getOauthAccessToken($this->config['appid'], $this->config['appsecret']);
			if($access_token){
				//刷新token
				if(!$this->getOauthAuth($access_token['access_token'], $access_token['openid'])){
					$access_token = '';
					$access_token = $this->getOauthRefreshToken($this->config['appid'], $access_token['refresh_token']);
				}
				//微信用户信息
				$userinfo = $this->getOauthUserinfo($access_token['access_token'], $access_token['openid']);
				
				//会员绑定功能
				$userinfo['login_user'] = $this->user_id;
				
				$userinfo['nickname'] = strip_tags(emoji_unified_to_html($userinfo['nickname']));//过滤emoji表情产生的html标签
				
				$this->doLogin($userinfo);
				
				$userinfo['user_callblock'] = $user_callblock;
				$this->user_wechat($userinfo);
			}
			else{
				$this->index($user_callblock);
			}
		}
		else{
			$this->index($user_callblock);
		}
	}

	/**
	 * 登录处理
	 * @param  [array] $userinfo [微信用户信息]
	 */
	public function doLogin($userinfo){
		
		$user_callblock = isset($_GET['user_callblock']) && !empty($_GET['user_callblock']) ? urlencode(trim($_GET['user_callblock'])) : '';
		
		if($userinfo){
			
			$userinfo['nickname'] = strip_tags(emoji_unified_to_html($userinfo['nickname']));//过滤emoji表情产生的html标签
			
			$info = array();
			$info = $userinfo;
			$info['ect_uid'] = $this->user_id;
			
			$info['nickname'] = strip_tags(emoji_unified_to_html($info['nickname']));//过滤emoji表情产生的html标签
			
			if(!empty($info['nickname'])){
				$nickname = explode('@', $info['nickname']);
				if(count($nickname) > 1){
					$info['nickname'] = str_replace("@", "#", $info['nickname']);
				}
			}
			
			$sql = $sql = 'SELECT uid FROM '.$this->ecs->table('wechat_user').' where unionid = "'.$userinfo['unionid'].'"' . " OR openid = '" .$userinfo['openid']. "'";
			if(!$this->db->getOne($sql)){
				$sql = "INSERT INTO ".$this->ecs->table('wechat_user')." (openid, nickname, sex, language, city, province, country, headimgurl, privilege, unionid, ect_uid, wechat_id, `from`) VALUES ('".$info['openid']."', '".$info['nickname']."', '".$info['sex']."', '".$info['language']."', '".$info['city']."', '".$info['province']."', '".$info['country']."', '".$info['headimgurl']."', '".serialize($info['privilege'])."', '".$info['unionid']."', '".$info['ect_uid']."', '".$this->wechat_id."', '3')";
				$this->db->query($sql);
			}
		}
		else{
			$this->index($user_callblock);
		}
	}

	/**
	 * oauth 授权跳转接口
	 * @param string $callback 回调URI
	 * @return string
	 */
	function getOauthRedirect($appid, $redirect_uri, $state='',$scope='snsapi_login'){
		return 'https://open.weixin.qq.com/connect/qrconnect?appid='.$appid.'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
	}

	/**
	 * 通过code获取Access Token
	 * @return array {access_token,expires_in,refresh_token,openid,scope}
	 */
	function getOauthAccessToken($appid, $appsecret){
		$code = isset($_GET['code'])?$_GET['code']:'';
		if (!$code) return false;
		$result = $this->http_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code');
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$errCode = $json['errcode'];
				$errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * 刷新access token并续期
	 * @param string $refresh_token
	 * @return boolean|mixed
	 */
	function getOauthRefreshToken($appid, $refresh_token){
		$result = $this->http_get('https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$appid.'&grant_type=refresh_token&refresh_token='.$refresh_token);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$errCode = $json['errcode'];
				$errMsg = $json['errmsg'];
				return false;
			}
			$this->user_token = $json['access_token'];
			return $json;
		}
		return false;
	}

	/**
	 * 获取授权后的用户资料
	 * @param string $access_token
	 * @param string $openid
	 * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
	 * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
	 */
	function getOauthUserinfo($access_token,$openid){
		$result = $this->http_get('https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$errCode = $json['errcode'];
				$errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * 检验授权凭证是否有效
	 * @param string $access_token
	 * @param string $openid
	 * @return boolean 是否有效
	 */
	function getOauthAuth($access_token,$openid){
	    $result = $this->http_get('https://api.weixin.qq.com/sns/auth?access_token='.$access_token.'&openid='.$openid);
	    if ($result)
	    {
	        $json = json_decode($result,true);
	        if (!$json || !empty($json['errcode'])) {
	            $errCode = $json['errcode'];
	            $errMsg = $json['errmsg'];
	            return false;
	        } else
	          if ($json['errcode']==0) return true;
	    }
	    return false;
	}

	/**
	 * GET 请求
	 * @param string $url
	 */
	function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}
}