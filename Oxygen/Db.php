<?php

class Oxygen_Db
{
    private static $registeredAdapters = array();
    private static $adaptersList = array();

    public static function getDefaultAdapter()
    {
        return self::getAdapter('default');
    }

    public static function getAdapter($adapterName)
    {
        $adaptersList = self::$adaptersList;

        if (!empty(self::$registeredAdapters[$adapterName]))
            return self::$registeredAdapters[$adapterName];
        else if (isset($adaptersList[$adapterName]))
        {
            try {
                self::$registeredAdapters[$adapterName] =
                    new PDO(
                        $adaptersList[$adapterName]['dsn'],
                        $adaptersList[$adapterName]['user'],
                        $adaptersList[$adapterName]['password']
                    );
                self::$registeredAdapters[$adapterName]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                return self::$registeredAdapters[$adapterName];
            }
            catch (Exception $e)
            {
                error_log('Cannot open connection to DSN '.$adaptersList[$adapterName]['dsn']);
            }
        }
        else
            error_log('Adapter "'.$adapterName.'" not found');
    }

    public static function loadAdapters($adaptersList)
    {
        self::$adaptersList = $adaptersList;
    }
}