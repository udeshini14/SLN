<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* *****************************   application/config/openid.php   ******************************** 
 *		
 *	Contains configuration options for OpenID.
 *  See http://openid.net/ for a description of what OpenID is.
 *
 * ***********************************************************************************************/

/*
 * 	Enable OpenID log in?
 */
$config['enabled'] = TRUE;

 
/*
 *	This list contains all the OpenID providers you are willing to offer to people who visit your site.
 *	It is up to you to decide which providers are trustworthy.
 *
 *  Eg:  $open_id['providers'] = array(
 *			'www.google.com' => array(
 *				'endpoint' => 'https://www.google.com/accounts/o8/id', 'title' => 'google'
 *			)
 *		)
 *
 *  In the example, a provider was added for Google (www.google.com). The endpoint defines where the YARDIS document
 *  can be found. This document contains the URL which is used for sending the user to the provider's login page.
 *	The title is a short word identifier associated with a provider. You can use whatever you want. Used by the login
 *  process to identify the provider selected by the user.
 */
$config['providers'] = array(
	'www.google.com' => array(
		'endpoint' => 'https://www.google.com/accounts/o8/id',
		'title' => 'google'
	),
	'www.yahoo.com' => array(
		'endpoint' => 'https://me.yahoo.com',
		'title' => 'yahoo'
	),
	'www.aol.com' => array(
		'endpoint' => 'https://www.aol.com',
		'title' => 'aol'
	)
);
