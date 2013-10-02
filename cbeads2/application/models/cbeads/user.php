<?php
namespace cbeads;
/** ********************* cbeads/user.php ***********************
 *
 *  This model defines the cbeads user table.
 *
 ** Changelog:
 * 
 *  2010/04/30 - Markus
 *  - Moved to inheriting from the Doctrine_Record_Ex class 
 *    because it provides the select() function which collects
 *    model data as well as other usefull functions that all
 *    models should have.
 *
 *  2010/05/01 - Markus
 *  - The model is now set up to automatically generate the table
 *    definition.
 *
 *  2011/03/04 - Markus
 *  - The model is now using the auto_generate_definition function.
 *
 *  2011/04/21 - Markus
 *  - Removed some old code.
 *
 *  2012/01/24 - Markus
 *	- Now checking if password matches '$OPENID$'. If this value is
 *    providede it indicates that the user can only log in with an
 *    OpenID account.
 *
 ******************************************************************/
class User extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.user');
        $this->auto_generate_definition();
    }

    public function setUp() {
        $this->hasMutator('password', '_encrypt_password');
        
        $this->hasMany('cbeads\Team_role as TeamRoles', 
                       array(
                            'local' => 'user_id',
                            'foreign' => 'team_role_id',
                            'refClass' => 'cbeads\User_team_role'
                       )
        );
        
    }

    protected function _encrypt_password($value) {
        if($value == '$LDAP$')
		{
            $this->_set('password', '$LDAP$');
		}
		elseif($value == '$OPENID$')
		{
			$this->_set('password', '$OPENID$');
		}
        else
        {
            $salt = '#*seCrEt!@-*%';
            $this->_set('password', md5($salt . $value));
        }
    }

    
}
