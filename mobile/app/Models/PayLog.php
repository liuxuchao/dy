<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PayLog
 */
class PayLog extends Model
{
    protected $table = 'pay_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_amount',
        'order_type',
        'is_paid',
        'openid',
        'transid'
    ];

    protected $guarded = [];

    
    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getOrderAmount()
    {
        return $this->order_amount;
    }

    /**
     * @return mixed
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * @return mixed
     */
    public function getIsPaid()
    {
        return $this->is_paid;
    }

    /**
     * @return mixed
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * @return mixed
     */
    public function getTransid()
    {
        return $this->transid;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderAmount($value)
    {
        $this->order_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderType($value)
    {
        $this->order_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPaid($value)
    {
        $this->is_paid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpenid($value)
    {
        $this->openid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTransid($value)
    {
        $this->transid = $value;
        return $this;
    }
}