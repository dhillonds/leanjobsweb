<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Question Model is designed to deal with questions and replies
* Author: Damanjeet Singh Dhillon
* Created at: April 19, 2019 @ 9:05pm
*/

class Question_model extends CI_Model {
	
	function __construct() {
        parent::__construct();
        $this->table = 'questions';
    }

    /**
    * Function to add questions for a job
    * Can be accessed via api for job creation
    * @param: array $questions_data
    **/
    function insert_questions($questions_data = array()){
        if(!empty($questions_data) && is_array($questions_data)){
           if($this->db->insert_batch($this->table, $questions_data)){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
    * Function to add questions for a job
    * Can be accessed via api for job creation
    * @param: array $replies_data
    **/
    function insert_replies($replies_data = array()){
        if(!empty($replies_data) && is_array($replies_data)){
           if($this->db->insert_batch('replies', $replies_data)){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
    * Function to register new user
    * Can be accessed via api for registration and admin from admin dashboard
    * @param: array $job_data
    **/
    function get_job_questions($job_id = NULL){
        $this->db->select(' ques_id, question_desc');
        $this->db->from($this->table);
        $this->db->where('job_id', $job_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
    * Function to register new user
    * Can be accessed via api for registration and admin from admin dashboard
    * @param: array $job_data
    **/
    function get_app_responses($app_id = NULL){
        $this->db->select(' q.ques_id, q.question_desc AS question, r.reply');
        $this->db->from('replies AS r');
        $this->db->join($this->table.' AS q', 'q.ques_id = r.ques_id');
        $this->db->where('r.app_id', $app_id);
        $query = $this->db->get();
        return $query->result_array();
    }

}