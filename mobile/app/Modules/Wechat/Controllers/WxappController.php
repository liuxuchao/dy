<?php

namespace App\Modules\Wechat\Controllers;

use App\Modules\Base\Controllers\BackendController;
use App\Extensions\Wxapp;

class WxappController extends BackendController
{
    public function __construct()
    {
        parent::__construct();
        L(require(MODULE_PATH . 'Language/' . C('shop.lang') . '/wechat.php'));
        $this->assign('lang', array_change_key_case(L()));
        // 获取配置信息
        $this->get_config();
        // 初始化
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {

    }

    /**
     * 小程序设置
     */
    public function actionIndex()
    {
        // 权限
        $this->admin_priv('wxapp_wechat_config');

        // 提交处理
        if (IS_POST) {
            $id = I('id', 0, 'intval');
            $data = I('post.data', '', ['htmlspecialchars','trim']);
            // 验证数据
            if (empty($data['wx_appid'])) {
                $this->message(L('must_appid'), null, 2);
            }
            if (empty($data['wx_appsecret'])) {
                $this->message(L('must_appsecret'), null, 2);
            }
            if (empty($data['wx_mch_id'])) {
                $this->message(L('must_mch_id'), null, 2);
            }
            if (empty($data['wx_mch_key'])) {
                $this->message(L('must_mch_key'), null, 2);
            }
            if (empty($data['token_secret'])) {
                $this->message(L('must_token_secret'), null, 2);
            }
            // 更新数据
            if (!empty($id)) {
                // 如果 wx_appsecret 包含 * 跳过不保存数据库
                if (strpos($data['wx_appsecret'], '*') == true) {
                    unset($data['wx_appsecret']);
                }
                dao('wxapp_config')->data($data)->where(['id' => $id])->save();
            } else {
                $data['add_time'] = gmtime();
                dao('wxapp_config')->data($data)->add();
            }
            $this->message(L('wechat_editor') . L('success'), url('index'));
        }

        // 查询
        $info = dao('wxapp_config')->find();
        if (!empty($info)) {
            // 用*替换字符显示
            $info['wx_appsecret'] = string_to_star($info['wx_appsecret']);
        }

        $this->assign('data', $info);
        $this->display();
    }

    /**
     * 新增小程序
     */
    public function actionAppend()
    {

    }

    /**
     * 删除小程序
     */
    public function actionDelete()
    {
        $condition['id'] = input('id', 0, 'intval');
        dao('wxapp_config')->where($condition)->delete();
    }


    /**
     * 模板消息
     */
    public function actionTemplate()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        $list = $this->model->table('wxapp_template')->order('id asc')->select();
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['add_time'] = local_date('Y-m-d H:i', $val['add_time']);
            }
        }

        $this->assign('list', $list);
        $this->display();
    }


    /**
     * 编辑模板消息
     */
    public function actionEditTemplate()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        if (IS_AJAX) {
            $id = I('post.id');
            $data = I('post.data', '', ['htmlspecialchars','trim']);
            if ($id) {
                $condition['id'] = $id;

                $data['add_time'] = gmtime();
                $this->model->table('wxapp_template')->data($data)->where($condition)->save();
                exit(json_encode(['status' => 1]));
            } else {
                exit(json_encode(['status' => 0, 'msg' => L('template_edit_fail')]));
            }
        }
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $condition['id'] = $id;
            $template = $this->model->table('wxapp_template')->where($condition)->find();
            $this->assign('template', $template);
        }

        $this->display();
    }

    /**
     * 启用或禁止模板消息
     */
    public function actionSwitch()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        $id = I('get.id', 0, 'intval');
        $status = I('get.status', 0, 'intval');
        if (empty($id)) {
            $this->message(L('empty'), null, 2);
        }
        $condition['id'] = $id;

        $data = [];
        $data['add_time'] = gmtime();

        // 启用模板消息
        if ($status == 1) {
            // 模板ID为空
            $template = $this->model->table('wxapp_template')->field('wx_template_id, wx_code, wx_keyword_id')->where($condition)->find();

            if (empty($template['wx_template_id'])) {
                $wx_keyword_id = explode(',',$template['wx_keyword_id']);
                $template_id = $this->weObj->wxaddTemplateMessage($template['wx_code'], $wx_keyword_id);
                // 已经存在模板ID
                if ($template_id) {
                    $data['wx_template_id'] = $template_id;
                    $this->model->table('wxapp_template')->data($data)->where($condition)->save();
                } else {
                    $this->message($this->weObj->errMsg, null, 2);
                }
            }
            // 重新启用 更新状态status
            $data['status'] = 1;
            $this->model->table('wxapp_template')->data($data)->where($condition)->save();
        } else {
            // 禁用 更新状态status
            $data['status'] = 0;
            $this->model->table('wxapp_template')->data($data)->where($condition)->save();
        }
        $this->redirect('template');
    }

    /**
     * 重置模板消息
     * @return
     */
    public function actionResetTemplate()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        if (IS_AJAX) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $id = I('get.id', 0, 'intval');
            if (!empty($id)) {
                $condition['id'] = $id;
                $template = dao('wxapp_template')->field('wx_template_id')->where($condition)->find();
                if (!empty($template['wx_template_id'])) {
                    $rs = $this->weObj->wxDelTemplate($template['wx_template_id']);
                    if (empty($rs)) {
                        $json_result['msg'] = L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg;
                        exit(json_encode($json_result));
                    }
                    dao('wxapp_template')->data(['wx_template_id' => '', 'status' => 0])->where(['id' => $id])->save();
                    $json_result['msg'] = '重置成功！';
                    exit(json_encode($json_result));
                }
            }
            $json_result['error'] = 1;
            $json_result['msg'] = '重置失败！';
            exit(json_encode($json_result));
        }
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
        $without = [
            'index',
            'append',
            'modify',
            'delete',
            'set_default'
        ];

        if (!in_array(strtolower(ACTION_NAME), $without)) {
            // 小程序配置信息
            $where['id'] = 1;
            $wechat = $this->model->table('wxapp_config')->field('wx_appid, wx_appsecret, status')->where($where)->find();
            if (empty($wechat)) {
                $wechat = [];
            }
            if (empty($wechat['status'])) {
                $this->message(L('open_wxapp'), url('index'), 2);
                exit;
            }
            $config = [];
            $config['appid'] = $wechat['wx_appid'];
            $config['secret'] = $wechat['wx_appsecret'];
            $this->weObj = new Wxapp($config);
            $this->assign('type', $wechat['type']);
        }
    }





}
