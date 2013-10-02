<?php
// application/plugins/doctrine_loader_helper.php

/** Changelog:
 *
 *  2010/04/30 - Markus
 *  - Added line to load Doctrine_Record_Ex class so that models can use it.
 *
 *  2010/05/01 - Markus
 *  - Enabled validation for models.
 *
 *  2011/01/19 - Markus
 *  - Enabled the use of DQL hooks.
 *
 */
// load Doctrine library
require_once APPPATH.'libraries/doctrine/Doctrine.php';

// load database configuration from CodeIgniter
require APPPATH.'config/database.php';      // Changed because it didn't work with require_once if doing user validation

// this will allow Doctrine to load Model classes automatically
spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('Doctrine', 'modelsAutoload'));
spl_autoload_register(array('Doctrine', 'extensionsAutoload'));

// Need to include this so that model files can use this class.
require APPPATH.'libraries/doctrine/Doctrine_Record_Ex.php';

// we load our database connections into Doctrine_Manager
// this loop allows us to use multiple connections later on
foreach ($db as $connection_name => $db_values) {

    // first we must convert to dsn format
    $dsn = $db[$connection_name]['dbdriver'] .
        '://' . $db[$connection_name]['username'] .
        ':' . $db[$connection_name]['password'].
        '@' . $db[$connection_name]['hostname'] .
        '/' . $db[$connection_name]['database'];

    Doctrine_Manager::connection($dsn,$connection_name);
}

// CodeIgniter's Model class needs to be loaded
require_once BASEPATH.'/core/Model.php';

Doctrine_Manager::setAttribute(Doctrine_Core::ATTR_MODEL_LOADING,
Doctrine_Core::MODEL_LOADING_CONSERVATIVE);

// telling Doctrine where our models are located
Doctrine::loadModels(APPPATH.'models');

// (OPTIONAL) CONFIGURATION BELOW

// this will allow us to use "mutators"
Doctrine_Manager::getInstance()->setAttribute(
    Doctrine::ATTR_AUTO_ACCESSOR_OVERRIDE, true);

// this sets all table columns to notnull and unsigned (for ints) by default
Doctrine_Manager::getInstance()->setAttribute(
    Doctrine::ATTR_DEFAULT_COLUMN_OPTIONS,
    array('notnull' => true, 'unsigned' => true));

// set the default primary key to be named 'id', integer, 4 bytes
Doctrine_Manager::getInstance()->setAttribute(
    Doctrine::ATTR_DEFAULT_IDENTIFIER_OPTIONS,
    array('name' => 'id', 'type' => 'integer', 'length' => 4));
    
Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_VALIDATE,
Doctrine_Core::VALIDATE_ALL);

// Allows DQL hooks to be used.
Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);
