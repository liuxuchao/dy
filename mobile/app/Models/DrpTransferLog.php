<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpTransferLog
 */
class DrpTransferLog extends Model
{
    protected $table = 'drp_transfer_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'money',
        'add_time'
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
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setMoney($value)
    {
        $this->money = $value;
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
}