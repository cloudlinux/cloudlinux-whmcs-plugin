<?php


namespace CloudLinuxLicenses\classes\models;

use Illuminate\Database\Eloquent\Model;


class Client extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblclients';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\Service', 'userid');
    }
}