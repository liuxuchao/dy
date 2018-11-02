<?php

namespace App\Modules\Flow\Controllers;

use App\Modules\Flow\Controllers\IndexController as FrontendController;

class AjaxController extends FrontendController
{
    /**
     * 构造，加载文件语言包和helper文件
     */
    public function __construct()
    {
        parent::__construct();

        $this->assign('area_id', $this->area_info['region_id']);
        $this->assign('warehouse_id', $this->region_id);
        $this->assign('area_city', $this->area_city);
    }


    /**
     * 不支持配送结算 （购物流程下单一个商品时）
     * @return
     */
    public function actionShippingPrompt()
    {
        if (IS_AJAX) {
            $shipping_prompt = input('shipping_prompt', '', ['html_in', 'trim']); //不支持配送商品 购物车记录id

            if ($shipping_prompt) {
                /* 取得购物类型 */
                $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
                $flow_type = ($_SESSION['flow_type'] == CART_ONESTEP_GOODS) ? CART_ONESTEP_GOODS : $flow_type;

                $cart_value = get_sc_str_replace($_SESSION['cart_value'], $shipping_prompt, 1);

                /* 对商品信息赋值 */
                $cart_goods_list = cart_goods($flow_type, $shipping_prompt, 1, $this->region_id, $this->area_id, $this->area_city); // 取得商品列表，计算合计
                $cart_goods_list_new = cart_by_favourable($cart_goods_list);

                // 将不支持配送购物车商品 is_checked = 0
                dao('cart')->data(['is_checked' => 0])->where(['rec_id'=> ['in', $shipping_prompt]])->save();

                $GLOBALS['smarty']->assign('goods_list', $cart_goods_list_new);

                $result['error'] = 1;
                $result['cart_value'] = $cart_value;
                $result['cart_content'] = $this->fetch('goods_shipping_prompt');

                exit(json_encode($result));
            }
        }
    }


}