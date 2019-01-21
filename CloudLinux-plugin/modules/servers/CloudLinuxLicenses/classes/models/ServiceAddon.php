<?php

namespace CloudLinuxLicenses\classes\models;


class ServiceAddon extends AbstractService
{
    /**
     * @var string
     */
    protected $table = 'tblhostingaddons';
    /**
     * @var array
     */
    protected $dates = ['regdate', 'nextinvoicedate', 'nextduedate'];
    /**
     * @var array
     */
    protected $fillable = ['status'];
    /**
     * @var string
     */
    protected $type = AbstractPackage::TYPE_ADDON;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentService()
    {
        return $this->belongsTo('CloudLinuxLicenses\classes\models\Service', 'hostingid');
    }

    /**
     * @param boolean $useOrderIP
     * @return string
     * @throws \Exception
     */
    public function getDedicatedIP($useOrderIP = true)
    {
        $ip = $this->getCustomField(AbstractPackage::CUSTOM_FIELD_IP);
        if (!$ip) {
            $ip = $this->parentService->dedicatedip ?: $this->parentService->order->ipaddress;
            $this->setCustomField(AbstractPackage::CUSTOM_FIELD_IP, $ip);
        }

        return $ip;
    }

    /**
     * @param string $ip
     * @return void
     */
    public function setDedicatedIP($ip)
    {
        $this->setCustomField(AbstractPackage::CUSTOM_FIELD_IP, $ip);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'Active';
    }

    /**
     * @param $params
     * @return mixed
     */
    public function onAfterModuleCreate($params) {}

    /**
     * @param $params
     * @return mixed
     */
    public function onAfterModuleTerminate($params) {}

    /**
     * @param $action
     * @param $params
     * @return mixed
     */
    public function onAfterSuspendOrUnsuspend($action, $params) {}

    /**
     * @param $params
     * @return mixed
     */
    public function onAddonActivation($params) {}

    /**
     * @param $params
     * @return mixed
     */
    public function onAddonTerminated($params) {}
}