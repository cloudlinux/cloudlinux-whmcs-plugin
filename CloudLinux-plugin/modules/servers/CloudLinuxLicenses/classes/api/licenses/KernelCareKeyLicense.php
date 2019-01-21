<?php

namespace CloudLinuxLicenses\classes\api\licenses;


class KernelCareKeyLicense extends ClnLicense
{
    public function getApiLicenseId()
    {
        return null;
    }

    public function getName()
    {
        return self::PRODUCT_KERNELCARE . ' - Key based';
    }
}