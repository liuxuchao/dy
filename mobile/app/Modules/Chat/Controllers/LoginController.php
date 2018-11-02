<?php

namespace App\Modules\Chat\Controllers;

use Think\Verify;
use App\Modules\Base\Controllers\FrontendController;

class LoginController extends FrontendController
{
    public function _initialize()
    {
        session('[start]');
    }

    /**
     * 用户登录
     */
    public function actionIndex()
    {
        if (is_mobile_browser() && IS_GET) {
            $this->redirect('chat/adminp/mobile');
        }
        /**
         * 用户登录
         */
        if (IS_POST) {
			//ecjia验证kefu登录
            $login_type = I('login_type', '', ['trim', 'html_in']);
            if ($login_type == 'app_admin_login' ) {
                $user_id = I('user_id', 0, 'intval');
                $is_admin = I('is_admin', 0, 'intval');
                $connect_code = I('connect_code', '', ['trim', 'html_in']);

                $connect_user = M('connect_user')->where(['user_id' => $user_id, 'connect_code' => $connect_code])->find();
                $service = M('im_service')->where(['user_id' => $user_id, 'status' => 1])->find();
                if (!empty($connect_user) && $connect_user['is_admin'] == 1  && !empty($service)) {
                    $field = 'user_id, user_name, password, action_list, last_login,suppliers_id,ec_salt';
                    $row = M('admin_user')->field($field)->where(['user_id' => $user_id])->find();//限制商家登录后台
                } else {
                    $this->ajaxReturn(['code' => 1, 'msg' => '该账号没有客服权限']);
                }
            } else {
				$input = $this->checkSignInData();

				$username = $input['username'];
				$password = $input['password'];
				$remember = $input['remember'];

				$ec_salt = M('admin_user')->field('ec_salt')->where(['user_name' => $username])->find();
				$ec_salt = $ec_salt['ec_salt'];

				$field = 'user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt';
				if (!empty($ec_salt)) {
					$row = M('admin_user')->field($field)->where(['user_name' => $username, 'password' => md5(md5($password) . $ec_salt)])->find();//限制商家登录后台
				} else {
					$row = M('admin_user')->field($field)->where(['user_name' => $username, 'password' => md5($password)])->find();//限制商家登录后台
				}
			}


            //查询结果
            if ($row) {
                // 登录成功
                $service = M('im_service')->where(['user_id' => $row['user_id'], 'status' => 1])->find();
                // 判断客服是否登录
//                if ($service['chat_status'] > 0) {
//                    $this->ajaxReturn(['code' => 1, 'msg' => '客服已登录']);
//                }
                if (empty($service) || empty($service['id'])) {
                    $this->ajaxReturn(['code' => 1, 'msg' => '该账号没有客服权限']);
                }

                $this->set_kefu_session($row['user_id'], $service['id'], $service['nick_name'], $service['login_time']);

                //记录登录
                if ($remember === '1') {
                    $time = time() + 3600 * 24 * 7;//记住密码时间为7天;
                    setcookie('ECSCP[kefu_id]', $service['id'], $time);
                    setcookie('ECSCP[kefu_token]', md5($row['password'] . C('hash_code')), $time);
                }

                // 登录成功
                $result = ['code' => 0, 'msg' => '登录成功'];
                if (is_mobile_browser()) {

                    // 成功则返回token
                    $result['token'] = $this->tokenEncode([
                        'id' => strtoupper(bin2hex(base64_encode($service['id']))),
                        'expire' => local_gettime() + 3600,   // 有效期一小时
                        'hash' => md5(C('DB_HOST') . C('DB_USER') . C('DB_PWD') . C('DB_NAME'))
                    ]);
                }

                $this->ajaxReturn($result);
            } else {
                $this->ajaxReturn(['code' => 1, 'msg' => '用户名或密码错误']);
            }
        }
        $this->display('admin.login');
    }

    /**
     * 用户退出
     */
    public function actionLogout()
    {
        $id = (int)$_SESSION['kefu_id'];   //客服ID

        $data['chat_status'] = 0;   // 改为退出状态
        M('im_service')->where('id=' . $id . "  AND status = 1")->save($data);

        $_SESSION['kefu_admin_id'] = '';
        $_SESSION['kefu_id'] = '';
        $_SESSION['kefu_name'] = '';
        $_SESSION['last_check'] = ''; // 用于保存最后一次检查订单的时间

        // 删除cookie
        setcookie('ECSCP[kefu_id]', '', time() - 1);
        setcookie('ECSCP[kefu_token]', '', time() - 1);

        $this->redirect('index');
    }

    /**
     * 验证码
     */
    public function actionCaptcha()
    {
        $params = [
            'fontSize' => 14, // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'fontttf' => '4.ttf',
            'bg' => [255, 255, 255]
        ];
        $verify = new Verify($params);
        $verify->entry();
    }

    /**
     * 登录数据校验
     */
    private function checkSignInData()
    {
        $username = I('username', '', ['htmlspecialchars','trim']);
        $password = I('password', '', ['htmlspecialchars','trim']);
        $catpcha = I('catpcha', '');
        $remember = I('remember', '');

        $result = ['code' => 0, 'msg' => ''];
        /** 用户名 */
        if (empty($username)) {
            $result['code'] = 1;
            $result['msg'] = '用户名为空';
            $this->ajaxReturn($result);
        }

        /** 密码 */
        if (empty($password)) {
            $result['code'] = 1;
            $result['msg'] = '密码为空';
            $this->ajaxReturn($result);
        }


        /** 手机登录不校验  验证码 */
        if (!is_mobile_browser()) {
            /** 验证码 */
            if (empty($catpcha)) {
                $result['code'] = 1;
                $result['msg'] = '验证码为空';
                $this->ajaxReturn($result);
            }

            /** 校验验证码 */
            $verify = new Verify();
            $res = $verify->check($catpcha);
            if (!$res) {
                $result['code'] = 1;
                $result['msg'] = '验证码错误';
                $this->ajaxReturn($result);
            }
        }

        return [
            'username' => $username,
            'password' => $password,
            'remember' => $remember
        ];
    }

    /**
     * 记录客服session
     */
    private function set_kefu_session($admin_id, $user_id, $username, $last_time)
    {
        $_SESSION['kefu_admin_id'] = $admin_id;
        $_SESSION['kefu_id'] = $user_id;
        $_SESSION['kefu_name'] = $username;
        $_SESSION['last_check'] = $last_time; // 用于保存最后一次检查订单的时间
    }

    /**
     * @param $data
     * @return string
     * 加密登录信息
     */
    private function tokenEncode($data)
    {
        $token = serialize(base64_encode(json_encode($data)));
        return $token;
    }
}
