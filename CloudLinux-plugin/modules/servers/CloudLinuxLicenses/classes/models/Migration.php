<?php


namespace CloudLinuxLicenses\classes\models;


use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

class Migration extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'CloudLinux_migrations';
    /**
     * @var array
     */
    protected $fillable = ['version'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('version');
            $table->timestamp('timestamp')->default(Capsule::connection()->raw('CURRENT_TIMESTAMP'));

            $table->primary('version');
        };
    }
}