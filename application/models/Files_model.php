<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Files Model is designed to deal with questions and replies
* Author: Damanjeet Singh Dhillon
* Created at: April 25, 2019 @ 6:23pm
*/

class Files_model extends CI_Model {
	
	function __construct() {
        parent::__construct();
        $this->table = 'resumes';
        $this->table2 = 'site_media';
    }

    /**
    * Function to add resume to db
    * Can be accessed via api for user
    * @param: array $resume_data
    **/
    function insert_resume($resume_data = array()){
        if(!empty($resume_data) && is_array($resume_data)){
           if($this->db->insert($this->table, $resume_data)){
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

    /**
    * Function to add profile pic to db
    * Can be accessed via api for user
    * @param: array $picture_data
    **/
    function insert_profile_pic($picture_data = array()){
        if(!empty($picture_data) && is_array($picture_data)){
           if($this->db->insert($this->table2, $picture_data)){
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

}