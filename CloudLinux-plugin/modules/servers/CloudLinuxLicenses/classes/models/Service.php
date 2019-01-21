<?php

namespace CloudLinuxLicenses\classes\models;


use CloudLinuxLicenses\classes\components\WHMCS;
use CloudLinuxLicenses\classes\components\ModuleLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Service extends AbstractService
{
    /**
     *
     */
    const SESSION_PRE_EDIT_FIELD = 'CloudLinuxModule_PreEdit';

    /**
     * @var string
     */
    protected $table = 'tblhosting';
    /**
     * @var array
     */
    protected $dates = ['regdate', 'nextinvoicedate', 'nextduedate', 'overideautosuspend'];
    /**
     * @var array
     */
    protected $fillable = ['status', 'dedicatedip', 'userid', 'orderid', 'packageid', 'server',
        'regdate', 'paymentmethod', 'nextduedate', 'firstpaymentamount', 'amount', 'billingcycle',
        'domainstatus', 'domain', 'username', 'password'];
    /**
     * @var string
     */
    protected $type = AbstractPackage::TYPE_PRODUCT;

    /**
     * @return HasMany
     */
    public function configurableOptions()
    {
        return $this->hasMany('CloudLinuxLicenses\classes\models\ServiceConfigOption', 'relid');
    }

    /**
     * @param boolean $useOrderIP
     * @return string
     */
    public function getDedicatedIP($useOrderIP = true)
    {
        if ($useOrderIP && empty($this->dedicatedip) && $this->order) {
            $this->setDedicatedIP($this->order->ipaddress);
        }

        return $this->dedicatedip;
    }

    /**
     * @param string $ip
     * @return void
     */
    public function setDedicatedIP($ip)
    {
        $this->dedicatedip = $ip;
        $this->save();
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->update([
            'domainstatus' => $status,
        ]);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->domainstatus === 'Active';
    }

    /**
     * @return ConfigOption[]
     */
    public function getConfigurableOptions()
    {
        $options = [];
        $serviceOptions = $this->configurableOptions()
            ->with('group')->with('configurableOption.clConfigurableOption')
            ->get();

        foreach ($serviceOptions as $serviceOption) {
            $serviceOption->configurableOption->serviceOption = $serviceOption;
            $options[$serviceOption->configid] = $serviceOption->configurableOption;
        }

        return $options;
    }

    /**
     * @return ConfigOption[]
     */
    public function getPostConfigurableOptions()
    {
        if (!isset($_POST['configoption'])) {
            return ModuleLogger::model()->log('Can not get configurable options from _POST');
        }

        $options = [];
        foreach ($_POST['configoption'] as $groupId => $optionId) {
            $group = ConfigOptionGroup::find($groupId);
            if ($group->isTypeCheckbox()) {
                $options[$groupId] = $group->options->first();
            } else {
                $options[$groupId] = ConfigOption::with('clConfigurableOption')->find($optionId);
            }
        }

        return $options;
    }

    /**
     * @return string
     */
    public function getPreEditVarName() {
        return sprintf('%s_%d', self::SESSION_PRE_EDIT_FIELD, $this->id);
    }

    /**
     * @param CLConfigurableOptions[] $terminateOptions
     * @param CLConfigurableOptions[] $createOptions
     * @param string $status
     */
    public function setPreEditVars($terminateOptions, $createOptions, $status)
    {
        $_SESSION[$this->getPreEditVarName()] = [
            'terminateOptions' => serialize($terminateOptions),
            'createOptions' => serialize($createOptions),
            'status' => serialize($status),
        ];
    }

    /**
     * @return array [
     *  'terminateOptions' => CLConfigurableOptions[],
     *  'createOptions' => CLConfigurableOptions[]
     *  'status' => string,
     * ]
     */
    public function getPreEditVars()
    {
        $field = $this->getPreEditVarName();
        $response = [];

        if (isset($_SESSION[$field])) {
            $response = array_map('unserialize', $_SESSION[$field]);
            unset($_SESSION[$field]);
        }

        return $response;
    }

    /**
     * @param $params
     */
    public function onAfterModuleCreate($params)
    {
        try {
            $addons = ServiceAddon::selectRaw('GROUP_CONCAT(tblhostingaddons.addonid) as ids')
                ->join('tbladdons', 'tbladdons.id', '=', 'tblhostingaddons.addonid')
                ->where('tblhostingaddons.hostingid', $this->id)
                ->where('tbladdons.module', '!=', 'CloudLinuxLicenses')
                ->groupBy('tblhostingaddons.hostingid')->first();
        } catch (\Exception $e) {
            // < v7.2
            $addons = ServiceAddon::selectRaw('GROUP_CONCAT(addonid) as ids')->where('hostingid', $this->id)
                ->groupBy('hostingid')->first();
        }

        $result1 = $addons ? CLAddonRelations::whereIn('addonID', explode(',', $addons->ids))->get()->toArray() : [];
        $result2 = CLFreeProductRelations::where('freeProductID', $params['packageid'])->get()->toArray();
        $result = array_merge($result1, $result2);

        foreach ($result as $relation) {
            //create new license product
            if (!isset($relation['licenseProductID'])) {
                continue;
            }

            $count = CLConnections::where('hosting_id', $this->id)
                ->where('product_id', $relation['licenseProductID'])->count();

            if (!$count) {
                $values = [
                    'userid' => $params['clientsdetails']['userid'],
                    'orderid' => $this->orderid,
                    'packageid' => $relation['licenseProductID'],
                    'server' => $this->server,
                    'regdate' => date('Y-m-d'),
                    'paymentmethod' => 'paypal',
                    'nextduedate' => $this->nextduedate,
                    'firstpaymentamount' => 0.00,
                    'amount' => 0.00,
                    'billingcycle' => 'Free account',
                    'domain' => $params['domain'],
                    'username' => $params['username'],
                    'password' => $params['password']
                ];

                // No sense, product already created
                $newService = Service::create($values);
                if (!empty($this->dedicatedip)) {
                    $newService->update(['dedicatedip' => $this->dedicatedip]);
                }

                try {
                    WHMCS::model()->moduleCreate($newService->id);
                    CLConnections::create([
                        'order_id' => $this->orderid,
                        'hosting_id' => $this->id,
                        'user_id' => $params['clientsdetails']['userid'],
                        'product_id' => $relation['licenseProductID'],
                        'relation_id' => $relation['id'],
                        'hostingb_id' => $newService->id,
                    ]);
                    $newService->update([
                        'domainstatus' => 'Active',
                    ]);
                } catch (\Exception $e) {
                    $newService->delete();
                }
            } else {    // run module command create for license product
                $services = self::where('orderid', $this->orderid)
                    ->where('id', '!=', $this->id)
                    ->where('packageid', $relation['licenseProductID'])->get();

                foreach ($services as $row) {
                    if (!empty($row->dedicatedip)) {
                        $row->update(['dedicatedip' => $this->dedicatedip]);
                    }

                    try {
                        WHMCS::model()->moduleCreate($row->id);
                        $this->setActiveRelatedAddons();
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // TODO: try to fix
        // Runs only if manually execute module create from admin side
        if (isset($_POST['ajax'])) {
            $this->configurableOptionsCreateLicense();
        }
    }

    /**
     * @param $params
     */
    public function onAfterModuleTerminate($params)
    {
        $connections = CLConnections::where('order_id', $this->orderid)->where('hosting_id', $this->id)->get();

        foreach ($connections as $row) {
            $services = Service::where('orderid', $this->orderid)->where('id', '!=', $this->id)
                ->where('packageid', $row->product_id)->get();
            foreach ($services as $service) {
                WHMCS::model()->moduleTerminate($service->id);
            }
        }

        // Terminate configurable option licenses
        foreach ($this->getConfigurableOptions() as $option) {
            if ($option->clConfigurableOption->count()) {
                // Type checkbox
                if ($option->group->isTypeCheckbox() && $option->serviceOption->qty === 0) {
                    continue;
                }
                foreach ($option->clConfigurableOption as $clOption) {
                    try {
                        $clOption->package->terminateLicense($this);
                    } catch (\Exception $e) {}
                }
            }
        }
    }

    /**
     * @param $action
     * @param $params
     */
    public function onAfterSuspendOrUnsuspend($action, $params)
    {
        $connections = CLConnections::where('order_id', $this->orderid)->where('hosting_id', $this->id)->get();

        foreach ($connections as $row) {
            $services = Service::where('orderid', $this->orderid)->where('id', '!=', $this->id)
                ->where('packageid', $row->product_id)->get();

            foreach ($services as $service) {
                $status = ($action === 'suspend') ? 'Active' : 'Suspended';

                if ($service->domainstatus === $status) {
                    if ($action === 'suspend') {
                        WHMCS::model()->moduleSuspend($service->id);
                    } else {
                        WHMCS::model()->moduleUnSuspend($service->id);
                    }
                }
            }
        }
    }

    /**
     * @param $params
     */
    public function onAddonActivation($params)
    {
        $addonRelations = CLAddonRelations::where('addonID', $params['addonid'])->get();

        foreach ($addonRelations as $relation) {
            $count = CLConnections::where('hosting_id', $this->id)
                ->where('order_id', $this->orderid)
                ->where('product_id', $relation->licenseProductID)->count();

            if ($count) {
                continue;
            }

            $newService = Service::create([
                'userid' => $this->userid,
                'orderid' => $this->orderid,
                'packageid' => $relation->licenseProductID,
                'server' => $this->server,
                'regdate' => date('Y-m-d'),
                'paymentmethod' => $this->paymentmethod,
                'nextduedate' => $this->nextduedate,
                'firstpaymentamount' => 0.00,
                'amount' => 0.00,
                'billingcycle' => 'Free account',
                'domainstatus' => $this->domainstatus,
                'domain' => $this->domain,
                'username' => $this->username,
                'password' => $this->password,
            ]);
            if ($newService->package->isKeyBased()) {
                $newService->setCustomField(AbstractPackage::CUSTOM_FIELD_KEY, '');
            }

            CLConnections::create([
                'order_id' => $this->orderid,
                'hosting_id' => $this->id,
                'user_id' => $this->userid,
                'product_id' => $relation->licenseProductID,
                'relation_id' => $relation->id,
                'hostingb_id' => $newService->id,
            ]);
        }
    }

    /**
     * @param $params
     */
    public function onAddonTerminated($params)
    {
        $services = Service::where('orderid', $params['orderid'])->where('id', '!=', $this->id)
            ->where('userid', $params['userid'])->get();
        $products = CLAddonRelations::selectRaw('GROUP_CONCAT(licenseProductID) as ids')
            ->where('addonID', $params['addonid'])
            ->groupBy('addonID')->first();

        foreach ($services as $service) {
            if (in_array($service->packageid, explode(',', $products->ids))) {
                WHMCS::model()->moduleTerminate($service->id);
            }
        }
    }

    /**
     *
     */
    public function setActiveRelatedAddons()
    {
        try {
            ServiceAddon::join('tbladdons', 'tbladdons.id', '=', 'tblhostingaddons.addonid')
                ->where('tbladdons.module', '!=', 'CloudLinuxLicenses')
                ->where('tblhostingaddons.hostingid', $this->id)
                ->where('tblhostingaddons.orderid', $this->orderid)
                ->getQuery()->update([
                    'tblhostingaddons.status' => 'Active',
                ]);
        } catch (\Exception $e) {
            // < v7.2
            ServiceAddon::where('hostingid', $this->id)
                ->where('orderid', $this->orderid)->update([
                    'status' => 'Active',
                ]);
        }
    }

    /**
     * @param CLFreeProductRelations | CLConfigurableOptions
     * @return Service
     */
    public function createRelated($relation)
    {
        if ($relation instanceof CLFreeProductRelations) {
            $productId = $relation->licenseProductID;
        } else {
            $productId = $relation->product_id;
        }

        $relatedService = Service::create([
            'userid' => $this->userid,
            'orderid' => $this->orderid,
            'packageid' => $productId,
            'server' => $this->server,
            'regdate' => date('Y-m-d'),
            'paymentmethod' => $this->paymentmethod,
            'nextduedate' => $this->nextduedate,
            'firstpaymentamount' => 0.00,
            'amount' => 0.00,
            'billingcycle' => 'Free account',
            'domain' => $this->domain,
            'username' => $this->username,
            'password' => decrypt($this->password)
        ]);

        if ($relatedService->package->isKeyBased()) {
            $relatedService->setCustomField(AbstractPackage::CUSTOM_FIELD_KEY, '');
        }

        CLConnections::create([
            'order_id' => $this->orderid,
            'hosting_id' => $this->id,
            'user_id' => $this->userid,
            'product_id' => $productId,
            'relation_id' => $relation->id,
            'hostingb_id' => $relatedService->id,
        ]);

        return $relatedService;
    }

    /**
     * @return void
     */
    public function onPreEdit()
    {
        $oldOptions = $this->getConfigurableOptions();
        $newOptions = $this->getPostConfigurableOptions();
        $terminateOptions = new Collection();
        $createOptions = new Collection();
        $dedicatedIP = isset($_POST['dedicatedip']) ? $_POST['dedicatedip'] : '';

        // Prepare to terminate old licenses
        foreach ($oldOptions as $oldOptionGroupId => $oldOption) {
            if ($oldOption->clConfigurableOption && $oldOption->clConfigurableOption->count()) {
                // Dedicated IP changed
                if ($this->dedicatedip && $dedicatedIP !== $this->dedicatedip) {
                    foreach ($oldOption->clConfigurableOption as $clOption) {
                        if ($clOption->package && !$clOption->package->isKeyBased()) {
                            $clOption->package->terminateLicense($this);
                        }
                    }
                    continue;
                }
                // Type dropdown & radio
                if (isset($newOptions[$oldOptionGroupId]) && $newOptions[$oldOptionGroupId]->id !== $oldOption->id) {
                    $terminateOptions = $terminateOptions->merge($oldOption->clConfigurableOption);
                }
                // Type checkbox
                if (!isset($newOptions[$oldOptionGroupId]) && $oldOption->group->isTypeCheckbox()
                    && $oldOption->serviceOption->qty === 1) {
                    $terminateOptions = $terminateOptions->merge($oldOption->clConfigurableOption);
                }
            }
        }

        // Prepare to create new licenses
        foreach ($newOptions as $newOptionGroupId => $newOption) {
            // Type dropdown & radio
            if ($newOption->clConfigurableOption && $newOption->clConfigurableOption->count()) {
                // Dedicated IP changed
                if ($dedicatedIP && $dedicatedIP !== $this->dedicatedip) {
                    foreach ($newOption->clConfigurableOption as $clOption) {
                        if ($clOption->package && !$clOption->package->isKeyBased()) {
                            $createOptions->push($clOption);
                        }
                    }
                    continue;
                }

                // Type checkbox
                if ($newOption->group->isTypeCheckbox() && $oldOptions[$newOptionGroupId]->serviceOption->qty === 0) {
                    $createOptions = $createOptions->merge($newOption->clConfigurableOption);
                } else if (isset($oldOptions[$newOptionGroupId]) && $oldOptions[$newOptionGroupId]->id !== $newOption->id) {
                    $createOptions = $createOptions->merge($newOption->clConfigurableOption);
                }
            }
        }

        // whmcs rewrites custom fields after 'PreServiceEdit' hook
        $this->setPreEditVars($terminateOptions, $createOptions, $this->domainstatus);
    }

    /**
     * @return void
     */
    public function onEdit()
    {
        $options = $this->getPreEditVars();

        if ($this->isActive()) {
            // Terminate old licenses
            foreach ($options['terminateOptions'] as $option) {
                $option->package->terminateLicense($this);
            }

            // Create new licenses
            foreach ($options['createOptions'] as $option) {
                $option->package->createLicense($this);
            }
        }

        // Check status
        if ($options['status'] !== $this->domainstatus) {
            if ($this->isActive()) {
                $this->configurableOptionsCreateLicense();
            } else {
                $this->configurableOptionsTerminateLicense();
            }
        }
    }

    /**
     * Create configurable option licenses
     */
    public function configurableOptionsCreateLicense()
    {
        foreach ($this->getConfigurableOptions() as $option) {
            if ($option->clConfigurableOption->count()) {
                // Type checkbox
                if ($option->group->isTypeCheckbox() && $option->serviceOption->qty === 0) {
                    continue;
                }
                foreach ($option->clConfigurableOption as $clOption) {
                    $clOption->package->createLicense($this);
                }
            }
        }
    }

    /**
     * Terminate configurable option licenses
     */
    public function configurableOptionsTerminateLicense()
    {
        foreach ($this->getConfigurableOptions() as $option) {
            if ($option->clConfigurableOption->count()) {
                foreach ($option->clConfigurableOption as $clOption) {
                    $clOption->package->terminateLicense($this);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return sprintf('clientsservices.php?userid=%d&id=%d', $this->userid, $this->id);
    }
}