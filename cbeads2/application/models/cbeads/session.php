<?php
/** ********************* cbeads/session.php ***********************
 *
 *  This model is for the ci_sessions table in the cbeads database.
 *  It will allow doctrine to access existing sessions (so we can
 *  find out who is online, as well as allow us to delete old
 *  sessions (for when the session class refuses to delete them
 *  automatically).
 *
 ** Changelog:
 *
 *  2010/03/31 - Markus
 *  - Created model for use by the cbeads/session controller.
 *
 *  2010/04/30 - Markus
 *  - Moved to inheriting from the Doctrine_Record_Ex class 
 *    because it provides the select() function which collects
 *    model data as well as other usefull functions that all
 *    models should have.
 *
 ******************************************************************/
namespace cbeads;

class Session extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.ci_sessions');
        $this->hasColumn('session_id', 'string', 40, array('primary' => 'true'));
        $this->hasColumn('ip_address', 'string', 16);
        $this->hasColumn('user_agent', 'string', 50);
        $this->hasColumn('last_activity', 'integer', 4, array('unsigned' => 'true'));
        $this->hasColumn('user_data', 'string');
    }
    
}
