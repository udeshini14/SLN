<?php

/** ********************** plugins/doctrine/lib/Doctrine/Record_Ex.php ***************************
 *
 *  This class is an extension of the Doctrine_Record class. It 
 *  provides additional methods to models that extend from this
 *  class.
 *
 ** Changelog:
 *
 *  2010/04/30 - Markus
 *  - Created class with select() and stringify() functions. The
 *    select function should not be overwritten unless really
 *    needed. The stringify self function can be overwritten
 *    if the auto generation result is not good enough.
 *
 *  2010/05/01 - Markus
 *  - Now typecasting the object id when generating the selected
 *    ids array (in select function).
 *
 *  2010/05/14 - Markus
 *  - Returned data now contains the model class name as
 *    'namespace\classname' as well as the object id provided.
 *    
 *  2010/05/18 - Markus
 *  - Added code to select() which refreshes the object's relationship
 *    data. This is done in case this object was loaded previously 
 *    and its relationships where accessed, so that the data is 
 *    up to date. Sadly this is not very efficient for when there
 *    have been no changes, but it cannot be avoided.
 *
 *  2010/05/19 - Markus
 *  - Renamed stringify_self to stringify_all_objects as that is a
 *    better name for that function. It works with all objects!
 *    Can be overriden in child class!
 *  - Added new function called stringify_self that stringifies only
 *    this object instance automatically.
 *    Can be overriden in child class!
 *  - Updated select to reflect change in function name.
 *
 *  2010/05/21 - Markus
 *  - Made the id parameter optional in the select() function.
 *
 *  2010/05/25 - Markus
 *  - In select() when collecting the composite relationship data,
 *    the foreign key column is now also included. Needed for 
 *    constructing tables for forms with composite relationships.
 *
 *  2010/06/08 - Markus
 *  - Modified select() so that for many to many relations, the
 *    local and foreign keys are returned as well as the class-
 *    name of the mapping table. Needed for adding mappings via
 *    the mapping table.
 *
 *  2010/06/17 - Markus
 *  - Made it clear in function comments for the stringify_self
 *    function that it can be overriden.
 *  - The stringify_all_objects function now uses stringify_self
 *    to stringify an object instance. This avoids having to
 *    override both functions for generating custom stringified
 *    versions. Made the function final so it cannot be overriden.
 *  
 *  2010/11/24 - Markus
 *  - Added function get_columns_for_instance_sorting(). This is intended to return a list of 
 *    columns to use in generating a string to be used for sorting instances of this model.
 *
 *  2010/12/14 - Markus
 *  - Added function auto_generate_definition() for setting up the column and indices for the
 *    model.
 *
 *  2010/12/20 - Markus
 *  - Added parameter 'relations_type' to select() so that the function can be told what type 
 *    of relations to return. In some cases it is not necessary to get relations where the
 *    foreign key is on another table.
 *  - Relations where the foreign key is on this table now add the relation alias to the column
 *    definitions.
 *  - Columns that have a 'unique' constraint now have this information added to the columns
 *    definition.
 * 
 *  2011/01/21 - Markus
 *  - WHen generating the model data, associate relations now store the name of the fk field.
 *
 ***********************************************************************************************/

class Doctrine_Record_Ex extends Doctrine_Record
{
    
    protected $_table = NULL;       // Stores a Doctrine table instance of the model
    protected $_columns = NULL;     // Stores the columns that exist in the table.
    protected $_sort_by_columns = array();  // Stores the model columns that should be used to generate a 
                                            // string that is then used for sorting instances of this model
    
    // Does nothing really... The setUp function in Doctrine_Record_Abstract is empty.
    public function setUp()
    {
        parent::setUp();
    }
    
