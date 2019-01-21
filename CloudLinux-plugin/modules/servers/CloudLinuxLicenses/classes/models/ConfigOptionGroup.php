<?php

namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class ConfigOptionGroup extends Model
{
    const TYPE_DROPDOWN = 1;
    const TYPE_RADIO = 2;
    const TYPE_CHECKBOX = 3;
    const TYPE_QUANTITY = 4;

    /**
     * @var string
     */
    protected $table = 'tblproductconfigoptions';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeNotHidden($query)
    {
        return $query->where('hidden', 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\ConfigOption', 'configid');
    }

    /**
     * @param $value
     * @return int
     */
    public function getOptiontypeAttribute($value)
    {
        return (int) $value;
    }

    /**
     * @return bool
     */
    public function isTypeDropdown() {
        return $this->optiontype === self::TYPE_DROPDOWN;
    }

    /**
     * @return bool
     */
    public function isTypeRadio() {
        return $this->optiontype === self::TYPE_RADIO;
    }

    /**
     * @return bool
     */
    public function isTypeCheckbox() {
        return $this->optiontype === self::TYPE_CHECKBOX;
    }

    /**
     * @return bool
     */
    public function isTypeQuantity() {
        return $this->optiontype === self::TYPE_QUANTITY;
    }
}