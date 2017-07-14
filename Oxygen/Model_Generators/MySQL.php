<?php

class Oxygen_Model_Generator_MySQL
{
    protected $db = NULL;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getTables()
    {
        $result = array();

        // We use the fetch num because the field is named 'Tables_in_DATABASE_NAME'
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);
        $res = $this->db->prepare("show tables");
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $res->execute();

        while (($row = $res->fetch()))
        {
            $result[] = $row[0];
        }

        return $result;
    }

    public function getFieldsList($tableName)
    {
        $result = array();

        $res = $this->db->prepare("show columns from ".$tableName);
        $res->execute();

        while (($row = $res->fetch()))
        {
            $fieldName = $row['Field'];
            $fieldType = '';

            if (strpos($row['Type'], 'int') !== false)
                $fieldType = 'int';

            if (strpos($row['Type'], 'text') !== false)
                $fieldType = 'str';

            $result[$fieldName] = $fieldType;
        }

        return $result;
    }
}
?>