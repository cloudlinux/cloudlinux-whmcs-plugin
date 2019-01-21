<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class CLFreeProductRelations extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'CloudLinux_FreeProductRelations';
    /**
     * @var array
     */
    protected $fillable = ['freeProductID', 'licenseProductID'];

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
    public function nonClPackage()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Package', 'freeProductID');
    }
}