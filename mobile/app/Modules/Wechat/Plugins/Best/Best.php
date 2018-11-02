<?php

namespace App\Modules\Wechat\Plugins\Best;

use App\Modules\Wechat\Controllers\PluginController;

/**
 * 精品查询类
 */
class Best extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 商家ID
    protected $wechat_ru_id = 0;
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param unknown $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->plugin_name = strtolower(basename(__FILE__, '.php'));
        $this->cfg = $cfg;
        $this->wechat_ru_id = $this->cfg['wechat_ru_id'];
    }

    /**
     * 安装
     */
    public function install()
    {
        $this->plugin_display('install', $this->cfg);
    }

    /**
     * 获取数据
     */
    public function returnData($fromusername, $info)
    {
        $articles = ['type' => 'text', 'content' => '暂无精品'];
        $map = [
            'is_on_sale' => 1,
            'is_delete' => 0,
            'is_alone_sale' => 1
        ];
        if ($this->wechat_ru_id > 0) {
            $map['store_best'] = 1;
            $map['user_id'] = $this->wechat_ru_id;
            $map['review_status'] = ['gt', 2];
        } else {
            $map['is_best'] = 1;
        }
        $data = dao('goods')->field('goods_id, goods_name, goods_img')->where($map)->order('sort_order ASC, goods_id desc')->limit(4)->select();
        if (!empty($data)) {
            $articles = [];
            $articles['type'] = 'news';
            foreach ($data as $key => $val) {
                $articles['content'][$key]['PicUrl'] = get_wechat_image_path($val['goods_img']);
                $articles['content'][$key]['Title'] = $val['goods_name'];
                $articles['content'][$key]['Url'] = __HOST__ . url('goods/index/index', ['id' => $val['goods_id'], 'wechat_ru_id' => $this->wechat_ru_id]);
            }
            // 积分赠送
            if ($this->wechat_ru_id == 0) {
                $this->updatePoint($fromusername, $info);
            }
        }

        return $articles;
    }

    /**
     * 积分赠送
     *
     * @param unknown $fromusername
     * @param unknown $info
     */
    public function updatePoint($fromusername, $info)
    {
        if (!empty($info)) {
            // 配置信息
            $config = [];
            $config = unserialize($info['config']);
            // 开启积分赠送
            if (isset($config['point_status']) && $config['point_status'] == 1) {
                $where = 'openid = "' . $fromusername . '" and keywords = "' . $info['command'] . '" and createtime > (UNIX_TIMESTAMP(NOW())- ' . $config['point_interval'] . ')';
                $sql = 'SELECT count(*) as num FROM {pre}wechat_point WHERE ' . $where . 'ORDER BY createtime DESC';
                $num = $GLOBALS['db']->query($sql);
                // 当前时间减去时间间隔得到的历史时间之后赠送的次数
                if ($num[0]['num'] < $config['point_num']) {
                    $this->do_point($fromusername, $info, $config['point_value']);
                }
            }
        }
    }

    /**
     * 行为操作
     */
    public function executeAction()
    {
    }
}
