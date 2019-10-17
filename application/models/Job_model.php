<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Jobs Model is designed to deal with jobs and related entities
* Author: Damanjeet Singh Dhillon
* Created at: April 19, 2019 @ 4:59pm
*/

class Job_model extends CI_Model {
	
	function __construct() {
        parent::__construct();
        $this->table = 'jobs';
    }

    /**
    * Function to create new job
    * Can be accessed via api for job creation
    * @param: array $job_data
    **/
    function create_job($job_data = array()){
        if(!empty($job_data) && is_array($job_data)){
           if($this->db->insert($this->table, $job_data)){
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

    /**
    * Function to apply job for front user
    * Can be accessed via api for apply jobs
    * @param: array $applicant_data
    **/
    function apply_job($applicant_data = array()){
        if(!empty($applicant_data) && is_array($applicant_data)){
           if($this->db->insert('applications', $applicant_data)){
                return $this->db->insert_id();
            }
        }
        return FALSE;
    }

    /**
    * Function to show jobs to front user
    * Can be accessed via api for front user
    * @param: int $start
    **/
    function jobs_user_listing($start = 0){
        $this->db->select('j.job_id, j.title, j.role_desc, j.job_reqs, j.wages, u.full_name AS creator, j.created_at');
        $this->db->from($this->table.' AS  j');
        $this->db->join('users AS u', 'u.user_id = j.admin_id');
        $this->db->where('j.is_active', JOB_ACTIVE);
        //$this->db->limit(10, $start);
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
    * Job applications by the user
    * Can be accessed via api for front user
    * @param: int $user_id
    **/
    function job_applications_listing($user_id = NULL){
        if(empty($user_id)){
            return FALSE;
        }
        $this->db->select('j.job_id, app.app_id, j.title, j.role_desc, j.job_reqs, j.wages, j.is_active, app.job_status, u.full_name AS creator, j.created_at');
        $this->db->from('applications AS app');
        $this->db->join($this->table.' AS  j', 'j.job_id = app.job_id');
        $this->db->join('users AS u', 'u.user_id = j.admin_id');
        $this->db->where('app.user_id', $user_id);
        //$this->db->limit(10, 0);// $start);
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
    * Function to show jobs to admin
    * Can be accessed via api for admin
    * @param: int $start
    **/
    function jobs_admin_listing($start = 0){
        $this->db->select('j.job_id, j.title, j.role_desc, j.job_reqs, j.wages, u.full_name AS creator, j.created_at, j.is_active, COUNT(app.app_id) AS num_applicants');
        $this->db->from($this->table.' AS  j');
        $this->db->join('users AS u', 'u.user_id = j.admin_id');
        $this->db->join('applications AS app', 'app.job_id = j.job_id','left');
        //$this->db->where('j.is_active', JOB_ACTIVE);
        $this->db->limit(10, $start);
        $this->db->group_by('j.job_id');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
    * List of job applicants for a job
    * Can be accessed via api for admin
    * @param: int $job_id, int $start
    **/
    function job_applicants_listing($job_id= NULL, $start = 0){
        if(empty($job_id)){
            return FALSE;
        }
        $this->db->select('app.app_id, u.full_name, u.email, u.phone_num, j.title, j.is_active, app.job_status, res.file AS resume_path');
        $this->db->from('applications AS app');
        $this->db->join('users AS u', 'u.user_id = app.user_id');
        $this->db->join('resumes AS res', 'res.resume_id = u.res_id','left');
        $this->db->join('jobs AS j', 'j.job_id = app.job_id');
        $this->db->where('app.job_id', $job_id);
        $this->db->group_by('app.app_id');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
    *  Verification of active job and if already applied
    *  Can be accessed via api for front user
    *  @param: int $job_id, int $user_id
    */
    public function verify_active_job( $job_id = NULL, $user_id = NULL){
        if(empty($job_id) || empty($user_id)){
            return FALSE;
        }

        $this->db->select('title');
        $this->db->from($this->table);
        $this->db->where('job_id', $job_id);
        $this->db->where($this->table.'.is_active', JOB_ACTIVE);
        $record = $this->db->get()->result_array();
        
        if(!empty($record)){
            $this->db->select('app_id');
            $this->db->from('applications');
            $this->db->where('job_id', $job_id);
            $this->db->where('user_id', $user_id);
            $record2 = $this->db->get()->result_array();
            if(empty($record2)){
                return TRUE;
            }
        }
        return FALSE;   
    }


    /**
    * Function to change the status of job
    * Can be accessed via api for changing status of the job via admin
    * @param: int $job_id
    **/
    function change_requisition($job_id = NULL){
        if(!empty($job_id)){
            $this->db->select('is_active');
            $this->db->from($this->table);
            $this->db->where('job_id', $job_id);
            $record = $this->db->get()->row_array();

            if(!empty($record)){
                
                switch ($record['is_active']) {
                    case JOB_ACTIVE:
                        $new_data = array('is_active' => JOB_INACTIVE );
                        break;
                    case JOB_INACTIVE:
                        $new_data = array('is_active' => JOB_ACTIVE );
                        break;
                    default:
                        $new_data = array('is_active' => JOB_ACTIVE );
                        break;
                }
                $this->db->where('job_id', $job_id);
                
                if($this->db->update($this->table, $new_data)){
                    return $new_data;
                }
            }

        }
        return FALSE;
    }

    /**
    * Function to change application status
    * Can be accessed via api for changing applicant status via admin
    * @param: int $app_id, int $new_status
    **/
    function change_application_status($app_id = NULL, $new_status = 0){
        if(!empty($app_id)){
            $this->db->select('job_status');
            $this->db->from('applications');
            $this->db->where('app_id', $app_id);
            $record = $this->db->get()->row_array();

            if(!empty($record)){
                switch ($record['job_status']) {
                    case STATUS_JOB_APPLIED:
                        $new_data = array('job_status' => $new_status );
                        break;
                    case STATUS_JOB_SHORTLISTED:
                        $new_data = array('job_status' => $new_status );
                        break;
                    case STATUS_JOB_ACCEPTED:
                        // Can't change now
                        break;
                    case STATUS_JOB_REJECTED:
                        // Can't change now
                        break;
                    default:
                        $new_data = array('job_status' => STATUS_JOB_APPLIED );
                        break;
                }
                if(!empty($new_data)){
                    $this->db->where('app_id', $app_id);
                    if($this->db->update('applications', $new_data)){
                        return $new_data;
                    }
                }
                
            }

        }
        return FALSE;
    }

}