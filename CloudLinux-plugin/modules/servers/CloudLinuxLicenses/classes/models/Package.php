<?php

namespace CloudLinuxLicenses\classes\models;


class Package extends AbstractPackage
{
    /**
     * @var string
     */
    protected $type = self::TYPE_PRODUCT;

    /**
     * @var string
     */
    protected $table = 'tblproducts';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeCL($query)
    {
        return $query->where('servertype', 'CloudLinuxLicenses');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeNotCL($query)
    {
        return $query->where('servertype', '!=', 'CloudLinuxLicenses');
    }

    /**
     * @return bool
     */
    public function isCL()
    {
        return $this->servertype === 'CloudLinuxLicenses';
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function getConfigOption($name)
    {
        if (($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            $key++;
            if (isset($this->{'configoption' . $key})) {
                return $this->{'configoption' . $key};
            } else {
                throw new \Exception('Undefined option: ' . $name);
            }
        } else {
            throw new \Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @param string $name
     * @param string $value
     * @throws \Exception
     */
    public function setConfigOption($name, $value)
    {
        if (($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            ++$key;
            $this->{'configoption' . $key} = $value;
        } else {
            throw new \Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @param array $attributes
     * @throws \Exception
     */
    public function setConfigOptions($attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            try {
                $this->setConfigOption($attribute, $value);
            } catch (\Exception $e) {
                // CException::log($e);
            }
        }
    }

    /**
     * Runs for AdminProductConfigFieldsSave & AddonConfigSave hooks
     * @throws \Exception
     */
    public function onSaveConfigFields()
    {
        if (!$this->isCL()) {
            return;
        }

        if ($this->getProductType() === 'CloudLinux') {
            $this->setConfigOption('useKey', 0);
            $this->save();
        }

        if ($this->isKeyBased()) {
            $this->createCustomField(AbstractPackage::CUSTOM_FIELD_KEY);
        } else {
            $this->removeCustomField(AbstractPackage::CUSTOM_FIELD_KEY);
        }
    }
}