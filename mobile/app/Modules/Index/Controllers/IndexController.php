<?php

namespace App\Modules\Index\Controllers;

use App\Extensions\Http;
use App\Libraries\Compile;
use App\Modules\Base\Controllers\FrontendController;

class IndexController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: X-HTTP-Method-Override, Content-Type, x-requested-with, Authorization');
    }

    /**
     * 首页信息
     * post: /index.php?m=index
     * param: null
     * return: module
     */
    public function actionIndex()
    {
        uaredirect(__PC__ . '/');
        if (IS_POST) {
            $preview = input('preview', 0);
            if ($preview) {
                $module = Compile::getModule('preview');
            } else {
                $module = Compile::getModule();
            }
            if ($module === false) {
                $module = Compile::initModule();
            }
            $this->response(['error' => 0, 'data' => $module ? $module : '']);
        }

        /**
         * 首页弹出广告位
         */
        $popup_ads = S('popup_ads');
        if ($popup_ads === false) {
            $popup_ads = dao('touch_ad')->where(['ad_name' => '首页红包广告'])->find();
            S('popup_ads', $popup_ads, 600);
        }

        $time = gmtime();
        $popup_enabled = 1;
        $ad_link = '';
        if ($popup_ads['enabled'] == 1 && ($popup_ads['start_time'] <= $time && $time < $popup_ads['end_time'])) {
            if (empty(cookie('popup_enabled'))) {
                $popup_enabled = get_data_path($popup_ads['ad_code'], 'afficheimg/');
                $ad_link = $popup_ads['ad_link'];
                cookie('ad_link', $ad_link);
                cookie('popup_enabled', $popup_enabled);
            }
        }
        $this->assign('ad_link', $ad_link);
        $this->assign('popup_ads', $popup_enabled);

        /**
         * 微信分享
         */
        $topic_id = input('topic_id', 0, 'intval');
        $pages = dao('touch_page_view')->field('title, thumb_pic, page_id')->where(['id' => $topic_id])->find();
        if ($topic_id > 0) {
            // 关联PC专题
            if ($pages['page_id'] > 0) {
                $topic = dao('topic')->field('title, description')->where(['topic_id' => $pages['page_id']])->find();
                $pages['title'] = $topic['title'];
                $pages['description'] = $topic['description'];
            }
            $pages['thumb_pic'] = get_image_path('data/gallery_album/original_img/' . $pages['thumb_pic']);
        }

        $position = assign_ur_here(0, $pages['title']);
        // SEO标题
        $seo = get_seo_words('index');
        foreach ($seo as $key => $value) {
            $seo[$key] = html_in(str_replace(['{sitename}', '{key}', '{description}'], [C('shop.shop_name'), C('shop.shop_keywords'), C('shop.shop_desc')], $value));
        }

        $page_title = !empty($seo['title']) ? $seo['title'] : $position['title'];
        $keywords = !empty($seo['keywords']) ? $seo['keywords'] : C('shop.shop_keywords');
        $description = !empty($seo['description']) ? $seo['description'] : (!empty($pages['description']) ? $pages['description'] : C('shop.shop_desc'));
        // 微信JSSDK分享
        $share_img = !empty($pages['thumb_pic']) ? $pages['thumb_pic'] : '';
        $share_data = [
            'title' => $page_title,
            'desc' => $description,
            'link' => '',
            'img' => $share_img,
        ];
        $this->assign('share_data', $this->get_wechat_share_content($share_data));
        $this->assign('page_title', $page_title);
        $this->assign('keywords', $keywords);
        $this->assign('description', $description);
        $this->display();
    }

    /**
     * 头部APP广告位
     */
    public function actionAppNav()
    {
        $app = C('shop.wap_index_pro') ? 1 : 0;
        $this->response(['error' => 0, 'data' => $app]);
    }

    /**
     * 站内快讯
     */
    public function actionNotice()
    {
        $condition = [
            'is_open' => 1,
            'cat_id' => 12
        ];
        $list = $this->db->table('article')->field('article_id, title, author, add_time, file_url, open_type')
            ->where($condition)->order('article_type DESC, article_id DESC')->limit(5)->select();
        $res = [];
        foreach ($list as $key => $vo) {
            $res[$key]['text'] = $vo['title'];
            $res[$key]['url'] = build_uri('article', ['aid' => $vo['article_id']]);
        }
        $this->response(['error' => 0, 'data' => $res]);
    }

    /**
     * 返回商品列表
     * post: /index.php?m=admin&c=editor&a=goods
     * param:
     * return:
     */
    public function actionGoods()
    {
        $number = input('post.number', 10);
        $condition = [
            'intro' => input('post.type', '')
        ];
        $list = $this->getGoodsList($condition, $number);
        $res = [];
        $endtime = gmtime(); // time() + 7 * 24 * 3600;
        foreach ($list as $key => $vo) {
            $res[$key]['desc'] = $vo['name']; // 描述
            $res[$key]['sale'] = $vo["sales_volume"]; // 销量
            $res[$key]['stock'] = $vo['goods_number']; // 库存
            if ($vo['promote_price']) {
                $res[$key]['price'] = min($vo['promote_price'], $vo['shop_price']);
            } else {
                $res[$key]['price'] = $vo['shop_price'];
            }
            $res[$key]['marketPrice'] = $vo["market_price"]; // 市场价
            $res[$key]['img'] = $vo['goods_thumb']; // 图片地址
            $res[$key]['link'] = $vo['url']; // 图片链接
            $endtime = $vo['promote_end_date'] > $endtime ? $vo['promote_end_date'] : $endtime;
        }
        $this->response(['error' => 0, 'data' => $res, 'endtime' => date('Y-m-d H:i:s', $endtime)]);
    }

    public function actionSpa()
    {
        $this->display();
    }

    /**
     * 返回商品列表
     * @param string $param
     * @return array
     */
    private function getGoodsList($param = [], $size = 10)
    {
        $data = [
            'id' => 0,
            'brand' => 0,
            'intro' => '',
            'price_min' => 0,
            'price_max' => 0,
            'filter_attr' => 0,
            'sort' => 'goods_id',
            'order' => 'desc',
            'keyword' => '',
            'isself' => 0,
            'hasgoods' => 0,
            'promotion' => 0,
            'page' => 1,
            'type' => 1,
            'size' => $size,
            C('VAR_AJAX_SUBMIT') => 1
        ];

        $data = array_merge($data, $param);
        $cache_id = md5(serialize($data));
        $list = S($cache_id);
        if ($list === false) {
            $url = url('category/index/products', $data, false, true);
            $res = Http::doGet($url);
            if ($res === false) {
                $res = file_get_contents($url);
            }
            if ($res) {
                $data = json_decode($res, 1);
                $list = empty($data['list']) ? false : $data['list'];
                S($cache_id, $list, 600);
            }
        }
        return $list;
    }
}
