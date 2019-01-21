<?php


namespace CloudLinuxLicenses\classes\api;


use CloudLinuxLicenses\classes\api\licenses\ClnLicense;
use CloudLinuxLicenses\classes\api\responseModels\Server;
use CloudLinuxLicenses\classes\models\AbstractPackage;

class KeyBasedImunify360 extends KeyBasedKernelCare
{
    public function __construct(ApiConfiguration $configuration, ClnLicense $license)
    {
        parent::__construct($configuration, $license);
        $this->url = 'https://cln.cloudlinux.com/api/im';
    }

    /**
     * Get server list by key
     *
     * API response [{
     *   "server_id": "s096slnkaAtoiAsd", // Server identifier
     *   "ip": "1.1.1.1", // Remote IP from which server was registered
     *   "created": "2014-04-30T11:26-0400", // Registration time
     *   },
     *   // Will return an empty list if no servers found
     * ]
     *
     * @param $key
     * @return Server[]
     * @throws \Exception
     */
    public function getServersByKey($key)
    {
        $this->path = '/srv/list.' . self::DATA_TYPE_JSON;
        $response = $this->call(array(
            'key' => $key,
        ));

        $data = $response->getData();

        foreach ($data as &$row) {
            $row = new Server($row);
        }

        return $data;
    }

    /**
     * Generate new license key
     *
     * API response {
     *  "data": "LICENSE KEY",  // For IM360 data[key]
     *  "success": BOOLEAN
     * }
     * @param AbstractPackage $package
     * @param string $description
     * @return string
     * @throws \Exception
     */
    public function createKey(AbstractPackage $package, $description = '')
    {
        $this->path = '/key/create.' . self::DATA_TYPE_JSON;
        $data = $this->callAndCheck([
            'limit' => $package->getKeyLimit(),
            'code' => $this->clnLicense->getApiLicenseId(),
            'description' => $description,  // not passed
        ])->getData();

        return isset($data['key']) ? $data['key'] : $data;
    }

    /**
     * Delete license key
     *
     * API response {
     *  "data": BOOLEAN,
     *  "success": BOOLEAN
     * }
     * @param $key
     * @return ApiResponse
     * @throws \Exception
     */
    public function removeKey($key)
    {
        $this->path = '/key/remove.' . self::DATA_TYPE_JSON;
        return $this->callAndCheck(array(
            'key' => $key,
        ));
    }
}
