<?php

/** ************************************** MySqlTable_definitions.php ***************************************
 *  
 * This helper allows column and relationship definitions to be extracted from a MySql table. This data is
 * needed to auto generate column and relationship definitions for Doctrine models.
 *
 * **********************************************************************************************************
 * Changelog:
 *
 * 2010/04/30 - Markus
 *- Modified mysql_defs_convert_columns() a bit so that all column properties that are set to false are
 *  still put in the options array. 
 * 
 * 2010/08/12 - Markus
 * - mysql_defs_getColumns() and mysql_defs_getRelations() now use the database values set in the database
 *   config file.
 *
 * 2010/12/13 - Markus
 * - added function mysql_defs_getConstraints().
 *
 * 2011/01/19 - Markus
 * - Updated mysql_defs_convert_columns() to ensure that when a default value exists, and the data type is
 *   integer, then the default value is also an integer. This was done to allow the creation of records in
 *   View tables (Doctrine would complain it couldn't get the id of the newly created item)
 * - Updated mysql_defs_getConstraints() to test if the requested table is a View. For Views, need to query
 *   the table it is based on.
 * - Did the same for mysql_defs_getColumns(). For columns however, only those that appear in the view are
 *   added.
 *
 * 2011/03/02 - Markus
 * - Added singleton class for storing data retrieved from the DB and to reuse it when checking if a table is 
 *  actually a View.
 *
 * 2011/03/04 - Markus
 * - Updated get_table_view_information() so that it checks the results for a match based on the namespace
 *   and tablename. Before it took just the first result from the VIEWs query.
 *
 * 2011/07/11 - Markus
 * - now using the Doctrine connection object in mysql_defs_getColumns() to run queries (instead of mysql_*
 *   functions). This allows using temporary tables (since temp tables are visible to only the connection 
 *   that created them. Is also brings the function into line with others that use the doctrine connection 
 *   object.
 *
 **************************************************************************************************************/

// Fetches the column definitions for a given table in a database.
// The column definitions are converted so they can be just passed
// to the hasColumn() method.
// namespace - the database name.
// table     - the table name.
// Returns an array of column definitions ready for use by
// the hasColumn() method.
function mysql_defs_getColumns($namespace, $table)
{
	$conn = Doctrine_Manager::connection();
	$sql = "SHOW FULL COLUMNS FROM `$namespace`.`$table`";
	$columns = $conn->fetchAssoc($sql);
    
    // If the table is actually a View, then need to get the definitions from the base table
    // and use those column definitions that actually appear in the View.
    $is_view = get_table_view_information($namespace, $table);
    if($is_view != FALSE)
    {
        $sql = "SHOW FULL COLUMNS FROM ". $is_view['based_on'];
		$base_columns = $conn->fetchAssoc($sql);
        foreach($base_columns as $b_col)
        {
            for($i = 0; $i < count($columns); $i++)
            {
                if($columns[$i]['Field'] == $b_col['Field'])
                    $columns[$i] = $b_col;
            }
        }
    }
    
    // Now convert each column definition into something Doctrine can use.
    $definitions = array();
    foreach ($columns as $col) {
        $definitions[] = mysql_defs_convert_columns($col);
    }

    return $definitions;
}

function mysql_defs_getRelations($namespace, $table)
{
    $link = mysql_connect($db[$active_group]['hostname'], $db[$active_group]['username'], $db[$active_group]['password']);
    if (!$link) {
        die('Could not connect: ' . mysql_error());
    }
    echo 'Connected successfully';
    
    $db_selected = mysql_select_db("$namespace", $link);
    if (!$db_selected) {
        die ("Can't use $namespace : " . mysql_error());
    }
    
    // Perform Query
    $relations = array();
    $query = "SELECT column_name, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.key_column_usage WHERE table_name = '" . $table . "' AND table_schema = '" . $namespace . "' and REFERENCED_COLUMN_NAME is not NULL";
    $results = mysql_query($query);
    foreach ($results as $result)
    {
        $result = array_change_key_case($result, CASE_LOWER);
        $relations[] = array('table'   => $result['referenced_table_name'],
                             'local'   => $result['column_name'],
                             'foreign' => $result['referenced_column_name']);
    }
    return $relations;
}



