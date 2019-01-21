<?php

namespace CloudLinuxLicenses\classes\models;

use CloudLinuxLicenses\classes\api\IPBased;
use CloudLinuxLicenses\classes\api\KeyBasedKernelCare;
use Illuminate\Database\Eloquent\Model;


abstract class AbstractService extends Model
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_PENDING = 'Pending';

    /**
     * @var string
     */
    protected $type;

    /**
     * @param boolean $useOrderIP
     * @return string
     */
    abstract public function getDedicatedIP($useOrderIP = true);

    /**
     * @param string $ip
     * @return void
     */
    abstract public function setDedicatedIP($ip);

    /**
     * @return boolean
     */
    abstract public function isActive();

    /**
     * @param $params
     * @return void
     */
    abstract public function onAfterModuleCreate($params);

    /**
     * @param $params
     * @return void
     */
    abstract public function onAfterModuleTerminate($params);

    /**
     * @param $action
     * @param $params
     * @return void
     */
    abstract public function onAfterSuspendOrUnsuspend($action, $params);

    /**
     * @param $params
     * @return void
     */
    abstract public function onAddonActivation($params);

    /**
     * @param $params
     * @return void
     */
    abstract public function onAddonTerminated($params);

    /**
     * @return Package
     */
    public function package()
    {
        if ($this instanceof ServiceAddon) {
            return $this->belongsTo('CloudLinuxLicenses\classes\models\Addon', 'addonid');
        } else {
            return $this->belongsTo('CloudLinuxLicenses\classes\models\Package', 'packageid');
        }
    }

    /**
     * @return Order
     */
    public function order()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Order', 'orderid');
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Client', 'userid');
    }

    /**
     * @return CustomFieldValue
     */
    public function customFieldValues()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\CustomFieldValue', 'relid');
    }

    /**
     * @throws \Exception
     */
    public function createAccount()
    {
        /* @var KeyBasedKernelCare | IPBased $api*/
        $api = $this->package->getApi();

        if ($this->package->isKeyBased()) {
            $key = $api->createKey($this->package, $this->getDescription());
            if ($key) {
                $this->setCustomField(AbstractPackage::CUSTOM_FIELD_KEY, $key);
            }
        } else {
            $ip = $this->getDedicatedIP();
            if ($api->checkLicense($ip)) {
                throw new \LogicException('License already exists for IP Address: ' . $ip);
            }
            $api->createLicense($ip);
        }
    }

    /**
     * @throws \Exception
     */
    public function terminateAccount()
    {
        $api = $this->package->getApi();

        if ($this->package->isKeyBased()) {
            $key = $this->getLicenseKey();
            $api->removeKey($key);
            $this->setCustomField(AbstractPackage::CUSTOM_FIELD_KEY, '');
        } else {
            $ip = trim($this->getDedicatedIP());
            $api->removeLicense($ip);
        }
    }

    /**
     * @throws \Exception
     */
    public function suspendAccount()
    {
        $api = $this->package->getApi();

        if (!$this->package->isKeyBased()) {
            $ip = trim($this->getDedicatedIP());
            $api->removeLicense($ip);
        }
    }

    /**
     * @throws \Exception
     */
    public function unSuspendAccount()
    {
        $api = $this->package->getApi();

        if (!$this->package->isKeyBased()) {
            $ip = trim($this->getDedicatedIP());
            if ($api->checkLicense($ip)) {
                throw new \LogicException('License already exists');
            }

            $api->createLicense($ip);
        }
    }

    public function getDescription()
    {
        if (!class_exists('JTransliteration')) {
            // FIXME
            include_once ROOTDIR . DS . implode(DS, ['modules', 'servers', 'CloudLinuxLicenses', 'classes',
                    'extensions', 'transliteration', 'JTransliteration.php']);
        }

        $clientName = $this->client->firstname .' '. $this->client->lastname;
        return 'whmcs-module: ' . \JTransliteration::transliterate($clientName);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return CustomFieldValue
     */
    public function setCustomField($name, $value)
    {
        $customField = CustomField::where('type', $this->type)
            ->where('relid', $this->package->id)
            ->where('fieldname', $name)
            ->first();

        CustomFieldValue::firstOrCreate([
            'relid' => $this->id,
            'fieldid' => $customField->id,
        ]);

        return CustomFieldValue::where('fieldid', $customField->id)
            ->where('relid', $this->id)
            ->update([
                'value' => $value,
            ]);
    }

    /**
     * @param string $name
     * @return string
     * @throws \Exception
     */
    public function getCustomField($name)
    {
        $related = ($this instanceof ServiceAddon) ? 'tblhostingaddons' : 'tblhosting';
        $data = $this->select('tblcustomfieldsvalues.*')
            ->join('tblcustomfieldsvalues', $related . '.id', '=', 'tblcustomfieldsvalues.relid')
            ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
            ->where('tblcustomfields.fieldname', $name)
            ->where($related . '.id', $this->id)
            ->where('tblcustomfieldsvalues.value', '!=', '')
            ->first();

        if (!$data) {
            return '';
        }

        return $data->value;
    }

    /**
     * @param string $name
     * @return CustomFieldValue|null
     */
    public function getCustomFieldValue($name)
    {
        $customField = CustomField::where('type', $this->type)
            ->where('relid', $this->package->id)
            ->where('fieldname', $name)
            ->first();

        return CustomFieldValue::where('relid', $this->id)
            ->where('fieldid', $customField->id)->first();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLicenseKey()
    {
        $key = $this->getCustomField('KernelCare Key');     // Old value
        if (!$key) {
            $key = $this->getCustomField(AbstractPackage::CUSTOM_FIELD_KEY);
        }

        return $key;
    }

    /**
     * @param string $ip
     * @throws \Exception
     */
    public function isIpUnique($ip)
    {
        try {
            $usedAddonIPs = ServiceAddon::select('*')
                ->join('tbladdons', 'tbladdons.id', '=', 'tblhostingaddons.addonid')
                ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhostingaddons.id')
                ->where('tbladdons.module', 'CloudLinuxLicenses')
                ->where('tblhostingaddons.id', '!=', $this->id)
                ->where('tblhostingaddons.status', 'Active')
                ->where('tblcustomfieldsvalues.value', $ip)
                ->whereIn('tbladdons.id', function ($query) {
                    $query->select('entity_id')
                        ->from('tblmodule_configuration')
                        ->where('entity_type', AbstractPackage::TYPE_ADDON)
                        ->where('setting_name', 'configoption3')
                        ->where('value', $this->package->getProductType());
                })
                ->whereIn('tblcustomfieldsvalues.fieldid', function($query) {
                    $query->select('tblcustomfields.id')
                        ->from('tblcustomfields')
                        ->where('tblcustomfields.type', 'addon')
                        ->where('tblcustomfields.fieldname', AbstractPackage::CUSTOM_FIELD_IP)
                        ->where('tblcustomfields.relid', self::getConnection()->raw('tbladdons.id'));
                })->count();
        } catch (\Exception $e) {
            // For whmcs version < 7.2
            $usedAddonIPs = 0;
        }

        $usedProductIPs = Service::select('*')
            ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
            ->where('tblproducts.servertype', 'CloudLinuxLicenses')
            ->where('tblhosting.dedicatedip', $ip)
            ->where('tblhosting.domainstatus', 'Active')
            ->where('tblhosting.id', '!=', $this->id)
            ->where('tblproducts.configoption3', $this->package->getProductType())
            ->count();

        if (($usedProductIPs + $usedAddonIPs)) {
            throw new \LogicException('IP is already used by another product or addon');
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
            return ServiceAddon::find($params['model']->id);
        } elseif (isset($params['serviceid'])) {
            return Service::find($params['serviceid']);
        }

        throw new \LogicException('Can\'t get service by params');
    }
}