<?php

class Generator_Exception extends Exception {};

class Oxygen_ModelsGenerator
{
    /* $special_tables format
          array(
            'table'                // Keep Table name as class
            'table' => 'className' // Set new className for this table
        );
    */
    CONST INDENTATION = '    ';
    CONST GENERATED_MODEL_PREFIX = 'Model_Generated_';

    public static function generateModels(
        $classPath,
        $generatedClassPath,
        $pdoAdapter = null,
        $adapterString = 'Oxygen_Db::getDefaultAdapter()',
        $special_tables = array()
    )
    {
        $db = $pdoAdapter !== null ? $pdoAdapter : Oxygen_Db::getDefaultAdapter();

        if (!$db)
            throw new Generator_Exception("Unable to connect to the database");

        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);

        $res = $db->prepare("show tables");

        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $res->execute();

        $tables = $res->fetchAll();

        if (!is_dir($generatedClassPath))
            mkdir($generatedClassPath, 0777, true);

        foreach($tables as $table)
        {
            $class = '<?php'."\n";

            $tableName = $table[0];

            if (in_array($tableName, array_keys($special_tables)) || substr($tableName, -1) != 's')
                $className = !empty($special_tables[$tableName]) ? $special_tables[$tableName] : Oxygen_Utils::convertToClassName($tableName);
            else
                $className = substr(Oxygen_Utils::convertToClassName($tableName), 0, -1);

            $name = $tableName;

            $res = $db->prepare("show columns from ".$tableName);
            $res->execute();
            $fields = $res->fetchAll();

            $last_field = end($fields);

            $class .= "class " . self::GENERATED_MODEL_PREFIX . ucfirst($className) . "\n{\n";

            foreach ($fields as $field)
            {
                $class .= str_repeat(self::INDENTATION, 1).'protected $'.Oxygen_Utils::convertToClassName($field['Field'])." = null;\n";
            }

            // Constructor
            $class .= "\n";
            $class .= str_repeat(self::INDENTATION, 1).'public function __construct($fieldsOrId = 0)'."\n";
            $class .= str_repeat(self::INDENTATION, 1)."{\n";
            $class .= str_repeat(self::INDENTATION, 2)."if (!empty(\$fieldsOrId) && is_numeric(\$fieldsOrId))\n";
            $class .= str_repeat(self::INDENTATION, 3)."\$this->load(\$fieldsOrId);\n";
            $class .= str_repeat(self::INDENTATION, 2)."else if (!empty(\$fieldsOrId) && is_array(\$fieldsOrId))\n";
            $class .= str_repeat(self::INDENTATION, 2)."{\n";
            $class .= str_repeat(self::INDENTATION, 3)."\$fields = \$fieldsOrId;\n\n";

            foreach ($fields as $field)
            {
                $class .= str_repeat(self::INDENTATION, 3).'$this->'.Oxygen_Utils::convertToClassName($field['Field']).
                    ' = isset($fields[\''.$field['Field'].'\']) ? $fields[\''.$field['Field'].'\'] : null;'."\n";
            }
            $class .= str_repeat(self::INDENTATION, 2)."}\n";
            $class .= str_repeat(self::INDENTATION, 2)."else \n";
            $class .= str_repeat(self::INDENTATION, 2)."{\n";

            foreach ($fields as $field)
            {
                $fieldValue = (strpos($field['Type'], 'int') !== false) ? '0' : "''";
                $class .= str_repeat(self::INDENTATION, 3).'$this->'.Oxygen_Utils::convertToClassName($field['Field'])." = {$fieldValue};\n";
            }
            $class .= str_repeat(self::INDENTATION, 2)."}\n";

            $class .= str_repeat(self::INDENTATION, 1)."}\n\n";

            // Save function
            $class .= str_repeat(self::INDENTATION, 1)."function save()\n    {\n";
            $class .= str_repeat(self::INDENTATION, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat(self::INDENTATION, 3).'INSERT INTO '.$tableName.' (';
            foreach ($fields as $field)
            {
                $class .= '`'.$field['Field'].'`';
                if ($field != $last_field)
                    $class .= ", ";
            }

            $class .= ')'."\n";
            $class .= str_repeat(self::INDENTATION, 3).'VALUES (';
            foreach ($fields as $field)
            {
                $class .= ':'.$field['Field'];
                if ($field != $last_field)
                    $class .= ", ";
            }
            $class .= ') ON DUPLICATE KEY UPDATE'."\n";
            foreach ($fields as $field)
            {
                $class .= str_repeat(self::INDENTATION, 3).'`'.$field['Field'].'` = VALUES('.$field['Field'].')';
                if ($field != $last_field)
                    $class .= ",";
                $class .= "\n";
            }
            $class .= str_repeat(self::INDENTATION, 2).'");'."\n\n";

            foreach ($fields as $field)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field['Field']);
                $fieldType = (strpos($field['Type'], 'int') !== false) ? ', PDO::PARAM_INT' : '';

                $class .= str_repeat(self::INDENTATION, 2).'$res->bindValue(\':'.$field['Field'].'\', $this->'.$camelCaseField.$fieldType.'); '."\n";
            }

            $class .= "\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res->execute();'."\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'if (empty($this->id))'."\n";
            $class .= str_repeat(self::INDENTATION, 3).'$this->id = $db->lastInsertId();'."\n";