/**
* Lists table constraints. This was taken from Doctrine\Import\Mysql.php
*
* @param string $table     database table name
* @return array
*/
function mysql_defs_getConstraints($namespace, $table)
{
    $conn = Doctrine_Manager::connection();
    $keyName = 'Key_name';
    $nonUnique = 'Non_unique';
    $colName = 'Column_name';
    if ($conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) && ($conn->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_FIX_CASE)) {
        if ($conn->getAttribute(Doctrine_Core::ATTR_FIELD_CASE) == CASE_LOWER) {
            $keyName = strtolower($keyName);
            $nonUnique = strtolower($nonUnique);
            $colName = strtolower($colName);
        } else {
            $keyName = strtoupper($keyName);
            $nonUnique = strtoupper($nonUnique);
            $colName = strtoupper($colName);
        }
    }
    
    // If the table is actually a View, then need to get the constraints from the base table.
    $result = get_table_view_information($namespace, $table);
    //nice_vardump($result);
    if($result != FALSE)
    {
        $query = "SHOW INDEX FROM " . $result['based_on'];
    }
    else
    {
        $table = $conn->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM `$namespace`.`$table`";
    }
    $indexes = $conn->fetchAssoc($query);
//nice_vardump($indexes);
    $result = array();
    $data = array();
    foreach ($indexes as $indexData) {
        //if ( ! $indexData[$nonUnique]) {
            if ($indexData[$keyName] !== 'PRIMARY') {
                $index = $conn->formatter->fixIndexName($indexData[$keyName]);
            } else {
                $index = 'PRIMARY';
            }
            $data['unique'] = !$indexData[$nonUnique];
            $data['field'] = $indexData[$colName];
            if ( ! empty($index)) {
                $result[$index][] = $data;
            }
        //}
    }
    //nice_vardump($result);
    return $result;
}


// Converts a column definition into something Doctrine can use
// with the hasColumn() method.
// val - the column definition
// Returns an array of values for use with Doctrine's hasColumn()
// method.
function mysql_defs_convert_columns($val)
{
    // This section comes from Doctrine\Import\Mysql.php listTableColumns() function 
    // and is slightly modified.
    $val = array_change_key_case($val, CASE_LOWER);
    $decl = mysql_defs_getPortableDeclaration($val);
    $values = isset($decl['values']) ? $decl['values'] : array();
    $val['default'] = $val['default'] == 'CURRENT_TIMESTAMP' ? null : $val['default'];

    $description = array(
                    'name'          => $val['field'],
                    'type'          => $decl['type'][0],
                    //'alltypes'      => $decl['type'],
                    //'ntype'         => $val['type'],
                    'length'        => $decl['length'],
                    'fixed'         => ((bool) $decl['fixed']),
                    'unsigned'      => ((bool) $decl['unsigned']),
                    'values'        => $values,
                    'primary'       => (strtolower($val['key']) == 'pri'),
                    'default'       => $val['default'],
                    'notnull'       => ($val['null'] != 'YES') ? TRUE : FALSE,
                    'autoincrement' => (strpos($val['extra'], 'auto_increment') !== false) ? TRUE : FALSE
                    );
    if (isset($decl['scale'])) {
        $description['scale'] = $decl['scale'];
    }
    
    $options = $description;

    // Construct the options array. This code was taken from Doctrine\Import\Builder.php buildColumns() function
    // --------
    // Remove name, alltypes, ntype. They are not needed in options array
    unset($options['name']);
    unset($options['alltypes']);
    unset($options['ntype']);

    // Remove notnull => true if the column is primary
    // Primary columns are implied to be notnull in Doctrine
    if (isset($options['primary']) && $options['primary'] == true && (isset($options['notnull']) && $options['notnull'] == true)) {
        unset($options['notnull']);
    }

    // Remove default if the value is 0 and the column is a primary key
    // Doctrine defaults to 0 if it is a primary key
    if (isset($options['primary']) && $options['primary'] == true && (isset($options['default']) && $options['default'] == 0)) {
        unset($options['default']);
    }
    
    // Should try to ensure the default value is the same data type as the field.
    if(isset($options['default']) && $options['type'] == 'integer')
    {
        $options['default'] = (int)$options['default'];
    }

    // Remove null and empty array values
    foreach ($options as $key => $value) {
        if (is_null($value) || (is_array($value) && empty($value))) {
            unset($options[$key]);
        }
    }

//     if (is_array($options) && !empty($options)) {
//         $description['options'] = mysql_defs_varExport($options);
//     }
    $description['options'] = $options;
    //echo $description['options'];
    // --------
    
    // Unset everything that is not 'name', 'type', 'length' or 'options'
    foreach ($description as $key => $value) {
        if ($key != 'name' && $key != 'type' && $key != 'length' && $key != 'options') {
            unset($description[$key]);
        }
    }
    
    return $description;
}

