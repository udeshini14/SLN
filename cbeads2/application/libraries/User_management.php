<?php

/** ****************************** libraries/User_management.php **********************************
 *
 *    This class provides functions for managing users. It must be used carefully!
 *
 ** Changelog:
 *
 *  2012/02/15 - Markus
 *  - Fixed bug in create_cbeads_user() where save() wasn't called on the newly created user.
 *
 *************************************************************************************************/
class User_management
{
	var $_CI = null;
	
	/**
	 *	An unknown error occurred. The function could not accomplish its task for some reason.
	 */
	CONST ERR_UNKNOWN = 1;
	/**
	 *	A validation failure occurred. Ensure the values provided are valid!
	 */
	CONST ERR_VALIDATION_FAILURE = 2;
	/**
	 *	The names used for finding a team-role are not valid. Either the team and/or the role don't exist, or no team-role record exists that use the two.
	 */
	CONST ERR_INVALID_TEAMROLE = 3;
	/**
	 *	A database error occurred.
	 */
	CONST ERR_DB_ERROR = 4;
	/**
	 *	Invalid user id.
	 */
	CONST ERR_INVALID_USER = 5;
	
	
	function User_management()
	{
		$this->_CI =& get_instance();      // Reference to the CodeIgniter instance
	}
	
	/**
	 *	Registers a user with CBEADS using only a username, email and first and last names.
	 *	@param array $values
	 *		An array containing these items:\n
	 *		- username - the username for this new user. Must be unique! Required!
	 *		- email - the email for this new user. Must be unique! Required!
	 *		- firstname - the first name of the user. Required!
	 *		- lastname - the last name of the user. Required!
	 *  @Returns an associative containing a 'success' flag. On success, the array also contains
	 *      the fields:\n
	 *		- user - holds the newly created user record
	 *		- password - holds the auto generated password.
	 *  	When an error occurs, there will be an error field containing an associative array
	 *		with these fields:\n
	 *		- ts - a timestamp for looking up the error in the CodeIgniter logfile.
	 *		- message - a message about the error.
	 *		- type - indicates the type of error. Use the error constants in the User_management
	 *		class to work out what error this is.\n
	 *		Possible errors: ERR_INVALID_TEAMROLE, ERR_DB_ERROR
	 */
	function register_user($values)
	{
		$username = isset($values['username']) ? trim($values['username']) : NULL;
		$email = isset($values['email']) ? trim($values['email']) : NULL;
		$firstname = isset($values['firstname']) ? trim($values['firstname']) : NULL;
		$lastname = isset($values['lastname']) ? trim($values['lastname']) : NULL;
		
		$result = $this->create_cbeads_user($username, $email, $firstname, $lastname);
		return $result;
	}
	
	/**
	 *	Gets a nonce which can be used to validate a password reset request.
	 *	@param integer $user_id
	 *		The user id for which to create the nonce.
	 *	@param array $opts
	 *		An associative array of options:\n
	 *		- duration - time in minutes that the nonce should remain valid. If not provided, 
	 *		defaults to 60 minutes.
	 *	@Returns an array containing a 'success' flag. On success, the array also contains
	 *		the field 'nonce' which contains the nonce generated.
	 *		On failure, there will be an error field containing an associative array
	 *		with these fields:\n
	 *		- ts - a timestamp for looking up the error in the CodeIgniter logfile.
	 *		- message - a message about the error.
	 *		- type - indicates the type of error. Use the error constants in the User_management
	 *		class to work out what error this is.\n
	 *		Possible errors: ERR_INVALID_USER, ERR_DB_ERROR
	 */
	function request_password_reset_nonce($user_id, $opts = array())
	{
		$user = Doctrine::getTable('cbeads\User')->find($user_id);
		$duration = isset($opts['duration']) ? $opts['duration'] : 60;
		if(!$user)
		{
			return array('success' => FALSE, 'msg' => 'User not found', 'type' => $this::ERR_INVALID_USER);
		}
		$result = $this->generate_password_reset_nonce($user_id, $duration);
		return $result;
	}
	
