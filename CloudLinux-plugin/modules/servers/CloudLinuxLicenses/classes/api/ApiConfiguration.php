<?php

namespace CloudLinuxLicenses\classes\api;


class ApiConfiguration
{
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param string $username
     * @param string $password
     * @param bool $debug
     */
    public function __construct($username, $password, $debug = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function getDebug() {
        return $this->debug;
    }
}