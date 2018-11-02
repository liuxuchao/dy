<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchAd
 */
class TouchAd extends Model
{
    protected $table = 'touch_ad';

    protected $primaryKey = 'ad_id';

    public $timestamps = false;

    protected $fillable = [
        'position_id',
        'media_type',
        'ad_name',
        'ad_link',
        'link_color',
        'ad_code',
        'start_time',
        'end_time',
        'link_man',
        'link_email',
        'link_phone',
        'click_count',
        'enabled',
        'is_new',
        'is_hot',
        'is_best',
        'public_ruid',
        'ad_type',
        'goods_name'
    ];

    protected $guarded = [];

    public function position()
    {
        return $this->belongsTo('App\Models\TouchAdPosition', 'position_id', 'position_id');
    }

    /**
     * @return mixed
     */
    public function getPositionId()
    {
        return $this->position_id;
    }

    /**
     * @return mixed
     */
    public function getMediaType()
    {
        return $this->media_type;
    }

    /**
     * @return mixed
     */
    public function getAdName()
    {
        return $this->ad_name;
    }

    /**
     * @return mixed
     */
    public function getAdLink()
    {
        return $this->ad_link;
    }

    /**
     * @return mixed
     */
    public function getLinkColor()
    {
        return $this->link_color;
    }

    /**
     * @return mixed
     */
    public function getAdCode()
    {
        return $this->ad_code;
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
    public function getLinkMan()
    {
        return $this->link_man;
    }

    /**
     * @return mixed
     */
    public function getLinkEmail()
    {
        return $this->link_email;
    }

    /**
     * @return mixed
     */
    public function getLinkPhone()
    {
        return $this->link_phone;
    }

    /**
     * @return mixed
     */
    public function getClickCount()
    {
        return $this->click_count;
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
    public function getIsNew()
    {
        return $this->is_new;
    }

    /**
     * @return mixed
     */
    public function getIsHot()
    {
        return $this->is_hot;
    }

    /**
     * @return mixed
     */
    public function getIsBest()
    {
        return $this->is_best;
    }

    /**
     * @return mixed
     */
    public function getPublicRuid()
    {
        return $this->public_ruid;
    }

    /**
     * @return mixed
     */
    public function getAdType()
    {
        return $this->ad_type;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPositionId($value)
    {
        $this->position_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMediaType($value)
    {
        $this->media_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdName($value)
    {
        $this->ad_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdLink($value)
    {
        $this->ad_link = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkColor($value)
    {
        $this->link_color = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdCode($value)
    {
        $this->ad_code = $value;
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
    public function setLinkMan($value)
    {
        $this->link_man = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkEmail($value)
    {
        $this->link_email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkPhone($value)
    {
        $this->link_phone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setClickCount($value)
    {
        $this->click_count = $value;
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
    public function setIsNew($value)
    {
        $this->is_new = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsHot($value)
    {
        $this->is_hot = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsBest($value)
    {
        $this->is_best = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPublicRuid($value)
    {
        $this->public_ruid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdType($value)
    {
        $this->ad_type = $value;
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
}