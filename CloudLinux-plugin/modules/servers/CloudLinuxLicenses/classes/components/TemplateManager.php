<?php


namespace CloudLinuxLicenses\classes\components;


class TemplateManager extends Component {
    const ASSETS_URL = 'modules/servers/CloudLinuxLicenses/templates/assets/';

    /**
     * Path to templates
     * @var string
     */
    protected $templateDirectory;

    /**
     * @var array
     */
    protected $lang;
    /**
     * @var array
     */
    protected $result = [];
    /**
     * @var string
     */
    protected $url;

    /**
     *
     */
    public function __construct()
    {
        $this->templateDirectory = ROOTDIR . DS
            . implode(DS, ['modules', 'servers', 'CloudLinuxLicenses', 'templates']);
    }

    /**
     * @return object
     * @throws \RuntimeException
     */
    public function getWHMCS()
    {
        global $whmcs;

        if (!$whmcs) {
            throw new \RuntimeException('Can\'t get WHMCS object');
        }
        return $whmcs;
    }

    /**
     * @return \Smarty
     * @throws \RuntimeException
     */
    public function getSmarty()
    {
        global $smarty;

        if (!$smarty) {
            throw new \RuntimeException('Can\'t get Smarty object');
        }
        return $smarty;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getLayoutPath($path)
    {
        $path = dirname($path) . DS . 'layout.php';
        return file_exists($path) ? $path : '';
    }

    /**
     * @param string $viewName
     * @param array $values
     * @param bool $return
     * @return string
     * @throws \RuntimeException
     */
    public function render($viewName, $values = [], $return = false)
    {
        $values['renderer'] = $this;
        $values['lang'] = $this->lang;

        $path = $this->templateDirectory . DS . $viewName . '.php';

        if (!file_exists($path)) {
            throw new \RuntimeException('Template file not found');
        }

        ob_start();
        extract($values, EXTR_OVERWRITE);
        require $path;
        $content = ob_get_contents();
        ob_end_clean();

        if ($layoutPath = $this->getLayoutPath($path)) {
            ob_start();
            require $layoutPath;
            $content = ob_get_contents();
            ob_end_clean();
        }

        if ($return) {
            return $content;
        }

        echo $content;
    }

    /**
     * @param string $filename
     */
    public function renderScript($filename)
    {
        echo '<script src="' . $this->getAssetsUrl() . $filename . '.js"></script>';
    }

    /**
     * @param string $filename
     */
    public function renderStyle($filename)
    {
        echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssetsUrl() . $filename . '.css" />';
    }

    /**
     * @param array $files
     */
    public function renderScripts($files = [])
    {
        foreach ($files as $file) {
            $this->renderScript($file);
        }
    }

    /**
     * @param array $files
     */
    public function renderStyles($files = [])
    {
        foreach ($files as $file) {
            $this->renderStyle($file);
        }
    }

    /**
     * @param $params
     */
    public function setLanguage($params)
    {
        global $CONFIG;

        if (!empty($_SESSION['Language'])) {
            $language = strtolower($_SESSION['Language']);
        } else if (strtolower($params['clientsdetails']['language']) !== '') {
            $language = strtolower($params['clientsdetails']['language']);
        } else {
            $language = $CONFIG['Language'];
        }

        $filename = $this->templateDirectory . DS . '..' . DS . 'lang' . DS . $language . '.php';
        if (!file_exists($filename)) {
            $filename = $this->templateDirectory . DS . '..' . DS . 'lang' . DS . 'english.php';
        }

        require $filename;
        $this->lang = isset($lang) ? $lang : array();
    }

    /**
     * @return array
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param integer $id
     * @return array
     */
    public function getResult($id)
    {
        return isset($this->result[$id]) ? $this->result[$id] : [];
    }

    /**
     * @return string
     */
    public function getAssetsUrl()
    {
        return isset($_GET['module']) ? '../' . self::ASSETS_URL : self::ASSETS_URL;
    }
}