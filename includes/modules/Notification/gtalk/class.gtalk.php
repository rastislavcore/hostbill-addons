<?php
require_once( "XMPPHP".DS."XMPP.php" );

class Gtalk extends NotificationModule {

    protected $modname = 'Gtalk messages';
    protected $description = 'Messages sent from Gtalk admin account to any gtalk.\n<br />Gtalk email for staff member can be set in his profile';
    
    /**
     * Module configuration, visible in Settings->modules
     * @var array
     */
    protected $configuration = array(
        'Gtalk admin email' => array(
            'value' => '',
            'type' => 'input',
            'description' => 'Insert there admin login email to Gtalk account. Example: admin@gmail.com'
        ), 
        'Gtalk admin password' => array(
            'value' => '',
            'type' => 'input',
            'description' => 'Insert there admin password to Gtalk account.'
        ), 
        'Admin Field' => array(
            'value' => 'gtacc',
            'type' => 'input',
            'description' => 'Provide variable name for Admins.'
        ),
        'Client Field' => array(
            'value' => 'gtacc',
            'type' => 'input',
            'description' => 'Provide variable name from Clients->Registration fields responsible for holding client Gtalk login.\nIf this field is empty no notifications will be sent to client'
        )
    );

    /**
     * Install module.
     * We need to add custom admin field for keeping his mobile number
     * We also need add custom client field (it can be later removed / updated by admin)
     */
    public function install() {

        $adm_fid = $this->configuration['Admin Field']['value'];
        if($adm_fid!="email") {
        $admin_field = array(
            'name' => 'Gtalk email',
            'code' => $adm_fid,
            'type' => 'input'
        );
        $fieldsmanager = HBLoader::LoadModel('EditAdmins/AdminFields');
        $fieldsmanager->addField($admin_field);
        }

        $clt_fid = $this->configuration['Client Field']['value'];
        if($clt_fid!="email") {
        $client_field = array(
            'name' => 'Gtalk email',
            'code' => $clt_fid,
            'field_type' => 'input',
            'editable'=>true,
            'type'=>'All',
            'description' => 'Provide your Gtalk email to recive informations to your Gtalk account.'
        );
        $clientfieldsmanager = HBLoader::LoadModel('Clients');
        $clientfieldsmanager->addCustomField($client_field);
        }
    }

    /**
     * Send notification to admin.
     * HostBill will automatically execute this function if admin needs
     * to be notified and is allowed to be notified about something
     *
     * @param integer $admin_id Administrator ID to notify (see hb_admin_* tables)
     * @param string $subject Subject (for sms it may be omited)
     * @param string $message Message to send
     */
    public function notifyAdmin($admin_id, $subject, $message) {

        $adm_fid = $this->configuration['Admin Field']['value'];
        //1. get related admin details, and check if he have mobile phone added
        $editadmins = HBLoader::LoadModel('EditAdmins');
        $admin = $editadmins->getAdminDetails($admin_id);

        if (!$admin) { //admin not found
            return false;
        } elseif (!$admin[$adm_fid]) { //admin field not found
            return false;
        }

        //send message
        return $this->_send($admin[$adm_fid], $subject, $message);
    }

    /**
     * Send notification to client
     * HostBill will automatically execute this function if client needs
     * to be notified and is allowed to be notified about something
     *
     *
     * @param integer $client_id Client ID to notify  (see hb_client_* tables)
     * @param string $subject Subject (for sms it may be omited)
     * @param string $message Message to send
     */
    public function notifyClient($client_id, $subject, $message) {

        $clt_fid = $this->configuration['Client Field']['value'];

        if (!$clt_fid) { //no client field configured->do not notify clients
            return false;
        }

        //. get client details and check for mobile phone field
        $clients = HBLoader::LoadModel('Clients');
        $client_details = $clients->getClient($client_id);

        if (!$client_details) {
            return false;
        } elseif (!$client_details[$clt_fid]) {
            //no account provided
            return false;
        }

        //send message
        return $this->_send($client_details[$clt_fid], $subject, $message);
    }


    private function _send($to, $subject, $message) {
        $conn = new XMPPHP_XMPP('talk.google.com', 5222, $this->configuration['Gtalk admin email']['value'], $this->configuration['Gtalk admin password']['value'], 'hosting', 'gmail.com', $printlog=False, $loglevel = XMPPHP_Log::LEVEL_ERROR);  
        
        try {  
            $conn->useEncryption(true);
            $conn->connect();  
            $conn->processUntil('session_start');  
            $conn->presence();
            $conn->subscribe($to);  
            $conn->message($to, "«".$subject."» ".$message);  
            $conn->disconnect();
            return true;  
        } catch(XMPPHP_Exception $e) {  
            $this->addError($e->getMessage());
            return false;  
        }
        
    }

}

?>
