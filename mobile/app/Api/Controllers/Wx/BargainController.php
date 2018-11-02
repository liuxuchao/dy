<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\BargainService;
use App\Services\AuthService;
use App\Api\Controllers\Controller;


/**
 * Class IndexController
 * @package App\Api\Controllers\Wx
 */
class BargainController extends Controller
{


    /** @var IndexService  */
    private $bargainService;
    private $authService;

    /**
     * IndexController constructor.
     * @param IndexService $bargainService
     * @param AuthService $authService
     */
    public function __construct(BargainService $bargainService, AuthService $authService)
    {
        $this->bargainService = $bargainService;
        $this->authService = $authService;
    }

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        // 获取广告位
        $banner = $this->bargainService->getAdsense('1036');
        $data['banner'] = $banner;

        return $this->apiReturn($data);
    }

    /**
     * 砍价商品列表
     * @param Request $request
     * @return array
     */
    public function bargainList(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|int'
        ]);
        //验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }

        $list = $this->bargainService->bargainGoodsList($request->get('page'), $request->get('per_page'),$uid);

        return $this->apiReturn($list);
    }


    /**
     * 砍价商品详情
     * @param Request $request
     * @return array
     */
    public function goodsDetail(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',   //砍价活动id
            'bs_id' => 'required|integer' //参与砍价id
        ]);

        //验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }
        $list = $this->bargainService->goodsDetail($request->get('id'), $uid,$request->get('bs_id'));

        return $this->apiReturn($list, $list['error']);
    }

    /**
     * 改变属性、数量时重新计算商品价格
     * @param Request $request
     * @return array
     */
    public function property(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',              //砍价活动id
            'num' => 'required|integer',
            'warehouse_id' => 'required|integer',    //仓库id
            'area_id' => 'required|integer'          //地区id
        ]);

        $price = $this->bargainService->goodsPropertiesPrice($request->get('id'), $request->get('attr_id'), $request->get('num'), $request->get('warehouse_id'), $request->get('area_id'));

        return $this->apiReturn($price);
    }

     /**
     * 我要参与
     * @param Request $request
     * @return array
     */
    public function addBargain(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer'      //砍价活动id
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        $result = $this->bargainService->addBargain($request->get('id'), $request->get('attr_id'), $uid);

        return $this->apiReturn($result);

    }


    /**
     * 去砍价
     * @param Request $request
     * @return array
     */
    public function goBargain(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',      //砍价活动id
            'bs_id' => 'required|integer',    //发起活动id
            'form_id' => 'required|string',
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        $result = $this->bargainService->goBargain($request->get('id'),$request->get('bs_id'), $uid,$request->get('form_id'));

        return $this->apiReturn($result);
    }

    /**
     * 砍价购买
     * @param Request $request
     * @return array
     */
    public function Bargainbuy(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'id' => 'required|integer',     //活动id
            'bs_id' => 'required|integer',  //参与活动id
            'num' => 'required|integer',    //数量
            'goods_id' => 'required|integer',
        ]);

        $res = $this->authService->authorization();   //返回用户ID
        if (isset($res['error']) && $res['error'] > 0) {
            return $this->apiReturn($res, 1);
        }

        //验证通过
        $args = array_merge($request->all(), ['uid'=>$res]);
        $result = $this->bargainService->addGoodsToCart($args);

        return $this->apiReturn($result);
    }

    /**
     * 我参与de砍价
     * @param Request $request
     * @return array
     */
    public function myBargain(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|int'
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        //验证通过
        $list = $this->bargainService->myBargain($uid, $request->get('page'), $request->get('per_page'));

        return $this->apiReturn($list);
    }

}
