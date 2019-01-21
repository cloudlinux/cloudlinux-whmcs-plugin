<?php


namespace CloudLinuxLicenses\classes\models;

use CloudLinuxLicenses\classes\components\WHMCS;
use Illuminate\Database\Eloquent\Model;


class CLConnections extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'CloudLinux_Connections';
    /**
     * @var array
     */
    protected $fillable = ['order_id', 'hosting_id', 'user_id', 'product_id', 'relation_id', 'hostingb_id'];

    /**
     *
     */
    public static function boot() {
        parent::boot();

        static::deleted(function ($model) {
            $service = Service::find($model->hostingb_id);
            if ($service && $service->package->isCL()) {
                WHMCS::model()->moduleTerminate($service->id);
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentService()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Service', 'hosting_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function childService()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Service', 'hostingb_id');
    }
}