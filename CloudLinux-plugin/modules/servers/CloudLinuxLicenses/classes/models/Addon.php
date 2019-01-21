<?php

namespace CloudLinuxLicenses\classes\models;


class Addon extends AbstractPackage
{
    /**
     * @var string
     */
    protected $type = self::TYPE_ADDON;

    /**
     * @var string
     */
    protected $table = 'tbladdons';

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeCL($query)
    {
        return $query->where('module', 'CloudLinuxLicenses');
    }

    /**
     * @return bool
     */
    public function isCL()
    {
        return $this->module === 'CloudLinuxLicenses';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function configuration()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\ModuleConfiguration', 'entity_id')
            ->where('entity_type', 'addon');
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
            $option = $this->configuration()->ofKey($key)->first();
            if ($option) {
                return $option->value;
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
            $key++;
            $option = $this->configuration()->ofKey($key)->first();
            if ($option) {
                $option->value = $value;
                $option->save();
            }
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
            $this->removeCustomField(AbstractPackage::CUSTOM_FIELD_IP);
            $this->createCustomField(AbstractPackage::CUSTOM_FIELD_KEY);
        } else {
            $this->removeCustomField(AbstractPackage::CUSTOM_FIELD_KEY);
            $this->createCustomField(AbstractPackage::CUSTOM_FIELD_IP);
        }
    }

}