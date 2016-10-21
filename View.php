<?php
class View
{
    CONST VIEW_RENDER_BRACKETS_TAGS = 1;
    CONST VIEW_RENDER_PHP = 2;

    public static $viewsFolder = '';
    public static $fileTypes = array();

    public static $defaultExt = '.phtml';

    protected static $helpers = array();
    protected static $mainLayout = NULL;

    protected $_viewFilename = '';

    protected $_layout = array();

    protected $_viewVars = array();
    protected $_enableRender = true;

    function __construct($viewFilename = '')
    {
        $this->_viewFilename = $viewFilename.self::$defaultExt;
        ob_start();
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
            throw new MVC_Exception('Helper "'.$method.'" not found');
    }

    public static function registerHelper($method, $class)
    {
        self::$helpers[$method] = $class;
    }

    public function __get($name)
    {
        return isset($this->_viewVars[$name]) ? $this->_viewVars[$name] : null;
    }

    public function __set($name, $value)
    {
        $this->_viewVars[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_viewVars[$name]);
    }

    public function __unset($name)
    {
        unset($this->_viewVars[$name]);
    }

    public function disableRender($disable = true)
    {
        $this->_enableRender = !$disable;
    }

    public function canRender()
    {
        return $this->_enableRender;
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
        Project::callPluginAction('beforeAddingLayout', array(&$this));

        // Append any response done before render
        $ob_render = $this->content = ob_get_clean();

        if ($this->_enableRender)
        {
            if (!empty($this->_viewFilename))
                $this->content = $this->partial($this->_viewFilename).$ob_render;

            foreach ($this->_layout as $layoutFile) {
                $this->content = $this->partial($layoutFile, array('content' => $this->content));
            }
        }

        if (!empty(self::$mainLayout))
            $this->content = $this->partial(self::$mainLayout, array('content' => $this->content));

        Project::callPluginAction('beforeRender', array(&$this));

        echo $this->content;
    }

    public function partial($template, array $parameters = array())
    {
        $this->_viewVars = array_merge($this->_viewVars, $parameters);

        // Include file in buffer and return it
        ob_start();
        include self::$viewsFolder . DIRECTORY_SEPARATOR . $template;
        return ob_get_clean();
    }

    public function pparse($template, array $parameters = array())
    {
        array_merge($this->_viewVars, $parameters);

        $contents = file_get_contents(self::$viewsFolder . DIRECTORY_SEPARATOR . $template);

        foreach($parameters as $parameter => $value)
        {
            $contents = str_replace('{'."$parameter".'}', $value, $contents);
        }

        // My first written RegExp ^^
        $contents = preg_replace('/{\w+}/', '', $contents);

        return $contents;
    }
}