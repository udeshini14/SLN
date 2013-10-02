<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 /**************************** Changes Made For CBEADS *****************************
  * 
  * This file contains changes to functions found in /system/core/Common.php
  * 
  * 2011/10/05 - Markus
  * - Updated _exception_handler() to only display errors when the display_errors
  *   setting is turned on. Also now logging errors to the php log file.
  *
  *
  *
  **********************************************************************************
 
 


// --------------------------------------------------------------------

/**
* Exception Handler
*
* This is the custom exception handler that is declaired at the top
* of Codeigniter.php.  The main reason we use this is to permit
* PHP errors to be logged in our own log files since the user may
* not have access to server logs. Since this function
* effectively intercepts PHP errors, however, we also need
* to display errors based on the current error_reporting level.
* We do that with the use of a PHP error template.
*
* @access	private
* @return	void
*/
if ( ! function_exists('_exception_handler'))
{
	function _exception_handler($severity, $message, $filepath, $line)
	{
		 // We don't bother with "strict" notices since they tend to fill up
		 // the log file with excess information that isn't normally very helpful.
		 // For example, if you are running PHP 5 and you use version 4 style
		 // class functions (without prefixes like "public", "private", etc.)
		 // you'll get notices telling you that these have been deprecated.
		if ($severity == E_STRICT)
		{
			return;
		}

		$_error =& load_class('Exceptions', 'core');

		// Should we display the error? We'll get the current error_reporting
		// level and add its bits with the severity bits to find out.
		// Must also obey the PHP 'display_errors' setting. 2011/10/05
		if (($severity & error_reporting()) == $severity && ini_get('display_errors') == TRUE)
		{
			$error->show_php_error($severity, $message, $filepath, $line);
		}

		// Should we log the error?  No?  We're done...
		if (config_item('log_threshold') == 0)
		{
			return;
		}

		// $_error->log_exception($severity, $message, $filepath, $line);
		// Log errors to the PHP error log. 2011/10/05
		$levels = array(
			E_ERROR				=>	'Error',
			E_WARNING			=>	'Warning',
			E_PARSE				=>	'Parsing Error',
			E_NOTICE			=>	'Notice',
			E_CORE_ERROR		=>	'Core Error',
			E_CORE_WARNING		=>	'Core Warning',
			E_COMPILE_ERROR		=>	'Compile Error',
			E_COMPILE_WARNING	=>	'Compile Warning',
			E_USER_ERROR		=>	'User Error',
			E_USER_WARNING		=>	'User Warning',
			E_USER_NOTICE		=>	'User Notice',
			E_STRICT			=>	'Runtime Notice'
		);
		$severity = (!isset($levels[$severity])) ? $severity : $levels[$severity];
		error_log("PHP $severity:  $message $filepath $line");
	}
}

