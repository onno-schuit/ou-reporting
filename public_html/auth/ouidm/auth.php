<?php

/**
 * @author Ir. M. K. Zamani 
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package  
 *
 * Authentication Plugin: OU IMD 
 *
 * Authenticates against an IDM server
 *
 * 07-12-2010  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');
require ('ouidm_enc.php');

/**
 * ouidm authentication plugin.
 */
class auth_plugin_ouidm extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_plugin_ouidm() {
        $this->authtype = 'ouidm';
        $this->config = get_config('auth/ouidm');   
        if (empty($this->config->extencoding)) {
            $this->config->extencoding = 'utf-8';
        }     
    }

    
    /**
     * Encrypt and decrypt requested data
     */
    function loginpage_hook() {
        global $frm;
        
        $ouidm_data = stripslashes(optional_param('ouidmdata'));
            
        if ($data = unserialize($ouidm_data)) {
            global $CFG;

            /**
             * Controle if the user is coming from a trusted server
             * otherwise don't access anything and give an error
             * 
             * */

            //$ipadrs = decrypt($data['ipadrs'], MY_KEY);
            
            
            /**
              Bug-Fix 
              Datum: 28-07-2011
              Als de encryption niet werkt gaan we de gebruikersnaam gewoon van $_SERVER['HTTP_OUUSER'] overnemen.            
            */          
            $ipadrs = $_SERVER['SERVER_ADDR'];              

            
            
            if (!$this->ouidm_isvalidremoteaddr($ipadrs)){              
                $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1202');
            }           

        
            /** 
             * @var Encryption
             * Encryption method.    
             */                 
                                
            //$decryt_username = decrypt($data['username'],MY_KEY);
            //$decryt_password = decrypt($data['username'],MY_KEY); 
            
            
            /**
              Bug-Fix (Encryption werkt met UTF-8 en moet opnieuw worden gemaakt)
              Datum: 28-07-2011
              Als de encryption niet werkt gaan we de gebruikersnaam gewoon van $_SERVER['HTTP_OUUSER'] overnemen.            
            */          
            if (empty($decryt_username) || empty($decryt_password))
            {
                //Controle if the usernames match
                $decryt_username = $_SERVER['HTTP_OUUSER'];
                $decryt_password = $_SERVER['HTTP_OUUSER'];                 
            }
            
                        
            $internalKey = '416b736dSDwbe9X@';
            $internalPassword = md5($decryt_username.$internalKey);
             
            if ($user = get_record('user', 'username', $decryt_username, 'mnethostid', $CFG->mnet_localhost_id)) {
                if (!validate_internal_user_password($user, $internalPassword)) {                                       
                     /**
                     * The user exists, but logs in for the first time with OU-IMD 
                     * Make a password and log the user in
                     * */
                    if (!$this->user_update_password($decryt_username, $internalPassword)){
                        $this->ouidm_redirect("Can not update password");
                    }                   
                }
            } else {
            //echo $decryt_username;
            //exit();
                $courseid = $this->ouidm_createuser($decryt_username, $internalPassword);               
            }
            $this->ouidm_update_user($decryt_username);
            $this->ouidm_course_update($decryt_username);
            $this->ouidm_user_update_group($decryt_username);
            $frm->username = $decryt_username;
            $frm->password = $internalPassword;

            
            return true;
        } elseif (!empty($ouidm_data)) {    
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1203');
        }
    } // function loginpage_hook
        
    

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG;
        if ($user = get_record('user', 'username', $username, 'mnethostid', $CFG->mnet_localhost_id)) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }    
    
    
    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    function user_update_password($username, $newpassword) {
        global $CFG;
        if ($user = get_record('user', 'username', $username, 'mnethostid', $CFG->mnet_localhost_id)) {
                return update_internal_user_password($user, $newpassword);
        }        
        return false;
    }     
    
    
    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return true;
    }    
    
   /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return true;
    }    
    
    
    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        global $CFG;
        
        if (!isset ($config->trusted_server1)) {
            $config->trusted_server1 = '127.0.0.1';
        }
        if (!isset ($config->trusted_server2)) {
            $config->trusted_server2 = '';
        }
        if (!isset ($config->trusted_server3)) {
            $config->trusted_server = '';
        }         
        if (!isset($config->failurl)) {
            $config->failurl = '';
        }

        if (!isset ($config->host)) {
            $config->host = 'localhost';
        }
        if (!isset ($config->name)) {
            $config->name = '';
        }
        if (!isset ($config->user)) {
            $config->user = '';
        }         
        if (!isset($config->pass)) {
            $config->pass = '';
        }
        if (!isset($config->table)) {
            $config->table = '';
        }       
        
        set_config('trusted_server1', $config->trusted_server1, 'auth/ouidm');
        set_config('trusted_server2', $config->trusted_server2, 'auth/ouidm');
        set_config('trusted_server3', $config->trusted_server3, 'auth/ouidm');
        
        set_config('host', $config->host, 'auth/ouidm');
        set_config('name', $config->name, 'auth/ouidm');
        set_config('user', $config->user, 'auth/ouidm');
        set_config('pass', $config->pass, 'auth/ouidm');
        set_config('table', $config->table, 'auth/ouidm');        
        
        
        $config = stripslashes_recursive($config);
        set_config('failurl', $config->failurl, 'auth/ouidm');
        
        return true;
    }

    
    /**
     * Attempts to create a new user record and course enrolment
     * based on configuration data passed with request.
     * 
     * You can only add a user if the course exists in Moodle.
     * If the course does not exist, redirect the user to another page
     * with information. 
     * @params: $username of the new user 
     * @params: $password the news password
     * 
     * @return: mixed courseid for which the use is enroled to or false if 
     *          the user couldn't be registered
     */
    function ouidm_createuser($username, $password) {
        global $CFG;

        $user_info = $this->get_user_info($username);

        $this->user = new stdClass();       
        $this->set_user_properties($username, $password, $user_info);       
               
        
        if (! $this->course_exists_for_kr_code_with_user("'$username'")){
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1205');
            return false;       
        }

        //print_object($this->user);
        if (! $this->user->id = insert_record('user', $this->user)) {
           $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1204');
           return false;
        }
        return $this->enrol_into_course();
    } // function ouidm_createuser


    function enrol_into_course() {
        if ( empty($this->user->idnumber)) return false;

        $user = get_record('user', 'id', $this->user->id); // ?? O.S.: shouldn't $user be identical to $this->user, at this point?
        if (!$course = get_record('course', 'idnumber', $this->user->idnumber)) return false;

        enrol_into_course($course, $user, 'ouidm');
        return $course->id;
    } // function enrol_into_course


    function get_user_info($username) {
        //connect to oracle database
        $conn = connect_oracle($this->config->user, $this->config->pass, $this->config->host.'/'.$this->config->name);
        if (!$conn) {
            $this->ouidm_redirect("There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1201");      
        }
        
        $str_username = "'$username'";
        $table_name = $this->config->table;         
        
        
        $sql = 'SELECT IDENTIFIKATIE, VOL_NAAM, VOORLETTERS, W_PLAATS,
                             E_MAIL_ADRES, LAST_CHANGE, KR_CODE, OL_CODE FROM '. $table_name .' WHERE IDENTIFIKATIE = '.$str_username; 
        
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        
        $user_info = oci_fetch_array($stid, OCI_ASSOC);
        
        oci_free_statement($stid);
        oci_close($conn);               
        return $user_info;
    } // function get_user_info


    function set_user_properties($username, $password, $user_info) {
        global $CFG;
        $this->user->confirmed   = 1;
        $this->user->lang        = current_language();
        $this->user->firstaccess = time();
        $this->user->mnethostid  = $CFG->mnet_localhost_id;
        $this->user->secret      = random_string(15);
        $this->user->auth        = 'ouidm';
        $this->user->firstname   = mysql_escape_string($user_info['VOORLETTERS']);
        $this->user->lastname    = mysql_escape_string($user_info['VOL_NAAM']);
        $this->user->email       = mysql_escape_string($user_info['E_MAIL_ADRES']);
        $this->user->city        = mysql_escape_string($user_info['W_PLAATS']);
        $this->user->country     = 'NL';
        $this->user->lang        = 'nl_utf8';
        $this->user->username    = $username;
        $this->user->password    = hash_internal_user_password($password);
        $this->user->idnumber    = $user_info['KR_CODE'];               
    } // function set_user_properties

    
    /**
     * Examine the config file and redirect if defined
     */
    function ouidm_redirect($debug="") {
        unset($_POST['ouidmdata']);
        if ((!empty($debug)) && (!empty($this->config->failurl))) {
            header("Location: ".$this->config->failurl."?debug=".$debug);           
            exit();
        } else{
            echo $debug;
            exit();
        }
        
        return true;
    }    
    
    
    
    /**
     * Check trusted client IP address
     * @params: $ipAdrs a string IP address (E.G. 127.0.0.1)
     * @return: 1 if ip is trusted 0 otherwise
     */

    function ouidm_isvalidremoteaddr($ipadrs) {
      /**
       *  Allowed Remote IP Addresses from the config
      */      
      $remoteips = array(
        'localhost' => '127.0.0.1',
        'trustedServer1' => $this->config->trusted_server1,
        'trustedServer2' => $this->config->trusted_server2,
        'trustedServer3' => $this->config->trusted_server3
       );
       
       foreach($remoteips as $remotekey => $remoteip) {
         if (strcmp(trim($remoteips[$remotekey]), trim($ipadrs)) == 0) {
           return 1;
         }
      }
      return 0;
    }    
    
    /**
     * Check if a course exists
     * @params: $username
     * @return: boolean true if course exist false if not
     **/
    function course_exists_for_kr_code_with_user($username){

     global $CFG;    
         
        //connect to oracle database
        $conn = connect_oracle($this->config->user, $this->config->pass, $this->config->host.'/'.$this->config->name);      
        if (!$conn) {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1201');      
        }
        
        //$str_username = "'$username'"; //We don't have to do this in here because $username is already having ''
        $table_name = $this->config->table; 
        
        $sql = 'SELECT IDENTIFIKATIE, KR_CODE FROM '. $table_name .' WHERE IDENTIFIKATIE = '.$username; 
        
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);         
        while ($mycourse_idm = oci_fetch_array($stid, OCI_ASSOC)){
            if (get_record('course', 'idnumber', $mycourse_idm['KR_CODE'])){
                oci_free_statement($stid);
                oci_close($conn);       
                return true;
            }
            
        }
    
        return false;   
    } // function course_exists_for_kr_code_with_user
    
    /**
     * Updates user group for all of the courses that a user
     * has bought
     * 
     * called everytime a user logs in
     *
     * @param  object  $decryt_username       User ID
     * @return boolean result
     *
     */    
    function ouidm_user_update_group($username){

         global $CFG;
         
        //connect to oracle database
        $conn = connect_oracle($this->config->user, $this->config->pass, $this->config->host.'/'.$this->config->name);
        if (!$conn) {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1201');      
        }
        
        $str_username = "'$username'";
        $table_name = $this->config->table; //This has to come from the form
        
        $sql = 'SELECT IDENTIFIKATIE, KR_CODE, OL_CODE FROM '. $table_name .' WHERE IDENTIFIKATIE = '.$str_username; 
        
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        
        while ($user_info = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {

               $idnumber = $user_info['KR_CODE'];
               $idnumber = "'$idnumber'";
               
               $sql_course = "select id, idnumber from {$CFG->prefix}course where idnumber = ".$idnumber;
           
               $course = get_record_sql($sql_course);

               $groupid = get_field('groups','id','name', $user_info['OL_CODE'], 'courseid', $course->id);
               
                /**
                 * Use the groups if it exists and register the user for his group.
                 * If not do nothing!
                 * */
                if (!empty($groupid)){
                    if (groups_group_exists($groupid)){
                        $userid = get_field('user','id', 'username', $user_info['IDENTIFIKATIE']);
                        groups_add_member($groupid, $userid);
                    }
                }   

        }//while        
        oci_free_statement($stid);
        oci_close($conn);
        return; 
    }    
    
    /**
     * Update the user courses. This method updates the use course list and deletes a user from
     * a course if the course doesn't exist in IDM Moodle interface.
     * @params: $userid id of the current user
     * @return: void 
     **/
    function ouidm_course_update($username){

         global $CFG;
         
        //connect to oracle database
        $conn = connect_oracle($this->config->user, $this->config->pass, $this->config->host.'/'.$this->config->name);
        if (!$conn) {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1201');      
        }
        
        $str_username = "'$username'";
        $table_name = $this->config->table; 
        
        $sql = 'SELECT IDENTIFIKATIE, KR_CODE FROM '. $table_name .' WHERE IDENTIFIKATIE = '.$str_username; 
        
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);         
        
        //All courses from IDM      
        $courseids_idm = array();
        while ($mycourse_idm = oci_fetch_array($stid)){
            $courseids_idm[] = $mycourse_idm['KR_CODE'];
        }
        $courseids_idm[] = 'manual'; // Provision for manually added courses. Onno Schuit, 20110912
        
        $userid = $this->ouidm_get_user($username)->id;
        
        //All courses from Moodle
        $courseids_moodle = array();
        if ($mycourses_moodle = get_my_courses($userid, 'visible DESC,sortorder DESC', 'idnumber', false, 0)) {
            foreach ($mycourses_moodle as $mycourse) {
                $courseids_moodle[] = $mycourse->idnumber;
            }
        }
/*      else {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1206');  
        }*/
        
        $deleted_courses = array_diff($courseids_moodle, $courseids_idm);       
        
        if ($deleted_courses = array_diff($courseids_moodle, $courseids_idm)){
            //Delete the user from the resulted course
            foreach ($deleted_courses as $my_deleted_course){       
                unenrol_student($this->ouidm_get_user($username)->id, $this->ouidm_get_courseid($my_deleted_course));
            }
        }
        
        oci_free_statement($stid);
        oci_close($conn);   
        return;
    }

    /**
     * Update the user account. 
     * @params: $userid id of the current user
     * @return: void 
     **/
    function ouidm_update_user($username){

         global $CFG;
         
        //connect to oracle database
        $conn = connect_oracle($this->config->user, $this->config->pass, $this->config->host.'/'.$this->config->name);
        if (!$conn) {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1201');      
        }
        
        $str_username = "'$username'";
        $table_name = $this->config->table; 
        
        $sql = 'SELECT IDENTIFIKATIE, VOL_NAAM, VOORLETTERS, W_PLAATS,
                             E_MAIL_ADRES, LAST_CHANGE, KR_CODE, OL_CODE FROM '. $table_name .' WHERE IDENTIFIKATIE = '.$str_username;  
        
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);         

        while ($myuser_idm = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)){
            $firstname   = $myuser_idm['VOORLETTERS'];
            $lastname    = $myuser_idm['VOL_NAAM'];
            $city        = $myuser_idm['W_PLAATS'];
            $email       = $myuser_idm['E_MAIL_ADRES'];
            $last_change = date('dYG', strtotime($myuser_idm['LAST_CHANGE']));
        }       

        $myuser_moodle = $this->ouidm_get_user($username);
        
        $firstaccess = $myuser_moodle->firstaccess;
        
        $userid = $this->ouidm_get_user($username)->id;
        
        if ($last_change != $firstaccess){
            //Update everything 
            $sql_update = "update {$CFG->prefix}user 
                           set firstname='$firstname', lastname='$lastname', city='$city', email='$email' 
                           where username='$username'";
              
            execute_sql($sql_update, false);
        }
        /*else {
            $this->ouidm_redirect('There was a problem with your account! Please contact the administrator (elosa@ou.nl). Code:1207');      
        }*/
        
        return;
    }   

    
    /**
     * Gets a user id of a given user  
     * @params: $username student number of a user used as a username
     * @return: mixted userid or false if the user does not exist.
     **/
    function ouidm_get_user($username){
        global $CFG;        
        
        $sql_user = "select * from {$CFG->prefix}user where username = ".$username;
         
        $userid = get_record_sql($sql_user);        
        
        return $userid;
        
    }

    /**
     * Gets a course id of a given user  
     * @params: $idnumber of a course
     * @return: mixted courseid or false if the course does not exist.
     **/
    function ouidm_get_courseid($idnumber){
        global $CFG;        
        
        $sql_course = "select id, idnumber from {$CFG->prefix}course where idnumber = ".$idnumber;
         
        $courseid = get_record_sql($sql_course);        
        
        return $courseid->id;
        
    }    
    
    
}

?>
