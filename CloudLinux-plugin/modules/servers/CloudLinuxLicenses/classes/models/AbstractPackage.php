<?php


namespace CloudLinuxLicenses\classes\models;


use CloudLinuxLicenses\classes\api\ApiConfiguration;
use CloudLinuxLicenses\classes\api\IPBased;
use CloudLinuxLicenses\classes\api\KeyBasedKernelCare;
use CloudLinuxLicenses\classes\api\KeyBasedImunify360;
use CloudLinuxLicenses\classes\api\licenses\ClnLicense;
use CloudLinuxLicenses\classes\components\ModuleLogger;
use Illuminate\Database\Eloquent\Model;


abstract class AbstractPackage extends Model
{
    /**
     *
     */
    const TYPE_PRODUCT = 'product';
    /**
     *
     */
    const TYPE_ADDON = 'addon';

    const CUSTOM_FIELD_KEY = 'License Key';
    const CUSTOM_FIELD_IP = 'License IP';

    /**
     * @var
     */
    protected $type;

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    abstract public function getConfigOption($name);

    /**
     * @param string $name
     * @param string $value
     * @throws \Exception
     */
    abstract public function setConfigOption($name, $value);

    /**
     * @param array $attributes
     * @throws \Exception
     */
    abstract public function setConfigOptions($attributes = []);

    /**
     * @param $query
     * @return mixed
     */
    abstract public function scopeTypeCL($query);

    /**
     * @return boolean
     */
    abstract public function isCL();

    /**
     * Runs for AdminProductConfigFieldsSave & AddonConfigSave hooks
     * @return void
     */
    abstract public function onSaveConfigFields();

