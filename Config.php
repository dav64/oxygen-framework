<?php
class Config
{
    CONST CONFIG_NODES_SEPARATOR = '/';

    // Project
    public static $pluginClass = 'Plugins';

    //---------------------------------------------------------------

    protected static $_instance;

    protected $_options = array(
        'routerSuffix' => array(
            'controller' => 'Controller',
            'action' => 'Action',
        ),
        'defaultRoute' => array(
            'controller' => 'index',
            'action' => 'index',
        ),
        'errorRoute' => array(
            'controller' => 'error',
            'action' => 'error',
        ),
        'view' => array(
            'folder' => '',
            'mainLayout' => 'layout.phtml',
        ),

        'plugins' => 'Plugins'
    );

/*
    TODO : Config: Json Loadable
 * {
    "Router" : {
        "controllerSuffix ": "Controller",
        "actionSuffix": "Action",
        "defaultController": "index',
        "defaultAction": "index',
        "errorController": "error',
        "errorAction": "error'
    }
    "View" : {
        "defaultExtension" : ".phtml",
        "viewFolder": ""
    }

    "pluginsClass": "Plugins";
}
 * Project->setConfig('router/defaultAction'); => $config['router']['defaultAction']
 *
 */

    public static function getInstance()
    {
        if (self::$_instance == null)
            self::$_instance = new self();

        return self::$_instance;
    }

    public function getOption($name, $defaultValue = null)
    {
        $path = explode(self::CONFIG_NODES_SEPARATOR, $name);
        $result = $defaultValue;
        $node = $this->_options;

        if (!empty($path))
        {
            $nodeFound = true;

            foreach ($path as $field)
            {
                if (isset($node[$field]))
                    $node = $node[$field];
                else
                {
                    $nodeFound = false;
                    break;
                }
            }

            if ($nodeFound)
                $result = $node;
        }

        return $result;
    }
}