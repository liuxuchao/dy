<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\TeamService;
use App\Services\AuthService;
use App\Services\GoodsService;
use App\Api\Controllers\Controller;


/**
 * Class IndexController
 * @package App\Api\Controllers\Wx
 */
class TeamController extends Controller
{


    /** @var IndexService  */
    private $teamService;
    private $authService;
	private $goodsService;

    /**
     * IndexController constructor.
     * @param IndexService $teamService
     * @param AuthService $authService
     */
    public function __construct(TeamService $teamService, AuthService $authService,GoodsService $goodsService)
    {
        $this->teamService = $teamService;
        $this->authService = $authService;
		$this->goodsService = $goodsService;
    }

    /**
     * 拼团首页
     * @return mixed
     */
    public function index()
    {
        // 获取拼团首页banner广告位
        $banner = $this->teamService->getAdsense('1023');
        $data['banner'] = $banner;
        
		//拼团首页banner下广告位
		$banner_bottom = $this->teamService->getAdsense('1024');
        $data['banner_bottom'] = $banner_bottom;

		//拼团首页热门活动广告位-left
		$ad_hot_left = $this->teamService->getAdsense('1025');
        $data['ad_hot_left'] = $ad_hot_left;

		//拼团首页热门活动广告位-right
		$ad_hot_right = $this->teamService->getAdsense('1026');
        $data['ad_hot_right'] = $ad_hot_right;

		//拼团首页热门活动下广告位
		$ad_hot_bottom = $this->teamService->getAdsense('1027');
        $data['ad_hot_bottom'] = $ad_hot_bottom;

		//拼团首页精选商品广告位-left
		$ad_best_left = $this->teamService->getAdsense('1028');
        $data['ad_best_left'] = $ad_best_left;

		//拼团首页精选商品广告位-right
		$ad_best_right = $this->teamService->getAdsense('1029');
        $data['ad_best_right'] = $ad_best_right;

		// 获取拼团主频道
        $team_categories =$this->teamService->teamCategories();
		$data['team_categories'] = $team_categories;
        return $this->apiReturn($data);
    }

