<?php

namespace App\Repositories\Order;

use App\Models\OrderGoods;
use App\Models\Goods;
use App\Models\OrderCloud;

class OrderGoodsRepository
{

    /**
     * 添加订单对应商品
     * @param $goods
     * @param $orderId
     */
    public function insertOrderGoods($goods, $orderId = 0)
    {
        foreach ($goods as $v) {
            if (empty($orderId)) {
                $newOrderId = $v['order_id'];
            } else {
                $newOrderId = $orderId;
            }

            $is_distribution = isset($v['is_distribution']) ? $v['is_distribution'] : 0;
            $dis_commission = isset($v['dis_commission']) ? $v['dis_commission'] : 0;
            $orderGoods                 = new OrderGoods;
            $orderGoods->order_id       = $newOrderId;
            $orderGoods->goods_id       = $v['goods_id'];
            $orderGoods->goods_name     = $v['goods_name'];
            $orderGoods->goods_sn       = $v['goods_sn'];
            $orderGoods->product_id     = $v['product_id'];
            $orderGoods->goods_number   = $v['goods_number'];
            $orderGoods->market_price   = $v['market_price'];
            $orderGoods->goods_price    = $v['goods_price'];
            $orderGoods->goods_attr     = $v['goods_attr'];
            $orderGoods->is_real        = $v['is_real'];
            $orderGoods->extension_code = $v['extension_code'];
            $orderGoods->parent_id      = $v['parent_id'];
            $orderGoods->is_gift        = $v['is_gift'];
            $orderGoods->ru_id          = $v['ru_id'];
            $orderGoods->goods_attr_id  = $v['goods_attr_id'];
            $orderGoods->is_distribution  = $is_distribution;
            $orderGoods->drp_money  = $dis_commission * $v['goods_price'] * $v['goods_number'] * $is_distribution / 100;
            $orderGoods->save();
        }
    }

    /**
     * 根据 商品ID  订单ID  查找  orderGoods记录
     * @param $oid
     * @param $gid
     * @return mixed
     */
    public function orderGoodsByOidGid($oid, $gid)
    {
        $model = OrderGoods::where('order_id', $oid)
            ->where('goods_id', $gid)
            ->first();

        if ($model === null) {
            return [];
        }
        return $model->toArray();
    }


    /**
     * 根据 订单ID 贡云的商品ID 下单会员ID 查找  orderGoods商品订单id
     * @param $oid
     * @param $gid
     * @param $uid
     * @return mixed
     */
    public function orderGoodsRecId($oid, $gid)
    {
        $goods = OrderGoods::from('order_goods as og')
            ->select('og.rec_id')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'og.goods_id')
            ->where('og.order_id', $oid)
            ->where('g.cloud_id', $gid)
            ->first();

        if ($goods === null) {
            return [];
        }
        return $goods->toArray();
    }


    /**
     * 添加贡云订单对应商品
     * @param $oid
     * @param $gid
     * @return mixed
     */
    public function insertOrderCloud($cloud_order)
    {
        $orderCloud = new OrderCloud();

        foreach ($cloud_order as $k => $v) {
            $orderCloud->$k = $v;
        }
        $res = $orderCloud->save();

        if ($res) {
            return $orderCloud->id;
        }
        return false;
    }

    /**
     * 根据 订单ID 贡云的商品ID 下单会员ID 查找  orderGoods商品订单id
     * @param $oid
     * @param $gid
     * @param $uid
     * @return mixed
     */
    public function orderCloudInfo($oid)
    {
        $goods = OrderCloud::from('order_cloud as oc')
            ->select('oc.parentordersn','og.goods_number','og.goods_price')
            ->leftjoin('order_goods as og', 'og.rec_id', '=', 'oc.rec_id')
            ->where('og.order_id', $oid)
            ->first();

        if ($goods === null) {
            return [];
        }
        return $goods->toArray();
    }




}