/**
* Maps a native array description of a field to a MDB2 datatype and length
* (Taken from Doctrine\DataDict\Mysql.php getPortableDeclaration() function.
*
* @param array  $field native field description
* @return array containing the various possible types, length, sign, fixed
*/
function mysql_defs_getPortableDeclaration(array $field)
{
    $dbType = strtolower($field['type']);
    $dbType = strtok($dbType, '(), ');
    if ($dbType == 'national') {
        $dbType = strtok('(), ');
    }
    if (isset($field['length'])) {
        $length = $field['length'];
        $decimal = '';
    } else {
        $length = strtok('(), ');
        $decimal = strtok('(), ');
        if ( ! $decimal ) {
            $decimal = null;
        }
    }
    $type = array();
    $unsigned = $fixed = null;

    if ( ! isset($field['name'])) {
        $field['name'] = '';
    }

    $values = null;
    $scale = null;

    switch ($dbType) {
        case 'tinyint':
            $type[] = 'integer';
            $type[] = 'boolean';
            if (preg_match('/^(is|has)/', $field['name'])) {
                $type = array_reverse($type);
            }
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 1;
        break;
        case 'smallint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 2;
        break;
        case 'mediumint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 3;
        break;
        case 'int':
        case 'integer':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 4;
        break;
        case 'bigint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 8;
        break;
        case 'tinytext':
        case 'mediumtext':
        case 'longtext':
        case 'text':
        case 'text':
        case 'varchar':
            $fixed = false;
        case 'string':
        case 'char':
            $type[] = 'string';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            } elseif (strstr($dbType, 'text')) {
                $type[] = 'clob';
                if ($decimal == 'binary') {
                    $type[] = 'blob';
                }
            }
            if ($fixed !== false) {
                $fixed = true;
            }
        break;
        case 'enum':
            $type[] = 'enum';
            preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
            $length = 0;
            $fixed = false;
            if (is_array($matches)) {
                foreach ($matches[1] as &$value) {
                    $value = str_replace('\'\'', '\'', $value);
                    $length = max($length, strlen($value));
                }
                if ($length == '1' && count($matches[1]) == 2) {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }

                $values = $matches[1];
            }
            $type[] = 'integer';
            break;
        case 'set':
            $fixed = false;
            $type[] = 'text';
            $type[] = 'integer';
        break;
        case 'date':
            $type[] = 'date';
            $length = null;
        break;
        case 'datetime':
        case 'timestamp':
            $type[] = 'timestamp';
            $length = null;
        break;
        case 'time':
            $type[] = 'time';
            $length = null;
        break;
        case 'float':
        case 'double':
        case 'real':
            $type[] = 'float';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
        break;
        case 'unknown':
        case 'decimal':
            if ($decimal !== null) {
                $scale = $decimal;
            }
        case 'numeric':
            $type[] = 'decimal';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
        break;
        case 'tinyblob':
        case 'mediumblob':
        case 'longblob':
        case 'blob':
        case 'binary':
        case 'varbinary':
            $type[] = 'blob';
            $length = null;
        break;
        case 'year':
            $type[] = 'integer';
            $type[] = 'date';
            $length = null;
        break;
        case 'bit':
            $type[] = 'bit';
        break;
        case 'geometry':
        case 'geometrycollection':
        case 'point':
        case 'multipoint':
        case 'linestring':
        case 'multilinestring':
        case 'polygon':
        case 'multipolygon':
            $type[] = 'blob';
            $length = null;
        break;
        default:
            $type[] = $field['type'];
            $length = isset($field['length']) ? $field['length']:null;
    }

    $length = ((int) $length == 0) ? null : (int) $length;
    $def =  array('type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed);
    if ($values !== null) {
        $def['values'] = $values;
    }
    if ($scale !== null) {
        $def['scale'] = $scale;
    }
    return $def;
}

