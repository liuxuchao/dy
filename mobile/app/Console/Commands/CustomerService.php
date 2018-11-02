<?php

namespace App\Console\Commands;

use Workerman\Worker;
use App\Extensions\WorkerEvent;
use Illuminate\Console\Command;

/**
 * Class CustomerService
 * @package App\Console\Commands
 */
class CustomerService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:chat {action=start} {--d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'customer service';

    /**
     * Workerman Event Handler
     *
     * @var object
     */
    protected $workermanEvent;

    /**
     * CustomerService constructor.
     * @param WorkerEvent $workermanEvent
     */
    public function __construct(WorkerEvent $workermanEvent)
    {
        parent::__construct();
        $this->workermanEvent = $workermanEvent;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * workerman 需要带参数 所以得强制修改
         */
        $action = $this->argument('action');
        if (!in_array($action, ['start', 'stop', 'restart', 'reload', 'status'])) {
            $this->error('Error Arguments');
            exit;
        }

        global $argv;
        $argv[0] = 'app:chat';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';

        /**
         * 自动检测端口号
         */
        $ip = $this->workermanEvent->getListenIp();
        $port_status = $index = 0;
        $chat_config = base_path('config/chat.php');
        $config = require($chat_config);
        do {
            $newPort = isset($config['port']) ? $config['port'] : rand(2048, 65535);
            $port_status = $this->checkPort($ip, $newPort);
            $this->workermanEvent->setPort($newPort);
            $config['port'] = (string)$newPort;
            file_put_contents($chat_config, "<?php\n\r return " . var_export($config, 1) . ';');
            $index++;
        } while ($port_status != 2 && $index < 3);

        /**
         * 创建一个Worker监听一个端口，使用websocket协议通讯
         */
        if ($this->workermanEvent->is_ssl) {
            $ws_worker = new Worker("websocket://" . $this->workermanEvent->getListenIp() . ":" . $this->workermanEvent->getPort(), $this->workermanEvent->getcontext());
            $ws_worker->transport = 'ssl';
        } else {
            $ws_worker = new Worker("websocket://" . $this->workermanEvent->getListenIp() . ":" . $this->workermanEvent->getPort());
        }

        /**
         * 启动4个进程对外提供服务
         */
        $ws_worker->count = 1; // 如何设置进程数：http://doc.workerman.net/315230
        $ws_worker->serviceContainer = [];
        $ws_worker->customerContainer = [];
        $ws_worker->eventContainer = $this->workermanEvent;
        /**
         * 停止服务 将客服状态改为下线
         */
        if ($action == 'stop' || $action == 'restart') {
            echo "change service status...\n";
            $ws_worker->eventContainer->changeServiceStatus();
            echo "all service in logout status\n";
        }

        $ws_worker->onConnect = function ($connection) {
            echo "new connection from ip " . $connection->getRemoteIp() . "\n";
        };

        /**
         * 接受客户端数据  并做处理
         * @param $connection
         * @param $data
         */
        $ws_worker->onMessage = function ($connection, $data) use ($ws_worker) {
            $data = json_decode($data, 1);
            $data['store_id'] = isset($data['store_id']) ? intval($data['store_id']) : 0;
            $data['goods_id'] = isset($data['goods_id']) ? intval($data['goods_id']) : 0;
            $event = $ws_worker->eventContainer;

            switch ($data['type']) {
                case 'login':
                    /**
                     * 用户登录  保存用户信息
                     */
                    $connection->uid = $data['uid'];
                    $connection->uname = $data['name'];
                    $data['user_type'] = (isset($data['user_type']) && $data['user_type'] == 'service') ? 'service' : 'customer';
                    $connection->userType = $data['user_type'];// 客服或者客户
                    $connection->avatar = $data['avatar'];// 头像
                    $connection->origin = $data['origin'];// 来源   PC | 手机
                    if ($data['user_type'] == 'service') {
                        $connection->store_id = $data['store_id'];//商家ID

                        $ws_worker->serviceContainer[$data['store_id']][$data['uid']] = $connection;

                        /** 验证用户   修改客服登录状态 */
                        $event->customerLogin($data['uid'], 1);
                    } elseif ($data['user_type'] == 'customer') {
                        /** 如果用户存在、剔除 */
                        if (isset($ws_worker->customerContainer[$data['uid']])) {
                            $msg = ['message_type' => 'others_login'];
                            $event->sendinfo($ws_worker->customerContainer[$data['uid']], $msg);
                        }
                        $connection->targetService = [
                            'store_id' => $data['store_id']
                        ];
                        $ws_worker->customerContainer[$data['uid']] = $connection;
                    }
                    $connection->send(json_encode(['msg' => 'yes', 'message_type' => 'init']));

                    break;
                case 'sendmsg':
                    /**
                     * 发送消息
                     */
                    $msg = ['from_id' => $connection->uid, 'name' => $connection->uname, 'time' => date('H:i:s'), 'message' => $data['msg'], 'avatar' => $data['avatar'], 'goods_id' => $data['goods_id'], 'store_id' => $data['store_id'], 'message_type' => 'come_msg', 'origin' => $data['origin']];

                    //客户发送给客服
                    if ($connection->userType == 'customer') {
                        if (empty($data['to_id'])) {
                            //群发
                            $msg['user_type'] = 'service';
                            $msg['origin'] = $data['origin'];
                            $msg['status'] = 1;
                            $event->savemsg($msg);
                            $msg['user_type'] = 'customer';
                            $msg['message_type'] = 'come_wait';
                            if (isset($ws_worker->serviceContainer[$data['store_id']])) {
                                foreach ($ws_worker->serviceContainer[$data['store_id']] as $uid => $con) {
                                    $event->sendinfo($con, $msg);
                                }
                            }
                        } else {
                            //直接发送
                            $msg['to_id'] = $data['to_id'];
                            if (isset($ws_worker->serviceContainer[$data['store_id']][$data['to_id']])) {
                                $msg['status'] = 0;
                                $event->sendmsg($ws_worker->serviceContainer[$data['store_id']][$data['to_id']], $msg);
                                $connection->targetService = [
                                    'store_id' => $data['store_id'],
                                    'sid' => $data['to_id']
                                ];
                            } elseif (!isset($ws_worker->serviceContainer[$data['store_id']][$data['to_id']])) {
                                //保存
                                $msg['user_type'] = 'service';
                                $msg['status'] = 1;
                                $event->savemsg($msg);
                            }
                        }
                    } elseif ($connection->userType == 'service') {
                        if (empty($data['to_id']) || !isset($ws_worker->customerContainer[$data['to_id']])) {
                            //用户不在  保存消息
                            $msg['to_id'] = $data['to_id'];
                            $msg['status'] = 1;
                            $event->savemsg($msg);
                            break;
                        }

                        if ($ws_worker->customerContainer[$data['to_id']]->targetService['store_id'] == $data['store_id']
                            && (
                                !isset($ws_worker->customerContainer[$data['to_id']]->targetService['sid'])
                                || $ws_worker->customerContainer[$data['to_id']]->targetService['sid'] == ''
                            )
                        ) {
                            // 当前没在聊天
                            // 设置客户当前聊天对象
                            $ws_worker->customerContainer[$data['to_id']]->targetService = [
                                'store_id' => $data['store_id'],
                                'sid' => $connection->uid
                            ];
                            $msg['status'] = 0;
                            $event->sendmsg($ws_worker->customerContainer[$data['to_id']], $msg);
                        } // 判断客户当前 是否正在聊天
                        elseif ($ws_worker->customerContainer[$data['to_id']]->targetService['store_id'] == $data['store_id']
                            && $ws_worker->customerContainer[$data['to_id']]->targetService['sid'] == $connection->uid
                        ) {
                            // 客户正在与本人聊天

                            $msg['status'] = 0;
                            $event->sendmsg($ws_worker->customerContainer[$data['to_id']], $msg);
                        } else {

                            // 客户在跟别人聊天
                            if ($ws_worker->customerContainer[$data['to_id']]->origin == 'H5') {
                                // 手机登录 存为离线消息
                                $msg['to_id'] = $data['to_id'];
                                $msg['status'] = 1;
                                $event->savemsg($msg);
                            } else {
                                // PC登录 直接发送
                                $event->sendmsg($ws_worker->customerContainer[$data['to_id']], $msg);
                            }
                            //
                        }
                    }
                    break;
                case 'info':
                    /**
                     * 通知所有客服消息被抢   ser_id为客服ID   cus_id为客户ID
                     */
                    $msg = ['cus_id' => $data['msg'], 'ser_id' => $data['from_id'], 'message_type' => 'robbed', 'goods_id' => $data['goods_id'], 'store_id' => $data['store_id']];
                    if (isset($ws_worker->serviceContainer[$data['store_id']])) {
                        foreach ($ws_worker->serviceContainer[$data['store_id']] as $uid => $con) {
                            if ($con->uid == $data['from_id']) {
                                continue;
                            }
                            $event->sendinfo($con, $msg);
                        }
                    }
                    $event->changemsginfo($msg);
                    //用户存在则通知用户已被接入
                    $msg = ['service_id' => $data['from_id'], 'name' => $connection->uname, 'store_id' => $data['store_id'], 'message_type' => 'user_robbed'];
                    if (isset($ws_worker->customerContainer[$data['msg']])) {
                        $msg['msg'] = $event->getreply(['service_id' => $data['from_id']]);
                        $msg['avatar'] = $ws_worker->serviceContainer[$data['store_id']][$data['from_id']]->avatar;
                        $event->sendinfo($ws_worker->customerContainer[$data['msg']], $msg);

                        // 设置客户当前聊天对象
                        $ws_worker->customerContainer[$data['msg']]->targetService = [
                            'store_id' => $data['store_id'],
                            'sid' => $data['from_id']
                        ];
                    }
                    break;
                case 'change_service':
                    /**
                     * 切换客服
                     * $data['type'];
                     * $data['to_id'];  客服ID
                     * $data['from_id'];  客服ID
                     * $data['goods_id'];
                     * $data['store_id'];
                     * $data['cus_id'];   客户ID
                     */
                    if ($connection->userType == 'service') {
                        if (
                            isset($ws_worker->customerContainer[$data['cus_id']]) &&
                            isset($ws_worker->serviceContainer[$data['store_id']][$data['from_id']])
                        ) {
                            $ws_worker->customerContainer[$data['cus_id']]->targetService = [
                                'store_id' => $data['store_id'],
                                'sid' => $data['to_id']
                            ];
                            //通知客户
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'store_id' => $data['store_id'],
                                'message_type' => 'change_service'];
                            $event->sendinfo($ws_worker->customerContainer[$data['cus_id']], $msg);
                            //通知客服本人
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'cus_id' => $data['cus_id'], 'message_type' => 'change_service'];
                            $event->sendinfo($connection, $msg);
                            //通知客服
                            $msg = ['sid' => $data['to_id'], 'fid' => $data['from_id'], 'cus_id' => $data['cus_id'], 'store_id' => $data['store_id'], 'message_type' => 'change_service'];
                            $event->sendinfo($ws_worker->serviceContainer[$data['store_id']][$data['to_id']], $msg);
                        }
                    }
                    break;
                case 'close_link':
                    /**
                     * 通知用户  客服已断开
                     */
                    $msg = ['to_id' => $data['to_id'], 'msg' => '客服已断开', 'message_type' => 'close_link'];
                    //用户存在则通知用户已被接入
                    if (isset($ws_worker->customerContainer[$data['to_id']])) {
                        $event->sendinfo($ws_worker->customerContainer[$data['to_id']], $msg);
                    }
                    break;
            }
        };

        /**
         * 当客户端断开链接时
         * @param $connection
         */
        $ws_worker->onClose = function ($connection) use ($ws_worker) {
            $event = $ws_worker->eventContainer;

            if ($connection->userType == 'service') {
                unset($ws_worker->serviceContainer[$connection->store_id][$connection->uid]);
            } elseif ($connection->userType == 'customer') {
                $msg = ['message_type' => 'others_login'];
                if (isset($ws_worker->customerContainer[$connection->uid])) {
                    $event->sendinfo($ws_worker->customerContainer[$connection->uid], $msg);
                }
                unset($ws_worker->customerContainer[$connection->uid]);
            }
            $event->customerLogin($connection->uid, 0);

            /**
             * 通知好友用户登出
             */
            foreach ($connection->worker->connections as $con) {
                $user = ['uid' => $connection->uid, 'message_type' => 'leave'];
                $event->sendmsg($con, $user);
            }
        };

        /**
         * 关闭客服   执行操作
         * 修改所有客服状态为  未登录
         */
        $ws_worker->onWorkerStop = function () use ($ws_worker) {
            echo "change service status...\n";
            $ws_worker->eventContainer->changeServiceStatus();
            echo "all service in logout status\n";
        };

        // 运行worker
        Worker::runAll();
    }

    /**
     * 检测端口是否开放
     * @param $ip
     * @param $port
     * @return string
     */
    private function checkPort($ip, $port)
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_nonblock($sock);
        socket_connect($sock, $ip, $port);
        socket_set_block($sock);
        $return = @socket_select($r = [$sock], $w = [$sock], $f = [$sock], 3);
        socket_close($sock);
        return $return; // 0:timeout; 1:running; 2:closed
    }
}
