<?php
/** ******************************** public/home.php ****************************************
 *
 *  This controller displays the home page which contains the login
 *  page. It is responsible for handling logins.
 *
 ** Changelog:
 *
 *  2010/08/24 - Markus
 *  - For now the email textbox has been removed from the login
 *    screen. Updated a error message to reflect this.
 *
 *  2010/08/30 - Markus
 *  - The send_to_login_page function now accepts a value that
 *    indicates why this function was called.
 *  - The index function now accepts a message value, which is
 *    displayed in the view. This is used to indicate if the user's
 *    session has expired.
 *
 *  2010/09/13 - Markus
 *  - Updated authenticate_user() to perform LDAP validation for 
 *    users that have '$LDAP$' as their password.
 *
 * -------------------------------------------------------------------
 *  2010/11/29 - Markus
 *  - Created this controller as a duplicate of cbeads/login which then replaces cbeads/login.
 *    It is a controller in the public namespace, making it accessible to all. Its purpose is
 *    to display the home page which acts as the login page. Better to have this as a public 
 *    controller than to make an exception in the user authentication/access module.
 *  - Cleaned up the code a bit.
 *  - When viewing public/home and a valid user session exists, the user is automatically shown
 *    the CBEADS view.
 *  - Added user_logout() function to handle logouts from the system. Want to show a message 
 *    telling the user they have logged out.
 *
 *  2010/12/02 - Markus
 *  - Added functions user_logout, show_home_page and session_expired.
 *
 *  2011/03/01 - Markus
 *  - Updated the validation function. The password and username fields are no longer given the
 *    required property. Instead, the user authentication function checks if both fields are 
 *    provided. That way when neither is provided only one error message is produced instead of
 *    two.
 *
 *  2011/04/18 - Markus
 *  - Added functions _record_login_success and _record_login_failure. These are called as 
 *    needed to record when a user has successfully logged in or failed to log in.
 *  - Removed functions user_logout and session_expired. Now using cookies to see if the user
 *    has logged out or their session has expired, and display the corresponding message.
 *  - In index(), when a login is successfully, a redirect is now performed to make the browser
 *    forget the username and password submitted with the form. Otherwise someone could move
 *    back in the browser history after they logged out, and hit refresh to force a resubmit of
 *    the username and password. This change hopefully works in all browsers.
 *
 *  2011/07/28 - Markus
 *  - Updated the _authenticate_user function. It now checks what the last used team-role was 
 *    for the user and sets it as the active one. Also added code to check if logging in is 
 *    enabled. If there are multiple failed log in attempts, the system now counts them. 
 *    Between 4 to 9 failed log in attempts the user must wait an ever increasing period for 
 *    the next log in attempt. After the 10 failed log in, the account is disabled (no log in).
 *
 *  2011/08/11 - Markus
 *  - Fixed bug in _authenticate_user. Had $teamrole = $teamroles[0] instead of
 *    $teamrole = $teamroles[0]->id.
 *
 *  2011/10/04 - Markus
 *  - Reorganised code in index() to make the flow more logical.
 *  - Removed function _do_validation() as the CI form_validation class is no longer used.
 *    Instead, calling _authenticate_user() directly.
 *  - Updated _authenticate_user() to store form validation errors in a class variable
 *    instead of using the CI form_validation class.
 *  - Now logging the error number when ldap bind fails. Stored in the CI log file.
 *  - Factored out duplicate code from _authenticate_user() and created a new function for it
 *    called _log_in_user. The function creates a new session for a user which means they are 
 *    logged in.
 *
 *  2011/10/05 - Markus
 *  - Added a try/catch block in _authenticate_user() to catch any potential exception caused by
 *    creating a temporary user and assigning the password to get hashed. Using custom exception
 *    to avoid the password being included in the error log as part of the stack trace.
 *
 *  2011/10/07 - Markus
 *  - Forgot to set a error message when LDAP bind() failed.
 *
 *  2011/11/02 - Markus
 *  - Removed function show_home_page() as it is no longer required.
 *  - Added function check_for_custom_home().
 *  - On login form submission, now checking if custom controllers have been specified. They are
 *    stored in a cookie 'cbeads_routing' so they can be used to determine which controllers to
 *    redirect to.
 *  - In index(), instead of checking for the existance of logout or session expired cookies,
 *    the uri segments are looked at to determine if a message needs to be displayed.
 *  - On successful login the cbeads_user_state cookie is now set.
 *
 *  2012/01/25 - Markus
 *  - Added function openid_login_request. This is used to initiate a redirect to an OpenID
 *    provider as requested.
 *  - Added function openid_login_reply. This is the function that OpenID providers redirect 
 *    users to once the user has accepted/rejected the request to provide information to this
 *    server. The function validates the parameters provided by the OpenID provider and if all
 *    is good begins logging in the user.
 *  - Added function _log_in_user_with_openid to log in the user with the given email provided
 *    by the OpenID provider.
 *  - Updated _authenticate_user() to test if the user account's password matches '$OPENID$'. 
 *    If it does, then the user can only log in using the OpenID method.
 *  - Updated _record_login_failure() to accept an optional parameter that contains a reason
 *    as to why the login attempt failed.
 *  - Updated index() to pass the error messsage to _record_login_failure().
 *
 *  2012/02/06 - Markus
 *  - Now using the 'base_url' config option instead of $_SERVER['HTTP_HOST'] when generating
 *    URLs for OpenID to make this work on server behind proxies.
 *
 *  2012/02/08 - Markus
 *  - Updated the index() function to pass variables to the public/home view that indicate if
 *    registration and password resetting are enabled.
 *  - Put in line in openid_login_reply function to obtain the attributes on reply validation
 *    failure.
 *
 *  2012/02/09 - Markus
 *	- Updated openid_login_reply() to fix a bug by setting the returnUrl explictly because by
 *    default the generated value may be incorrect. Ie, if pointing www.myserver.com to 
 *    /apache/cbeads/web, then the generated url would be www.myserver.com/cbeads/web/ when it
 *    shouldn't have cbeads/web as part of the URL.
 *
 **********************************************************************************************/
