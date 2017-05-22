<?php
class Autoloader
{
    protected $appFolder = '';
    protected $namespaces = array();

    // Autoloader
    // Class: Namespace_Class_Name => Namespace/ClassName
    // Controller: Class_Name => ClassNameController
    function autoload($className)
    {
        $classFile = null;
        $baseFolder = $this->appFolder;

        if ($this->endsWith($className, 'Controller'))
            $classFile = $baseFolder. DIRECTORY_SEPARATOR . 'Controllers'.DIRECTORY_SEPARATOR . substr($className, 0, -10) . '.php';
        else
        {
            $lastNamespace = '';

            foreach ($this->namespaces as $namespace => $namespaceFolder)
            {
                if ($this->startsWith($className, $namespace) && strlen($namespace) > strlen($lastNamespace))
                {
                    $classFile = $namespaceFolder . DIRECTORY_SEPARATOR . substr($className, strlen($namespace)+1) . '.php';
                    $lastNamespace = $namespace;
                }
            }
        }

        if (!empty ($classFile) && file_exists($classFile))
            include $classFile;
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

    private function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0)
            return true;

        return (substr($haystack, -$length) === $needle);
    }
}