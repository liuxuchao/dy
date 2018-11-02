<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\CartService;
use App\Api\Controllers\Controller;

/**
 * Class CartController
 * @package App\Api\Controllers\Wx
 */
class CartController extends Controller
{
    private $cartService;
    private $authService;

    /**
     * Cart constructor.
     * @param CartService $cartService
     * @param AuthService $authService
     */
    public function __construct(CartService $cartService, AuthService $authService)
    {
        $this->cartService = $cartService;
        $this->authService = $authService;
    }

    /**
     * 获取购物车页面信息（购物车列表）
     * @param Request $request
     * @return array
     */
    public function cart(Request $request)
    {
        //验证数据
        $this->validate($request, []);

        $cart = $this->cartService->getCart();

        return $this->apiReturn($cart);
    }

    /**
     * 添加商品到购物车
     * @param Request $request
     * @return array
     */
    public function addGoodsToCart(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'id' => 'required|integer',
            'num' => 'required|integer',
        ]);

        $res = $this->authService->authorization();   //返回用户ID
        if (isset($res['error']) && $res['error'] > 0) {
            return $this->apiReturn($res, 1);
        }

        //验证通过
        $args = array_merge($request->all(), ['uid'=>$res]);
        $result = $this->cartService->addGoodsToCart($args);

        return $this->apiReturn($result);
    }

     /**
     * 添加优惠活动（赠品）到购物车
     * @param Request $request
     * @return array
     */
    public function addGiftCart(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'act_id' => 'required|integer',
            'ru_id' => 'required|integer',
            //'select_gift' => 'required|integer', //选中赠品id 
        ]);

        $res = $this->authService->authorization();   //返回用户ID
        if (isset($res['error']) && $res['error'] > 0) {
            return $this->apiReturn($res, 1);
        }

        //验证通过
        $args = array_merge($request->all(), ['uid'=>$res]);
        //dump($args);exit;
        $result = $this->cartService->addGiftCart($args);
       

        return $this->apiReturn($result);
    }



    /**
     * 更新购物车商品
     * @param Request $request
     * @return mixed
     */
    public function updateCartGoods(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'id' => 'required|integer',
            'amount' => 'required|integer'
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }
        $args = $request->all();
        $args['uid'] = $uid;

        return $this->cartService->updateCartGoods($args);
    }

    /**
     * 删除商品
     * @param Request $request
     * @return array
     */
    public function deleteCartGoods(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        $uid = $this->authService->authorization();   //返回用户ID
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        $args = $request->all();
        $args['uid'] = $uid;

        //删除商品
        $res = $this->cartService->deleteCartGoods($args);

        return $res;
    }
}
