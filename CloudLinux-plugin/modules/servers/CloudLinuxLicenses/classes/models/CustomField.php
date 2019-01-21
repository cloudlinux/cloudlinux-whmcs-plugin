<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class CustomField extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblcustomfields';
    /**
     * @var array
     */
    protected $fillable = ['type', 'relid', 'fieldname', 'fieldtype', 'adminonly'];
}