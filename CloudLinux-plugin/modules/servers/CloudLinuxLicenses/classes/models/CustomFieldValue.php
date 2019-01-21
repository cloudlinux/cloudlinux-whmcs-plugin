<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class CustomFieldValue extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tblcustomfieldsvalues';
    /**
     * @var array
     */
    protected $fillable = ['fieldid', 'relid', 'value'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customField()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\CustomField', 'fieldid');
    }
}