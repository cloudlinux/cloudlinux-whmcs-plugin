<?php

const SITE_URL = 'http://repo.cloudlinux.com/plugins/';

if (PHP_SAPI !== 'cli') {
    die('Command line execution only' . PHP_EOL);
}

chdir(__DIR__);

require __DIR__ . '/init.php';

BillingPlugin::deploy();

/*
 * end script
 */

class BillingPlugin
{
    protected $options;
    protected $link;

    protected function __construct()
    {
        $opts_short = 'h::u::f::m::';
        $opts_long = array(
            'help::',
            'user::',
            'forced::',
            'local::',
            'migrate::',
        );

        $this->options = getopt($opts_short, $opts_long);

        if ($this->issetOption('help')) {
            $this->showHelp();
        }
    }

    public static function deploy()
    {
        $billing = new WhmcsPlugin();

        if (!$billing->commandExists('unzip')) {
            die("Unzip command not found." . PHP_EOL . "Please install unzip" . PHP_EOL . PHP_EOL);
        };

        $versionFrom = $billing->getCurrentVersion();

        $versionTo = $billing->issetOption('local')
            ? $billing->getLocalVersionAndLink($billing->getOption('local'))
            : $billing->getLastVersionAndLink();

        // TODO: fix when will be use whole script
        // if ($billing->issetOption('migrate')) {
            $billing->migrate();
            die("Db migration performed. Files not copied" . PHP_EOL . PHP_EOL);
        // }

        if (version_compare($versionFrom, $versionTo) >= 0 && !$billing->issetOption('forced')) {
            die("CloudLinux plugin is already up-to-date." . PHP_EOL . PHP_EOL);
        }

        $billing->say('Will be installed last version: ' . $versionTo);

        $tmpName = '/tmp/clPlugin.zip';

        if ($billing->issetOption('local')) {
            $billing->copyFile($billing->link, $tmpName);
        } else {
            $billing->downloadFile($billing->link, $tmpName);
        }

        $result = $billing->unZip($tmpName);

        $billing->changeOwner($result);
        unlink($tmpName);

        if (!is_null($versionFrom)) {
            $billing->migrate();
        }

        if (is_null($versionFrom)) {
            $billing->say("Installed version $versionTo of CloudLinux plugin.\n");
        } else {
            $billing->say("CloudLinux plugin upgraded from version $versionFrom to version $versionTo\n");
        }

    }

    protected function say($msg)
    {
        echo $msg . PHP_EOL;
    }

    protected function showHelp()
    {
        $help = <<<HELP

Using deploy script:

Put this file to WHMCS web docroot directory and type in command line:
    php clDeploy.php

The script will download last KuberDock plugin to current directory and upgrade db if needed.

Possible keys:

--help, -h - print this help
    php clDeploy.php --help

--forced -f - Execute script even if current version is last.
    By default script stops if user has last version.

--user, -u - change owner of downloaded files (both commands beneath change owner to whmcs:whmcs)
    php clDeploy.php --user=whmcs
    php clDeploy.php --user=whmcs:whmcs

    Use this key only if you have write permissions!

--migrate, -m - Run database migrations

HELP;

        die($help);
    }

    protected function getOption($option, $default = null)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        $short = $option[0];
        if (isset($this->options[$short])) {
            return $this->options[$short];
        }

        return $default;
    }

    protected function issetOption($option)
    {
        $short = $option[0];
        return isset($this->options[$option]) || isset($this->options[$short]);
    }

    protected function getLocalVersionAndLink($local)
    {
        $this->link = $local;
        $regexp = "/whmcs-cl-plugin\-([\d\.\-]*)\.zip/";
        preg_match($regexp, $local, $match);
        $versionTo = $match ? $match[1] : 'local';
        $this->say('Will be installed local version: ' . $versionTo);

        return $versionTo;
    }

    protected function getLastVersionAndLink()
    {
        // get site
        $ch = curl_init(SITE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $site = curl_exec($ch);
        $status = curl_getinfo($ch);
        if ($status['http_code'] != 200) {
            die('Cannot download file ' . SITE_URL . PHP_EOL);
        }
        curl_close($ch);

        $regexp = "href=[\'\"](whmcs-cl-plugin\-([\d\.]*)\.zip)[\'\"]";

        // get last link
        $versionTo = null;
        if (preg_match_all("/$regexp/siU", $site, $matches)) {
            foreach ($matches[1] as $index => $currentUrl) {
                $versionTo = $this->getMax($versionTo, $matches[2][$index]);
                if ($versionTo==$matches[2][$index]) {
                    $this->link = SITE_URL . $currentUrl;
                }
            }
        }

        return $versionTo;
    }

    public function warningHandler($errno, $errstr)
    {
        // do nothing
    }

    protected function unZip($file)
    {
        $this->say('Unzip plugin');
        return shell_exec('unzip -o ' . $file);
    }

    protected function changeOwner($string)
    {
        if (!$this->issetOption('user')) {
            return;
        }

        $user = $this->getOption('user');

        if (!stripos($user, ':')) {
            $user = $user . ':' . $user;
        }

        $this->say('Change group:user to ' . $user);

        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'Archive:') === 0) {
                continue;
            }

            $line = preg_replace('/(inflating\:\s|extracting\:\s)/', '', $line);
            if (!$line) {
                continue;
            }

            shell_exec("chown $user $line");
        }
    }

    protected function getMax($a, $b)
    {
        $aArr = explode('.', $a);
        $bArr = explode('.', $b);

        for ($i=0; $i<=min(count($aArr), count($bArr)); $i++) {
            $aChunk = each($aArr);
            $bChunk = each($bArr);

            if ($bChunk === $aChunk) {
                continue;
            }

            return $bChunk > $aChunk
                ? $b
                : $a;
        }
    }

    protected function copyFile($url, $path)
    {
        $file = file_get_contents($url);
        file_put_contents($path, $file);
    }

    protected function downloadFile($url, $path)
    {
        $this->say('Download plugin from ' . $url);

        $ch = curl_init($url);
        $fp = fopen($path, 'wb');
        if (!$fp) {
            die("Cannot write file: $path" . PHP_EOL);
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);

        if (curl_errno($ch)) {
            die('Cannot download file: ' . curl_error($ch) . PHP_EOL);
        }

        curl_close($ch);

        fclose($fp);
    }

    protected function commandExists($cmd)
    {
        return (bool) shell_exec("which $cmd 2>/dev/null");
    }
}

class WhmcsPlugin extends BillingPlugin
{
    /**
     * Returns null if this is first installation, otherwise string like '1.0.7.3'
     *
     * @return null|string
     */
    public function getCurrentVersion()
    {
        set_error_handler(array($this, "warningHandler"), E_WARNING);
        if ((include __DIR__ . '/modules/addons/CloudLinuxAddon/CloudLinuxAddon.php') === false) {
            $this->say('CloudLinux plugin not installed. Performing installation');
            return null;
        }
        restore_error_handler();

        $config = CloudLinuxAddon_config();

        $this->say('Current version is ' . $config['version']);
        return $config['version'];
    }

    public function migrate()
    {
        $this->say('Performing DB migration');
        try {
            \CloudLinuxLicenses\classes\migrations\Migration::up();
        } catch (Exception $e) {
            die('DB migration not possible: ' . $e->getMessage() . PHP_EOL);
        }
    }
}