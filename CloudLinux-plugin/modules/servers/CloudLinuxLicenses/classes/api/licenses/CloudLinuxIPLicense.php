<?php

namespace CloudLinuxLicenses\classes\api\licenses;


class CloudLinuxIPLicense extends ClnLicense
{
    public function getApiLicenseId()
    {
        return ClnLicense::IPBASED_CLOUDLINUX;
    }

    public function getName()
    {
        return self::PRODUCT_CLOUDLINUX . ' - IP based';
    }
}