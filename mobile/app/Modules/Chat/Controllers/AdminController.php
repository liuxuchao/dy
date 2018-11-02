<?php

namespace App\Modules\Chat\Controllers;

use App\Modules\Chat\Models\Kefu;
use App\Modules\Base\Controllers\FrontendController;

class AdminController extends FrontendController
{
    protected $config = [];

    public function __construct()
    {
        parent::__construct();
        C('URL_MODEL', 0);
    }    
    public function _initialize()
    {
        $this->config = load_config(ROOT_PATH . 'config/chat.php');

        $sessionList = ["addreply", "removereply", "insertuserreply"];
        if (empty($_POST) || in_array(strtolower(ACTION_NAME), $sessionList)) {
            session('[start]');
        }
    }

    /**
     * admin.index
     */
    public function actionIndex()
    {
        if (is_mobile_browser() && IS_GET) {
            $this->redirect('chat/adminp/mobile');
        }

        $signInData = $this->userCheck();
        $admin = $signInData['admin'];
        $service = $signInData['service'];

        /** 验证失败则跳转到登录页 */
        if (empty($signInData['service'])) {
            $this->redirect('login/index');
        }
        if ($service['chat_status'] == 1 && empty(session("kefu_id"))) {
            $this->error('客服已登录', 'login/index');
        }

        /** 等待接入 */
        $waitMessageArr = Kefu::getWait($admin['ru_id']);
        $waitMessage = $waitMessageArr['waitMessage'];

        $this->assign('total_wait', $waitMessageArr['total']); //
        $this->assign('wait_message_list', json_encode($waitMessageArr['waitMessageDataList'])); //待接入消息列表

        /** 聊天记录 */
        $messageList = Kefu::getChatLog($service);

        $this->assign('message_list', $messageList);
        $this->assign('message_list_json', json_encode($messageList));

        /** 快捷回复 */
        $reply = Kefu::getReply($service['id'], 1);
        $this->assign('reply', json_encode($reply)); //

        $reply = Kefu::getReply($service['id'], 2);
        $this->assign('take_reply', json_encode($reply)); //

        $reply = Kefu::getReply($service['id'], 3);
        $this->assign('leave_reply', json_encode($reply)); //

        /**
         * socket server
         */
        if (empty($this->config['listen_route'])) {
            $listen_route = $this->getServerIp();
        } else {
            $listen_route = $this->config['listen_route'];
        }

        if (empty($this->config['port'])) {
            $this->error('socket端口号未配置');
        }
        /** 没有聊天默认图片 */
        $this->assign('mouse_img', __ROOT__ . '/public/assets/chat/images/mouse.png');
        $this->assign('root_path', rtrim(dirname(__ROOT__), '/'));
        $this->assign('listen_route', $listen_route); //;
        $this->assign('port', $this->config['port']); //;

        $this->assign('user_id', $service['id']); //;
        $this->assign('store_id', $admin['ru_id']); //;
        $this->assign('nick_name', $service['nick_name']); //
        $this->assign('wait_message', $waitMessage); //
        $this->assign('image_path', __ROOT__ . '/public/assets/chat/images/'); //

        $storeInfo = Kefu::getStoreInfo($admin['ru_id']);

        $this->assign('avatar', $storeInfo['logo_thumb']); //
        $this->assign('user_name', $storeInfo['shop_name']); //

        //判断https
        $this->assign('is_ssl', is_ssl()); //
        //获取客服列表
        $serviceList = Kefu::getServiceList($admin['ru_id'], $service['id']);
        $this->assign('service_list', $serviceList); //

        // 改变客服登录状态
        $_GET['id'] = $service['id'];
        $_GET['status'] = 1;
        $this->actionChangeLogin();

        $this->display('index');
    }

