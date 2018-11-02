<?php

namespace App\Modules\Oauth\Controllers;

use App\Extensions\Form;
use App\Extensions\Wechat;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
        L(require(LANG_PATH . C('shop.lang') . '/other.php'));
        $this->assign('lang', array_change_key_case(L()));
        $this->load_helper('passport');
    }

    public function actionIndex()
    {
        $type = I('get.type', '', ['trim', 'html_in']);
        $refer = I('get.refer', '', ['trim', 'html_in']);
        $back_url = I('get.back_url', '', ['htmlspecialchars','urldecode']);
        $back_url = strip_tags(html_out($back_url));
        // 会员中心授权管理绑定
        $user_id = input('get.user_id', 0, 'intval');
        $file = ADDONS_PATH . 'connect/' . $type . '.php';
        if (file_exists($file)) {
            include_once($file);
        } else {
            show_message(L('msg_plug_notapply'), L('msg_go_back'), url('user/login/index'));
        }
        // 处理url
        $url = url('/', [], false, true);
        $url = rtrim($url, 'index.php'); // 兼容第三方登录回调地址 默认:域名/mobile/index.php
        if ($user_id > 0) {
            $param = [
                'm' => 'oauth',
                'type' => $type,
                'user_id' => $user_id,
                'back_url' => empty($back_url) ? url('user/index/index') : $back_url
            ];
        } else {
            $param = [
                'm' => 'oauth',
                'type' => $type,
                'refer' => $refer,
                'back_url' => empty($back_url) ? url('user/index/index') : $back_url
            ];
        }
        $url .= 'index.php?' . http_build_query($param, '', '&');
        $config = $this->getOauthConfig($type);
        // 判断是否安装
        if (!$config) {
            show_message(L('msg_plug_notapply'), L('msg_go_back'), url('user/login/index'));
        }
        $obj = new $type($config);

        // 授权回调
        if (isset($_GET['code']) && $_GET['code'] != '') {
            if ($res = $obj->callback($url, $_GET['code'])) {
                $back_url = strip_tags(html_out($back_url));
                $param = get_url_query($back_url);
                // 处理推荐u参数
                if (isset($param['u'])) {
                    $up_uid = get_affiliate();  // 获得推荐uid
                    $res['parent_id'] = (!empty($param['u']) && $param['u'] == $up_uid) ? intval($param['u']) : 0;
                    $res['drp_parent_id'] = (!empty($param['u']) && $param['u'] == $up_uid) ? intval($param['u']) : 0;//同步分销关系
                }
                // 处理分销商d参数
                if (isset($param['d'])) {
                    $up_drpid = get_drp_affiliate();  // 获得分销商d参数
                    $res['drp_parent_id'] = (!empty($param['d']) && $param['d'] == $up_drpid) ? intval($param['d']) : 0;
                    $res['parent_id'] = (!empty($param['d']) && $param['d'] == $up_drpid) ? intval($param['d']) : 0;//同步推荐分成关系
                }

                session('unionid', $res['unionid']);
                $_SESSION['oauth_info'] = $res;

                // 会员中心授权管理绑定
                if (isset($_SESSION['user_id']) && $user_id > 0 && $_SESSION['user_id'] == $user_id && !empty($res['unionid'])) {
                    $back_url = empty($back_url) ? url('user/profile/account_safe') : $back_url;
                    if ($this->UserBind($res, $user_id, $type) === true) {
                        redirect($back_url);
                    } else {
                        show_message(L('msg_account_bound'), L('msg_rebound'), $back_url, 'error');
                    }
                } else {
                    // 授权登录
                    if ($this->oauthLogin($res, $type) === true) {
                        redirect($back_url);
                    }

                    if (!empty($_SESSION['unionid']) && isset($_SESSION['unionid']) || $res['unionid']) {
                        // 注册并验证手机号
                        if (!empty($refer) && $refer == 'user') {
                            $this->redirect('oauth/index/bindregister', ['type' => $type, 'back_url' => $back_url]);
                        }
                        // 保存微信粉丝信息
                        if ($this->UpdateWechatUser($res, $type) === true) {
                            redirect($back_url);
                        }
                    } else {
                        show_message(L('msg_author_register_error'), L('msg_go_back'), url('user/login/index'), 'error');
                    }
                }
            } else {
                show_message(L('msg_authoriza_error'), L('msg_go_back'), url('user/login/index'), 'error');
            }
            return;
        }
        // 授权开始
        $url = $obj->redirect($url);
        redirect($url);
    }

    /**
     * 微信绑定手机号注册
     * @return
     */
    public function actionBindRegister()
    {
        if (IS_POST) {
            $mobile = input('mobile', '', ['trim', 'html_in']);
            $sms_code = input('mobile_code', '', ['trim', 'html_in']);
            $type = input('type', '', ['trim', 'html_in']);
            $back_url = input('back_url', '', ['htmlspecialchars','urldecode']);
            $back_url = empty($back_url) ? url('user/index/index') : $back_url;
            $back_url = strip_tags(html_out($back_url));

            // 验证手机号不能为空
            if (empty($mobile)) {
                exit(json_encode(['status' => 'n', 'info' => L('mobile_notnull')]));
            }
            // 验证手机号格式
            if (is_mobile($mobile) == false) {
                exit(json_encode(['status' => 'n', 'info' => L('mobile_format_error')]));
            }
            // 验证短信验证码
            if (C('shop.sms_signin') == 1) {
                if ($mobile != $_SESSION['sms_mobile'] || $sms_code != $_SESSION['sms_mobile_code']) {
                    exit(json_encode(['status' => 'n', 'info' => L('mobile_auth_code_error')]));
                }
            }

            $res = $_SESSION['oauth_info'];
            $res['mobile_phone'] = $mobile;
            $userinfo = get_connect_user($res['unionid']);
            if (!empty($userinfo)) {
                if (empty($userinfo['mobile_phone'])) {
                    // 更新会员手机号
                    $user_data = [
                        'mobile_phone' => $res['mobile_phone'],
                    ];
                    dao('users')->data($user_data)->where(['user_id' => $userinfo['user_id']])->save();
                }
                // 登录
                $this->doLogin($userinfo['user_name']);
                exit(json_encode(['status' => 'y', 'info' => L('正在登录...'), 'url' => $back_url]));
            } else {
                // 验证此手机号是否已经绑定 第三方登录 含微信、QQ
                $map['u.mobile_phone'] = $mobile;
                $map['u.user_name'] = $mobile;
                $map['_logic'] = 'OR';
                $final['_complex'] = $map;
                $final['cu.connect_code'] = 'sns_'.$type;
                $user_connect = dao('users')
                    ->alias('u')
                    ->join(C('DB_PREFIX') . 'connect_user cu on u.user_id = cu.user_id')
                    ->field('u.user_id, u.user_name, u.mobile_phone')
                    ->where($final)
                    ->find();
                if (!empty($user_connect)) {
                    exit(json_encode(['status' => 'n', 'info' => L('该手机号已被注册或绑定'), 'url' => $back_url]));
                }
                // 验证会员或手机号是否已注册
                $condition['mobile_phone'] = $mobile;
                $condition['user_name'] = $mobile;
                $condition['_logic'] = 'OR';
                $users = dao('users')->field('user_id, user_name, mobile_phone')->where($condition)->find();
                if (!empty($users)) {
                    if (C('shop.sms_signin') == 1 && $mobile == $_SESSION['sms_mobile'] && $sms_code == $_SESSION['sms_mobile_code'] ) {
                        // 更新社会化登录用户信息
                        $res['user_id'] = $users['user_id'];
                        update_connnect_user($res, $type);
                        // 查询是否绑定
                        $userinfo = get_connect_user($res['unionid']);
                        if (!empty($userinfo)) {
                            // 登录
                            $this->doLogin($userinfo['user_name']);
                            exit(json_encode(['status' => 'y', 'info' => L('验证成功'), 'url' => $back_url]));
                        }
                    } else {
                        exit(json_encode(['status' => 'n', 'info' => L('change_mobile')]));
                    }
                }
                // 注册
                $result = $this->doRegister($res, $type);
                if ($result == true) {
                    exit(json_encode(['status' => 'y', 'info' => L('验证成功'), 'url' => $back_url]));
                } else {
                    exit(json_encode(['status' => 'n', 'info' => L('验证失败'), 'url' => $back_url]));
                }
            }
            return;
        }

        $type = input('type', '', ['trim', 'html_in']);
        $back_url = input('back_url', '', ['htmlspecialchars','urldecode']);
        $back_url = empty($back_url) ? url('user/index/index') : $back_url;
        $back_url = strip_tags(html_out($back_url));

        $oauth_info = $_SESSION['oauth_info'];
        if (empty($oauth_info)) {
            show_message(L('请先授权登录'), L('msg_go_back'), url('user/login/index'), 'error');
        }

        $this->assign('oauth_info', $oauth_info);
        $this->assign('type', $type);
        $this->assign('back_url', $back_url);
        $this->assign("sms_signin", C('shop.sms_signin'));
        $this->assign('page_title', L('验证手机号'));
        $this->display();
    }

    /**
     * 会员中心授权管理绑定帐号(自动)
     * @param
     */
    protected function UserBind($res, $user_id, $type)
    {
        // 查询users用户是否存在
        $users = dao('users')->field('user_id, user_name')->where(['user_id' => $user_id])->find();
        if ($users && !empty($res['unionid'])) {
            // 查询users用户是否被其他人绑定
            $connect_user_id = dao('connect_user')->where(['open_id' => $res['unionid'], 'connect_code' => 'sns_' . $type])->getField('user_id');
            if ($connect_user_id > 0 && $connect_user_id != $users['user_id']) {
                return false;
            }

            // 更新社会化登录用户信息
            $res['user_id'] = $users['user_id'];
            update_connnect_user($res, $type);

            // 更新微信用户信息
            if (is_dir(APP_WECHAT_PATH) && is_wechat_browser() && $type == 'wechat') {
                $res['openid'] = session('openid');
                update_wechat_user($res, 1); // 1 不更新ect_uid
            }

            // 重新登录
            $this->doLogin($users['user_name']);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取第三方登录配置信息
     *
     * @param type $type
     * @return type
     */
    protected function getOauthConfig($type)
    {
        $sql = "SELECT auth_config FROM {pre}touch_auth WHERE `type` = '$type' AND `status` = 1";
        $info = $this->db->getRow($sql);
        if ($info) {
            $res = unserialize($info['auth_config']);
            $config = [];
            foreach ($res as $key => $value) {
                $config[$value['name']] = $value['value'];
            }
            return $config;
        }
        return false;
    }

    /**
     * 授权自动登录
     * @param  $res
     */
    protected function oauthLogin($res, $type)
    {
        // 兼容老用户
        $older_user = dao('users')->field('user_name, user_id')->where(['aite_id' => $type . '_' . $res['unionid']])->find();
        if (!empty($older_user)) {
            // 清空aite_id
            dao('users')->data(['aite_id' => ''])->where(['user_id' => $older_user['user_id']])->save();
            // 同步社会化登录用户信息表
            $res['user_id'] = $older_user['user_id'];
            update_connnect_user($res, $type);
        }

        // 查询新用户
        $userinfo = get_connect_user($res['unionid']);

        // 已经绑定过的 授权自动登录
        if ($userinfo) {
            // 已注册用户更新手机号
            if ($userinfo && empty($userinfo['mobile_phone'])) {
                $this->redirect('oauth/index/bindregister', ['type' => $type]);
                exit();
            }
            $this->doLogin($userinfo['user_name']);
            // 更新会员表信息(无需再次更新，否则会覆盖已设置的内容)
            /*
            $user_data = [
                'nick_name' => $res['nickname'],
                'sex' => $res['sex'],
                'user_picture' => $res['headimgurl'],
            ];
            dao('users')->data($user_data)->where(['user_id' => $userinfo['user_id']])->save();
            */
            // 更新社会化登录用户信息
            $res['user_id'] = !empty($userinfo['user_id']) ? $userinfo['user_id'] : $_SESSION['user_id'];
            update_connnect_user($res, $type);
            // 更新微信授权用户信息
            if (is_dir(APP_WECHAT_PATH) && is_wechat_browser() && $type == 'wechat') {
                $res['openid'] = session('openid');
                update_wechat_user($res, 1); // 1 不更新ect_uid
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置成登录状态
     * @param  $username
     */
    protected function doLogin($username)
    {
        $this->users->set_session($username);
        $this->users->set_cookie($username);
        update_user_info();
        recalculate_price();
    }

    /**
     * 授权注册
     * @param        $res
     * @param string $back_url
     */
    protected function doRegister($res, $type = '')
    {
        $username = get_wechat_username($res['unionid'], $type);
        $password = mt_rand(100000, 999999);
        $email = $username . '@qq.com';
        $extends = [
            'nick_name' => !empty($res['nickname']) ? $res['nickname'] : '',
            'sex' => !empty($res['sex']) ? $res['sex'] : 0,
            'user_picture' => !empty($res['headimgurl']) ? $res['headimgurl'] : '',
            'mobile_phone' => !empty($res['mobile_phone']) ? $res['mobile_phone'] : '',
        ];
        // 微信通粉丝 保存的推荐参数信息
        if (is_dir(APP_WECHAT_PATH)) {
            $wechat_user = dao('wechat_user')->field('drp_parent_id, parent_id')->where(['unionid' => $res['unionid']])->find();
            if (!empty($wechat_user)) {
                if (is_dir(APP_DRP_PATH)) {
                    $res['drp_parent_id'] = $wechat_user['drp_parent_id'] > 0 ? $wechat_user['drp_parent_id'] : 0;
                }
                $res['parent_id'] = $wechat_user['parent_id'] > 0 ? $wechat_user['parent_id'] : 0;
            }
        }
        // 普通用户
        if (is_dir(APP_DRP_PATH) && $res['drp_parent_id'] > 0) {
            $extends['drp_parent_id'] = $res['drp_parent_id'] > 0 ? $res['drp_parent_id'] : 0;
        }        
        if($res['parent_id'] > 0){
            $extends['parent_id'] = $res['parent_id'] > 0 ? $res['parent_id'] : 0;
        }
        // 查询是否绑定
        $userinfo = get_connect_user($res['unionid']);
        if (empty($userinfo)) {
            if (register($username, $password, $email, $extends) !== false) {
                // 更新社会化登录用户信息
                $res['user_id'] = session('user_id');
                update_connnect_user($res, $type);

                // 更新微信用户信息
                if (is_dir(APP_WECHAT_PATH) && is_wechat_browser() && $type == 'wechat') {
                    $res['openid'] = session('openid');
                    update_wechat_user($res);
                    //关注送红包
                    $this->sendBonus();
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 更新微信粉丝信息
     * @return
     */
    public function UpdateWechatUser($res, $type = '')
    {
        // 更新微信用户信息
        if (is_dir(APP_WECHAT_PATH) && is_wechat_browser() && $type == 'wechat') {
            $res['openid'] = session('openid');
            update_wechat_user($res);
        }
        return true;
    }

    /**
     * 关注送红包
     */
    protected function sendBonus()
    {
        // 查询平台微信配置信息
        $wxinfo = dao('wechat')->field('id, token, appid, appsecret, encodingaeskey')->where(['default_wx' => 1, 'status' => 1])->find();
        if ($wxinfo) {
            // 查询功能扩展 是否安装
            $rs = $this->db->query("SELECT name, keywords, command, config FROM {pre}wechat_extend WHERE command = 'bonus' and enable = 1 and wechat_id = " . $wxinfo['id'] . " ORDER BY id ASC");
            $addons = reset($rs);
            $file = APP_PATH . 'Wechat/Plugins/' . ucfirst($addons['command']) . '/' . ucfirst($addons['command']) . '.php';
            if (file_exists($file)) {
                require_once($file);
                $new_command = '\\App\\Modules\\Wechat\\Plugins\\' . ucfirst($addons['command']) . '\\' . ucfirst($addons['command']);
                $wechat = new $new_command();
                $data = $wechat->returnData($_SESSION['openid'], $addons);
                if (!empty($data)) {
                    $config['token'] = $wxinfo['token'];
                    $config['appid'] = $wxinfo['appid'];
                    $config['appsecret'] = $wxinfo['appsecret'];
                    $config['encodingaeskey'] = $wxinfo['encodingaeskey'];
                    $weObj = new Wechat($config);
                    $weObj->sendCustomMessage($data['content']);
                }
            }
        }
    }

    // 重新绑定合并帐号
    public function actionMergeUsers()
    {
        if ($_SESSION['user_id']) {
            if (IS_POST) {
                $username = I('username', '', ['trim', 'html_in']);
                // 验证
                $form = new Form();
                // 验证手机号并通过手机号查找用户名
                if ($form->isMobile($username, 1)) {
                    $user_name = dao('users')->field('user_name')->where(['mobile_phone' => $username])->find();
                    $username = $user_name['user_name'];
                }
                // 验证邮箱并通过邮箱查找用户名
                if ($form->isEmail($username, 1)) {
                    $user_name = dao('users')->field('user_name')->where(['email' => $username])->find();
                    $username = $user_name['user_name'];
                }
                $password = I('password', '', ['htmlspecialchars','trim']);
                $back_url = I('back_url', '', 'urldecode');
                // 数据验证
                if (!$form->isEmpty($username, 1) || !$form->isEmpty($password, 1)) {
                    show_message(L('msg_input_namepwd'), L('msg_go_back'), '', 'error');
                }
                $from_user_id = $_SESSION['user_id'];
                // 查询users用户是否存在
                $new_user_id = $this->users->check_user($username, $password);
                if ($new_user_id > 0) {
                    // 同步社会化登录用户信息
                    $from_connect_user = dao('connect_user')->field('user_id')->where(['user_id' => $from_user_id])->select();
                    if (!empty($from_connect_user)) {
                        foreach ($from_connect_user as $key => $value) {
                            dao('connect_user')->where('user_id = ' . $value['user_id'])->setField('user_id', $new_user_id);
                        }
                    }
                    if (is_dir(APP_WECHAT_PATH)) {
                        // 微信用户
                        $from_wechat_user = dao('wechat_user')->field('ect_uid')->where(['ect_uid' => $from_user_id])->find();
                        if (!empty($from_wechat_user)) {
                            dao('wechat_user')->where('ect_uid = ' . $from_wechat_user['ect_uid'])->setField('ect_uid', $new_user_id);
                        }
                    }

                    // 合并绑定会员数据 $from_user_id  $new_user_id
                    $res = merge_user($from_user_id, $new_user_id);
                    if ($res == true) {
                        // 退出重新登录
                        $this->users->logout();
                        $back_url = empty($back_url) ? url('user/index/index') : $back_url;
                        show_message(L('logout'), [L('back_up_page'), "返回首页"], [$back_url, url('/')], 'success');
                    }
                    return;
                } else {
                    show_message(L('msg_account_bound_fail'), L('msg_rebound'), '', 'error');
                }
                return;
            }
            $back_url = I('back_url', '', ['htmlspecialchars','urldecode']);
            $this->assign('back_url', $back_url);
            $this->assign('page_title', "重新绑定帐号");
            $this->display();
        } else {
            show_message("请登录", L('msg_go_back'), url('user/login/index'), 'error');
        }
    }

}
