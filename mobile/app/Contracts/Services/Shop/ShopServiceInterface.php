<?php

namespace App\Contracts\Services\Shop;

/**
 * Interface ShopServiceInterface
 * @package App\Contracts\Services\Shop
 */
interface ShopServiceInterface
{

    /**
     * 创建店铺
     *
     * @return mixed
     */
    public function create();

    /**
     * 获取店铺基础信息
     * @param $id 店铺id
     * @return mixed
     */
    public function get($id);

    /**
     * 更新店铺信息
     *
     * @return mixed
     */
    public function update();

    /**
     * 获取店铺状态信息
     *
     * @return mixed
     */
    public function getStatus();

    /**
     * 店铺列表
     * @param int $category 主营类别
     * @param null $region 地区
     * @param null $coordinate 坐标
     * @param null $sort 排序
     * @param null $order
     * @param int $page 分页
     * @param int $size 分页大小
     * @return mixed
     * // $category = 0, $region = null, $coordinate = null, $sort = null, $order = null, $page = 1, $size = 10
     */
    public function search($attributes);

    /**
     * 店铺地址库新建一个地址
     *
     * @return mixed
     */
    public function createAddress();

    /**
     * 店铺地址库获取一个地址
     *
     * @return mixed
     */
    public function getAddress();

    /**
     * 店铺地址库更新一个地址
     *
     * @return mixed
     */
    public function updateAddress();

    /**
     * 店铺地址库删除一个地址
     *
     * @return mixed
     */
    public function deleteAddress();

    /**
     * 店铺地址库获取所有地址
     *
     * @return mixed
     */
    public function searchAddress();

    /**
     * 创建门店网点
     *
     * @return mixed
     */
    public function createStore();

    /**
     * 获取一个门店网点详情
     *
     * @return mixed
     */
    public function getStore();

    /**
     * 更新一个门店网点详情
     *
     * @return mixed
     */
    public function updateStore();

    /**
     * 删除一个门店网点详情
     *
     * @return mixed
     */
    public function deleteStore();

    /**
     * 获取门店网点列表
     *
     * @return mixed
     */
    public function searchStore();

    /**
     * 获取门店网点设置
     *
     * @return mixed
     */
    public function storeSetting();

    /**
     * 获取网点商品
     *
     * @return mixed
     */
    public function getStoreGoods();

    /**
     * 更新网点商品
     *
     * @return mixed
     */
    public function updateStoreGoods();

    /**
     * 图片上传接口
     *
     * @return mixed
     */
    public function uploadMaterials();
}