	/**
	 *	Validates a password reset nonce for a given user. Also removed any expired
	 *	reset nonces.
	 *  @param integer $user_id
	 *		The user id for which to validate the password nonce.
	 *	@param string $nonce
	 *		The nonce string.
	 *	@Returns boolean TRUE if the nonce validates, otherwise boolean FALSE.
	 */
	function validate_password_reset_nonce($user_id, $nonce)
	{
		$prn = Doctrine::getTable('cbeads\Password_reset_nonce')->findOneByNonceAndUserId($nonce, $user_id);
		if($prn)
		{
			$this->clear_old_password_reset_nonces();
			return true;
		}
		return false;
	}
	
	/**
	 *	Invalidates a password reset nonce for a given user. This should be called
	 *  when a user has reset their password successfully.
	 *  @param integer $user_id
	 *		The user id for which to invalidate the password nonce.
	 *	@param string $nonce
	 *		The nonce string.
	 *	@Returns nothing.
	 */
	function invalidate_password_reset_nonce($user_id, $nonce)
	{
		$prn = Doctrine::getTable('cbeads\Password_reset_nonce')->findOneByNonceAndUserId($nonce, $user_id);
		if($prn) $prn->delete();
		$this->clear_old_password_reset_nonces();
	}
	
	
	
	
	/** Private Functions **/
	
	// Creates a new user. Email is sent to the specified email address containing
	// the randomly generated password.
	private function create_cbeads_user($username, $email, $firstname, $lastname)
	{

		$pass = $this->generate_random_string();
		$require_pass_change = FALSE;

		// Create user.
		$user = new cbeads\User();
		$user->uid = $username;
		$user->email = $email;
		$user->firstname = $firstname;
		$user->lastname = $lastname;
		$user->password = $pass;
		$user->change_password = $require_pass_change ? 1 : 0;
		$user->can_login = 1;
		if(!$user->isValid())
		{
			$errorMessages = "";
			$errors_ = $user->getErrorStack();
			foreach($errors_ as $fieldName => $errorCodes)
			{
				$errorMessages .= "\nField '" . $fieldName . "': ";
				for($i = 0; $i < count($errorCodes); $i++)
				{
					if($errorCodes[$i] == "type") $errorMessages .= "type does not match";
					elseif($errorCodes[$i] == "length") $errorMessages .= "length does not match";
					elseif($errorCodes[$i] == "constraint") $errorMessages .= "constraint does not match";
					elseif($errorCodes[$i] == "unique") $errorMessages .= "the value is not unique";
					elseif($errorCodes[$i] == "notnull") $errorMessages .= "must provide a value";
					else $errorMessages .= $errorCodes[$i];
					if($i+1 < count($errorCodes)) $errorMessages .= ', ';
				}
			}
			$ts = time();		// Can use the timestamp to look up error in the log file. Assumes two errors don't occur at the same time!
			log_message('error', "User Management: Account Creation Failure @$ts\n$errorMessages");
			$error = array('ts' => $ts, 'message' => $errorMessages, 'type' => $this::ERR_DB_ERROR);
			return array('success' => FALSE, 'error' => $error);
		}

		// Assign the user to the team and role specified in the config file.
		$tr = $this->_CI->config->item('cbeads_registration_default_teamrole');
		$team = $tr['team'];
		$role = $tr['role'];
		$q = Doctrine_Query::create()
			->select('tr.id')
			->from('cbeads\Team_role tr, tr.Team t, tr.Role r')
			->where('t.name = ? AND r.name = ?', array($team, $role));
		$tr = $q->fetchOne();
		if($tr === FALSE)
		{
			$ts = time();
			log_message('error', "User Management: Account Creation Failure @$ts\nUnable to locate the requested team-role ($team, $role)");
			$error = array('ts' => $ts, 'message' => "Unable to locate the requested team-role ($team, $role).", 'type' => $this::ERR_INVALID_TEAMROLE);
			return array('success' => FALSE, 'error' => $error);
		}
		$user->link('TeamRoles', array($tr->id));
		$user->save();
		
		return array('success' => TRUE, 'user' => $user, 'password' => $pass);
	}

	
	// Checks if a string contains characters allowed in a name.
	// Allowed characters are: letters, spaces and `
	// TODO: Is it possible to check for any unicode letters, for i18n? Or maybe just have a list 
	//       of characters that cannot be included.
	private function is_name_valid($name)
	{
		$res = preg_match('/[^a-zA-Z0-9 `]/', $name);
		return $res == 0 ? TRUE : FALSE;
	}
	
