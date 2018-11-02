<?php

namespace App\Api\Controllers\Wx;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\DrpService;
use App\Api\Controllers\Controller;

/**
 * Class DrpController
 * @package App\Api\Controllers\Wx
 */
class DrpController extends Controller
{
    private $drpService;
    private $authService;

    /**
     * Drp constructor.
     * @param DrpService $store
     */
    public function __construct(DrpService $drpService, AuthService $authService)
    {
        $this->drpService = $drpService;
        $this->authService = $authService;
    }

    /**
     * 店铺首页
     * @return mixed
     */
    public function index(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->index($uid);
    }

    /**
     * 购买分销模式
     * @return mixed
     */
    public function con(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->con($uid);
    }

    /**
     * 申请页面
     * @return mixed
     */
    public function purchase(Request $request)
    {
        $this->validate($request, [
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->purchase($uid);
    }

    /**
     * 购买结算
     * @return mixed
     */
    public function PurchasePay(Request $request)
    {
        $this->validate($request, [
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->PurchasePay($uid);
    }

    /**
     * 开店信息
     * @return mixed
     */
    public function register(Request $request)
    {

        $this->validate($request, [
            'shopname' => 'required|string',
            'realname' => 'required|string',
            'mobile' => 'required|string'
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->drpRegister($uid, $request->input('shopname'), $request->input('realname'), $request->input('mobile'), $request->input('qq'));
    }

    /**
     * 开店信息完成
     * @return mixed
     */
    public function regend(Request $request)
    {

        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->regEnd($uid);
    }

    /**
     * 分享二维码
     * @return mixed
     */
    public function usercard(Request $request)
    {
        $this->validate($request, [
            'path' => 'required|string',
            'uid' => 'required|integer',
        ]);

        return $this->drpService->userCard($request->get('uid'),$request->get('path'));
    }

    /**
     * 我的团队
     * @return mixed
     */
    public function team(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required|integer',  //下线会员ID
        ]);

        return $this->drpService->team($request->get('uid'));
    }

    /**
     * 团队详情
     * @return mixed
     */
    public function teamdetail(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required|integer',  //下线会员ID
        ]);

        return $this->drpService->teamdetail($request->get('uid'));
    }

    /**
     * 下线会员
     * @return mixed
     */
    public function OfflineUser(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required|integer',  //会员ID
            ]);

        return $this->drpService->OfflineUser($request->get('uid'));
    }

    /**
     * 会员排行
     * @return mixed
     */
    public function ranklist(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->ranklist($uid);
    }


    /**
     * 购买分销商显示信息
     * @return mixed
     */
    public function buymsg(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->purchase($uid);
    }

    /**
     * 查看店铺
     * @return mixed
     */
    public function shop(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required|integer',
        ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->shop($request->get('uid'));
    }

    /**
     * 店铺商品显示
     * @return mixed
     */
    public function shopgoods(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',  //店铺ID
            'uid' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer',  //1全部  2上新  3促销
            'type' => 'required|integer',
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->shopgoods($request->get('uid'), $request->get('id'), $request->get('page'), $request->get('size'), $request->get('status'), $request->get('type'));
    }

    /**
     * 店铺订单
     * @return mixed
     */
    public function order(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer'
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->order($uid, $request->get('page'), $request->get('size'), $request->get('status'));
    }

    /**
     * 店铺订单
     * @return mixed
     */
    public function orderdetail(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|integer'
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->orderdetail($uid, $request->get('order_id'));
    }

    /**
     * 店铺设置
     * @return mixed
     */
    public function settings(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }
        $args = $request->all();
        $args['uid'] = $uid;
        return $this->drpService->settings($args);
    }

    /**
     * 分类
     * @return mixed
     */
    public function category(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }
        $args = $request->all();
        $args['uid'] = $uid;
        return $this->drpService->category($args);
    }


    /**
     * 添加
     * @return mixed
     */
    public function add(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'type' => 'required|integer'
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->add($uid, $request->get('id'), $request->get('type'));
    }

    /**
     * 我的代言
     * @return mixed
     */
    public function showgoods(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'type' => 'required|integer'
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->showgoods($uid, $request->get('page'), $request->get('size'), $request->get('type'));
    }

    /**
     * 佣金明细
     * @return mixed
     */
    public function drplog(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer'// 全部2  为分成0  已分成1
            ]);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->drplog($uid, $request->get('page'), $request->get('size'), $request->get('status'));
    }

    /**
     * 文章
     * @return mixed
     */
    public function news(Request $request)
    {
        $this->validate($request, []);

        $uid = $this->authService->authorization();   //返回用户ID

        //验证参数
        if (isset($uid['error']) && $uid['error'] > 0) {
            return $this->apiReturn($uid, 1);
        }

        return $this->drpService->news($uid);
    }

}
