<?php

namespace App\Extensions;

use App\Models\ImService;

/**
 * Class WorkerEvent
 * @package App\Extensions
 */
class WorkerEvent
{
    /**
     * @var string
     */
    private $port = '';

    /**
     * @var string
     */
    private $root_path = '';

    /**
     * @var string
     */
    private $listen_route = '';

    /**
     * @var string
     */
    private $listen_ip = '0.0.0.0';

    /**
     * @var
     */
    private $context;

    /**
     * @var int
     */
    public $timer_expire;

    /**
     * @var bool
     */
    public $is_ssl = false;

    /**
     * WorkerEvent constructor.
     */
    public function __construct()
    {
        $c = require_once base_path('config/chat.php');
        if (!isset($c['listen_route']) || empty($c['listen_route'])) {
            die(' listen_route need to be configured ');
        }
        if (!isset($c['root_path']) || empty($c['root_path'])) {
            die(' root_path need to be configured ');
        }
        if (!isset($c['port']) || empty($c['port'])) {
            die(' port need to be configured ');
        }

        $this->root_path = rtrim($c['root_path'], '/') . '/';
        $this->port = $c['port'];
        if (isset($c['listen_route']) && !empty($c['listen_route'])) {
            $this->listen_route = $c['listen_route'];
        }
        if (isset($c['listen_ip']) && !empty($c['listen_ip'])) {
            $this->listen_ip = $c['listen_ip'];
        }
        if (stripos($this->root_path, 'https') === 0) {
            $this->is_ssl = true;
        }
        if ($this->is_ssl) {
            $this->setpem($c['local_cert'], $c['local_pk']);
        }
        $this->timer_expire = 10;//定时结束会话时间
        unset($c);
    }

    /**
     * 修改客服状态
     * 系统关闭   将客服状态改为  未登录
     */
    public function changeServiceStatus()
    {
        ImService::whereRaw('1=1')->update(['chat_status' => 0]);
    }

    /**
     * 设置端口
     * @param null $port
     */
    public function setPort($port = null)
    {
        if (!is_null($port)) {
            $this->port = $port;
        }
    }

    /**
     * 获取端口
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 获取域名
     * @return string
     */
    public function getListenRoute()
    {
        return $this->listen_route;
    }

    /**
     * 获取IP
     * @return string
     */
    public function getListenIp()
    {
        return $this->listen_ip;
    }

    /**
     * 链接数据库
     * @param $data
     * @param $path
     * @return mixed
     */
    public function db($data, $path)
    {
        $ch = curl_init($path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * @param $id
     */
    public function checkUser($id)
    {
        $this->db(['id' => $id], $this->dbconnPath);
    }

    /**
     * @param $id
     * @param $status
     */
    public function customerLogin($id, $status)
    {
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/changelogin';
        $this->db(['id' => $id, 'status' => $status], $url);
    }

    /**
     * 发送消息并保存
     * @param $connection
     * @param $data
     * @param string $type
     */
    public function sendmsg($connection, $data, $type = '')
    {
        $connection->send(json_encode($data));
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/storagemessage';
        $data['user_type'] = $connection->userType;
        $data['to_id'] = $connection->uid;
        $this->db($data, $url);
    }

    /**
     * 保存消息
     * @param $data
     */
    public function savemsg($data)
    {
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/storagemessage';
        $data['to_id'] = empty($data['to_id']) ? 0 : $data['to_id'];
        $this->db($data, $url);
    }

    /**
     * 发送消息
     * @param $connection
     * @param $data
     * @param string $type
     */
    public function sendinfo($connection, $data, $type = '')
    {
        $connection->send(json_encode($data));
    }

    /**
     * 更新接入的消息
     * @param $data
     */
    public function changemsginfo($data)
    {
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/changemsginfo';

        $this->db($data, $url);
    }

    /**
     * 获取接入回复
     * @param $data
     * @return mixed
     */
    public function getreply($data)
    {
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/getreply';

        return $this->db($data, $url);
    }

    /**
     * 获取证书路径
     * @return mixed
     */
    public function getcontext()
    {
        return $this->context;
    }

    /**
     * 定时发起关闭会话
     * @return mixed
     */
    public function closeolddialog()
    {
        $url = $this->root_path . 'mobile/index.php?r=chat/admin/closeolddialog';

        return $this->db(['close_all_dialog' => 'close_all_dialog'], $url);
    }

    /**
     * 设置证书路径
     * @param $cert
     * @param $pk
     */
    public function setpem($cert, $pk)
    {
        $this->context = [
            'ssl' => [
                // 使用绝对路径
                'local_cert' => $cert, // 也可以是crt文件
                'local_pk' => $pk,
                'verify_peer' => false,
            ]
        ];
    }
}