function convert_r($row)
{
    
}

// Taken from Doctrine\Builder.php
function mysql_defs_varExport($var)
{
    $export = var_export($var, true);
    $export = str_replace("\n", PHP_EOL . str_repeat(' ', 50), $export);
    $export = str_replace('  ', ' ', $export);
    $export = str_replace('array (', 'array(', $export);
    $export = str_replace('array( ', 'array(', $export);
    $export = str_replace(',)', ')', $export);
    $export = str_replace(', )', ')', $export);
    $export = str_replace('  ', ' ', $export);

    return $export;
}


// Gets information on a table that is a View.
// namespace: the name of the database
// tablename: the name of the table
// Returns false if the table is not a View. If it is a view it returns an associative
// array. Eg: array('based_on' => name of the table this view is based on)
function get_table_view_information($namespace, $tablename)
{
    $conn = Doctrine_Manager::connection();
    

    //nice_vardump(mysql_views_cache);
    $mic =& mysql_info_cache_get_instance();
    if($mic === NULL) 
    {
        $mic = new mysql_info_cache();
        $query = "SELECT VIEW_DEFINITION, TABLE_SCHEMA, TABLE_NAME FROM `information_schema`.`VIEWS`";
        $mic->view_definitions = $conn->fetchAssoc($query);
    }

    //$query = "SELECT VIEW_DEFINITION FROM `information_schema`.`VIEWS` WHERE TABLE_SCHEMA = '$namespace' AND TABLE_NAME = '$tablename'";
    //$results = $conn->fetchAssoc($query);
    if(count($mic->view_definitions) == 0) return FALSE;
    // Find the view definition for this namespace and tablename.
    $results = $mic->view_definitions;
    $found = FALSE;
    foreach($results as $result)
    {
        if($result['TABLE_NAME'] == $tablename && $result['TABLE_SCHEMA'] == $namespace)
        {
            $found = TRUE;
            break;
        }
    }
    //nice_vardump($results);
    //$result = $results[0];
    if(!$found) return FALSE;    // Could not find a view definition.
    $parts = preg_split('/,/', $result['VIEW_DEFINITION']);  // / `SELECT `namespace`.`table`.`field` AS field (, `namespace`.`table`.`field` AS field) from `namespace`.`table` [where ....]
    $parts = preg_split('/ /', $parts[0]);   // `SELECT `namespace`.`table`.`field` AS field
    $parts = preg_split('/\./', $parts[1]);   // `namespace`.`table`.`field`
    return array('based_on' => $parts[0] . '.' . $parts[1]);
}

// A singleton class used for storing data that is needed over multiple calls to functions in here.
class mysql_info_cache {

    private static $instance;           // Ensure the class persists.
    public $view_definitions;           // For storing VIEW schema definitions.

    public function mysql_info_cache()  // On instantiation it stores a copy of itself that it can return.
    {
        self::$instance =& $this;
    }

    public static function &get_instance()
    {
        return self::$instance;
    }
}

// Allows accessing the class.
function &mysql_info_cache_get_instance()
{
    return mysql_info_cache::get_instance();
}

