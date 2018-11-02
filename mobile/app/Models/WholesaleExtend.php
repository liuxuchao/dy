<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesaleExtend
 */
class WholesaleExtend extends Model
{
    protected $table = 'wholesale_extend';

    protected $primaryKey = 'extend_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'is_delivery',
        'is_return',
        'is_free'
    ];

    protected $guarded = [];

    
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
    public function getIsDelivery()
    {
        return $this->is_delivery;
    }

    /**
     * @return mixed
     */
    public function getIsReturn()
    {
        return $this->is_return;
    }

    /**
     * @return mixed
     */
    public function getIsFree()
    {
        return $this->is_free;
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
    public function setIsDelivery($value)
    {
        $this->is_delivery = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReturn($value)
    {
        $this->is_return = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFree($value)
    {
        $this->is_free = $value;
        return $this;
    }
}