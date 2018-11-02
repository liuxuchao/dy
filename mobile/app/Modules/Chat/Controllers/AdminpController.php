<?php

namespace App\Modules\Chat\Controllers;

use App\Api\Foundation\Token;
use App\Modules\Chat\Models\Kefu;
use App\Modules\Base\Controllers\FrontendController;

class AdminpController extends FrontendController
{
    protected $config = [];
    private $user;    //客服信息

    public function _initialize()
    {
        $this->config = load_config(ROOT_PATH . 'config/chat.php');
    }

    public function actionMobile()
    {
        /* 协议 */
        $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';

        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            /* 端口 */
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'] . $port;
            }
        }

        $domain = $protocol . $host . __ROOT__ . '/';

        $this->assign('domain', $domain);
        $this->display('mobile');
    }

    /**
     * adminp.index
     */
    public function actionIndex()
    {
        // 校验用户身份
        $this->userInfo();

        $result['code'] = 0;

        $service = [
            'id' => $this->user['service_id']
        ];

        /** 验证失败则跳转到登录页 */
        if (empty($this->user['service_id'])) {
            $result['code'] = 1;
            $result['message'] = '该账号没有客服权限';
            $this->ajaxReturn($result);
        }
        if ($service['chat_status'] == 1) {
            $result['code'] = 1;
            $result['message'] = '客服已登录';
            $this->ajaxReturn($result);
        }

        /** 聊天记录 */

        $messageList = Kefu::getChatLog($service);
//        if ( count($messageList) == 1 && empty($messageList['id']) ) {
//            $messageList = [];
//        }

        $result['message_list'] = $messageList;

        // 改变客服登录状态
        $id = $service['id'];  //获取客服ID
        $status = 1;  //获取客服ID
        $status = in_array($status, [0, 1]) ? $status : 0;

        $data['chat_status'] = $status;

        M('im_service')->where('id=' . $id . "  AND status = 1")->save($data);
        //

        $this->ajaxReturn($result);
    }

    /**
     * 获取初始信息
     */
    public function actionInitInfo()
    {
        //
        $this->userInfo();

        $result = ['code' => 0, 'message' => '', 'data' => []];

        //
        $listen_route = $this->config['listen_route'];

        if (empty($this->config['port'])) {
            $result['code'] = 1;
            $result['message'] = 'socket端口号未配置';
            $this->ajaxReturn($result);
        }
        //
        $result['data']['listen_route'] = $listen_route;
        $result['data']['port'] = $this->config['port'];

        // 店铺信息
        $storeId = $this->getStoreIdByServiceId($this->user['service_id']);

        $storeInfo = Kefu::getStoreInfo($storeId);
        $result['data']['avatar'] = $storeInfo['logo_thumb'];
        $result['data']['user_name'] = $storeInfo['shop_name'];

        // 客服信息
        $service = Kefu::getServiceById($this->user['service_id']);
        $result['data']['nick_name'] = $service['nick_name'];
        $result['data']['user_id'] = $this->user['service_id'];
        $result['data']['store_id'] = $storeId;

        //判断https
        $result['data']['is_ssl'] = is_ssl();

        $this->ajaxReturn($result);
    }

    /**
     * 访客列表
     * 待接入用户
     */
    public function actionVisit()
    {
        // 校验用户身份
        $this->userInfo();

        $serviceId = $this->user['service_id'];  // 客服ID

        $storeId = $this->getStoreIdByServiceId($serviceId);

        /** 等待接入 */
        $waitMessageArr = Kefu::getWait($storeId);

        if (count($waitMessageArr['waitMessage']) === 1 && empty($waitMessageArr['waitMessage'][0]['id'])) {
            $waitMessageArr['waitMessage'] = [];
        }

        $result = [
            'code' => 0,
            'message_list' => $waitMessageArr['waitMessageDataList'],
            'visit_list' => $waitMessageArr['waitMessage'],
            'total' => $waitMessageArr['total']
        ];
        $this->ajaxReturn($result);
    }

    /**
     * 聊天页面历史记录
     */
    public function actionChatList()
    {
        // 校验用户身份
        $this->userInfo();

        $serviceId = $this->user['service_id'];
        $userId = I('user_id', 0, 'intval');
        $rootUrl = dirname(__ROOT__);
        // 查询 店铺ID
        $storeId = $this->getStoreIdByServiceId($serviceId);

        $page = I('page', 1, 'intval');
        if ($page > 6) {
            $this->ajaxReturn(json_encode(['error' => 1, 'content' => '没有更多了']));
        }
        $default_size = 3; //默认显示条数
        $size = 10;
        $type = I('type', 0, 'intval');//
        if ($type === 'default') {
            $page = 1;
            $size = $default_size;
        }

        $serArr = $this->getServiceIdByRuId($storeId);
        $serArr = implode(',', $serArr);

        $sql = "SELECT id, IF(from_user_id = " . $userId . ", to_user_id, from_user_id) as service_id, message, user_type, from_user_id, to_user_id, dialog_id,
 from_unixtime(add_time) as add_time, status FROM " . Kefu::$pre . "im_message WHERE ((from_user_id = " . $userId . " AND to_user_id IN (" . $serArr . ")) OR (to_user_id = " . $userId . " AND from_user_id IN (" . $serArr . "))) AND to_user_id <> 0 ORDER BY add_time DESC, id DESC";
        $default = I('default', 0, 'intval');
        $start = ($page - 1) * $size;
        if ($default == 1) {
            $start += $default_size;
        }
        $sql .= ' limit ' . $start . ', ' . $size;

        $services = $this->db->getAll($sql);
        foreach ($services as $k => $v) {
            if ($v['user_type'] == 1) {
                $sql = "SELECT s.nick_name, i.logo_thumb FROM " . Kefu::$pre . "im_service s"
                    . " LEFT JOIN " . Kefu::$pre . "admin_user u ON s.user_id = u.user_id"
                    . " LEFT JOIN " . Kefu::$pre . "seller_shopinfo i ON i.ru_id = u.ru_id"
                    . " WHERE s.id = " . $v['from_user_id'];
                $nickName = $this->db->getRow($sql);
                $services[$k]['name'] = get_shop_name($storeId, 1);

                //
                if (strpos($nickName['logo_thumb'], 'http') !== false) {
                    $services[$k]['avatar'] = $nickName['logo_thumb'];
                } else {
                    if (empty($nickName['logo_thumb'])) {
                        $services[$k]['avatar'] = __PUBLIC__ . '/assets/chat/images/service.png';
                    } else {
                        $services[$k]['avatar'] = $nickName['logo_thumb'];
                    }
                }

            } elseif ($v['user_type'] == 2) {
                $users = get_wechat_user_info($v['from_user_id']);

                $services[$k]['name'] = $users['nick_name'];
                if (empty($users['user_picture'])) {
                    $services[$k]['avatar'] = __PUBLIC__ . '/assets/chat/images/avatar.png';
                } else {
                    if (strpos($users['user_picture'], 'http') !== false) {
                        $services[$k]['avatar'] = $users['user_picture'];
                    } else {
                        $services[$k]['avatar'] = rtrim($rootUrl, '/') . '/' . $users['user_picture'];
                    }
                }
            }

            $services[$k]['message'] = htmlspecialchars_decode($v['message']);
            $services[$k]['time'] = $v['add_time'];
            $services[$k]['id'] = $v['id'];
        }

        //
        $did = $services[0]['dialog_id'];    // 会话ID
        $result['goods'] = '';

        if (!empty($did)) {
            $sql = "SELECT g.goods_id, goods_name, goods_thumb, shop_price as goods_price FROM " . Kefu::$pre . "im_dialog d";
            $sql .= " LEFT JOIN " . Kefu::$pre . "goods g on d.goods_id = g.goods_id";
            $sql .= " WHERE d.id = " . $did;
            $goods = $this->db->getRow($sql);
            $goods['goods_price'] = price_format($goods['goods_price'], true);
            $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            $goods['goods_url'] = rtrim($rootUrl, '/') . '/goods.php?id=' . $goods['goods_id'];
            if (empty($goods['goods_id'])) {
                $result['goods'] = null;
            } else {
                $result['goods'] = $goods;
            }

        }
        $result['code'] = 0;
        $result['message_list'] = $services;

        $this->ajaxReturn($result);
    }

    /**
     * 获取商品信息
     */
    public function actionGoodsInfo()
    {
        $rootUrl = dirname(__ROOT__);

        // 校验用户身份
        $this->userInfo();

        //获取商品信息
        $gid = I('gid', 0,'intval');
        if ($gid == 0) {
            $this->ajaxReturn(['error' => 1, 'content' => "invalid params"]);
        }
        $data = Kefu::getGoods($gid);

        //
        $data['goods_url'] = rtrim($rootUrl, '/') . '/goods.php?id=' . $gid;
        $data['goods_thumb'] = get_image_path($data['goods_thumb']);
        $data['goods_price'] = price_format($data['shop_price'], true);
        unset($data['shop_price']);

        $result = [
            'code' => 0,
            'goods_info' => $data,
        ];

        $this->ajaxReturn($result);
    }

    /**
     * 用户信息
     * 设置页面
     */
    public function actionServiceInfo()
    {
        // 校验用户身份
        $this->userInfo();

        $result = ['code' => 0, 'message' => '', 'data' => ''];
        $id = $this->user['service_id'];   //客服ID

        // 客服信息
        $service = Kefu::getServiceById($id);

        // 没有找到客服信息
        if (empty($service)) {
            $result['code'] = 1;
            $result['message'] = '客服信息错误';

            $this->ajaxReturn($result);
        }

        // 管理员信息
        $admin = Kefu::getAdmin($service['user_id']);

        // 没有找到管理员信息
        if (empty($admin)) {
            $result['code'] = 1;
            $result['message'] = '管理员信息错误';

            $this->ajaxReturn($result);
        }
        // 查找店铺信息
        $store = Kefu::getStoreInfo($admin['ru_id']);


        //  返回数据
        $result['data'] = [
            'nick_name' => $service['nick_name'],
            'user_name' => $admin['user_name'],
            'service_avatar' => $store['logo_thumb']
        ];

        $this->ajaxReturn($result);
    }

    /**
     * 根据客服ID 获取店铺ID
     * @param $serviceId
     */
    private function getStoreIdByServiceId($serviceId)
    {
        //
        $sql = "SELECT u.ru_id FROM " . Kefu::$pre . "im_service" . ' s'
            . " LEFT JOIN " . Kefu::$pre . "admin_user" . ' u ON s.user_id = u.user_id'
            . " WHERE s.id = {$serviceId}";

        $ruId = $this->db->getOne($sql); //客服列表

        return $ruId;
    }

    /**
     * 根据店铺ID 查找客服列表
     * 返回客服ID 列表
     * @param $storeId
     */
    private function getServiceIdByRuId($storeId)
    {
        //根据店铺ID查找客服列表
        $sql = "SELECT s.id FROM " . Kefu::$pre . "im_service" . ' s'
            . " LEFT JOIN " . Kefu::$pre . "admin_user" . ' u ON s.user_id = u.user_id'
            . " WHERE u.ru_id = {$storeId}";

        $serArr = $this->db->getCol($sql); //客服列表

        return $serArr;
    }


    /**
     * 退出接口
     */
    public function actionLogout()
    {
        // 校验用户身份
        $this->userInfo();

        $result = [
            'code' => 0,
            'message' => '退出成功'
        ];

        $id = $this->user['service_id'];   //客服ID

        if (empty($id)) {
            $result['code'] = 1;
            $result['message'] = '验证失败';

            $this->ajaxReturn($result);
        }
        $this->logoutStatus();  // 客服退出操作

        $this->ajaxReturn($result);
    }

    /**
     * 退出操作
     * 将客服状态改为退出
     */
    private function logoutStatus()
    {
        $id = $this->user['service_id'];   //客服ID

        $data['chat_status'] = 0;   // 改为退出状态
        M('im_service')->where('id=' . $id . "  AND status = 1")->save($data);
    }

    /**
     * 校验用户身份
     */
    private function userInfo()
    {
        $result = [
            'code' => 0
        ];
        $token = $_SERVER['HTTP_TOKEN'];   // 获取到token
        $data = $this->tokenDecode($token);

        if ($data) {
            // 检查用户信息
            $userId = base64_decode(hex2bin($data['id']));

            $expire = $data['expire'];
            $time = local_gettime();  // 现在时间

            if ($expire < $time) {
                // token过期
                $result['code'] = 1;
                $result['message'] = '用户登录已失效';
                $user = [
                    'service_id' => $userId
                ];
                $this->user = $user;
                $this->logoutStatus();  // 客服退出操作

                $this->ajaxReturn($result);
            }
            // 验证hash
            $hash = $data['hash'];
            if (C('DB_HOST') . C('DB_USER') . C('DB_PWD') . C('DB_NAME') == $hash) {
                $result['code'] = 1;
                $result['message'] = '验证未通过';
                $this->ajaxReturn($result);
            }

            // 存储用户数据
            $user = [
                'service_id' => $userId
            ];

            $this->user = $user;
        } else {
            $result['code'] = 1;
            $result['message'] = '验证未通过';
            $this->ajaxReturn($result);
        }
    }

    /**
     * @param $token
     * @return bool|mixed
     * 解密token
     */
    private function tokenDecode($token)
    {
        try {
            $data = json_decode(base64_decode(unserialize($token)), 1);
            // 判断数据
            if (!is_array($data)) {
                return false;
            }

            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }
}
