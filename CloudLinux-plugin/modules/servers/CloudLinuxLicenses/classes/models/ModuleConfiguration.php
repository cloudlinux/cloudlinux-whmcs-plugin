<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class ModuleConfiguration extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblmodule_configuration';

    /**
     * @param $query
     * @param int $key
     * @return mixed
     */
    public function scopeOfKey($query, $key)
    {
        return $query->where('setting_name', 'configoption' . $key);
    }
}