    /**
     * @return Server
     */
    public function server()
    {
        return $this->hasOne('CloudLinuxLicenses\classes\models\Server', 'type', 'servertype');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = array(
            'username' => array(
                'FriendlyName' => 'Username',
                'Type' => 'text',
                'Size' => '15',
                'Default' => '',
            ),
            'password' => array(
                'FriendlyName' => 'IP registration token',
                'Type' => 'password',
                'Size' => '15',
                'Description' => 'API secret key from Profile page in CLN'
            ),
            'licenseType' => array(
                'FriendlyName' => 'Product',
                'Type' => 'dropdown',
                'Options' => sprintf('%s,%s,%s',
                    ClnLicense::PRODUCT_CLOUDLINUX, ClnLicense::PRODUCT_KERNELCARE, ClnLicense::PRODUCT_IMUNIFY360),
                'Default' => ClnLicense::PRODUCT_CLOUDLINUX,
            ),
            'debug' => array(
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Logs on \'Module Log\''
            ),
            'useKey' => array(
                'FriendlyName' => 'Create Key based license',
                'Type' => 'yesno',
                'Description' => 'For KernelCare/Imunify360',
                'Default' => 'no',
            ),
            'keyLimit' => array(
                'FriendlyName' => 'Key Limit',
                'Type' => 'text',
                'Size' => '10',
                'Default' => 1,
                'Description' => 'For KernelCare/Imunify360',
            ),
            'keyType' => array(
                'FriendlyName' => 'License Type',
                'Type' => 'dropdown',
                'Options' => array_values($this->getLicenseTypes()),
                'Default' => 30,
                'Description' => 'For Imunify360',
            ),
        );
        return $config;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUsername()
    {
        return (string) $this->getConfigOption('username');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPassword()
    {
        return (string) $this->getConfigOption('password');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getProductType()
    {
        return (string) $this->getConfigOption('licenseType');
    }

    /**
     * Enable API debug
     * @return bool
     * @throws \Exception
     */
    public function getDebug()
    {
        return (bool) $this->getConfigOption('debug');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getUseKey()
    {
        try {
            return (bool) $this->getConfigOption('useKey');
        } catch (\Exception $e) {
            // old value
            return (bool) $this->getConfigOption('useKernelCareKey');
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getKeyLimit()
    {
        try {
            return (int) $this->getConfigOption('keyLimit');
        } catch (\Exception $e) {
            // old value
            return (int) $this->getConfigOption('KernelCareKeyLimit');
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getLicenseType()
    {
        return $this->getConfigOption('keyType');
    }

    /**
     * @return array
     */
    public function getLicenseTypes()
    {
        return array(
            ClnLicense::IMUNIFY360_UNLIMITED => 'Unlimited users per server',
            ClnLicense::IMUNIFY360_SINGLE => 'Single user per server',
            ClnLicense::IMUNIFY360_30 => 'Up to 30 users per server',
            ClnLicense::IMUNIFY360_250 => 'Up to 250 users per server',
            // TODO: License::IMUNIFY360_CLEAN => 'ImunifyClean',
        );
    }

    /**
     * @return IPBased|KeyBasedKernelCare|KeyBasedImunify360
     * @throws \Exception
     */
    public function getApi()
    {
        $productType = $this->getProductType();
        $configuration = new ApiConfiguration($this->getUsername(), $this->getPassword(), $this->getDebug());
        $license = ClnLicense::factory($productType, $this->isKeyBased())
            ->setType($this->getLicenseType())
            ->setKeyLimit($this->getKeyLimit());

        if (!$this->isKeyBased()) {
            $api = new IPBased($configuration, $license);
        } else {
            switch ($productType) {
                case 'KernelCare':
                case 'KarnelCare':  // old value
                    $api = new KeyBasedKernelCare($configuration, $license);
                    break;
                case 'Imunify360':
                    $api = new KeyBasedImunify360($configuration, $license);
                    break;
                default:
                    throw new \LogicException('Undefined product type');
                    break;
            }
        }

        return $api;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isKeyBased()
    {
        return ($this->getUseKey() && in_array($this->getProductType(),
                array(ClnLicense::PRODUCT_KERNELCARE, ClnLicense::PRODUCT_IMUNIFY360), true));
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isImunify()
    {
        return $this->getProductType() === 'Imunify360';
    }

    /**
     * @return bool
     */
    public function isAddon()
    {
        return $this->type === self::TYPE_ADDON;
    }

    /**
     * @param string $field
     */
    public function createCustomField($field)
    {
        CustomField::firstOrCreate(array(
            'type' => $this->type,
            'relid' => $this->id,
            'fieldname' => $field,
            'fieldtype' => 'text',
            'adminonly' => 'on',
        ));
    }

    /**
     * @param string $field
     */
    public function removeCustomField($field)
    {
        $customField = CustomField::where('type', $this->type)
            ->where('relid', $this->id)
            ->where('fieldname', $field)->first();
        if ($customField) {
            $customField->delete();
        }
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function createLicense(Service $service) {
        $api = $this->getApi();

        if ($this->isKeyBased()) {
            $fieldName = $api->getClnLicense()->getName();
            $service->package->createCustomField($fieldName);
            $key = $api->createKey($this, $service->getDescription());
            if ($key) {
                $this->getConnection()->enableQueryLog();
                $service->setCustomField($fieldName, $key);
            }
        } else {
            try {
                $api->createLicense($service->getDedicatedIP(false));
            } catch (\InvalidArgumentException $e) {
                $service->setStatus($service::STATUS_PENDING);
                ModuleLogger::model()->log($e->getMessage(), $service->getUrl());
                ModuleQueue::log($service->id, $e->getMessage(), ModuleQueue::ACTION_CREATE_LICENSE);
            }
        }
    }

    /**
     * @param Service $service
     * @throws \Exception
     */
    public function terminateLicense(Service $service) {
        $api = $this->getApi();

        if ($this->isKeyBased()) {
            $fieldName = $api->getClnLicense()->getName();
            $key = $service->getCustomField($fieldName);
            if ($key) {
                $api->removeKey($key);
            }
            $service->setCustomField($fieldName, '');
        } else {
            try {
                $this->getApi()->removeLicense($service->getDedicatedIP(false));
            } catch (\InvalidArgumentException $e) {
                ModuleLogger::model()->log($e->getMessage(), $service->getUrl());
                ModuleQueue::log($service->id, $e->getMessage(), ModuleQueue::ACTION_TERMINATE_LICENSE);
            }
        }
    }

    /**
     * @param array $params
     * @return $this
     * @throws \LogicException
     */
    public static function getByParams($params)
    {
        if (isset($params['model']) && $params['model'] instanceof \WHMCS\Service\Addon) {
            return Addon::find($params['model']->addonid);
        } elseif (isset($params['packageid'])) {
            return Package::find($params['packageid']);
        }

        throw new \LogicException('Can\'t get service by params');
    }
}