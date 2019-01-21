<?php


namespace CloudLinuxLicenses\classes\components;


class Component
{
    /**
     * @var array
     */
    protected static $models = [];

    /**
     * @return $this
     */
    public static function model()
    {
        $className = get_called_class();

        if (!isset(self::$models[$className])) {
            self::$models[$className] = new static();
        }

        return self::$models[$className];
    }
}