class Home extends CI_Controller {

	
	private $record_failure = FALSE;		// Only want to record login failure when the username and password were provided.
	private $form_data = array();			// Stores information to use when the login form is being displayed.
	
    // Constructor initialises the controller.
    public function __construct() 
    {
        parent::__construct();
        $this->load->helper(array('form','url', 'cookie'));
        //$this->load->library('form_validation');
    }

    public function index()
    {
		$message = "";
		$this->check_for_custom_home();

		// Was the login form submitted?
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			// Want to store the controller used for login, logout and the 'home' page if they
			// have been provided with the submitted form.
			$routing = array();
			if(isset($_POST['custom_login_controller'])) $routing['login'] = $_POST['custom_login_controller'];
			if(isset($_POST['custom_home_controller'])) $routing['home'] = $_POST['custom_home_controller'];
			if(isset($_POST['custom_logout_controller'])) $routing['logout'] = $_POST['custom_logout_controller'];
		
			if($this->_authenticate_user() == FALSE)
			{
				if($this->record_failure) 
					$this->_record_login_failure($this->form_data['username'], $this->form_data['error_msg']);
			}
			else
			{
				$this->_record_login_success();
				
				$data = serialize($routing);
				setcookie('cbeads_routing', $data, 0, '/');
				
				// Can be used to tell if a user was previously logged in or not.
				setcookie('cbeads_user_state', 1, 0, '/');
				
				// Force a redirect to this same page to clear the user's username and password from the browser's cache/history. This prevents one from going back in the history and resubmitting the login form data.
				redirect('public/home');
			}
		}
		else	// No valid session and no form submitted. Check if any actions have been specified.
		{
			$args = $this->uri->uri_to_assoc(4);	// Get elements after: index.php/public/home/index			
		
			// Check for a cookie that indicates a certain action was performed previously.
			if(isset($args['action_logged_out']))
			{
				$message = 'You have logged out from the system';
			}
			if(isset($args['action_expired']))
			{
				$message = 'Your session has expired. Please log in again.';
			}
		}
		
