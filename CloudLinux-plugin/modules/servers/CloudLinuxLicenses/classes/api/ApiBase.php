<?php
/**
 * Created by PhpStorm.
 * User: R. Rakhmanberdiev
 * Date: 9/26/14
 * Time: 12:34 PM
 */

namespace CloudLinuxLicenses\classes\api;


use CloudLinuxLicenses\classes\api\licenses\ClnLicense;

abstract class ApiBase {
    /**
     * @var string
     */
    const DATA_TYPE_JSON = 'json';

    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $requestUrl;
    /**
     * Request arguments
     * @var array
     */
    protected $arguments;
    /**
     * @var ClnLicense
     */
    protected $clnLicense;
    /**
     * @var ApiResponse
     */
    private $response;
    /**
     * @var ApiConfiguration
     */
    private $configuration;

    public function __construct(ApiConfiguration $configuration, ClnLicense $license)
    {
        $this->configuration = $configuration;
        $this->clnLicense = $license;
    }

    /**
     * @return ClnLicense
     */
    public function getClnLicense() {
        return $this->clnLicense;
    }

    /**
     * @param array $params
     * @return ApiResponse
     * @throws \Exception
     */
    public function call($params = array())
    {
        $params['token'] = $this->getToken();

        $ch = curl_init();
        $this->arguments = $params;
        $this->requestUrl = $this->url . $this->path . '?'. http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);

        if ($this->configuration->getDebug() && function_exists('logModuleCall')) {
            logModuleCall(
                'CloudLinux Licenses', $this->url, print_r($params, true), '', print_r($response, true),
                array($this->configuration->getUsername(), $this->configuration->getPassword())
            );
        }

        if ($response === false) {
            $err = ucwords(curl_error($ch));
            $err = $err ?: "Unable connect to: {$this->url}";
            curl_close($ch);

            throw new \Exception($err);
        }
        curl_close($ch);

        $this->parseResponse($response);

        return $this->response;
    }

    /**
     * @param array $params
     * @param string $errorMessage
     * @return ApiResponse
     * @throws \Exception
     */
    public function callAndCheck($params = array(), $errorMessage = '')
    {
        $response = $this->call($params);

        if (!$response->getStatus()) {
            $message = $errorMessage ?: $response->getMessage();
            throw new \RuntimeException($message);
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getToken() {
        $now = time();
        $sha1 = sha1($this->configuration->getPassword() . $now);

        return "{$this->configuration->getUsername()}|$now|$sha1";
    }

    /**
     * @param $response
     * @return ApiResponse
     * @throws \Exception
     */
    private function parseResponse($response)
    {
        $this->response = new ApiResponse();
        $this->response->raw = $response;
        $this->response->parsed = json_decode($response, true);

        return $this->response;
    }
}