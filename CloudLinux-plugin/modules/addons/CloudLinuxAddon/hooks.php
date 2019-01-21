<?php
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
include_once ROOTDIR . DS .'modules'.DS .'servers'.DS .'CloudLinuxLicenses'.DS. 'CloudLinuxLoader.php';

/**
 * Create a childs for the parent product
 * 
 * @param array $params
 */
add_hook('AfterModuleCreate', 1, function ($params) {
    $params = $params['params'];
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAfterModuleCreate($params);
});

/**
 * After Module Terminate
 * @param array $params
 */
add_hook('AfterModuleTerminate', 1, function ($params) {
    $params = $params['params'];
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAfterModuleTerminate($params);
});

/**
 * After Module Suspend
 * @param array $params
 */
add_hook('AfterModuleSuspend', 1, function ($params) {
    $params = $params['params'];
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAfterSuspendOrUnsuspend('suspend', $params);
});

/**
 * After Module UnSuspend
 * @param array $params
 */
add_hook('AfterModuleUnsuspend', 1, function ($params) {
    $params = $params['params'];
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAfterSuspendOrUnsuspend('unsuspend', $params);
});

/**
 * Addon Activation
 * @param array $params
 */
add_hook('AddonActivation', 1, function ($params) {
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAddonActivation($params);
});

/**
 * Addon Terminate
 * @param array $params
 */
add_hook('AddonTerminated', 1, function ($params) {
    $service = \CloudLinuxLicenses\classes\models\AbstractService::getByParams($params);
    $service->onAddonTerminated($params);
});

/**
 * Runs when an order is accepted prior to any acceptance actions being executed.
 * @param array $vars
 */
add_hook('AcceptOrder', 1, function($vars) {
    $order = \CloudLinuxLicenses\classes\models\Order::find($vars['orderid']);
    if ($order) {
        foreach ($order->services as $service) {
            /* @var $service \CloudLinuxLicenses\classes\models\Service */
            $service->configurableOptionsCreateLicense();
        }
    }
});

/**
 * Cancel Order
 * @param array $params
 */
function hookCloudLinuxAddonCancelOrder($params) {
    \CloudLinuxLicenses\classes\models\CLConnections::where('order_id', $params['orderid'])->delete();

    $order = \CloudLinuxLicenses\classes\models\Order::find($params['orderid']);
    if ($order) {
        foreach ($order->services as $service) {
            /* @var $service \CloudLinuxLicenses\classes\models\Service */
            $service->configurableOptionsTerminateLicense();
        }
    }
}
add_hook('DeleteOrder', 1, 'hookCloudLinuxAddonCancelOrder');
add_hook('CancelOrder', 1, 'hookCloudLinuxAddonCancelOrder');
add_hook('CancelAndRefundOrder', 1, 'hookCloudLinuxAddonCancelOrder');
add_hook('PendingOrder', 1, 'hookCloudLinuxAddonCancelOrder');
add_hook('FraudOrder', 1, 'hookCloudLinuxAddonCancelOrder');

/**
 * Delete Existing Connections
 * @param array $params
 */
add_hook('ClientDelete', 1, function ($params) {
    \CloudLinuxLicenses\classes\models\CLConnections::where('user_id', $params['userid'])->delete();
});

/**
 * Delete Existing Connections
 * @param array $params
 */
add_hook('ServiceDelete', 1, function ($params) {
    \CloudLinuxLicenses\classes\models\CLConnections::where('hosting_id', $params['serviceid'])
        ->where('user_id', $params['userid'])->get()->each(function ($model) {
            $model->delete();
        });
});

/**
 * After Shopping CartCheckout
 * @param type $params
 */
add_hook('AfterShoppingCartCheckout', 1, function ($params) {
    /* @var \CloudLinuxLicenses\classes\models\Service[] $services */
    $services = \CloudLinuxLicenses\classes\models\Service::whereIn('id', $params['ServiceIDs'])->get();

    foreach ($services as $row) {
        // Product relations
        $relations = \CloudLinuxLicenses\classes\models\CLFreeProductRelations::where('freeProductID', $row->packageid)->get();

        foreach ($relations as $relation) {
            $count = \CloudLinuxLicenses\classes\models\CLConnections::where('hosting_id', $row->id)
                ->where('product_id', $relation->licenseProductID)->count();
            if (!$count) {
                $row->createRelated($relation);
            }
        }
    }
});

