<?php 

/** *********************************** helpers/MY_url_helper *************************************
 *
 *  This helper module extends the functionality of the system/helpers/url_helper module.
 *
 *  Changelog:
 *  2011/03/01 - Markus
 *  - Created file and added new function 'base_web_url'.
 *
** ***********************************************************************************************/

/**
 * Base Web URL
 *
 * Returns the "base_web_url" item from your config file. This should point to the folder
 * containing all web resources. Usually this will be the same url as 'base_url'
 *
 * @access	public
 * @return	string
 */
function base_web_url()
{
	$CI =& get_instance();
    return $CI->config->slash_item('base_url');
}
