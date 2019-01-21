<?php


namespace CloudLinuxLicenses\classes\api\responseModels;


class AbstractResponseModel extends \stdClass
{
    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }

        return $this->{$name};
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->{$name});
    }

    /**
     * AbstractResponseModel constructor.
     * @param $data
     */
    public function __construct($data)
    {
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    public function toDate($value)
    {
        return $value ? fromMySQLDate($value) : '';
    }
}