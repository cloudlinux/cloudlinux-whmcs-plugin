<?php


namespace CloudLinuxLicenses\classes\api;


use CloudLinuxLicenses\classes\api\licenses\ClnLicense;
use CloudLinuxLicenses\classes\api\responseModels\Key;
use CloudLinuxLicenses\classes\api\responseModels\Server;
use CloudLinuxLicenses\classes\models\AbstractPackage;

class KeyBasedKernelCare extends ApiBase
{
    public function __construct(ApiConfiguration $configuration, ClnLicense $license)
    {
        parent::__construct($configuration, $license);
        $this->url = 'https://cln.cloudlinux.com/api/kcare';
    }

    /**
     * Check if key exists for current user
     *
     * @param string $key
     * @return bool - true if key exists
     * @throws \Exception
     */
    public function checkKey($key)
    {
        return $this->getKey($key) ? true : false;
    }

    /**
     * @param string $key
     * @return Key|null
     * @throws \Exception
     */
    public function getKey($key)
    {
        foreach ($this->getKeys()->getData() as $row) {
            if ($row['key'] === $key) {
                return new Key($row);
            }
        }

        return null;
    }

    /**
     * Get keys by token
     *
     * API response [{
     *   "key": "WsTs821nSAtiastD", // key identifier"enabled": false,
     *   "created": "2014-04-30T11:26-0400", // key creation time
     *   "limit": 2, // key servers max limit
     *   "note": "Some custom key note"
     *   },
     *   // Will return an empty list if user has no keys
     * ]
     *
     * @return ApiResponse
     * @throws \Exception
     */
    public function getKeys()
    {
        $this->path = '/key/list.' . self::DATA_TYPE_JSON;
        return $this->callAndCheck();
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
        $this->path = '/key/servers.' . self::DATA_TYPE_JSON;
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
            'note' => $description,
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
        $this->path = '/key/delete.' . self::DATA_TYPE_JSON;
        return $this->callAndCheck(array(
            'key' => $key,
        ));
    }
}