    // id: the id of the object to get values from. If NULL then no values are associated
    //     with columns/relationships.
    // get_relations: indicates if relationship information should be included or not.
    //                Optional parameter; TRUE by default.
    // relations_type: indicates the type of relations to get. Default is 0 (all), 1 for foreign key relations, 2 for only O-M/M-M
    // Returns a structure containing column data (values, related objects if column is a
    // foreign key) and relationship information for relationships that point to this class.
    public function select($id = NULL, $get_relations = TRUE, $relations_type = 0)
    {
        $data = array();            // Array containing data to return.
        $columns_data = array();    // Array containing data on columns.
        $relations_data = array();  // Array containing data on relationships that are not already recorded in the columns_data array.
        $obj = NULL;                // Object for which to get existing values (will stay NULL if no object id provided)
        
        $data['object_id'] = $id;
        $data['classname'] = get_class($this);
        $table = \Doctrine::getTable(get_class($this));
        
        // Fetch the object if it is specified.
        if($id > -1)
        {
            $obj = $table->find($id);
            if($obj !== FALSE) $obj->refreshRelated();
        }
        
        $relations = $table->getRelations();
        
        // Get the table structure (columns)
        $columns = $table->getColumns();
        
        $opts = $table->getOptions();
        $indexes = $opts['indexes'];
        
//nice_vardump($indexes);
        foreach($columns as $col_name => $opts)
        {
            
            $options = array();
            if(isset($opts['notnull'])) $options['required'] = $opts['notnull'];
            if(isset($opts['primary']) && $opts['primary']) $options['primary'] = TRUE;
            if(isset($opts['autoincrement']) && $opts['autoincrement']) $options['autoincrement'] = TRUE;
            if(isset($opts['values']) && is_array($opts['values'])) $options['options'] = $opts['values'];
            if(isset($opts['default'])) $options['default'] = $opts['default'];
            // if fixed length, save length property?
            if($obj) $options['value'] = $obj->$col_name;
            
            if($get_relations && ($relations_type == 0 || $relations_type == 1))
            {
                // See if this column is a foreign key column
                foreach($relations as $name => $relation)
                {
                    if($relation->getType() == Doctrine_Relation::ONE && $relation->getLocal() == $col_name) // Only interested in One-to-One and Many-to-One relations
                    {
                        // Get all objects in the foreign table as key/value pairs (primary_key => stringified object)
                        $class = $relation->getClass();
                        $related_instance = new $class();
                        $related_objects = array();
                        if(method_exists($related_instance, 'stringify_all_objects'))
                        {
                            $related_objects = $related_instance->stringify_all_objects();
                        }
                        $options['related'] = $related_objects;
                        $options['foreign_class'] = $class;
                        $options['relation_alias'] = $relation->getAlias();
                        $options['column_name'] = $relation->getLocal();
                    }
                }
            }
            
            $columns_data[$col_name] = $options;
        }
        
        if($get_relations && ($relations_type == 0 || $relations_type == 2))
        {
            // Check for relationships where the foreign table references objects in here.
            // These will be One to Many or Many to Many relationships.
            foreach($relations as $name => $relation)
            {
                if($relation->getType() == Doctrine_Relation::ONE) 
                    continue; // Skip to next relationship if not One to Many or Many to Many
                
                $reldata = array();
                $reldata['objects'] = array();
                $reldata['selected'] = array();
                
                // See if this relationship is many to many
                $val = $relation->offsetGet('refTable');
                $reldata['many_to_many'] = (empty($val)) ? FALSE : TRUE;
                
                // Check if the relationship is associative or composite for One to Many relationships.
                // Composite relationships are identified by the On delete cascade option being set.
                if($relation->offsetGet('onDelete') == 'CASCADE' && !$reldata['many_to_many'])
                {
                    $reldata['composite'] = TRUE;
                    
                    // For composite objects, get all the related objects and the table column definitions
                    $class = $relation->getClass();
                    $related_instance = new $class();
                    $model_data = $related_instance->select(NULL, FALSE);
                    $reldata['model_data'] = $model_data;
                    // If working with an object, then get all related objects.
                    if($obj)
                    {
                        $related_objs = $obj[$name];
                        foreach($related_objs as $relobj)
                        {
                            $reldata['objects'][] = $relobj->ToArray(FALSE);
                        }
                    }
                    
                    // Need to know the foreign key column name.
                    $reldata['foreign_key'] = $relation->getForeign();
                }
                else
                {
                    $reldata['composite'] = FALSE;
                    $reldata['foreign_key'] = $relation->getForeign();
                    $reldata['local_key'] = $relation->getLocal();
                    if(!empty($val)) $reldata['mapping_table'] = $val->getClassnameToReturn();
                    // Get all objects in the related table as stringified.
                    $class = $relation->getClass();
                    $related_instance = new $class();
                    if(method_exists($related_instance, 'stringify_all_objects'))
                    {
                        $reldata['objects'] = $related_instance->stringify_all_objects();
                    }
                    // If working with an object, then get all related object ids.
                    if($obj)
                    {
                        $related_objs = $obj[$name];
                        foreach($related_objs as $relobj)
                        {
                            $reldata['selected'][] = (integer)$relobj->id;   // Should the relevant model return the primary key(s) ? Might not have a primary key call id!
                        }
                    }
                }
                
                $reldata['foreign_class'] = $class;
                $relations_data[$name] = $reldata;
            }
        }
        
        // Add constraints to column definitions.
        foreach($indexes as $index)
        {
            foreach($index['fields'] as $field)
            {
                //nice_vardump($field);
                if($index['type'] == 'unique')
                {
                    if(isset($columns_data[$field]))
                    {
                        $columns_data[$field]['unique'] = TRUE;
                    }
                }
            }
        }
        
        
        $data['columns'] = $columns_data;
        $data['relationships'] = $relations_data;

        return $data;
        
    }

    
    // Stringifies ALL records in the table and returns results as id => stringified object 
    // pairs. It uses stringify_self for stringifying object instances.
    final public function stringify_all_objects()
    {
        $data = array();
        
        // $table = \Doctrine::getTable(get_class($this));
        // $objects = $table->findAll();
        if($this->_table == NULL) $this->_table = \Doctrine::getTable(get_class($this));
        $objects = $this->_table->findAll();
        foreach($objects as $obj)
            $data[$obj->id] = $obj->stringify_self();
        
        return $data;
    }
    