    /**
     * 登录验证
     */
    private function userCheck()
    {
        //检查cookie
        $cookie = cookie('ECSCP');

        $cookie['kefu_id'];
        $cookie['kefu_token'];
        if (isset($cookie['kefu_id']) && !empty($cookie['kefu_id'])) {
            //记住密码验证
            //验证密码
            $service = Kefu::getServiceById($cookie['kefu_id']);

            $adminId = $service['user_id'];
            if (!empty($adminId)) {
                $admin = Kefu::getAdmin($adminId);

                $token = md5($admin['password'] . C('hash_code'));
                if ($token == $cookie['kefu_token']) {
                    return [
                        'admin' => $admin,
                        'service' => $service
                    ];
                }
            }
        }

        //没有记住密码  判断是否已登录
        $kefuId = session('kefu_id');
        $adminId = session('kefu_admin_id');  // 管理员ID
        $admin = Kefu::getAdmin($adminId);
        $service = Kefu::getService($adminId);
        if ($service['id'] != $kefuId) {
            return false;
        }

        return [
            'admin' => $admin,
            'service' => $service
        ];
    }

    /**
     * 聊天列表
     */
    public function actionHistory()
    {
        $uid = I('uid', 0, 'intval');
        $tid = I('tid', 0, 'intval');
        $page = I('page', 0, 'intval');
        $keyword = I('keyword', '');
        $time = I('time', '');

        $data = Kefu::getHistory($uid, $tid, $keyword, $time, $page);

        $this->ajaxReturn($data);
    }

    /**
     * 搜索最近10条记录
     */
    public function actionSearchhistory()
    {
        $mid = I('mid', 0,'intval');

        $data = Kefu::getSearchHistory($mid);

        $this->ajaxReturn($data);
    }

    /**
     * 将未读消息改为已读
     */
    public function actionChangeMessageStatus()
    {
        $serviceId = (int)$_SESSION['kefu_id'];
        $customId = I('id', 0, 'intval');
        if (empty($serviceId)) {
            $this->ajaxReturn(['error' => 1, 'msg' => '没有客服']);
        }
        Kefu::changeMessageStatus($serviceId, $customId);
    }

    /**
     * 获取商品信息
     */
    public function actionGetGoods()
    {
        //获取商品信息
        $gid = empty($_POST['gid']) ? 0 : intval($_POST['gid']);
        if ($gid == 0) {
            $this->ajaxReturn(['error' => 1, 'content' => "invalid params"]);
        }
        $data = Kefu::getGoods($gid);

        $this->ajaxReturn($data);
    }

    /**
     * 获取店铺信息
     */
    public function actionGetStore()
    {
        //获取店铺信息
        $sid = empty($_POST['sid']) ? 0 : intval($_POST['sid']);
        if ($sid == 0) {
            $this->ajaxReturn(['error' => 1, 'content' => "invalid params"]);
        }
        $data = Kefu::getStoreInfo($sid);

        $this->ajaxReturn($data);
    }

    /**
     * 添加快捷回复
     */
    public function actionAddReply()
    {
        $content = I('content');
        $customerId = $_SESSION['kefu_id'];

        $customer = M('im_configure');
        $data['ser_id'] = $customerId;
        $data['type'] = 1;
        $data['content'] = addslashes($content);
        $data['is_on'] = 0;
        $id = $customer->add($data);

        $this->ajaxReturn(['error' => 0, 'id' => $id]);
    }

    /**
     * 删除快捷回复
     */
    public function actionRemoveReply()
    {
        $id = I('id', 0 ,'intval');
        $customerId = $_SESSION['kefu_id'];

        $customer = M('im_configure');
        $customer->where('id=' . $id . ' and ser_id=' . $customerId)->delete();

        $this->ajaxReturn(['error' => 0]);
    }

    /**
     * 修改客服状态
     */
    public function actionChangeStatus()
    {
        $status = I('status');
        $customerId = $_SESSION['kefu_id'];

        $customer = M('im_service');
        $data['chat_status'] = $status;
        $id = $customer->where('id=' . $customerId)->save($data);

        $this->ajaxReturn(['error' => 0, 'id' => $id]);
    }

    /**
     * 添加接入回复
     */
    public function actionInsertUserReply()
    {
        $mid = I('mid', 0, 'intval');
        $content = I('content', '', ['htmlspecialchars','addslashes']);
        $customerId = $_SESSION['kefu_id'];

        $configure = M('im_configure');
        $res = $configure->where('id=' . $mid)->find();

        $data['ser_id'] = $customerId;
        $data['type'] = 2;
        $data['content'] = trim($content);
        if (!empty($res)) {
            $res = $configure->where('id=' . $mid)->save($data);
        } else {
            $mid = $configure->data($data)->add();
        }
        $this->ajaxReturn(['error' => 0, 'mid' => $mid]);
    }

