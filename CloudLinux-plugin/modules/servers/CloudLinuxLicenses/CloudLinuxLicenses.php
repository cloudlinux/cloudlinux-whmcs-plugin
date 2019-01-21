<?php

include_once __DIR__ . DIRECTORY_SEPARATOR . 'CloudLinuxLoader.php';

/**
 * Config options are the module settings defined on a per product basis.
 * 
 * @return array
 */

function CloudLinuxLicenses_ConfigOptions() {
    $package = new \CloudLinuxLicenses\classes\models\Package();
    return $package->getConfig();
}

/**
 * This function is called when a new product is due to be provisioned.
 * 
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_CreateAccount($params) {
    try {
        $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
        $service->createAccount();
        return 'success';
    } catch (Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * This function is called when a termination is requested.
 * 
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_TerminateAccount($params) {
    try {
        $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
        $service->terminateAccount();
        return 'success';
    } catch (Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * This function is called when a suspension is requested.
 * 
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_SuspendAccount($params) {
    try {
        $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
        $service->suspendAccount();
        return 'success';
    } catch (Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * This function is called when an unsuspension is requested.
 * 
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_UnsuspendAccount($params) {
    try {
        $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
        $service->unSuspendAccount();
        return 'success';
    } catch (Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * This function is used to define additional fields
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_AdminServicesTabFields($params) {
    // For CSRF token
    @session_start();

    $url = "clientsservices.php?userid={$params['userid']}&id={$params['serviceid']}";
    $url .= isset($params['addonId']) ? '&aid=' . $params['addonId'] : '';

    try {
        $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
        $package = \CloudLinuxLicenses\classes\models\Addon::getByParams($params);
        $renderer = \CloudLinuxLicenses\classes\components\TemplateManager::model();
        $api = $package->getApi();
        $csrf = \CloudLinuxLicenses\classes\components\Csrf::data();

        // Change IP\Key query
        if (isset($_POST['action']) && !empty($_POST['ajax'])) {
            ob_clean();

            try {
                if (!$service->isActive()) {
                    throw new \LogicException('Service is not active');
                }

                switch ($_POST['action']) {
                    case 'changeIPAddress':
                        $api->changeIP($_POST['value'], $service);
                        echo 'success';
                        break;
                    case 'changeLicenseKey':
                        $newKey = $_POST['value'];
                        if (!$api->checkKey($newKey)) {
                            throw new Exception(sprintf('Key "%s" not found', $newKey));
                        }
                        $service->setCustomField(\CloudLinuxLicenses\classes\models\Package::CUSTOM_FIELD_KEY, $newKey);
                        echo 'success';
                        break;
                    default:
                        throw new \LogicException('Unknown function');
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            exit(0);
        }

        $tabLicense = '';
        $commands = '<div id="clCommands">';

        if ($package->isKeyBased()) {
            $template = 'admin/licenseTable_KeyBased_';
            $template .= $package->isImunify() ? 'Imunify360' : 'KernelCare';
            $key = $service->getLicenseKey();
            $commands .= '<input type="button" class="btn btn-primary" value="Change License Key" id="clChangeValue">
                <div id="clChangeValueDialog" style="display: none;">
                <br/>New License Key: <input type="text" id="clNewValueInput" style="width: 200px;"/></div>';

            if ($key) {
                $serverList = [];
                $keyModel = $api->getKey($key);

                if ($keyModel) {
                    $serverList = $api->getServersByKey($key);
                }

                $tabLicense = $renderer->render($template, array(
                    'keyModel' => $keyModel,
                    'key' => $key,
                    'serverList' => $serverList,
                    'package' => $package,
                    'url' => $url,
                    'csrf' => $csrf,
                    'clnLicense' => $api->getClnLicense(),
                ), true);
            }
        } else {
            $ip = $service->getDedicatedIP(false);
            $commands .= '<input type="button" class="btn btn-primary" value="Change IP Address" id="clChangeValue">
                <div id="clChangeValueDialog" style="display:none;">
                <br/>New IP Address: <input type="text" id="clNewValueInput" style="width: 200px;"/></div>';

            if (method_exists($api, 'getLicense')) {
                $license = $api->getLicense($ip);
            }

            if ($license) {
                $server = $api->getServer($ip);
                $tabLicense = $renderer->render('admin/licenseTable_IPBased', array(
                    'license' => $license,
                    'server' => $server,
                    'package' => $package,
                    'url' => $url,
                    'csrf' => $csrf,
                    'clnLicense' => $api->getClnLicense(),
                ), true);
            } else {
                $tabLicense = $renderer->render('admin/licenseTable_NoRecords', [
                    'package' => $package,
                    'url' => $url,
                    'csrf' => $csrf,
                ],true);
            }
        }

        $commands .= '</div>';

        return array(
            '' => $commands,
            'License Details' => $tabLicense ?: 'No Records Found',
        );
    } catch (\Exception $e) {
        echo '<div class="errorbox"><strong>ERROR</strong><br/>' . $e->getMessage() . '</div>';
    }

    return array();
}

/**
 * This function is used on Client Area
 * 
 * @param array $params
 * @return string
 */
function CloudLinuxLicenses_ClientArea($params) {
    $template = new \CloudLinuxLicenses\classes\components\TemplateClient($params);
    return $template->index();
}
