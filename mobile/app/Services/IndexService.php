<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Article\ArticleRepository;

/**
 * 商店首页服务
 * Class IndexService
 * @package App\Services
 */
class IndexService
{
    private $goodsRepository;
    private $shopRepository;
    private $articleRepository;
    private $root_url;

    /**
     * IndexService constructor.
     * @param GoodsRepository $goodsRepository
     * @param ShopRepository $shopRepository
     * @param ArticleRepository $articleRepository
     * @param Request $request
     */
    public function __construct(GoodsRepository $goodsRepository, ArticleRepository $articleRepository, ShopRepository $shopRepository, Request $request)
    {
        $this->goodsRepository = $goodsRepository;
        $this->articleRepository = $articleRepository;
        $this->shopRepository = $shopRepository;
        $this->root_url = dirname(dirname($request->root())) . '/';
    }

    /**
     * 微信小程序 首页推荐商品
     * @return array
     */
    public function bestGoodsList($type = 'best')
    {
        $arr = [
            'goods_id',   //商品id
            'goods_name',   //商品名
            'shop_price',   //商品价格
            'goods_thumb',   //商品图片
            'promote_price',   //商品促销价格
            'promote_start_date',   //商品促销开始时间
            'promote_end_date',   //商品促销结束时间
            'goods_link',    //商品链接
            'goods_number',   //商品销量
            'market_price',   //商品原价格
        ];
        $goodsList = $this->goodsRepository->findByType($type);  //获取推荐商品

        $data = array_map(function ($v) use ($arr) {
            foreach ($v as $ck => $cv) {
                if (!in_array($ck, $arr)) {
                    unset($v[$ck]);
                }
            }
            if ($v['promote_price'] && $v['promote_start_date'] < gmtime() && $v['promote_end_date'] > gmtime()) {
                $v['shop_price'] = ($v['shop_price'] > $v['promote_price']) ? $v['promote_price'] : $v['shop_price'];
            }
            $v['goods_thumb'] = get_image_path($v['goods_thumb']);
            $v['goods_stock'] = $v['goods_number'];
            $v['market_price_formated'] = price_format($v['market_price'], false);
            $v['shop_price_formated'] = price_format($v['shop_price'], false);
            unset($v['goods_number']);
            return $v;
        }, $goodsList);

        return $data;
    }

    /**
     * 获取banner
     * @return array
     */
    public function getBanners()
    {
        $res = $this->shopRepository->getPositions('weapp', 10);  //获取banner

        $ads = [];

        foreach ($res as $row) {
            if (!empty($row['position_id'])) {
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                    "data/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = [
                    'pic' => get_image_path($src),
                    'banner_id' => $row['ad_id'],
                    'link' => $row['ad_link'],
                ];
            }
        }

        return $ads;
    }

    /**
     * 获取广告位
     * @return array
     */
    public function getAdsense()
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $number = $shopconfig->getShopConfigByCode('wx_index_show_number');
        if (empty($number)) {
            $number = 10;
        }

        $adsense = $this->shopRepository->getPositions('', $number);  //获取广告位

        $ads = [];
        foreach ($adsense as $row) {
            if (!empty($row['position_id'])) {
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                    "data/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = [
                    'pic' => get_image_path($src),
                    'adsense_id' => $row['ad_id'],
                    'link' => $row['ad_link'],
                ];
            }
        }
        return $ads;
    }

    /**
     * 获取广告位与店铺广告与新闻
     * @return array
     */
    public function getAd()
    {
        $ads['ad'] = $this->shopRepository->getAd();  //获取广告位
        $ads['store'] = $this->shopRepository->getStore();  //获取店铺广告
        $ads['article'] = $this->article();  //获取新闻
        return $ads;
    }

    /**
     * 获取新闻
     * @return array
     */
    public function article()
    {
        $res = $this->shopRepository->getArticle();//获取新闻

        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';

        foreach ($res as $k => $v) {
            $res[$k]['content'] = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $rootPath . '/images/upload', $v['content']);
        }
        return $res;
    }
}
