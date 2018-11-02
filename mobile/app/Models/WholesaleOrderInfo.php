<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesaleOrderInfo
 */
class WholesaleOrderInfo extends Model
{
    protected $table = 'wholesale_order_info';

    protected $primaryKey = 'order_id';

    public $timestamps = false;

    protected $fillable = [
        'main_order_id',
        'order_sn',
        'user_id',
        'order_status',
        'consignee',
        'country',
        'province',
        'city',
        'district',
        'street',
        'address',
        'mobile',
        'email',
        'postscript',
        'inv_payee',
        'inv_content',
        'order_amount',
        'add_time',
        'extension_code',
        'inv_type',
        'tax',
        'is_delete',
        'invoice_type',
        'vat_id',
        'tax_id',
        'pay_id',
        'pay_status',
        'pay_time',
        'pay_fee'
    ];

    protected $guarded = [];

    
    /**
     * @return mixed
     */
    public function getMainOrderId()
    {
        return $this->main_order_id;
    }

    /**
     * @return mixed
     */
    public function getOrderSn()
    {
        return $this->order_sn;
    }

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
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * @return mixed
     */
    public function getConsignee()
    {
        return $this->consignee;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPostscript()
    {
        return $this->postscript;
    }

    /**
     * @return mixed
     */
    public function getInvPayee()
    {
        return $this->inv_payee;
    }

    /**
     * @return mixed
     */
    public function getInvContent()
    {
        return $this->inv_content;
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
    public function getAddTime()
    {
        return $this->add_time;
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
    public function getInvType()
    {
        return $this->inv_type;
    }

    /**
     * @return mixed
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @return mixed
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * @return mixed
     */
    public function getInvoiceType()
    {
        return $this->invoice_type;
    }

    /**
     * @return mixed
     */
    public function getVatId()
    {
        return $this->vat_id;
    }

    /**
     * @return mixed
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * @return mixed
     */
    public function getPayId()
    {
        return $this->pay_id;
    }

    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->pay_status;
    }

    /**
     * @return mixed
     */
    public function getPayTime()
    {
        return $this->pay_time;
    }

    /**
     * @return mixed
     */
    public function getPayFee()
    {
        return $this->pay_fee;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMainOrderId($value)
    {
        $this->main_order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderSn($value)
    {
        $this->order_sn = $value;
        return $this;
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
    public function setOrderStatus($value)
    {
        $this->order_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsignee($value)
    {
        $this->consignee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCountry($value)
    {
        $this->country = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProvince($value)
    {
        $this->province = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCity($value)
    {
        $this->city = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDistrict($value)
    {
        $this->district = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStreet($value)
    {
        $this->street = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddress($value)
    {
        $this->address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMobile($value)
    {
        $this->mobile = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPostscript($value)
    {
        $this->postscript = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvPayee($value)
    {
        $this->inv_payee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvContent($value)
    {
        $this->inv_content = $value;
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
    public function setAddTime($value)
    {
        $this->add_time = $value;
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
    public function setInvType($value)
    {
        $this->inv_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTax($value)
    {
        $this->tax = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvoiceType($value)
    {
        $this->invoice_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVatId($value)
    {
        $this->vat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxId($value)
    {
        $this->tax_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayId($value)
    {
        $this->pay_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayStatus($value)
    {
        $this->pay_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayTime($value)
    {
        $this->pay_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayFee($value)
    {
        $this->pay_fee = $value;
        return $this;
    }
}