/**
 * Executes after a product configurable options upgrade has been processed
 *
 * @param array $params array('upgradeid' => id)
 */
add_hook('AfterConfigOptionsUpgrade', 1, function ($params) {
    $model = \CloudLinuxLicenses\classes\models\Upgrades::find($params['upgradeid']);
    $model->afterUpgrade();
});

/**
 * Executes after a product upgrade has been processed
 *
 * @param array $params array('upgradeid' => id)
 */
add_hook('AfterProductUpgrade', 1, function ($params) {
    $model = \CloudLinuxLicenses\classes\models\Upgrades::find($params['upgradeid']);
    $model->afterUpgrade();
});

/**
 * Run after edit product config options in "Setup" => "Product/Services" => Product/Services
 *
 * @param $params array('pid' => productId)
 */
add_hook('AdminProductConfigFieldsSave', 1, function ($params) {
    $package = \CloudLinuxLicenses\classes\models\Package::find($params['pid']);
    $package->onSaveConfigFields();
});

/*
 * Executes as an addon is being saved.
 * */
add_hook('AddonConfigSave', 1, function ($params) {
    $addon = \CloudLinuxLicenses\classes\models\Addon::find($params['id']);
    $addon->onSaveConfigFields();
});

/**
 * Allows returning of output for display in the client area product details page.
 * @param $params array('service' => object)
 */
add_hook('ClientAreaProductDetailsOutput', 1, function ($vars) {
    /* @var $service \CloudLinuxLicenses\classes\models\Service */
    $renderer = \CloudLinuxLicenses\classes\components\TemplateManager::model();
    $renderer->setLanguage([]);
    $service = \CloudLinuxLicenses\classes\models\Service::find($vars['service']->id);
    $content = '';
    $ip = $service->getDedicatedIP();

    foreach ($service->getConfigurableOptions() as $option) {
        /* @var \CloudLinuxLicenses\classes\models\ConfigOption $e */
        if ($option->clConfigurableOption->count()) {
            foreach ($option->clConfigurableOption as $clOption) {
                $api = $clOption->package->getApi();

                if ($clOption->package->isKeyBased()) {
                    $fieldName = $api->getClnLicense()->getName();
                    $key = $service->getCustomField($fieldName);
                    $keyModel = $api->getKey($key);
                    $content .= $renderer->render('client/key_licenses', [
                        'key' => $key,
                        'keyModel' => $keyModel,
                        'servers' => $key ? $api->getServersByKey($key) : [],
                        'clnLicense' => $api->getClnLicense(),
                    ], true);
                } else {
                    $license = $api->getLicense($ip);
                    $content .= $renderer->render('client/ip_licenses', [
                        'ip' => $ip,
                        'status' => $license['registered'] ? 'registered' : 'unregistered',
                        'server' => $api->getServer($ip),
                        'clnLicense' => $api->getClnLicense(),
                    ], true);
                }
            }
        }
    }

    return $renderer->render('client/licenses', [
        'content' => $content,
    ], true);
});

/**
 * Executes as the service is being saved, before any changes have been made.
 * @param $params array('serviceid' => int)
 */
add_hook('PreServiceEdit', 1, function($vars) {
    /* @var \CloudLinuxLicenses\classes\models\Service $service */
    $service = \CloudLinuxLicenses\classes\models\Service::find($vars['serviceid']);
    $service->onPreEdit();
});

/**
 * Executes when the Service has been edited. After the changes have been made.
 * @param $params array('serviceid' => int, 'userid' => int)
 */
add_hook('ServiceEdit', 1, function($vars) {
    /* @var \CloudLinuxLicenses\classes\models\Service $service */
    $service = \CloudLinuxLicenses\classes\models\Service::find($vars['serviceid']);
    $service->onEdit();
});