            $class .= str_repeat(self::INDENTATION, 1).'}'."\n\n";

            // Load function
            $class .= str_repeat(self::INDENTATION, 1)."function load(\$id = 0)\n    {\n";
            $class .= str_repeat(self::INDENTATION, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat(self::INDENTATION, 3).'SELECT ';
            foreach ($fields as $field)
            {
                $class .= '`'.$field['Field'].'`';
                if ($field != $last_field)
                    $class .= ", ";
            }
            $class .= "\n";
            $class .= str_repeat(self::INDENTATION, 3).'FROM '.$tableName."\n";
            $class .= str_repeat(self::INDENTATION, 3).'WHERE id = :id'."\n";
            $class .= str_repeat(self::INDENTATION, 2).'");'."\n\n";

            $class .= str_repeat(self::INDENTATION, 2).'$res->bindValue(\':id\', !empty($id) ? $id : $this->id);'."\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res->execute();'."\n\n";

            $class .= str_repeat(self::INDENTATION, 2).'if ($row = $res->fetch())'."\n";
            $class .= str_repeat(self::INDENTATION, 2).'{'."\n";
            foreach ($fields as $field)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field['Field']);

                $class .= str_repeat(self::INDENTATION, 3).'$this->'.$camelCaseField.' = $row[\''.$field['Field'].'\'];'."\n";
            }
            $class .= str_repeat(self::INDENTATION, 2).'}'."\n";

            $class .= str_repeat(self::INDENTATION, 1).'}'."\n\n";

            // Delete function
            $class .= str_repeat(self::INDENTATION, 1)."function delete(\$id = 0)\n    {\n";
            $class .= str_repeat(self::INDENTATION, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat(self::INDENTATION, 3).'DELETE FROM '.$tableName."\n";
            $class .= str_repeat(self::INDENTATION, 3).'WHERE id = :id'."\n";
            $class .= str_repeat(self::INDENTATION, 2).'");'."\n\n";

            $class .= str_repeat(self::INDENTATION, 2).'$res->bindValue(\':id\', !empty($id) ? $id : $this->id);'."\n\n";
            $class .= str_repeat(self::INDENTATION, 2).'$res->execute();'."\n";

            $class .= str_repeat(self::INDENTATION, 1).'}'."\n\n";

            // Find function
            $findPrototype = <<<'EOM'
    /**
     * Find rows in database according to criterion
     *
     * $criterion array : find criterion as array(
     *      'select' => array('field1', 'field2')
     *      'where' => array('cond1', 'cond2')
     *      'other' => array('ORDER BY something')
     * )
     * $returnObjects bool : if true, rows will be returned as Model___CLASSNAME__ instances
     * */
    public static function find($criterion = array(), $returnObjects = false)
    {
        return Oxygen_Db::find(
            __ADAPTER__,
            '__TABLE__',
            'Model___CLASSNAME__',
            $criterion,
            $returnObjects
        );
    }
EOM;
            $class .= str_replace(
                array('__TABLE__', '__ADAPTER__', '__CLASSNAME__'),
                array($tableName, $adapterString, ucfirst($className)),
                $findPrototype
            )."\n\n";

            // Setters
            $class .= str_repeat(self::INDENTATION, 1).'// Setters'."\n";
            foreach ($fields as $field)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field['Field']);

                $class .= str_repeat(self::INDENTATION, 1).'function set'.ucfirst($camelCaseField).'($'.$camelCaseField.')'."\n";
                $class .= str_repeat(self::INDENTATION, 1).'{'."\n";
                $class .= str_repeat(self::INDENTATION, 2).'$this->'.$camelCaseField.' = $'.$camelCaseField.';'."\n";
                $class .= str_repeat(self::INDENTATION, 2).'return $this;'."\n";
                $class .= str_repeat(self::INDENTATION, 1).'}'."\n";
                if ($field != $last_field)
                    $class .= "\n";
            }

            $class .= "\n";

            // Getters
            $class .= str_repeat(self::INDENTATION, 1).'// Getters'."\n";
            foreach ($fields as $field)
            {
                $class .= str_repeat(self::INDENTATION, 1).'function get'.ucfirst(Oxygen_Utils::convertToClassName($field['Field'])).'()'."\n";
                $class .= str_repeat(self::INDENTATION, 1).'{'."\n";
                $class .= str_repeat(self::INDENTATION, 2).'return $this->'.Oxygen_Utils::convertToClassName($field['Field']).';'."\n";
                $class .= str_repeat(self::INDENTATION, 1).'}'."\n";
                if ($field != $last_field)
                   $class .= "\n";
            }

            // End Class bracket
            $class .= '}'."\n";

            // Writing base model file
            file_put_contents($generatedClassPath . ucfirst($className).'.php', $class);

            echo 'Generated Model '.ucfirst($className).'.php'."<br/>\n";

            // Create Model in model folder if not exists
            if (!file_exists($classPath . ucfirst($className).'.php'))
            {
                $model = "<?php\n";
                $model .= 'class Model_'.ucfirst($className).' extends ' . self::GENERATED_MODEL_PREFIX . ucfirst($className) . "\n";
                $model .= "{\n\n";
                $model .= "}\n";

                file_put_contents($classPath . ucfirst($className).'.php', $model);
            }
        }
    }
}