    /**
     * 拼团商品列表
     * @param Request $request
     * @return array
     */
    public function teamList(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
			'size' => 'required|integer',
			'tc_id' => 'required|integer'
        ]);

        $list = $this->teamService->teamGoodsList($request->get('page'), $request->get('size'), $request->get('tc_id'));

        return $this->apiReturn($list);
    }


	/**
     * 首页下单提示轮播
     * @return mixed
     */
    public function virtualOrder(Request $request)
    {
        //验证参数
        $this->validate($request, []);

		//验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }

		$data = $this->teamService->virtualOrder($uid);

		return $this->apiReturn($data);
	}



	/**
     * 拼团频道页面
     * @return mixed
     */
    public function categoriesIndex(Request $request)
    {
		//验证参数
        $this->validate($request, [
            'tc_id' => 'required|integer'  //拼团顶级频道id
        ]);

		// 获取频道广告位
        $banner = $this->teamService->categoriesAdsense($request->get('tc_id'), 'banner');
        $data['banner'] = $banner;

		$ads_left = $this->teamService->categoriesAdsense($request->get('tc_id'), 'left');
		$data['ads_left'] = $ads_left;

		$ads_right = $this->teamService->categoriesAdsense($request->get('tc_id'), 'right');
		$data['ads_right'] = $ads_right;

		// 获取拼团主频道
        $team_categories =$this->teamService->teamCategories();
		$data['team_categories'] = $team_categories;

		// 获取拼团子频道
        $team_categories_child =$this->teamService->teamCategoriesChild($request->get('tc_id'));
		$data['team_categories_child'] = $team_categories_child['list'];
		// 获取主频道名称
		$data['title'] = $team_categories_child['title'];

        return $this->apiReturn($data);
    }

	/**
     * 拼团子频道商品列表
     * @return mixed
     */
    public function categoryList(Request $request)
    {
		//验证参数
        $this->validate($request, [
			'page' => 'required|integer',
			'size' => 'required|integer',
            'tc_id' => 'required|integer',  //拼团子频道id
			'keyword' => 'required|string',
			'sort_key' => 'required|integer',
			'sort_value' => 'required|string',
        ]);

		// 获取拼团子频道商品列表
        $list =$this->teamService->categoryGoodsList($request->get('tc_id'),$request->get('page'),$request->get('size'),$request->get('keyword'),$request->get('sort_key'),$request->get('sort_value'));

        return $this->apiReturn($list);
    }

	/**
     * 拼团排行
     * @return mixed
     */
    public function teamRanking(Request $request)
    {
		//验证参数
        $this->validate($request, [
			'page' => 'required|integer',
			'size' => 'required|integer',
            'type' => 'required|integer'
        ]);

		// 拼团排行
        $list = $this->teamService->teamRankingList($request->get('page'), $request->get('size'), $request->get('type'));

        return $this->apiReturn($list);
    }

	/**
     * 拼团商品详情
     * @param Request $request
     * @return array
     */
    public function goodsDetail(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'goods_id' => 'required|integer',
			'team_id' => 'required|integer'
        ]);

        //验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }
        $list = $this->teamService->goodsDetail($request->get('goods_id'), $uid, $request->get('team_id'));

        return $this->apiReturn($list, $list['error']);
    }





	/**
     * 拼团商品改变属性、数量时重新计算商品价格
     * @param Request $request
     * @return array
     */
    public function teamProperty(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'goods_id' => 'required|integer',         //商品id
            'num' => 'required|integer',
            'warehouse_id' => 'required|integer',    //仓库id
            'area_id' => 'required|integer'          //地区id
        ]);

        $price = $this->teamService->goodsPropertiesPrice($request->get('goods_id'), $request->get('attr_id'), $request->get('num'), $request->get('warehouse_id'), $request->get('area_id'));

        return $this->apiReturn($price);
    }

	/**
     * 拼团购买
     * @param Request $request
     * @return array
     */
    public function teamBuy(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'goods_id' => 'required|integer',     //拼团商品id
            't_id' => 'required|integer',         //拼团活动id
            'num' => 'required|integer',          //数量
            'team_id' => 'required|integer',      //拼团开团id
        ]);

        $res = $this->authService->authorization();   //返回用户ID
        if (isset($res['error']) && $res['error'] > 0) {
            return $this->apiReturn($res, 1);
        }

        //验证通过
        $args = array_merge($request->all(), ['uid'=>$res]);
        $result = $this->teamService->addGoodsToCart($args);

        return $this->apiReturn($result);
    }

    /**
     * 等待成团页面
     * @param Request $request
     * @return array
     */
    public function teamWait(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'team_id' => 'required|int',  //开团id
			'user_id' => 'required|int'   //开团会员id
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        //验证通过
        $list = $this->teamService->teamWait($uid, $request->get('team_id'), $request->get('user_id'));

        return $this->apiReturn($list);
    }

	/**
     * 拼推荐商品
     * @return mixed
     */
    public function teamIsBest(Request $request)
    {
		//验证参数
        $this->validate($request, [
			'page' => 'required|integer',
			'size' => 'required|integer',
			'type' => 'required|integer',  //3
        ]);

		// 拼团排行
        $list = $this->teamService->teamRankingList($request->get('page'), $request->get('size'), $request->get('type'));

        return $this->apiReturn($list);
    }


	/**
     * 拼团成员
     * @return mixed
     */
    public function teamUser(Request $request)
    {
		//验证参数
        $this->validate($request, [
			'team_id' => 'required|int',  //开团id
			'page' => 'required|integer',
			'size' => 'required|integer',
        ]);

		// 拼团成员
        $list = $this->teamService->teamUser($request->get('team_id'), $request->get('page'), $request->get('size'));

        return $this->apiReturn($list);
    }

	/**
     * 我的拼团
     * @return mixed
     */
    public function teamUserOrder(Request $request)
    {
		//验证参数
        $this->validate($request, [
			'type' => 'required|int',
			'page' => 'required|integer',
			'size' => 'required|integer',
        ]);

		//验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }
		// 拼团订单
        $list = $this->teamService->teamUserOrder($uid, $request->get('type'), $request->get('page'), $request->get('size'));

        return $this->apiReturn($list);
    }


























}
