<?php


class CloudLinux_AutoLoader {
    protected $prefixes = array();

    public function __construct()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $baseDir A base directory for class files in the
     * namespace.
     * @return void
     */
    public function addNamespace($prefix, $baseDir)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        $this->prefixes[$prefix][] = $baseDir;
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class)
    {
        $prefix = $class;

        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir
                . str_replace('\\', '/', $relativeClass)
                . '.php';

            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}

$loader = new CloudLinux_AutoLoader();
$loader->addNamespace('CloudLinuxLicenses', __DIR__);