    /**
     * 接入回复是否开启
     */
    public function actionTakeUserReply()
    {
        $id = I('id', 0, 'intval');
        $status = I('status', 0, 'intval');
        if (empty($id)) {
            $this->ajaxReturn(['error' => 1, 'msg' => '请先编辑接入回复']);
        }

        $configure = M('im_configure');
        $data['is_on'] = $status;
        $id = $configure->where('id=' . $id)->save($data);
        $this->ajaxReturn(['error' => 0, 'id' => $id]);
    }

    /**
     * 添加离开回复
     */
    public function actionInsertUserLeaveReply()
    {
        $mid = I('mid', 0 ,'intval');
        $content = I('content', '', ['htmlspecialchars','addslashes']);
        $customerId = $_SESSION['kefu_id'];

        $configure = M('im_configure');
        $res = $configure->where('id=' . $mid)->find();

        $data['ser_id'] = $customerId;
        $data['type'] = 3;
        $data['content'] = trim($content);
        if (!empty($res)) {
            $res = $configure->where('id=' . $mid)->save($data);
        } else {
            $mid = $configure->data($data)->add();
        }
        $this->ajaxReturn(['error' => 0, 'mid' => $mid]);
    }

    /**
     * 离开回复是否开启
     */
    public function actionUserLeaveReply()
    {
        $id = I('id', 0, 'intval');
        $status = I('status', 0, 'intval');
        if (empty($id)) {
            $this->ajaxReturn(['error' => 1, 'msg' => '请先编辑离开回复']);
        }

        $configure = M('im_configure');
        $data['is_on'] = $status;
        $id = $configure->where('id=' . $id)->save($data);
        $this->ajaxReturn(['error' => 0, 'id' => $id]);
    }

    /**
     * 会话信息
     */
    public function actionDialogInfo()
    {
        $uid = I('uid', 0, 'intval');
        $cid = I('cid', 0, 'intval');

        $dialog = Kefu::getRecentDialog($uid, $cid);

        $user = Kefu::userInfo($dialog['customer_id']);

        // if ($dialog) {
        //     $service['id'] = $dialog['id'];
        // }

        $dialogInfo = [
            'customer_id' => $dialog['customer_id'],
            'avatar' => $user['avatar'],
            'name' => $user['user_name'],
            'services_id' => $uid,
            'goods' => ($dialog['goods_id'] > 0) ? Kefu::getGoods($dialog['goods_id']) : '',
            'store_id' => $dialog['store_id'],
            'start_time' => $dialog['start_time'],
            'origin' => ($dialog['origin'] == 1) ? "PC" : "H5",
            // 'message' => Kefu::getChatLog($service),
        ];
        $this->ajaxReturn($dialogInfo);
    }

    /**
     * 关闭会话
     */
    public function actionCloseDialog()
    {
        $uid = I('uid', 0, 'intval');
        $tid = I('tid', 0, 'intval');

        Kefu::closeWindow($uid, $tid);
    }

    /**
     * 创建会话
     */
    public function actionCreatedialog()
    {
        $uid = I('uid', 0, 'intval');  //客服
        $fid = I('fid', 0, 'intval');  //之前的客服
        $cid = I('cid', 0, 'intval');  //客户ID

        $dialog = Kefu::getRecentDialog($fid, $cid);

        Kefu::addDialog([
            'customer_id' => $dialog['customer_id'],
            'services_id' => $uid,
            'goods_id' => $dialog['goods_id'],
            'store_id' => $dialog['store_id'],
            'start_time' => $dialog['start_time'],
            'origin' => $dialog['origin'],
        ]);
    }

    /**
     * 关闭会话
     * 条件： 超过1个小时没有对话
     * 秒为单位
     */
    public function actionCloseOldDialog()
    {
        $expire = 600;

        $array = Kefu::closeOldWindow($expire);
        echo json_encode($array);
    }

    /**
     * socket数据
     * 修改客服登录状态
     */
    public function actionChangeLogin()
    {
        $id = I('id', 0, 'intval');  //获取客服ID
        $status = I('status', 0, 'intval');  //获取客服ID
        $status = in_array($status, [0, 1]) ? $status : 0;

        $data['chat_status'] = $status;

        M('im_service')->where('id=' . $id . "  AND status = 1")->save($data);
    }


