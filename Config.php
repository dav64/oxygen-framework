<?php
class Config
{
    CONST CONFIG_NODES_SEPARATOR = '/';

    protected static $_instance;
    protected $_options = array();

    public static function getInstance()
    {
        if (self::$_instance == null)
            self::$_instance = new self();

        return self::$_instance;
    }

    public static function loadConfig($options)
    {
        self::$_instance->_options = $options;
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

    public function setOption($name, $value)
    {
        $path = explode(self::CONFIG_NODES_SEPARATOR, $name);

        $temp = &$this->_options;
        foreach($path as $key) {
            $temp = &$temp[$key];
        }
        $temp = $value;
        unset($temp);
    }
}