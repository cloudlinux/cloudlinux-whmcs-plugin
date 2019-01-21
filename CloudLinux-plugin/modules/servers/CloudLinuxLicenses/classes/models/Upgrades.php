<?php


namespace CloudLinuxLicenses\classes\models;

use CloudLinuxLicenses\classes\components\WHMCS;
use Illuminate\Database\Eloquent\Model;


class Upgrades extends Model
{
    const TYPE_CONFIGOPTIONS = 'configoptions';
    const TYPE_PACKAGE = 'package';

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblupgrades';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Service', 'relid');
    }

    /**
     * @param $value
     * @return int
     */
    public function getNewvalueAttribute($value)
    {
        return (int) $value;
    }

    /**
     * @throws \Exception
     */
    public function afterUpgrade() {

        if ($this->isTypeConfigOptions()) {
            $this->upgradeConfigOptions();
        }
        if ($this->isTypePackage()) {
            $this->upgradePackage();
        }
    }

    /**
     * Upgrade for configurable option relations
     */
    public function upgradeConfigOptions()
    {
        list($groupId, $oldId) = explode('=>', $this->originalvalue);
        $newId = $this->newvalue;
        $groupId = (int) $groupId;
        $oldId = (int) $oldId;

        $optionGroup = ConfigOptionGroup::find($groupId);
        // Checkbox
        if ($optionGroup && $optionGroup->isTypeCheckbox() && $oldId === 0 && $newId === 1) {
            $this->upgradeCheckbox($groupId);
            return;
        }
        if ($optionGroup && $optionGroup->isTypeCheckbox() && $oldId === 1 && $newId === 0) {
            $this->downgradeCheckbox($groupId);
            return;
        }

        $oldOption = CLConfigurableOptions::where('option_id', $oldId)->first();
        if ($oldOption) {
            $oldOption->package->terminateLicense($this->service);
        }

        $newOption = CLConfigurableOptions::where('option_id', $newId)->first();
        if ($newOption) {
            $newOption->package->createLicense($this->service);
        }
    }

    /**
     * @throws \Exception
     */
    public function upgradePackage()
    {
        $oldId = $this->originalvalue;
        list($newId, $paymentType) = explode(',', $this->newvalue);

        $oldPackage = Package::find($oldId);
        if ($oldPackage->isCL()) {
            $service = Service::find($this->relid);
            if ($oldPackage->isKeyBased()) {
                // FIXME: after upgrade to non-cl product old `custom field` not exist
                if ($key = $service->getLicenseKey()) {
                    $oldPackage->getApi()->removeKey($key);
                }
            } else {
                $oldPackage->getApi()->removeLicense($service->getDedicatedIP());
            }
        }

        $newPackage = Package::find($newId);
        if ($newPackage->isCL()) {
            WHMCS::model()->moduleCreate($this->relid);
        }
    }

    /**
     * @return bool
     */
    public function isTypeConfigOptions() {
        return $this->type === self::TYPE_CONFIGOPTIONS;
    }

    /**
     * @return bool
     */
    public function isTypePackage() {
        return $this->type === self::TYPE_PACKAGE;
    }

    /**
     * @param int $optionGroupId
     */
    private function upgradeCheckbox($optionGroupId) {
        $option = CLConfigurableOptions::where('option_group_id', $optionGroupId)->first();

        if ($option) {
            $option->package->createLicense($this->service);
        }
    }

    /**
     * @param int $optionGroupId
     */
    private function downgradeCheckbox($optionGroupId) {
        $option = CLConfigurableOptions::where('option_group_id', $optionGroupId)->first();

        if ($option) {
            $option->package->terminateLicense($this->service);
        }
    }
}