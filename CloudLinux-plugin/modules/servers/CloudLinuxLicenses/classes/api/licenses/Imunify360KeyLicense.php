<?php

namespace CloudLinuxLicenses\classes\api\licenses;


class Imunify360KeyLicense extends ClnLicense
{
    /**
     * @return int|string
     * @throws \ErrorException
     */
    public function getApiLicenseId()
    {
        $licenses = $this->getLicenseRelations();
        if (!isset($licenses[$this->type])) {
            throw new \ErrorException(sprintf('Unable to get license ID for type: %s', $this->type));
        }

        return $licenses[$this->type];
    }

    /**
     * @return string
     * @throws \ErrorException
     */
    public function getName()
    {
        return self::PRODUCT_IMUNIFY360 . ' - Key based (' . $this->getLicenseName()[$this->getApiLicenseId()]. ')';
    }

    /**
     * @return array
     */
    private function getLicenseRelations() {
        return array(
            self::IMUNIFY360_SINGLE => self::KEYBASED_IMUNIFY360_SINGLE,
            self::IMUNIFY360_30 => self::KEYBASED_IMUNIFY360_30,
            self::IMUNIFY360_250 => self::KEYBASED_IMUNIFY360_250,
            self::IMUNIFY360_UNLIMITED => self::KEYBASED_IMUNIFY360_UNLIMITED,
            self::IMUNIFY360_CLEAN => self::KEYBASED_IMUNIFY360_CLEAN,
        );
    }

    /**
     * @return array
     */
    private function getLicenseName() {
        return array(
            self::KEYBASED_IMUNIFY360_SINGLE => 'Single user per server',
            self::KEYBASED_IMUNIFY360_30 => 'Up to 30 users per server',
            self::KEYBASED_IMUNIFY360_250 => 'Up to 250 users per server',
            self::KEYBASED_IMUNIFY360_UNLIMITED => 'Unlimited users per server',
            self::KEYBASED_IMUNIFY360_CLEAN => 'Imunify360 Clean',
        );
    }
}