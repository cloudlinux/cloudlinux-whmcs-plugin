<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class CLAddonRelations extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'CloudLinux_AddonRelations';
    /**
     * @var array
     */
    protected $fillable = ['addonID', 'licenseProductID'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Package', 'licenseProductID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function addon()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Addon', 'addonID');
    }
}