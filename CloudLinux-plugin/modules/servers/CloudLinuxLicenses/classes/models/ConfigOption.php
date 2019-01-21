<?php

namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class ConfigOption extends Model
{
    /**
     * @var ServiceConfigOption
     */
    public $serviceOption;

    /**
     * @var string
     */
    protected $table = 'tblproductconfigoptionssub';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeNotHidden($query)
    {
        return $query->where('hidden', 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\ConfigOptionGroup', 'configid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function clConfigurableOption()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\CLConfigurableOptions','option_id');
    }

}