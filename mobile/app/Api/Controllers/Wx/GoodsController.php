<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\GoodsService;
use App\Api\Controllers\Controller;

/**
 * Class GoodsController
 * @package App\Api\Controllers\Wx
 */
class GoodsController extends Controller
{
    private $goodsService;
    private $authService;

    public function __construct(GoodsService $goodsService, AuthService $authService)
    {
        $this->goodsService = $goodsService;
        $this->authService = $authService;
    }

    /**
     * 商品列表
     * @param Request $request
     * @return array
     */
    public function goodsList(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|int',
            'warehouse_id' => 'required|integer',    //仓库id
            'area_id' => 'required|integer'          //地区id
        ]);

        //验证通过
        $list = $this->goodsService->getGoodsList($request->get('id'), $request->get('keyword'), $request->get('page'), $request->get('per_page'), $request->get('sort_key'), $request->get('sort_value'), $request->get('warehouse_id'), $request->get('area_id'), $request->get('proprietary'), $request->get('price_min'), $request->get('price_max'), $request->get('brand'), $request->get('province_id'), $request->get('city_id'), $request->get('county_id'), $request->get('fil_key'));

        return $this->apiReturn($list);
    }

    /**
     * 商品筛选条件
     * @param Request $request0
     * @return array
     */
    public function goodsFilterCondition(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'cat_id' => 'required|integer'
        ]);

        //验证通过
        $list = $this->goodsService->getGoodsFilterCondition($request->get('cat_id'));

        return $this->apiReturn($list);
    }

    /**
     * 商品详情
     * @param Request $request
     * @return array
     */
    public function goodsDetail(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        //验证通过  @param  商品ID
        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            $uid = 0;
        }

        $list = $this->goodsService->goodsDetail($request->get('id'), $uid);

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
            'id' => 'required|integer',
//            'attr_id' => 'required',
            'num' => 'required|integer',
            'warehouse_id' => 'required|integer',    //仓库id
            'area_id' => 'required|integer'          //地区id
        ]);

        $price = $this->goodsService->goodsPropertiesPrice($request->get('id'), $request->get('attr_id'), $request->get('num'), $request->get('warehouse_id'), $request->get('area_id'));

        return $this->apiReturn($price);
    }

    /**
     * 分享商品
     * @param Request $request
     * @return array
     */
    public function Share(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
            'path' => 'required|string'
        ]);

        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        $share = $this->goodsService->goodsShare($request->get('id'), $uid, $request->get('path'));

        return $this->apiReturn($share);
    }

    /**
     * 优惠券
     * @param Request $request
     * @return array
     */
    public function Coupons(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'cou_id' => 'required|integer'
        ]);

        $uid = $this->authService->authorization();
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        $result = $this->goodsService->getCoupon($request->get('cou_id'), $uid);

        return $this->apiReturn($result);
    }


    /**
     * 历史商品
     * @param Request $request
     * @return array
     */
    public function history(Request $request)
    {
        //验证参数
        $this->validate($request, [
        ]);

        $result = $this->goodsService->history($request->get('list'), $request->get('page'), $request->get('size'));

        return $this->apiReturn($result);
    }

    /**
     * 保存商品记录
     * @param Request $request
     * @return array
     */
    public function goodssave(Request $request)
    {
        //验证参数
        $this->validate($request, [
        ]);

        $result = $this->goodsService->goodsSave($request->get('list'), $request->get('goodsId'));

        return $this->apiReturn($result);
    }


}
