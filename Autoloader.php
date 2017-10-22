<?php
class Autoloader
{
    protected $appFolder = '';
    protected $namespaces = array();

    /**
     * Main autoloader function
     * load the class 'Namespace_Class_Name' by require_once('Namespace/ClassName.php')
     *
     * @param string $className
     */
    public function autoload($className)
    {
        $classFile = null;

        $config = Config::getInstance();

        foreach ($this->namespaces as $namespace => $namespaceFolder)
        {
            if (substr($className, 0, strlen($namespace)) === $namespace)
            {
                // Remove the namespace and starting underscore in class name
                $classNameWithoutNamespace = substr($className, strlen($namespace)+1);

                // Try the to load class file at the namespace root folder
                $classFile = $namespaceFolder . DIRECTORY_SEPARATOR . $classNameWithoutNamespace;

                if (file_exists($classFile.'.php'))
                {
                    $classFile .= '.php';
                    break;
                }
                else
                {
                    // Replace all underscores by directoriy separators and find the class file
                    $classFile = $namespaceFolder . DIRECTORY_SEPARATOR
                        . str_replace('_', DIRECTORY_SEPARATOR, $classNameWithoutNamespace);

                    if (file_exists($classFile.'.php'))
                    {
                        $classFile .= '.php';
                        break;
                    }
                }
            }

            // This is not the wanted namespace, continue...
            $classFile = '';
        }

        if (!empty($classFile))
            require_once $classFile;
    }

    public function __construct($rootDir)
    {
        $this->appFolder = $rootDir;
    }

    /**
     * Add a new autoloaded class
     *
     * @param string $type
     *      Namespace of the class
     * @param string $folder
     *      Where are stored that class type
     */
    public function addClassType($type, $folder)
    {
        $this->namespaces[$type] = $folder;
    }

    /**
     * Get the folder (relative to application) of a specified namespace
     *
     * @param string $type
     * @return boolean|string
     */
    public function getClassFolder($type)
    {
        return isset($this->namespaces[$type]) ? $this->namespaces[$type] : false;
    }

    /**
     * Register our autoloader
     */
    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Load a controller class from the specified className
     *
     * @param string $controllerName
     */
    public function loadControllerClass($controllerName)
    {
        $config = Config::getInstance();

        $controllersFolder = $config->getOption('router/controllersFolder');

        $controllerPrefix = $config->getOption('router/prefix/controller');
        $controllerSuffix = $config->getOption('router/suffix/controller');

        $filePrefix = $config->getOption('router/prefix/controllerFile');
        $fileSuffix = $config->getOption('router/suffix/controllerFile');

        $controllerClassFile = ucfirst(Oxygen_Utils::convertSeparatorToUcLetters($controllerName, $filePrefix, $fileSuffix));

        $classFile = $this->appFolder . $controllersFolder . DIRECTORY_SEPARATOR . $controllerClassFile . '.php';

        if (!empty ($classFile) && file_exists($classFile))
            require_once $classFile;
    }
}