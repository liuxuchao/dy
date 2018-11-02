<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Wholesale
 */
class Wholesale extends Model
{
    protected $table = 'wholesale';

    protected $primaryKey = 'act_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'goods_id',
        'wholesale_cat_id',
        'goods_name',
        'rank_ids',
        'goods_price',
        'enabled',
        'review_status',
        'review_content',
        'price_model',
        'goods_type',
        'goods_number',
        'moq',
        'is_recommend',
        'is_promote',
        'start_time',
        'end_time',
        'shipping_fee',
        'freight',
        'tid'
    ];

    protected $guarded = [];

    
    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getWholesaleCatId()
    {
        return $this->wholesale_cat_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getRankIds()
    {
        return $this->rank_ids;
    }

    /**
     * @return mixed
     */
    public function getGoodsPrice()
    {
        return $this->goods_price;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getPriceModel()
    {
        return $this->price_model;
    }

    /**
     * @return mixed
     */
    public function getGoodsType()
    {
        return $this->goods_type;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getMoq()
    {
        return $this->moq;
    }

    /**
     * @return mixed
     */
    public function getIsRecommend()
    {
        return $this->is_recommend;
    }

    /**
     * @return mixed
     */
    public function getIsPromote()
    {
        return $this->is_promote;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getShippingFee()
    {
        return $this->shipping_fee;
    }

    /**
     * @return mixed
     */
    public function getFreight()
    {
        return $this->freight;
    }

    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWholesaleCatId($value)
    {
        $this->wholesale_cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRankIds($value)
    {
        $this->rank_ids = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsPrice($value)
    {
        $this->goods_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnabled($value)
    {
        $this->enabled = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPriceModel($value)
    {
        $this->price_model = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsType($value)
    {
        $this->goods_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMoq($value)
    {
        $this->moq = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsRecommend($value)
    {
        $this->is_recommend = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPromote($value)
    {
        $this->is_promote = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingFee($value)
    {
        $this->shipping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFreight($value)
    {
        $this->freight = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
        return $this;
    }
}