<?php
namespace cbeads;
/** ****************************** cbeads/password_reset_nonce.php ********************************
 *
 *  This is the model for the password_reset_nonce table.
 *
 ** Changelog:
 * 
 *
 *************************************************************************************************/
class Password_reset_nonce extends \Doctrine_Record_Ex
{

    public function setTableDefinition() {
        $this->setTableName('cbeads.password_reset_nonce');
        $this->auto_generate_definition();
    }
	
} 