    // Stringifies THIS object instance automatically by first looking for all fields that
    // can match 'name' and combining them to form a stringified version of the object. If
    // there are no fields that match 'name' then it will use the id as the string.
    // (The model that extends this class can override the function to generate 
    // better stringified versions)
    public function stringify_self()
    {
        $string = NULL;
        
        // $table = \Doctrine::getTable(get_class($this));
        // $columns = $table->getColumns();
        if($this->_table == NULL) $this->_table = \Doctrine::getTable(get_class($this));
        if($this->_columns == NULL) $this->_columns = $this->_table->getColumns();
        
        $useable = array();     // Array of usable column names.

        // foreach($columns as $col_name => $opts)
        foreach($this->_columns as $col_name => $opts)
        {
            if(stristr($col_name, 'name') !== FALSE && $opts['type'] == 'string')   // Only interested in strings
                $useable[] = $col_name;
        }
        
        if(count($useable) == 0)    // If no acceptable fields found, use the id field
        {
            $useable = array(0 => 'id');
        }
        
        $cnt = count($useable);
        
        // Join the values of the acceptable fields together for this object instance.
        for($i = 0; $i < $cnt - 1; $i++)
        {
            $string .= $this->$useable[$i] . ' ';
        }
        $string .= $this->$useable[$cnt - 1];
        
        return $string;
    }
    
    // This function returns the model columns to use when sorting instances of this model.
    // When the stringify_self function is overriden, this function should also be overriden 
    // to provide an array of columns that match the ones used in the custom stringify_self 
    // function.
    public function get_columns_for_instance_sorting()
    {
        if($this->_table == NULL) $this->_table = \Doctrine::getTable(get_class($this));
        if($this->_columns == NULL) $this->_columns = $this->_table->getColumns();
        
        $useable = array();     // Array of usable column names.

        // foreach($columns as $col_name => $opts)
        foreach($this->_columns as $col_name => $opts)
        {
            if(stristr($col_name, 'name') !== FALSE && $opts['type'] == 'string')   // Only interested in strings
                $useable[] = $col_name;
        }
        
        if(count($useable) == 0)    // If no acceptable fields found, use the id field
        {
            $useable = array(0 => 'id');
        }
        
        return $useable;
    }
    
    // This function can be used to auto generate the column and index definitions for a model.
    // In the model setTableDefinition() function set the table name and then call this function.
    // Based on the underlying table definition it generates the model.
    public function auto_generate_definition()
    {
        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        
        $fullname = $this->getTable()->getTableName();
        $parts = preg_split('/\./', $fullname);
        $defs = mysql_defs_getColumns($parts[0], $parts[1]);
//nice_vardump($defs);
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
        }

        //nice_vardump($this->getTable()->getColumns());

        //$this->unique(array('name'));
        //nice_vardump($this->index('name_unqidx'));

        // Get the table contraints. Need to tell Doctrine about some of them.
        $va = mysql_defs_getConstraints($parts[0],$parts[1]);
        //nice_vardump($va);
        
        foreach($va as $key_name => $items)
        {
            foreach($items as $item)
            {
                if($item['unique'])
                    $this->unique(array($item['field']));       // Use this rather than $this->index() for setting unique indices.
            }
        }
        
        //nice_vardump($this->getTable()->getOptions());  
    }
}




?>