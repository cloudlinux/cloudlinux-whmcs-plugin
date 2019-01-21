<?php


namespace CloudLinuxLicenses\classes\api;


use CloudLinuxLicenses\classes\api\licenses\ClnLicense;
use CloudLinuxLicenses\classes\api\responseModels\AbstractResponseModel;
use CloudLinuxLicenses\classes\api\responseModels\Server;
use CloudLinuxLicenses\classes\components\Csrf;

class IPBased extends ApiBase
{
    public function __construct(ApiConfiguration $configuration, ClnLicense $license)
    {
        parent::__construct($configuration, $license);
        $this->url = 'https://cln.cloudlinux.com/api/ipl';
    }

    /**
     * Check license by ip
     *
     * @param string $ip
     * @param integer $type
     * @return boolean
     * @throws \Exception
     */
    public function checkLicense($ip, $type = null)
    {
        $this->path = '/check.' . self::DATA_TYPE_JSON;
        $response = $this->callAndCheck(array(
            'ip' => $ip,
        ));
        $data = $response->getData();

        return in_array($type ?: $this->getClnLicense()->getApiLicenseId(), $data);
    }

    /**
     * @param string $ip
     * @return array
     * @throws \Exception
     */
    public function getLicense($ip)
    {
        if (!$ip) {
            return array();
        }

        $licenses = $this->licenseList();

        foreach ($licenses as $row) {
            if ($row['ip'] === $ip && $row['type'] ===  $this->getClnLicense()->getApiLicenseId()) {
                return $row;
            }
        }

        return array();
    }

    /**
     * @param string $ip
     * @return AbstractResponseModel
     * @throws \Exception
     */
    public function getServer($ip)
    {
        $this->path = '/server.' . self::DATA_TYPE_JSON;
        $response = $this->callAndCheck(array(
            'ip' => $ip,
        ));

        foreach ($response->getData() as $row) {
            if ($row['type'] === $this->getClnLicense()->getApiLicenseId()) {
                return new Server($row);
            }
        }

        return null;
    }

    /**
     * @param string $ip
     * @param integer $type
     * @return array
     * @throws \Exception
     */
    public function removeLicense($ip, $type = null)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('IP address is not valid');
        }

        $this->path = '/remove.' . self::DATA_TYPE_JSON;
        $response = $this->callAndCheck(array(
            'ip' => $ip,
            'type' => $type ?: $this->getClnLicense()->getApiLicenseId(),
        ));

        return $response->getData();
    }

    /**
     * Register license for given ip
     *
     * API response {
     *  "ip": "1.1.1.1",
     *  "type": 1
     *  "registered": false,
     *  "created": "2014-04-30T11:26-0400"
     * }
     *
     * @param string $ip
     * @return ApiResponse
     * @throws \Exception
     */
    public function createLicense($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('IP Address is not valid');
        }

        $this->path = '/register.' . self::DATA_TYPE_JSON;
        $response = $this->callAndCheck(array(
            'ip' => $ip,
            'type' => $this->getClnLicense()->getApiLicenseId(),
        ));

        return $response;
    }

    /**
     * Return all licenses owned by authorized user
     *
     * API reponse [
     *  {"ip": "1.1.1.1",
     *  "type": 16
     *  "registered": false,
     *  "created": "2014-04- 30T11:26-0400"}
     * , ...]
     *
     *  ip(string)
     *  type(int) ­ license type (1,2,16)
     *  registered(boolean) ­ true if server was registered in CLN with this license (CLN licenses only).
     *  created(string) ­ license creation time
     *
     * @return array
     * @throws \Exception
     */
    public function licenseList()
    {
        $this->path = '/list.' . self::DATA_TYPE_JSON;
        $response = $this->callAndCheck(array(), 'Get license list error');

        return $response->getData();
    }

    /**
     * @param string $ip
     * @param \CloudLinuxLicenses\classes\models\AbstractService $service
     * @return boolean
     * @throws \Exception
     */
    public function changeIP($ip, $service)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("IP Address '{$ip}' is not valid");
        }

        $licenseId = $this->getClnLicense()->getApiLicenseId();
        $oldIP = $service->getDedicatedIP();
        $service->isIpUnique($ip);
        Csrf::check();

        if ($this->checkLicense($oldIP, $licenseId)) {
            $this->removeLicense($oldIP, $licenseId);
        }

        $this->createLicense($ip, $licenseId);
        $service->setDedicatedIP($ip);

        return true;
    }
}