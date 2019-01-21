<?php

namespace CloudLinuxLicenses\classes\models;


use Illuminate\Database\Eloquent\Model;


class ServiceConfigOption extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblhostingconfigoptions';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function clConfigurableOption()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\CLConfigurableOptions',
            'option_id', 'optionid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function configurableOption()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\ConfigOption',
            'optionid','id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\ConfigOptionGroup',
            'configid','id');
    }

    /**
     * @param mixed $value
     * @return int
     */
    public function getQtyAttribute($value)
    {
        return (int) $value;
    }

    /**
     * @return bool
     */
    public function isRelated() {
        return ($this->qty > 0 || $this->group->isTypeDropdown() || $this->group->isTypeRadio())
            && $this->clConfigurableOption->count();
    }
}