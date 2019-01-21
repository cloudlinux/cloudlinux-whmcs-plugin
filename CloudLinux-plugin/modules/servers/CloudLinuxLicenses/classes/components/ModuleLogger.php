<?php

namespace CloudLinuxLicenses\classes\components;

class ModuleLogger extends Component
{
    /**
     * @param $message
     * @param null|string $url
     */
    public function log($message, $url = null) {
        $link = $url ? sprintf('<a href="%s">Service</a>', $url) : '';

        if (function_exists('logModuleCall')) {
            logModuleCall('CloudLinux Licenses', $link ?: 'module error', '', '', $message, array());
        }
    }
}