<?php


namespace CloudLinuxLicenses\classes\components;


use CloudLinuxLicenses\classes\models\AbstractPackage;
use CloudLinuxLicenses\classes\models\AbstractService;
use CloudLinuxLicenses\classes\models\ServiceAddon;

class TemplateClient extends TemplateManager
{
    /**
     * @var array
     */
    private $params;

    /**
     * TemplateClient constructor.
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct();

        $this->setLanguage($params);
        $this->params = $params;
        $this->templateDirectory = ROOTDIR . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, ['modules', 'servers', 'CloudLinuxLicenses', 'templates', 'client']);
        $this->setUrl('clientarea.php?action=productdetails&id=' . (int) $_REQUEST['id']);
    }

    public function index()
    {
        $whmcs = $this->getWHMCS();
        // For some reason v7.2 runs this function within admin area for the product with addon
        if ($whmcs && $whmcs->isAdminAreaRequest()) {
            return '';
        }

        $service = AbstractService::getByParams($this->params);
        if ($service instanceof ServiceAddon) {
            $this->setUrl($this->getUrl() . '#tabAddons');
        }
        $package = AbstractPackage::getByParams($this->params);
        $api = $package->getApi();

        if ($_POST) {
            try {
               $this->indexPost($service, $package, $api);
            } catch (\Exception $e) {
                $this->result[$service->id] = ['error' => true, 'message' => $e->getMessage()];
            }
        }

        try {
            if ($package->isKeyBased()) {
                $key = $service->getLicenseKey();

                if ($key) {
                    $serverList = [];
                    $template = 'licenseTable_KeyBased_';
                    $template .= $package->isImunify() ? 'Imunify360' : 'KernelCare';
                    $keyModel = $api->getKey($key);

                    if ($keyModel) {
                        $serverList = $api->getServersByKey($key);
                    }

                    return $this->render($template, array(
                        'keyModel' => $keyModel,
                        'serverList' => $serverList,
                        'package' => $package,
                        'id' => $service->id,
                        'csrfField' => Csrf::render(),
                        'clnLicense' => $api->getClnLicense(),
                    ), true);
                }
            } else {
                $ip = $service->getDedicatedIP();
                $license = $api->getLicense($ip);

                if ($license) {
                    $server = $api->getServer($ip);
                    return $this->render('licenseTable_IPBased', [
                        'license' => $license,
                        'server' => $server,
                        'package' => $package,
                        'id' => $service->id,
                        'csrfField' => Csrf::render(),
                        'clnLicense' => $api->getClnLicense(),
                    ], true);
                }
            }
        } catch (\Exception $e) {
            return $this->render('error', [
                'message' => $e->getMessage(),
            ], true);
        }
    }

    private function indexPost(AbstractService $service, AbstractPackage $package, $api)
    {
        if (isset($_POST['sid']) && (int) $_POST['sid'] !== $service->id) {
            return;
        }

        if (!$service->isActive()) {
            throw new \LogicException('Service is not active');
        }

        if (isset($_POST['method'])) {
            if ($_POST['method'] === 'newLicenseKey' && $package->isKeyBased()) {
                $licenseKey = $_POST['value'];
                if (!$api->checkKey($licenseKey)) {
                    throw new \InvalidArgumentException(sprintf('Key "%s" not found', $licenseKey));
                }
                Csrf::check();
                $service->setCustomField(AbstractPackage::CUSTOM_FIELD_KEY, $licenseKey);
                $this->result[$service->id] = ['error' => false, 'message' => $this->lang['key_has_been_changed']];
            }

            if ($_POST['method'] === 'newIPAddress' && !$package->isKeyBased()) {
                $api->changeIP($_POST['value'], $service);
                $this->result[$service->id] = ['error' => false, 'message' => $this->lang['ip_has_been_changed']];
            }
        }
    }
}