		if(isset($routing['login']))
		{
			$url = $routing['login'];
			if(!empty($this->form_data['username'])) $url .= '/username/' . $this->form_data['username'];
			if(!empty($this->form_data['error_msg'])) $url .= '/auth_error_msg/' . $this->form_data['error_msg'];
			if(!empty($message)) $url .= "/msg/$message";
			redirect($url);
		}
		else
		{
			$this->load->view('public/home', array(
				'message' => $message,
				'installation_name' => $this->config->item('cbeads_installation_name'),
				'form_data' => $this->form_data,
				'allow_registration' => $this->config->item('cbeads_public_registration_enabled'),
				'allow_password_reset' => $this->config->item('cbeads_password_reset_enabled')
			));
		}
    }

	// If a custom home page has been stored in the cbeads_routing cookie, then it redirects
	// to it. Otherwise it loads the default cbeads view.
	private function check_for_custom_home()
	{
        if($this->session->userdata('~uname'))	// User is logged in.
        {
			if(isset($_COOKIE['cbeads_routing']))	
			{
				$data = unserialize($_COOKIE['cbeads_routing']);
				if(!empty($data['home'])) 		// A custom home controller requested
				{
					redirect($data['home']);
				}
			}
			// Load the default cbeads frame setup.
			$this->load->view('cbeads/view_setup');
			return;
        }
	}
	
	// Is called when OpenID login is enabled and a user selected an OpenID provider (OP).
	// The provider requested is passed in the URL. A check is performed to ensure the 
	// provider is one of the accepted providers. The endpoint of the OP is discovered and
	// the user redirected to OP login page.
	public function openid_login_request()
	{
		$routing = array();
		if(isset($_POST['custom_login_controller'])) $routing['login'] = $_POST['custom_login_controller'];
		if(isset($_POST['custom_home_controller'])) $routing['home'] = $_POST['custom_home_controller'];
		if(isset($_POST['custom_logout_controller'])) $routing['logout'] = $_POST['custom_logout_controller'];
		
		$error = null;
		
		// OpenID login must have been enabled.
		$this->config->load('openid', TRUE);
		$openid = $this->config->item('openid');
		if($openid['enabled'] !== TRUE)
			$error = "OpenID login is not enabled on this server";

		// Must have a provider set.
		if(!isset($_POST['provider']) || $_POST['provider'] == '')
			$error = "No account provider was specified.";
		
		// Check if the provider is allowed on this server.
		$provider = $_POST['provider'];
		$endpoint = null;
		foreach($openid['providers'] as $name => $opts)
		{
			if($opts['title'] == $provider)
				$endpoint = $opts['endpoint'];
		}
		if($endpoint === null)
			$error = "The provider '$provider' is not accepted by this server!";
		
		if($error !== null)
		{
			if(isset($routing['login']))
			{
				$url = $routing['login'] . "/msg/$error";
				redirect($url);
			}
			else
			{
				$this->load->view('public/home', array(
					'message' => $error,
					'installation_name' => $this->config->item('cbeads_installation_name')
				));
			}
		}

		try
		{
			include_once(APPPATH . 'libraries/openid.php');
			$openid = new LightOpenID($this->config->item('base_url'));
			$openid->identity = $endpoint;
			$openid->realm     = $this->config->item('base_url');
			$openid->returnUrl = $openid->realm . "index.php/public/home/openid_login_reply";
			$openid->required = array('contact/email');
			header("Location: " . $openid->authUrl());
		}
		catch(Exception $e)
		{
			$timestamp = time();
			$html =  "Message: " . $e->getMessage() ."\n";
			$html .=  "Code: " . $e->getCode() ."\n";
			$html .=  "File: " . $e->getFile() ."\n";
			$html .=  "Line: " . $e->getLine() ."\n";
			$html .=  "Trace: " . $e->getTraceAsString()  ."\n";
			$html .=  "TS: " . $timestamp . "\n";
			$res = error_log("PHP Exception:  " . $html);
			$error = "A problem occurred with the OpenID login. If this problem persists, please contact the site administrator.";
			if(isset($routing['login']))
			{
				$url = $routing['login'] . "/msg/$error";
				redirect($url);
			}
			else
			{
				$this->load->view('public/home', array(
					'message' => $error,
					'installation_name' => $this->config->item('cbeads_installation_name'),
					'form_data' => $this->form_data
				));
			}
		}
	}
	
	// Is called when an OpenID provider has redirected the user to here. Need to check if
	// the login succeeded. On success, the user can be logged in (assuming a matching account
	// is located).
	// On failure an error message is generated and the user returned to the login page.
	public function openid_login_reply()
	{
		parse_str($_SERVER['QUERY_STRING'],$_GET);	// The global GET array is cleared by CodeIgniter if enable query strings is set to false in the config file. LightOpenID requires it, so it must be repopulated.
		
		$error = null;
		$email = "";
		
		// OpenID login must have been enabled.
		$this->config->load('openid', TRUE);
		$openid = $this->config->item('openid');
		if($openid['enabled'] !== TRUE)
			$error = "OpenID login is not enabled on this server";
		
		include_once(APPPATH . 'libraries/openid.php');
		try
		{
			$openid = new LightOpenID($this->config->item('base_url'));
			$openid->realm     = $this->config->item('base_url');
			$openid->returnUrl = $openid->realm . "index.php/public/home/openid_login_reply";
			if ($openid->mode)
			{
				if($openid->validate())
				{
					$attr = $openid->getAttributes();
					//echo "Match user with email: " . $attr['contact/email'];
					// Attempt to log the user in. This may fail if no matching account is found.
					$result = $this->_log_in_user_with_openid($attr['contact/email']);
					if($result === TRUE)
					{
						$this->_record_login_success();
						// Can be used to tell if a user was previously logged in or not.
						setcookie('cbeads_user_state', 1, 0, '/');
						// Force a redirect to clear the url. User will be sent to their requested home page.
						redirect('public/home');
						return;
					}
					// In the future, could send user to a registration page at this point.
					$error = $result['error'];
				}
				else
				{
					if($openid->mode == 'cancel')		// User cancelled the authentication request. Send back to login screen.
					{
						if(isset($_COOKIE['cbeads_routing']))	
						{
							$data = unserialize($_COOKIE['cbeads_routing']);
							if(!empty($data['login'])) 		// A custom login controller requested
							{
								redirect($data['login']);
							}
						}
						// Show default cbeads login view.
						$this->load->view('public/home', array(
							'installation_name' => $this->config->item('cbeads_installation_name')
						));
						return;
					}
					else	// Validation on the data from the provider failed.
					{
						$error = "Failed to validate response from OpenID Provider.";
					}
					$attr = $openid->getAttributes();
					$email = $attr['contact/email'];
				}
			}
			else	// Data received did not contain any mode value.
			{
				$error = "Mode not provided!";
			}
		}
		catch(Exception $e)
		{
			$timestamp = time();
			$html =  "Message: " . $e->getMessage() ."\n";
			$html .=  "Code: " . $e->getCode() ."\n";
			$html .=  "File: " . $e->getFile() ."\n";
			$html .=  "Line: " . $e->getLine() ."\n";
			$html .=  "Trace: " . $e->getTraceAsString()  ."\n";
			$html .=  "TS: " . $timestamp . "\n";
			$res = error_log("PHP Exception:  " . $html);
			$error = "A problem occurred with the OpenID login. If this problem persists, please contact the site administrator.";
		}
		
		$this->_record_login_failure('OpenID: ' . $email, $error);
		
		if(isset($_COOKIE['cbeads_routing']))	
		{
			$data = unserialize($_COOKIE['cbeads_routing']);
			if(!empty($data['login'])) 		// A custom login controller requested
			{
				$url = $data['login'];
				if($error !== null) $url .= '/auth_error_msg/' . $error;
				redirect($url);
			}
		}
		// Show default cbeads login view.
		$this->load->view('public/home', array(
			'message' => $error,
			'installation_name' => $this->config->item('cbeads_installation_name')
		));
		
	}
	
	// Logs in a user that used OpenID to validate their identity.
	private function _log_in_user_with_openid($email)
	{
		// Check all account emails. Only one should match. If there is more than one
		// then we cannot be sure which account to use.
		$users = Doctrine::getTable('cbeads\User')->findByEmail($email);
		if(count($users) == 1)
		{
			$u = $users[0];
			// Ensure the user's account was not disabled.
			$status = json_decode($u->status, TRUE);
			$can_log_in = $u->can_login;
			if(!$can_log_in) 
			{
				return array('error' => 'Your account has been disabled. Please contact the site administrator to enable your account.');
			}
			$this->_log_in_user($u, $status);
			return TRUE;
		}
		else
		{
			if(count($users) == 0)
			{
				$error = "No account could be found to match your email address.";
			}
			else
			{
				$error = "Unfortunately you could not be logged into cbeads because two or more accounts use the same email address. Please contact the site administrator to discuss this issue.";
			}
			return array('error' => $error);
		}
	}

    // Called by the form validation function. Here we check if the user exists, if their password is valid 
	// and if they are event able to log in.
    private function _authenticate_user()
    {
        $username = $this->input->post('username');
		$password = $this->input->post('password');
		$this->form_data['username'] = $username;
		if($password == '' && $username == '') 
		{
			// $this->form_validation->set_message('_authenticate_user','Username and password are required.');
			$this->form_data['error_msg'] = 'Username and password are required.';
			return FALSE;
		}
		else if($username == '')
		{
			// $this->form_validation->set_message('_authenticate_user','Username is required.');
			$this->form_data['error_msg'] = 'Username is required.';
			return FALSE;
		}
		else if($password == '')
		{
			// $this->form_validation->set_message('_authenticate_user', 'Password is required.');
			$this->form_data['error_msg'] = 'Password is required.';
			return FALSE;
		}
		
		$this->record_failure = TRUE;		// Can now record login failure since username and pass were provided.
        $user_found = FALSE;				// Was the user found in the system?
		$can_log_in = FALSE;				// Can the user log in?
		$must_wait = FALSE;					// Have to wait before attempting another log in?
		$msg = 'Invalid login. Please try again.';
		
		$u = Doctrine::getTable('cbeads\User')->findOneByUid($username);
		$status = NULL;
		if($u)
		{
			$user_found = TRUE;
			// Test if a log in attempt is allowed. There might be a log in time out present due to previous log in failures, or
			// the user may not be able to log in at all.
			$status = json_decode($u->status, TRUE);
			$can_log_in = $u->can_login;
			if(!$can_log_in) 
			{
				$msg = "Your account has been disabled. Please contact the site administrator to enable your account.";
			}
			else
			{
				$can_log_in = TRUE;
				if(isset($status['login_status']) && $status['login_status'] !== NULL)
				{
					$parts = preg_split('/:/', $status['login_status']);
					if(count($parts) == 2)
					{
						$last_log_in = $parts[0];
						$failed = $parts[1];
						// First three failures are allowed. From the 4th onwards, have to wait some time before another attempt is allowed.
						if($failed > 3)
						{
							$wait = ($failed - 3) * 60;
							if(time() < $last_log_in + $wait) 
							{
								$can_log_in = FALSE;
								$must_wait = TRUE;
							}
						}
					}
				}
			}
		}
		
		$authentication_failed = FALSE;
        if($user_found && $can_log_in)
        {
            // Users with a password value of '$LDAP$' need to be authenticated using ldap.
            if($u->password == '$LDAP$')
            {
                // Bind the user & pass to the LDAP directory server specified in the config file.
                require APPPATH.'config/config.php';
                $conn = ldap_connect($config['cbeads_LDAP_IP']);	// Creates a link identifier.
                $ldaprdn = "cn=$username,".$config['cbeads_BASEDN'];
                $ldappass = "$password";
                $ldapbind = @ldap_bind($conn, $ldaprdn, $ldappass );	// Does actual connection to server.
                // Check binding success. Means login succeeded.
                if ($ldapbind) 
                {
					$this->_log_in_user($u, $status);
                    return TRUE;
                }
				else
				{
					// Record ldap error number. Should special error messages be generated based on the value?
					$errno = ldap_errno($conn);
					log_message('Error', 'LDAP Bind failed with error number: ' . $errno);
					$this->form_data['error_msg'] = $msg;
					return FALSE;
				}
            }
			// Users with a password value of '$OPENID$' can ONLY log in using the OpenID method!
			elseif($u->password == '$OPENID$')
			{
				$this->form_data['error_msg'] = 'This account can only be accessed via an OpenID provider.';
				$this->session->sess_destroy();
				$this->record_failure = FALSE;
				return;
			}
            else
            {
				// Create a temp user to hash the password for comparison.
				$u_input = new cbeads\User();
				// This might cause an exception (see the _set() function in Doctrine Record class)
				// although I'm not sure. Just in case, handle anything unexpected ourselves because
				// the log file should not store the entered password (this means not including the
				// trace string!).
				try
				{
					$u_input->password = $password;		// hashed on assign
				}
				catch(Exception $e)
				{
					$timestamp = time();
					$html =  "Message: " . $e->getMessage() ."\n";
					$html .=  "Code: " . $e->getCode() ."\n";
					$html .=  "File: " . $e->getFile() ."\n";
					$html .=  "Line: " . $e->getLine() ."\n";
					$html .=  "TS: " . $timestamp . "\n";
					error_log("PHP exception:  " . $html);
					if(ini_get('display_errors') == TRUE) 
					{
						show_error(str_replace("\n", "<br>", $html));
					}
					else
					{
						show_error("An unexpected error has occurred. Please inform the site administrator about this problem.");
					}
				}
				if($u->password == $u_input->password)
				{
					$this->_log_in_user($u, $status);
					return TRUE;
				}
            }
			$authentication_failed = TRUE;
        }

		// If the user exists but the password was wrong, increment the failed log in counter.
		if($user_found && $authentication_failed)
		{
			if(!isset($status['login_status']) || $status['login_status'] === NULL)
			{
				$status['login_status'] = time() . ':' . 0;
			}
			$parts = preg_split('/:/', $status['login_status']);
			if(count($parts) != 2) $parts = array(time(), 1);
			$last_log_in = $parts[0];
			$failed = $parts[1];
			// First three failures are allowed. From the 4th onwards, increment the wait time by one minute. After 10 failures, disable logging in.
			if($failed > 8)
			{
				$u->can_login = 0;
				$status['login_status'] = time() . ':0';
				$msg = "Your account has been disabled due to too many failed log in attempts. Please contact the site administrator to enable you account.";
			}
			elseif($failed > 2)
			{
				$failed++;
				$status['login_status'] = time() . ":$failed";
				$msg = "Invalid Login. Please wait " . ($failed - 3) . ($failed - 3 > 1 ? " minutes" : " minute") . " before attempting to log in again.";
			}
			else
			{
				$failed++;
				$status['login_status'] = time() . ":$failed";
			}
			$u->status = json_encode($status);
			$u->save();
		}
		// When in the timeout period tell the user how many minutes are left to wait.
		if($must_wait)
		{
			$parts = preg_split('/:/', $status['login_status']);
			$last_log_in = $parts[0];
			$failed = $parts[1];
			$minutes = $last_log_in + ($failed - 3) * 60 - time();
			$c = ceil(($last_log_in + ($failed - 3) * 60 - time()) / 60);
			$msg = "There have been $failed unsuccessful log in attempts. Please wait $c " . ($c > 1 ? "minutes" : "minute") ." before attempting to log in again.";
			$this->record_failure = FALSE;		// Don't record log in attempts when the account is timed out.
		}
		
        //$this->form_validation->set_message('_authenticate_user',$msg);
        $this->form_data['error_msg'] = $msg;
		$this->session->sess_destroy();
        
        return FALSE;
    }

    // Records a login success.
    private function _record_login_success()
    {
        $this->load->library('user_agent');
        $browser = $this->agent->browser();
        $version = $this->agent->version();
        $ts = time();
        $user_id = $this->session->userdata('~id');
        $event = "login_success";
        
        $data = array('event' => $event, 'uid' => $user_id, 'ts' => $ts, 'ip' => $_SERVER['REMOTE_ADDR'], 'browser' => $browser,
                      'version' => $version);
        $result = cbeads_write_to_access_log($data);
    }
    
    // Records a login failure to the CBEADS access log.
	// @Param string $username
	//		The username used to log in for normal and LDAP logins. For OpenID logins, the format is:
	//      OpenID: email_used
	// @Param string $reason
	//		A message storing the reason for the login failure. Default value is null.
    private function _record_login_failure($username, $reason = null)
    {
        $this->load->library('user_agent');
        $browser = $this->agent->browser();
        $version = $this->agent->version();
        $ts = time();
        $event = "login_failure";
        
        $data = array('event' => $event, 'username' => $username, 'ts' => $ts, 'ip' => $_SERVER['REMOTE_ADDR'], 'browser' => $browser,
                      'version' => $version);
		if($reason !== null)
			$data['reason'] = $reason;
        cbeads_write_to_access_log($data);
    }

	// Logs in a user by creating a session for them. Also updates their status information.
	// u: the doctrine record for the user to log in.
	// status: the status data for that user. Must have been json_decoded().
	private function _log_in_user($u, $status)
	{
		// The user will either be assigned to the first team-role available or to the last used team-role if recorded.
		$teamroles = $u->TeamRoles;
		$teamrole = $teamroles[0]->id;
		if(isset($status['last_team-role']) && $status['last_team-role'] !== NULL) $teamrole = $status['last_team-role'];
		
		$data = array('~id' => $u->id, '~uname' => $u->uid, '~fullname' => $u->firstname . " " . $u->lastname, '~team-role' => $teamrole);
		$this->session->set_userdata($data);
		// Reset the login status value for this user.
		$status['login_status'] = time() . ':' . 0;
		$u->status = json_encode($status);
		$u->save();
	}
	
}