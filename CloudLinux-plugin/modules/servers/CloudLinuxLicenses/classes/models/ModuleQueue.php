<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class ModuleQueue extends Model
{
    const ACTION_CREATE_LICENSE = 'Module create license';
    const ACTION_TERMINATE_LICENSE = 'Module remove license';

    /**
     * @var string
     */
    protected $table = 'tblmodulequeue';
    /**
     * @var array
     */
    protected $fillable = [
        'service_type', 'service_id', 'module_name',
        'module_action', 'last_attempt', 'last_attempt_error',
        'num_retries', 'completed',
    ];

    protected $attributes = [
        'service_type' => 'service',
        'module_name' => 'CloudLinuxLicenses',
        'module_action' => 'Module provision error',
    ];

    protected $dates = [
        'last_attempt',
    ];

    /**
     * @param int $serviceId
     * @param string $error
     * @param string $action
     */
    public static function log($serviceId, $error, $action) {
        $date = new \DateTime();
        $model = self::firstOrCreate([
            'service_id' => $serviceId,
            'last_attempt_error' => $error,
            'module_action' => $action,
        ]);
        $model->update([
            'num_retries' => ++$model->num_retries,
            'last_attempt' => $date->getTimestamp(),
        ]);
    }
}