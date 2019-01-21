<?php

namespace CloudLinuxLicenses\classes\api\licenses;


abstract class ClnLicense
{
    const PRODUCT_CLOUDLINUX = 'CloudLinux';
    const PRODUCT_KERNELCARE = 'KernelCare';
    const PRODUCT_IMUNIFY360 = 'Imunify360';

    const IMUNIFY360_UNLIMITED = 0;
    const IMUNIFY360_SINGLE = 1;
    const IMUNIFY360_30 = 2;
    const IMUNIFY360_250 = 3;
    const IMUNIFY360_CLEAN = 4;

    const IPBASED_CLOUDLINUX = 1;
    const IPBASED_KERNELCARE = 16;
    const IPBASED_IMUNIFY360_CLEAN = 40;
    const IPBASED_IMUNIFY360_SINGLE = 41;
    const IPBASED_IMUNIFY360_30 = 42;
    const IPBASED_IMUNIFY360_250 = 43;
    const IPBASED_IMUNIFY360_UNLIMITED = 49;

    const KEYBASED_IMUNIFY360_CLEAN = 'CLEAN';
    const KEYBASED_IMUNIFY360_SINGLE = '360_1';
    const KEYBASED_IMUNIFY360_30 = '360_30';
    const KEYBASED_IMUNIFY360_250 = '360_250';
    const KEYBASED_IMUNIFY360_UNLIMITED = '360_UN';

    protected $type;
    protected $keyLimit;

    /**
     * @return string|integer
     */
    abstract public function getApiLicenseId();

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @param self::IMUNIFY360_SINGLE|self::IMUNIFY360_30|self::IMUNIFY360_250|self::IMUNIFY360_UNLIMITED|self::IMUNIFY360_CLEAN $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @param integer $limit
     * @return $this
     */
    public function setKeyLimit($limit) {
        $this->keyLimit = $limit;
        return $this;
    }

    /**
     * @param @param self::PRODUCT_CLOUDLINUX|self:PRODUCT_KERNELCARE|self::PRODUCT_IMUNIFY360 $product
     * @param boolean $isKeyBased
     * @return $this
     * @throws \ErrorException
     */
    public static function factory($product, $isKeyBased)
    {
        $className = sprintf('%s%sLicense', $product, $isKeyBased ? 'Key' : 'IP');
        $class =  __NAMESPACE__ . '\\' . $className;

        if (!class_exists($class)) {
            throw new \ErrorException(sprintf('Undefined class name: %s', $className));
        }

        return new $class();
    }
}