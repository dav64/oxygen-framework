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

    // TODO: set parameters as array ? (Not bother with optional parameters order)
    public static function generateModels(
        $classPath,
        $generatedClassPath,
        $adapterName = null,
        $special_tables = array(),
        $dbms = 'MySQL',
        $model_prefix = 'Model_',
        $generated_model_prefix = 'Model_Generated_',
        $indentation = '    '
    )
    {
        $db = $adapterName !== null ? Oxygen_Db::getAdapter($adapterName) : Oxygen_Db::getDefaultAdapter();
        $adapterString = $adapterName !== null ? 'Oxygen_Db::getAdapter(\''.$adapterName.'\')' : 'Oxygen_Db::getDefaultAdapter()';

        if (!$db)
            throw new Generator_Exception("Unable to connect to the database");

        if (!is_dir($generatedClassPath) || !is_writable($generatedClassPath))
            throw new Generator_Exception("Directory '". $generatedClassPath . "' must exist and be writtable");

        $generatorFilename = __DIR__ . '/Model_Generators/'.$dbms.'.php';

        $generatorClass = 'Oxygen_Model_Generator_'.$dbms;

        if (!class_exists($generatorClass))
        {
            if (!file_exists($generatorFilename))
                throw new Generator_Exception("Model generator not found for the '".$dbms."' database type");

            require $generatorFilename;
        }

        $generator = new $generatorClass($db);

        //if (!is_subclass_of($generator, 'DBMS'))
        //    throw new Generator_Exception("DBMS handler not correct");

        $tables = $generator->getTables();

        foreach($tables as $tableName)
        {
            $class = '<?php'."\n";

            // strip last 's' from table name (ie. table 'users' become class User)
            if (in_array($tableName, array_keys($special_tables)) || substr($tableName, -1) != 's')
                $className = !empty($special_tables[$tableName]) ? $special_tables[$tableName] : Oxygen_Utils::convertToClassName($tableName);
            else
                $className = substr(Oxygen_Utils::convertToClassName($tableName), 0, -1);

            $name = $tableName;

            $fields = $generator->getFieldsList($tableName);

            end($fields);
            $last_field = key($fields);

            $class .= "class " . $generated_model_prefix . ucfirst($className) . "\n{\n";

            foreach ($fields as $field => $fieldType)
            {
                $class .= str_repeat($indentation, 1).'protected $'.Oxygen_Utils::convertToClassName($field)." = null;\n";
            }

            // Constructor
            $class .= "\n";
            $class .= str_repeat($indentation, 1).'public function __construct($fieldsOrId = 0)'."\n";
            $class .= str_repeat($indentation, 1)."{\n";
            $class .= str_repeat($indentation, 2)."if (!empty(\$fieldsOrId) && is_numeric(\$fieldsOrId))\n";
            $class .= str_repeat($indentation, 3)."\$this->load(\$fieldsOrId);\n";
            $class .= str_repeat($indentation, 2)."else if (!empty(\$fieldsOrId) && is_array(\$fieldsOrId))\n";
            $class .= str_repeat($indentation, 2)."{\n";
            $class .= str_repeat($indentation, 3)."\$fields = \$fieldsOrId;\n\n";

            foreach ($fields as $field => $fieldType)
            {
                $class .= str_repeat($indentation, 3).'$this->'.Oxygen_Utils::convertToClassName($field).
                    ' = isset($fields[\''.$field.'\']) ? $fields[\''.$field.'\'] : '.self::getDefaultValueByType($fieldType).';'."\n";
            }
            $class .= str_repeat($indentation, 2)."}\n";
            $class .= str_repeat($indentation, 2)."else \n";
            $class .= str_repeat($indentation, 2)."{\n";

            foreach ($fields as $field => $fieldType)
            {
                $fieldValue = self::getDefaultValueByType($fieldType);
                $class .= str_repeat($indentation, 3).'$this->'.Oxygen_Utils::convertToClassName($field)." = {$fieldValue};\n";
            }
            $class .= str_repeat($indentation, 2)."}\n";

            $class .= str_repeat($indentation, 1)."}\n\n";

            // Save function
            $class .= str_repeat($indentation, 1)."function save()\n    {\n";
            $class .= str_repeat($indentation, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat($indentation, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat($indentation, 3).'INSERT INTO '.$tableName.' (';
            foreach ($fields as $field => $fieldType)
            {
                $class .= '`'.$field.'`';
                if ($field != $last_field)
                    $class .= ", ";
            }

            $class .= ')'."\n";
            $class .= str_repeat($indentation, 3).'VALUES (';
            foreach ($fields as $field => $fieldType)
            {
                $class .= ':'.$field;
                if ($field != $last_field)
                    $class .= ", ";
            }
            $class .= ') ON DUPLICATE KEY UPDATE'."\n";
            foreach ($fields as $field => $fieldType)
            {
                $class .= str_repeat($indentation, 3).'`'.$field.'` = VALUES('.$field.')';
                if ($field != $last_field)
                    $class .= ",";
                $class .= "\n";
            }
            $class .= str_repeat($indentation, 2).'");'."\n\n";

            foreach ($fields as $field => $fieldType)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field);
                $fieldType = !empty($fieldType) ? ', PDO::PARAM_'.strtoupper($fieldType) : '';

                $class .= str_repeat($indentation, 2).'$res->bindValue(\':'.$field.'\', $this->'.$camelCaseField.$fieldType.'); '."\n";
            }

            $class .= "\n";
            $class .= str_repeat($indentation, 2).'$res->execute();'."\n\n";
            $class .= str_repeat($indentation, 2).'if (empty($this->id))'."\n";
            $class .= str_repeat($indentation, 3).'$this->id = $db->lastInsertId();'."\n";

            $class .= str_repeat($indentation, 1).'}'."\n\n";

            // Load function
            $class .= str_repeat($indentation, 1)."function load(\$id = 0)\n    {\n";
            $class .= str_repeat($indentation, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat($indentation, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat($indentation, 3).'SELECT ';
            foreach ($fields as $field => $fieldType)
            {
                $class .= '`'.$field.'`';
                if ($field != $last_field)
                    $class .= ", ";
            }
            $class .= "\n";
            $class .= str_repeat($indentation, 3).'FROM '.$tableName."\n";
            $class .= str_repeat($indentation, 3).'WHERE id = :id'."\n";
            $class .= str_repeat($indentation, 2).'");'."\n\n";

            $class .= str_repeat($indentation, 2).'$res->bindValue(\':id\', !empty($id) ? $id : $this->id);'."\n\n";
            $class .= str_repeat($indentation, 2).'$res->execute();'."\n\n";

            $class .= str_repeat($indentation, 2).'if ($row = $res->fetch())'."\n";
            $class .= str_repeat($indentation, 2).'{'."\n";
            foreach ($fields as $field => $fieldType)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field);
                $class .= str_repeat($indentation, 3).'$this->'.$camelCaseField.' = $row[\''.$field.'\'];'."\n";
            }
            $class .= str_repeat($indentation, 2).'}'."\n";

            $class .= str_repeat($indentation, 1).'}'."\n\n";

            // Delete function
            $class .= str_repeat($indentation, 1)."function delete(\$id = 0)\n    {\n";
            $class .= str_repeat($indentation, 2).'$db = '.$adapterString.";\n\n";
            $class .= str_repeat($indentation, 2).'$res = $db->prepare("'."\n";
            $class .= str_repeat($indentation, 3).'DELETE FROM '.$tableName."\n";
            $class .= str_repeat($indentation, 3).'WHERE id = :id'."\n";
            $class .= str_repeat($indentation, 2).'");'."\n\n";

            $class .= str_repeat($indentation, 2).'$res->bindValue(\':id\', !empty($id) ? $id : $this->id);'."\n\n";
            $class .= str_repeat($indentation, 2).'$res->execute();'."\n";

            $class .= str_repeat($indentation, 1).'}'."\n\n";

            // Find function
            $findPrototype = <<<'EOM'
__INDENTATION__/**
__INDENTATION__ * Find rows in database according to criterion
__INDENTATION__ *
__INDENTATION__ * $criterion array : find criterion as array(
__INDENTATION__ *      'select' => array('field1', 'field2')
__INDENTATION__ *      'where' => array('cond1', 'cond2')
__INDENTATION__ *      'other' => array('ORDER BY something')
__INDENTATION__ * )
__INDENTATION__ * $returnObjects bool : if true, rows will be returned as '__CLASSNAME__' instances
__INDENTATION__ * */
__INDENTATION__public static function find($criterion = array(), $returnObjects = false)
__INDENTATION__{
__INDENTATION____INDENTATION__return Oxygen_Db::find(
__INDENTATION____INDENTATION____INDENTATION____ADAPTER__,
__INDENTATION____INDENTATION____INDENTATION__'__TABLE__',
__INDENTATION____INDENTATION____INDENTATION__'__CLASSNAME__',
__INDENTATION____INDENTATION____INDENTATION__$criterion,
__INDENTATION____INDENTATION____INDENTATION__$returnObjects
__INDENTATION____INDENTATION__);
__INDENTATION__}
EOM;
            $class .= str_replace(
                array('__TABLE__', '__ADAPTER__', '__CLASSNAME__', '__INDENTATION__'),
                array($tableName, $adapterString, $model_prefix.ucfirst($className), $indentation),
                $findPrototype
            )."\n\n";

            // Setters
            $class .= str_repeat($indentation, 1).'// Setters'."\n";
            foreach ($fields as $field => $fieldType)
            {
                $camelCaseField = Oxygen_Utils::convertToClassName($field);

                $class .= str_repeat($indentation, 1).'function set'.ucfirst($camelCaseField).'($'.$camelCaseField.')'."\n";
                $class .= str_repeat($indentation, 1).'{'."\n";
                $class .= str_repeat($indentation, 2).'$this->'.$camelCaseField.' = $'.$camelCaseField.';'."\n";
                $class .= str_repeat($indentation, 2).'return $this;'."\n";
                $class .= str_repeat($indentation, 1).'}'."\n";
                if ($field != $last_field)
                    $class .= "\n";
            }

            $class .= "\n";

            // Getters
            $class .= str_repeat($indentation, 1).'// Getters'."\n";
            foreach ($fields as $field => $fieldType)
            {
                $class .= str_repeat($indentation, 1).'function get'.ucfirst(Oxygen_Utils::convertToClassName($field)).'()'."\n";
                $class .= str_repeat($indentation, 1).'{'."\n";
                $class .= str_repeat($indentation, 2).'return $this->'.Oxygen_Utils::convertToClassName($field).';'."\n";
                $class .= str_repeat($indentation, 1).'}'."\n";
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
                $model .= 'class '.$model_prefix.ucfirst($className).' extends ' . $generated_model_prefix . ucfirst($className) . "\n";
                $model .= "{\n\n";
                $model .= "}\n";

                file_put_contents($classPath . ucfirst($className).'.php', $model);
            }
        }
    }

    protected static function getDefaultValueByType($type)
    {
        $result = 'null';

        switch ($type)
        {
            case 'int':
                $result = '0';
            break;

            case 'str':
                $result = "''";
            break;

            case 'bool':
                $result = 'false';
            break;

            default:
                $result = 'null';
            break;
        }

        return $result;
    }
}