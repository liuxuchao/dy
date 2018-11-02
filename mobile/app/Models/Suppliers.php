<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Suppliers
 */
class Suppliers extends Model
{
    protected $table = 'suppliers';

    protected $primaryKey = 'suppliers_id';

    public $timestamps = false;

    protected $fillable = [
        'suppliers_name',
        'suppliers_desc',
        'is_check'
    ];

    protected $guarded = [];

    
    /**
     * @return mixed
     */
    public function getSuppliersName()
    {
        return $this->suppliers_name;
    }

    /**
     * @return mixed
     */
    public function getSuppliersDesc()
    {
        return $this->suppliers_desc;
    }

    /**
     * @return mixed
     */
    public function getIsCheck()
    {
        return $this->is_check;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersName($value)
    {
        $this->suppliers_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersDesc($value)
    {
        $this->suppliers_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCheck($value)
    {
        $this->is_check = $value;
        return $this;
    }
}