<?php
/**
 * Created by PhpStorm.
 * User: R. Rakhmanberdiev
 * Date: 10/6/14
 * Time: 11:49 PM
 */

namespace CloudLinuxLicenses\classes\api;


class ApiResponse {
    /**
     * @var string
     */
    public $raw = '';
    /**
     * @var array
     */
    public $parsed = array();

    /**
     * Getter method
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->{$name})) {
            return $this->{$name};
        } elseif(method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)}();
        }
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return isset($this->parsed['success']) && ($this->parsed['success'] === true);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return isset($this->parsed['message']) ? $this->parsed['message'] : '';
    }

    /**
     * @param string $message
     */
    private function setMessage($message)
    {
        $this->parsed['message'] = $message;
    }

    /**
     * @return array()
     */
    public function getData()
    {
        return $this->parsed['data'];
    }
} 