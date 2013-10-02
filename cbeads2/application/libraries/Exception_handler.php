<?php

/* **********************************   libraries/Exception_handler.php    ****************************************
 *
 *  This class is used for handling global exceptions and set as the expection handler in CodeIgniter.php.
 *  Exceptions are automatically logged. Depending on whether display_errors is on (set in index.php or
 *  the php.ini file), all the exception information is shown to the user or only the message to the user.
 *  For servers that are open to the world, display_errors should be turned off.
 *
 *  Changelog:
 *  2011/04/14 - Markus
 *  - Created class.
 *
 *  2011/10/05 - Markus
 *  - Fixed a big problem with this class. It now correctly checks the display_errors php setting to
 *    determine if exceptions should be shown. Otherwise a generic error message is displayed.
 *  - Now logs exceptions to the php error log.
 *
 ******************************************************************************************************************/


class Exception_handler
{
    public static function handle_exception($exception)
    {
        $timestamp = time();
        $html =  "Message: " . $exception->getMessage() ."\n";
        $html .=  "Code: " . $exception->getCode() ."\n";
        $html .=  "File: " . $exception->getFile() ."\n";
        $html .=  "Line: " . $exception->getLine() ."\n";
        $html .=  "Trace: " . $exception->getTraceAsString()  ."\n";
        $html .=  "TS: " . $timestamp . "\n";
        $res = error_log("PHP Unhandled exception:  " . $html);
		$display_errors = ini_get('display_errors');
		// Only show the exception message when errors are to be displayed. Otherwise just display that a problem has occurred.
        if($display_errors == TRUE) 
        {
            show_error(str_replace("\n", "<br>", $html));
        }
		else
		{
			show_error("An unexpected error has occurred. Please inform the site administrator about this problem.");
		}
    }

}

?>