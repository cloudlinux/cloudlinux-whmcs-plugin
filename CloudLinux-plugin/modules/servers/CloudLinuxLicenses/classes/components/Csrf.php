<?php

namespace CloudLinuxLicenses\classes\components;


class Csrf
{
    /**
     * Don't use name 'token' to avoid conflict with built-in whmsc token
     */
    const TOKEN_NAME = 'cl_csrf_token';

    /**
     * @return string
     */
    public static function get()
    {
        $field = self::getTokenField();
        if (!isset($_SESSION[$field])) {
            $_SESSION[$field] = md5(uniqid(mt_rand(). time() . 'some_salt', false));
        }

        return $_SESSION[$field];
    }

    /**
     * @return \stdClass
     */
    public static function data()
    {
        return (object) [
            'field' => self::getTokenField(),
            'value' => self::get(),
        ];
    }

    /**
     * @return string
     */
    public static function getTokenField()
    {
        return self::TOKEN_NAME;
    }

    /**
     * @return string
     */
    public static function render()
    {
        return '<input type="hidden" name="' . self::getTokenField() . '" value="' . self::get() . '">';
    }

    /**
     * @throws \Exception
     */
    public static function check()
    {
        if ($_POST[self::getTokenField()] !== self::get()) {
            throw new \InvalidArgumentException('Invalid token');
        }

        if (isset($_SESSION[self::getTokenField()])) {
            unset($_SESSION[self::getTokenField()]);
        }
    }
}
