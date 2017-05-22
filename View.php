<?php
class View_Exception extends Exception {}
class Helper_Exception extends Exception {}

class View
{
    CONST RENDER_PARTIAL = 1;
    CONST RENDER_LAYOUTS = 2;
    CONST RENDER_MAIN_LAYOUT = 4;

    CONST RENDER_ALL = 7;

    public static $viewsFolder = '';
    public static $defaultExt = '';

    protected $_content = '';

    protected static $helpers = array();
    protected static $mainLayout = NULL;

    protected $_viewFilename = '';

    protected $_layout = array();

    protected $_viewVars = array();
    protected $_enableRender = array(
        self::RENDER_PARTIAL => true,
        self::RENDER_LAYOUTS => true,
        self::RENDER_MAIN_LAYOUT => true
    );

    function __construct($viewFilename = '')
    {
        $this->_viewFilename = $viewFilename.self::$defaultExt;
        ob_start();
    }

    public static function setHelpers($helpers)
    {
        self::$helpers = $helpers;
    }

    public function __call($method, $args)
    {
        $helperClass = isset(self::$helpers[$method]) ? self::$helpers[$method] : null;

        if (!empty($helperClass) && class_exists($helperClass) && is_subclass_of($helperClass, 'Helper'))
        {
            $helper = new $helperClass($this);
            return call_user_func_array(array($helper, $method), $args);
        }
        else
            throw new Helper_Exception('Helper "'.$method.'" not found');
    }

    public function __get($name)
    {
        return isset($this->_viewVars[$name]) ? $this->_viewVars[$name] : null;
    }

    public function __set($name, $value)
    {
        if ($name[0] == '_')
            throw new View_Exception('trying to set reserved property');

        $this->_viewVars[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_viewVars[$name]);
    }

    public function __unset($name)
    {
        if ($name[0] == '_')
            throw new View_Exception('trying to unset reserved property');

        unset($this->_viewVars[$name]);
    }

    private function _setRender($type, $canRender)
    {
        foreach ($this->_enableRender as $renderType => $renderValue)
        {
            if ($renderType & $type)
                $this->_enableRender[$renderType] = $canRender;
        }
    }

    public function disableRender($type = self::RENDER_ALL)
    {
        $this->_setRender($type, false);
        return $this;
    }

    public function enableRender($type = self::RENDER_ALL)
    {
        $this->_setRender($type, true);
        return $this;
    }

    public function canRender($type = self::RENDER_ALL)
    {
        return isset($this->_enableRender[$type]) ? $this->_enableRender[$type] : null;
    }

    public static function setMainLayout($layoutFile)
    {
        self::$mainLayout = $layoutFile;
    }

    public function addLayout($layoutFile)
    {
        array_push($this->_layout, $layoutFile);
    }

    public function setViewFile($viewFile = '')
    {
        $this->_viewFilename = $viewFile;
    }

    public function render()
    {
        // Append any response done before render
        $ob_render = $this->_content = ob_get_clean();
        ob_start();

        try
        {
            Project::callPluginAction('beforeAddingLayout', array(&$this));

            if ($this->canRender(self::RENDER_PARTIAL))
            {
                if (!empty($this->_viewFilename))
                    $this->_content = $this->partial($this->_viewFilename).$ob_render;
            }

            if ($this->canRender(self::RENDER_LAYOUTS))
            {
                foreach ($this->_layout as $layoutFile) {
                    $this->_content = $this->partial($layoutFile, array('content' => $this->_content));
                }
            }

            if (!empty(self::$mainLayout) && $this->canRender(self::RENDER_MAIN_LAYOUT))
                $this->_content = $this->partial(self::$mainLayout, array('content' => $this->_content));

            Project::callPluginAction('beforeRender', array(&$this));

        }
        catch (Exception $e)
        {
            ob_get_clean();
            throw $e;
        }

        ob_get_clean();
        echo $this->_content;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    public function partial($template, array $parameters = array())
    {
        $config = Config::getInstance();

        $this->_viewVars = array_merge($this->_viewVars, $parameters);

        $viewFilename = self::$viewsFolder . DIRECTORY_SEPARATOR . $template;

        if (!is_readable($viewFilename))
            throw new View_Exception('Partial file "'.$viewFilename.'" cannot be found');

        // Include file in buffer and return it
        ob_start();
        include $viewFilename;
        return ob_get_clean();
    }

    // Simple Page template parse
    public function pparse($template, array $parameters = array(), $deleteNotFound = true)
    {
        array_merge($this->_viewVars, $parameters);

        $viewFilename = self::$viewsFolder . DIRECTORY_SEPARATOR . $template;

        if (!is_readable($viewFilename))
            throw new View_Exception('Parsed view "'.$viewFilename.'" cannot be found');

        $contents = file_get_contents($viewFilename);

        foreach($parameters as $parameter => $value)
        {
            $contents = str_replace('{'."$parameter".'}', $value, $contents);
        }

        if ($deleteNotFound)
            $contents = preg_replace('/{\w+}/', '', $contents); // My first written RegExp ^^

        return $contents;
    }
}