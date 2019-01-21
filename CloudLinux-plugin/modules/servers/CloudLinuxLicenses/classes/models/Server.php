<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class Server extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblservers';
}