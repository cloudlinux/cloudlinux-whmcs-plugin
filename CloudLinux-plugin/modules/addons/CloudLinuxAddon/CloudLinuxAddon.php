<?php

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
include_once ROOTDIR . DS .'modules'.DS .'servers'.DS .'CloudLinuxLicenses'.DS. 'CloudLinuxLoader.php';

function CloudLinuxAddon_config()
{
    return [
        'name' => 'CloudLinux Licenses Addon',
        'description' => 'CloudLinux Licenses Addon For WHMCS helps you to automatically link any number of the 
            products and sell them in the packets for your clients.',
        'version' => '1.3.4',
        'author' => '<a href="https://www.cloudlinux.com/" target="_blank">CloudLinux</a>',
        'fields' => [],
    ];
}

function CloudLinuxAddon_activate()
{
    return \CloudLinuxLicenses\classes\components\Addon::model()->execute('activate');
}

function CloudLinuxAddon_deactivate()
{
    return \CloudLinuxLicenses\classes\components\Addon::model()->execute('deactivate');
}

/**
 * Admin area output
 * @param array $params
 */
function CloudLinuxAddon_output($params)
{
    $template = new \CloudLinuxLicenses\classes\components\TemplateManager();
    $controller = new \CloudLinuxLicenses\classes\controllers\AddonController($template, $params['modulelink']);
    $controller->run();
}