	// Generates a random string of a certain length containing numbers and lower case letters.
	private function generate_random_string()
	{
		$length = 10;						// 10 characters long
		$str = '';
		for($i = 0; $i < $length; $i++)
		{
			$num = mt_rand(0, 35);
			$str .= base_convert($num, 10, 36);		// Values above 9 are converted to characters.
		}

		return $str;
	}
	
	// Generates a password reset nonce for a user to be valid for the specified duration.
	// Also removes any expired reset nonces.
	// user_id - the id of the user
	// duration - for how many minutes the nonce will remain valid.
	private function generate_password_reset_nonce($user_id, $duration)
	{
		$nonce = $this->get_uuid();
		$ts = time();
		$prn = new cbeads\Password_reset_nonce();
		$prn->user_id = $user_id;
		$prn->expires = date('Y-m-d H:i:s', $ts + $duration * 60);		// now + minutes in miliseconds.
		$prn->nonce = $nonce;
		if(!$prn->isValid())
		{
			$errorMessages = "";
			$errors_ = $prn->getErrorStack();
			foreach($errors_ as $fieldName => $errorCodes)
			{
				$errorMessages .= "\nField '" . $fieldName . "': ";
				for($i = 0; $i < count($errorCodes); $i++)
				{
					if($errorCodes[$i] == "type") $errorMessages .= "type does not match";
					elseif($errorCodes[$i] == "length") $errorMessages .= "length does not match";
					elseif($errorCodes[$i] == "constraint") $errorMessages .= "constraint does not match";
					elseif($errorCodes[$i] == "unique") $errorMessages .= "the value is not unique";
					elseif($errorCodes[$i] == "notnull") $errorMessages .= "must provide a value";
					else $errorMessages .= $errorCodes[$i];
					if($i+1 < count($errorCodes)) $errorMessages .= ', ';
				}
			}
			$ts = time();		// Can use the timestamp to look up error in the log file. Assumes two errors don't occur at the same time!
			log_message('error', "User Management: Request Password Reset Failure @$ts\n$errorMessages");
			$error = array('ts' => $ts, 'message' => $errorMessages, 'type' => $this::ERR_DB_ERROR);
			return array('success' => FALSE, 'error' => $error);
		}
		$prn->save();
		$this->clear_old_password_reset_nonces();
		return array('success' => TRUE, 'nonce' => $nonce);
	}
	
	// Deletes any password reset nonces that have expired.
	private function clear_old_password_reset_nonces()
	{
		$ts = time();
		$q = Doctrine_Query::create()
			->delete('cbeads\Password_reset_nonce prn')
			->where('prn.expires <= ?', array(date('Y-m-d H:i:s', $ts)));
		$q->execute();
	}
	
	// Generates a pseudo random UUID (Unique Universal Idenfifier). Code taken from
	// http://www.php.net/manual/en/function.uniqid.php#94959
	private function get_uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

		  // 32 bits for "time_low"
		  mt_rand(0, 0xffff), mt_rand(0, 0xffff),

		  // 16 bits for "time_mid"
		  mt_rand(0, 0xffff),

		  // 16 bits for "time_hi_and_version",
		  // four most significant bits holds version number 4
		  mt_rand(0, 0x0fff) | 0x4000,

		  // 16 bits, 8 bits for "clk_seq_hi_res",
		  // 8 bits for "clk_seq_low",
		  // two most significant bits holds zero and one for variant DCE1.1
		  mt_rand(0, 0x3fff) | 0x8000,

		  // 48 bits for "node"
		  mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	
}


?>