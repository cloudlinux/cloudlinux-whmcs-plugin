<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class Admin extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tbladmins';

    /**
     * @return $this
     */
    public static function getAdmin()
    {
        return self::where('roleid', 1)->first();
    }
}