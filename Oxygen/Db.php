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
                $dsn = isset($adaptersList[$adapterName]['dsn']) ? $adaptersList[$adapterName]['dsn'] : '';
                $login = isset($adaptersList[$adapterName]['user']) ? $adaptersList[$adapterName]['user'] : '';
                $password = isset($adaptersList[$adapterName]['password']) ? $adaptersList[$adapterName]['password'] : '';

                self::$registeredAdapters[$adapterName] = new PDO($dsn, $login, $password);
                self::$registeredAdapters[$adapterName]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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

        return false;
    }

    public static function loadAdapters($adaptersList)
    {
        self::$adaptersList = $adaptersList;
    }

    public static function find($db, $table, $class, $criterion = array(), $returnObjects = false)
    {
        $result = array();

        if (is_array($criterion))
        {
            $select = '';
            $from = '';
            $where = '';
            $other = '';

            if (empty($criterion['from']))
                $criterion['from'] = $table;

            if (empty($criterion['select']))
                $criterion['select'] = '*';

            $select = is_array($criterion['select']) ? implode(',', $criterion['select']) : $criterion['select'];
            $from = is_array($criterion['from']) ? implode(',', $criterion['from']) : $criterion['from'];

            if (!empty($criterion['where']))
                $where = 'WHERE ('. (is_array($criterion['where']) ? implode(') AND (', $criterion['where']) : $criterion['where']). ')';

            if (!empty($criterion['other']))
                $other = is_array($criterion['other']) ? implode("\n", $criterion['other']) : $criterion['other'];


            $query = 'SELECT '.$select.'
                      FROM '.$from.'
                      '.$where.'
                      '.$other;

            $res = $db->prepare($query);

            if (!empty($criterion['bind']))
            {
                foreach ($criterion['bind'] as $param => $value)
                {
                    $res->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }

            $res->execute();

            while ($row = $res->fetch())
            {
                if ($returnObjects)
                    $result[] = new $class($row);
                else
                    $result[] = $row;
            }
        }

        return $result;
    }
}