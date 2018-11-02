<?php

/**
 * DSC 订单接口列表
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zhuo $
 * $Id: order.php zhuo $
 */

$order_action = array(
    'dsc.order.list.get',                           //获取订单列表
    'dsc.order.info.get',                           //获取单条订单信息
    'dsc.order.insert.post',                        //插入订单信息
    'dsc.order.update.post',                        //更新订单信息
    'dsc.order.del.get',                           //删除订单信息
    'dsc.order.confirmorder.post',                  //订单发货
    
    'dsc.order.goods.list.get',                     //获取订单商品列表
    'dsc.order.goods.info.get',                     //获取单条订单商品信息
    'dsc.order.goods.insert.post',                  //插入订单商品信息
    'dsc.order.goods.update.post',                  //更新订单商品信息
    'dsc.order.goods.del.get',                     //删除订单商品信息
);
