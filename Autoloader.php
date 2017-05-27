<?php
class Autoloader
{
    protected $appFolder = '';
    protected $namespaces = array();

    // Autoloader
    // Class: Namespace_Class_Name => Namespace/ClassName
    function autoload($className)
    {
        $classFile = null;
        $baseFolder = $this->appFolder;

        $config = Config::getInstance();

        $lastNamespace = '';

        foreach ($this->namespaces as $namespace => $namespaceFolder)
        {
            if (self::startsWith($className, $namespace) && strlen($namespace) > strlen($lastNamespace))
            {
                $classFile = $namespaceFolder . DIRECTORY_SEPARATOR . substr($className, strlen($namespace)+1) . '.php';
                $lastNamespace = $namespace;
            }
        }

        if (!empty ($classFile) && file_exists($classFile))
            require_once $classFile;
    }

    function __construct($rootDir)
    {
        $this->appFolder = $rootDir;
    }

    function addClassType($type, $folder)
    {
        $this->namespaces[$type] = $folder;
    }

    function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function loadControllerClass($controllerName)
    {
        $config = Config::getInstance();

        $controllersFolder = $config->getOption('router/controllersFolder', '/Controllers');

        $controllerPrefix = $config->getOption('router/prefix/controller');
        $controllerSuffix = $config->getOption('router/suffix/controller', 'Controller');

        $filePrefix = $config->getOption('router/prefix/controllerFile');
        $fileSuffix = $config->getOption('router/suffix/controllerFile');

        $controllerClassFile = ucfirst(Oxygen_Utils::convertUriToAction($controllerName, $filePrefix, $fileSuffix));

        $classFile = $this->appFolder . $controllersFolder . DIRECTORY_SEPARATOR . $controllerClassFile . '.php';

        if (!empty ($classFile) && file_exists($classFile))
            require_once $classFile;
    }

    protected static function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }
}