<?php

namespace CloudLinuxLicenses\classes\api\licenses;


class KernelCareIPLicense extends ClnLicense
{
    public function getApiLicenseId()
    {
        return ClnLicense::IPBASED_KERNELCARE;
    }

    public function getName()
    {
        return self::PRODUCT_KERNELCARE . ' - IP based';
    }
}