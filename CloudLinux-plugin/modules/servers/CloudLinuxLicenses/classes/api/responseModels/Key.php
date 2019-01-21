<?php


namespace CloudLinuxLicenses\classes\api\responseModels;


use CloudLinuxLicenses\classes\models\AbstractPackage;

class Key extends AbstractResponseModel
{
    public function status()
    {
        if (!isset($this->enabled)) {
            return '';
        }

        return $this->enabled ? 'enabled' : 'disabled';
    }

    public function createdDate()
    {
        return $this->created ? $this->toDate($this->created) : '-';
    }

    public function getType(AbstractPackage $package)
    {
        return $package->getApi()->getClnLicense()->getName();
    }
}
