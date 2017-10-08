<?php
class Autoloader
{
    protected $appFolder = '';
    protected $namespaces = array();

    // Autoloader
    // Class: Namespace_Class_Name => Namespace/ClassName
    public function autoload($className)
    {
        $classFile = null;
        $baseFolder = $this->appFolder;

        $config = Config::getInstance();

        $lastNamespace = '';

        foreach ($this->namespaces as $namespace => $namespaceFolder)
        {
            if (substr($className, 0, strlen($namespace)) === $namespace && strlen($namespace) > strlen($lastNamespace))
            {
                $classFile = $namespaceFolder . DIRECTORY_SEPARATOR . substr($className, strlen($namespace)+1) . '.php';
                $lastNamespace = $namespace;
            }
        }

        if (!empty ($classFile) && file_exists($classFile))
            require_once $classFile;
    }

    public function __construct($rootDir)
    {
        $this->appFolder = $rootDir;
    }

    public function addClassType($type, $folder)
    {
        $this->namespaces[$type] = $folder;
    }

    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function loadControllerClass($controllerName)
    {
        $config = Config::getInstance();

        $controllersFolder = $config->getOption('router/controllersFolder');

        $controllerPrefix = $config->getOption('router/prefix/controller');
        $controllerSuffix = $config->getOption('router/suffix/controller');

        $filePrefix = $config->getOption('router/prefix/controllerFile');
        $fileSuffix = $config->getOption('router/suffix/controllerFile');

        $controllerClassFile = ucfirst(Oxygen_Utils::convertUriToAction($controllerName, $filePrefix, $fileSuffix));

        $classFile = $this->appFolder . $controllersFolder . DIRECTORY_SEPARATOR . $controllerClassFile . '.php';

        if (!empty ($classFile) && file_exists($classFile))
            require_once $classFile;
    }
}