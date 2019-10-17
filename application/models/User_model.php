<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* User Model is designed to deal with data of admin and front end user
* Author: Damanjeet Singh Dhillon
* Created at: April 9, 2019 @ 1:59am
*/

class User_model extends CI_Model {
	
	function __construct() {
        parent::__construct();
        $this->table = 'users';
        $this->resume_file_path = base_url().'public/uploads/resumes/';
        $this->profile_pic_file_path = base_url().'public/uploads/profilepictures/';
    }

    /**
    * Function to register new user
    * Can be accessed via api for registration and admin from admin dashboard
    * @param: array $user_data
    **/
    function create_user($user_data = array()){
        if(!empty($user_data) && is_array($user_data)){
            if ( !empty($user_data['password']) ) {
                $salt = $this->generate_salt();
                $passcode = $this->generate_password( $user_data['password']);
                
                if(!empty($salt) && !empty($passcode)){ 
                    $user_data['salt'] = $salt;
                    $user_data['passcode'] = $passcode;
                    unset($user_data['password']);

                    if($this->is_unique_email($user_data['email'])){
                        if($this->db->insert($this->table, $user_data)){
                            $return_data = array(   
                                                    'user_id'   => $this->db->insert_id(),
                                                    'salt'      => $salt
                                                );
                            return $return_data;
                        }
                    }
                }

            }
        }
        return FALSE;
    }

    /**
    * Function to update user details
    * Can be accessed via api for registration and admin from admin dashboard
    * @param: array $user_data
    **/
    function update_user($user_data = array(), $user_id = NULL, $salt = NULL){
        if(!empty($user_data) && is_array($user_data)){
            if(!empty($salt) && !empty($user_id)){ 
                //if($this->is_unique_email($user_data['email'])){
                    $this->db->where('user_id', $user_id);
                    $this->db->where('salt', $salt);
                    
                    if($this->db->update($this->table, $user_data)){
                        $user_data['user_id'] = $user_id;
                        $user_data['salt'] = $salt;
                        return $user_data;
                    }
                //}
            }
        }
        return FALSE;
    }

    /**
    * Function to login for user and admin
    * @param: array $login_data
    **/
    function get_single_user($login_data = array()){
        if(!empty($login_data) && is_array($login_data)){
            $this->db->select('u.*, sm.file AS profile_pic_path, rsme.file AS resume_path');
            $this->db->from($this->table.' AS u');
            $this->db->join('site_media AS sm', 'sm.site_media_id = u.site_media_id','left');
            $this->db->join('resumes AS rsme', 'rsme.resume_id = u.res_id','left');
            $this->db->where('email', $login_data['email']);
            $this->db->where('is_admin', $login_data['is_admin']);
            //$this->db->group_by('u.user_id');
            $query = $this->db->get();
            
            if( count($query->result()) > 0 ) {
                $user_data = $query->row_array();
                $this->load->library('bcrypt');
                if ($this->bcrypt->check_password($login_data['password'], $user_data['passcode'])) {
                    // Password does match stored password.
                    unset($user_data['passcode']);
                    return $user_data;
                }
            }
            return FALSE;
        }
    }
    
    /**
    *   Generate encrypted password
    *   @param : string password
    *
    **/
    function generate_password( $password = null) {
        if ( empty($password) )
            return false;
        
        //generate password using bycrypt library
        $this->load->library('bcrypt');
        $hash = $this->bcrypt->hash_password($password);
        
        return $hash;
    }

    /**
    *   Generate salt
    *   @param : int $n ( how many characters generate ), string $type
    *
    **/
    function generate_salt( $n = SALT_LENGTH, $type = 'alnum' ) {
        $this->load->helper('string');
        return random_string( $type, $n );
    }

    /**
    *   Validate user password
    *
    *   @param: string $password
    **/
    function validate_password( $password = null, $id = null) {
        if ( empty($password) || empty($id) ) {
            return FALSE;
        }
        
        $query = $this->db->get_where( $this->table, array( 'id' => $id ) );
        
        if( count($query->result()) > 0 ) {
            $stored_hash = $query->row_array();
        } else {
            return FALSE;
        }
        $this->load->library('bcrypt');
        if ($this->bcrypt->check_password($password, $stored_hash['passcode'])) {
            // Password does match stored password.
            return TRUE;
        }
        return FALSE;
    }

    /**
    *   Check for the existing email id in user profile
    *   @param: Email id of the user , users id
    */
    public function is_unique_email( $email = null){
        if(empty($email)){
            return FALSE;
        }

        $this->db->select('user_id');
        $this->db->from($this->table);
        $this->db->where($this->table.'.email', $email);
        //$this->db->where($this->table.'.is_admin!=', ADMIN);
        $record = $this->db->get()->result_array();
        
        if(empty($record)){
            return TRUE;
        }else{
            return FALSE;   
        }
    }

    /**
    *   Check for the verifiying admin rights
    *   @param: Email id of the user , users id
    */
    public function verify_admin_rights( $user_id = null, $salt = NULL){
        if(empty($user_id) || empty($salt)){
            return FALSE;
        }

        $this->db->select('email');
        $this->db->from($this->table);
        $this->db->where('user_id', $user_id);
        $this->db->where('salt', $salt);
        $this->db->where($this->table.'.is_admin', IS_ADMIN);
        $record = $this->db->get()->result_array();
        
        if(!empty($record)){
            return TRUE;
        }else{
            return FALSE;   
        }
    }

    /**
    *   Check to verify front user
    *   @param: Email id of the user , users id
    */
    public function verify_front_user( $user_id = null, $salt = NULL){
        if(empty($user_id) || empty($salt)){
            return FALSE;
        }

        $this->db->select('email');
        $this->db->from($this->table);
        $this->db->where('user_id', $user_id);
        $this->db->where('salt', $salt);
        $this->db->where($this->table.'.is_admin!=', IS_ADMIN);
        $record = $this->db->get()->result_array();
        
        if(!empty($record)){
            return TRUE;
        }else{
            return FALSE;   
        }
    }

}