    /**
     * socket数据
     * 存储消息
     * @from_id
     * @name
     * @time
     * @avatar
     * @goods_id
     * @message_type
     * @user_type
     * @to_id
     */
    public function actionStorageMessage()
    {
        $data = I();  //获取数据

        $fromId = empty($data['from_id']) ? 0 : intval($data['from_id']);
        $toId = empty($data['to_id']) ? 0 : intval($data['to_id']);
        $goodsId = empty($data['goods_id']) ? 0 : intval($data['goods_id']);
        $storeId = empty($data['store_id']) ? 0 : intval($data['store_id']);
        $status = ($data['status'] === 0 || $data['status'] === '0') ? 0 : 1;
        $origin = (empty($data['origin']) || $data['origin'] == 'PC') ? 1 : 2;
        if ($fromId == 0) {
            return;
        }
        $user_type = ($data['user_type'] == 'service') ? 2 : 1;

        $dialogData = [
            'customer_id' => ($data['user_type'] == 'service') ? $data['from_id'] : $data['to_id'],
            'services_id' => ($data['user_type'] == 'service') ? $data['to_id'] : $data['from_id'],
            'goods_id' => $goodsId,
            'store_id' => $storeId,
            'start_time' => time(),
            'end_time' => '',
            'origin' => $origin
        ];

        /** 检查会话表 */
        $dialogId = Kefu::isDialog($dialogData);

        if (!$dialogId) {
            //如果不存在  则创建新会话  并结束之前所有会话
            //添加会话表
            $dialogId = Kefu::addDialog($dialogData);
        }

        //存储
        $data['message'] = strip_tags(trim($data['message']));
        $d = [
            'from_user_id' => $fromId,
            'to_user_id' => $toId,
            'message' => strip_tags(trim($data['message'])),
            'add_time' => time(),
            'user_type' => $user_type,
            'dialog_id' => $dialogId,
            'status' => $status
        ];
        $res = M('im_message')->data($d)->add();
        if (!$res) {
            logResult('storage_message:' . json_encode($data));
        }
    }

    /**
     * socket数据
     * 修改客户待接入的消息
     */
    public function actionChangeMsgInfo()
    {
        $cusId = I('cus_id', 0, 'intval');  //客户ID
        $serId = I('ser_id', 0, 'intval');  //客服ID
        /** 修改会话表 */

        Kefu::updateDialog($cusId, $serId);
    }


    /**
     * 切换客服 更新会话表与消息表
     */
    public function actionChangeNewMsgInfo()
    {
        $cusId = I('cus_id', 0, 'intval');  //客户ID
        $serId = I('ser_id', 0, 'intval');  //客服ID
        /** 修改会话表 */

        Kefu::updateNewDialog($cusId, $serId);
    }

    /**
     * socket数据
     * 获取接入回复
     */
    public function actionGetreply()
    {
        $serviceId = I('service_id', 0, 'intval');  //客服ID

        $content = Kefu::getServiceReply($serviceId);

        if (empty($content)) {
            $content = '您好';
        }
        echo $content;
    }

    /**
     * 获取IP
     */
    private function getServerIp()
    {
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }

    /**
     * 上传图片
     */
    public function actionUploadImage()
    {
        $this->load_helper('common');

        $path = 'images/upload/images/' . date('Ymd');
        $result = $this->upload($path, true, 2);

        if ($result['error'] == 0) {
            $arr = [
                'code' => 0,//0表示成功，其它失败
                'msg' => '上传成功',//提示信息 //一般上传失败后返回
                'data' => [
                    'src' => get_image_path($result['url']),
                    'title' => ''
                ]
            ];
            $this->ajaxReturn($arr);
        }
    }


    /**
     * 处理链接信息api
     */
    public function actionTransMessage()
    {
        //获取商品信息
        $message = I('message', '', ['html_in', 'trim']);
        if (empty($message)) {
            $this->ajaxReturn(['error' => 1, 'content' => "invalid params"]);
        }
        $data = Kefu::format_msg($message);

        $this->ajaxReturn($data);
    }

}
