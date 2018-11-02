<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CartCombo
 */
class CartCombo extends Model
{
    protected $table = 'cart_combo';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'goods_id',
        'goods_sn',
        'product_id',
        'group_id',
        'goods_name',
        'market_price',
        'goods_price',
        'goods_number',
        'goods_attr',
        'img_flie',
        'is_real',
        'extension_code',
        'parent_id',
        'rec_type',
        'is_gift',
        'is_shipping',
        'can_handsel',
        'goods_attr_id',
        'warehouse_id',
        'area_id',
        'model_attr',
        'add_time',
        'commission_rate'
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
    public function getSessionId()
    {
        return $this->session_id;
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
    public function getGoodsSn()
    {
        return $this->goods_sn;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->group_id;
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
    public function getMarketPrice()
    {
        return $this->market_price;
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
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttr()
    {
        return $this->goods_attr;
    }

    /**
     * @return mixed
     */
    public function getImgFlie()
    {
        return $this->img_flie;
    }

    /**
     * @return mixed
     */
    public function getIsReal()
    {
        return $this->is_real;
    }

    /**
     * @return mixed
     */
    public function getExtensionCode()
    {
        return $this->extension_code;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getRecType()
    {
        return $this->rec_type;
    }

    /**
     * @return mixed
     */
    public function getIsGift()
    {
        return $this->is_gift;
    }

    /**
     * @return mixed
     */
    public function getIsShipping()
    {
        return $this->is_shipping;
    }

    /**
     * @return mixed
     */
    public function getCanHandsel()
    {
        return $this->can_handsel;
    }

    /**
     * @return mixed
     */
    public function getGoodsAttrId()
    {
        return $this->goods_attr_id;
    }

    /**
     * @return mixed
     */
    public function getWarehouseId()
    {
        return $this->warehouse_id;
    }

    /**
     * @return mixed
     */
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * @return mixed
     */
    public function getModelAttr()
    {
        return $this->model_attr;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getCommissionRate()
    {
        return $this->commission_rate;
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
    public function setSessionId($value)
    {
        $this->session_id = $value;
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
    public function setGoodsSn($value)
    {
        $this->goods_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductId($value)
    {
        $this->product_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGroupId($value)
    {
        $this->group_id = $value;
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
    public function setMarketPrice($value)
    {
        $this->market_price = $value;
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
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttr($value)
    {
        $this->goods_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgFlie($value)
    {
        $this->img_flie = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReal($value)
    {
        $this->is_real = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExtensionCode($value)
    {
        $this->extension_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecType($value)
    {
        $this->rec_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsGift($value)
    {
        $this->is_gift = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShipping($value)
    {
        $this->is_shipping = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCanHandsel($value)
    {
        $this->can_handsel = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsAttrId($value)
    {
        $this->goods_attr_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWarehouseId($value)
    {
        $this->warehouse_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAreaId($value)
    {
        $this->area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setModelAttr($value)
    {
        $this->model_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommissionRate($value)
    {
        $this->commission_rate = $value;
        return $this;
    }
}