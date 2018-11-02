<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CollectGoods
 */
class CollectGoods extends Model
{
    protected $table = 'collect_goods';

    protected $primaryKey = 'rec_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'goods_id',
        'add_time',
        'is_attention'
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
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getIsAttention()
    {
        return $this->is_attention;
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
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsAttention($value)
    {
        $this->is_attention = $value;
        return $this;
    }
}