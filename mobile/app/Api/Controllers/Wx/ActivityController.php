<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\ActivityService;
use App\Services\AuthService;
use App\Services\GoodsService;
use App\Api\Controllers\Controller;


/**
 * Class IndexController
 * @package App\Api\Controllers\Wx
 */
class ActivityController extends Controller
{


    /** @var IndexService  */
    private $activityService;
    private $authService;
	private $goodsService;

    /**
     * IndexController constructor.
     * @param IndexService $teamService
     * @param AuthService $authService
     */
    public function __construct(
        ActivityService $activityService, 
        AuthService $authService,
        GoodsService $goodsService)
    {
        $this->activityService = $activityService;
        $this->authService = $authService;
		$this->goodsService = $goodsService;
    }


    /**
     * 优惠活动 - 活动首页
     * @return mixed
     */
    public function index(Request $request)
    {
         //验证参数
        $this->validate($request, []);

        $list['list'] = $this->activityService->activityList();

        return $this->apiReturn($list);
    }


    /**
     * 优惠活动 - 活动详情
     * @param Request $request
     * @return array
     */
    public function detail(Request $request)
    {
        //验证参数
        $this->validate($request, [
			'act_id' => 'required|integer'
        ]);
        $list = $this->activityService->detail($request->get('act_id'));

        return $this->apiReturn($list);
    }


    /**
     * 优惠活动 - 活动商品
     * @param Request $request
     * @return array
     */
    public function activityGoods(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'act_id' => 'required|integer'
        ]);
        $list = $this->activityService->activityGoods($request->get('act_id'),$request->get('page'),$request->get('size'));

        return $this->apiReturn($list);
    }

    /**
     * 优惠活动 - 活动商品凑单
     * @param Request $request
     * @return array
     */
    public function coudan(Request $request)
    {
        //验证参数
        $this->validate($request, [            
            'act_id' => 'required|integer'
        ]);

        //验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }
        $info = $this->activityService->coudan($uid, $request->get('act_id'));
        
        return $this->apiReturn($info);

    }
    

    /**
     * 优惠活动 - 活动商品凑单列表
     * @param Request $request
     * @return array
     */
    public function coudanList(Request $request)
    {
        //验证参数
        $this->validate($request, [            
            'page' => 'required|integer',
            'size' => 'required|integer',
            'act_id' => 'required|integer'
        ]);

        $list = $this->activityService->activityGoods($request->get('act_id'),$request->get('page'),$request->get('size'));

        return $this->apiReturn($list);

    }


}
