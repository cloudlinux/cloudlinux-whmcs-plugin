<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class CLConfigurableOptions extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'CloudLinux_ConfigurableOptionsRelations';
    /**
     * @var array
     */
    protected $fillable = ['id', 'product_id', 'option_group_id', 'option_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Package', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function option()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\ConfigOption', 'option_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function optionGroup()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\ConfigOptionGroup', 'option_group_id');
    }
}