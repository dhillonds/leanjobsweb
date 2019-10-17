<?php
use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Jobs extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('job_model');
        $this->load->model('question_model');
        $this->load->model('user_model');
        $this->load->library('form_validation');
    }

    public function list_user_get(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "No active jobs found.", 
                        'data' =>  array(), 
                    );
        $page = (!empty($this->get('page')))?($this->get('page')):0;
        $user_id = (!empty($this->get('user_id')))?($this->get('user_id')): NULL;
        if(!empty($user_id)){
            $jobs_listing = $this->job_model->jobs_user_listing($page);
            if(!empty($jobs_listing)){
                $jobs_detailed = array();
                foreach ($jobs_listing as $key => $value) {
                    $value['questions'] = $this->question_model->get_job_questions($value['job_id']); 
                    $value['can_apply'] = $this->job_model->verify_active_job($value['job_id'], $user_id); 
                    $jobs_detailed[] = $value;
                }
                $data = array(
                                'status' => TRUE, 
                                'message' => "Active jobs found.", 
                                'data' => $jobs_detailed, 
                            );
            }
        }
        echo json_encode($data);
    }

    public function list_admin_get(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "No active jobs found.", 
                        'data' =>  array(), 
                    );
        $page = (!empty($this->get('page')))?($this->get('page')):0;
        $jobs_listing = $this->job_model->jobs_admin_listing($page);
        if(!empty($jobs_listing)){
            $jobs_detailed = array();
            foreach ($jobs_listing as $key => $value) {
                $value['questions'] = $this->question_model->get_job_questions($value['job_id']); 
                $jobs_detailed[] = $value;
            }
            $data = array(
                            'status' => TRUE, 
                            'message' => "Active jobs found.", 
                            'data' => $jobs_detailed, 
                        );
        }
        echo json_encode($data);
    }

    public function list_applications_get(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "No applicants found.", 
                        'data' =>  array(), 
                    );
        //$page = (!empty($this->get('page')))?($this->get('page')):0;
        $job_id = (!empty($this->get('job_id')))?($this->get('job_id')): NULL;
        $user_id = (!empty($this->get('user_id')))?($this->get('user_id')): NULL;
        $apps_listing = $this->job_model->job_applicants_listing($job_id);//$page);
        if(!empty($apps_listing)){
            $apps_listing_detailed = array();
            foreach ($apps_listing as $key => $value) {
                $value['responses'] = $this->question_model->get_app_responses($value['app_id']); 
                $apps_listing_detailed[] = $value;
            }
            $data = array(
                            'status' => TRUE, 
                            'message' => "Applicants found.", 
                            'data' => $apps_listing_detailed, 
                        );
        }
        echo json_encode($data);
    }

    public function user_applications_get(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "No applications found.", 
                        'data' =>  array(), 
                    );
        //$page = (!empty($this->get('page')))?($this->get('page')):0;
        $user_id = (!empty($this->get('user_id')))?($this->get('user_id')): NULL;
        $apps_listing = $this->job_model->job_applications_listing($user_id);//$page);
        if(!empty($apps_listing)){
            $apps_listing_detailed = array();
            foreach ($apps_listing as $key => $value) {
                $value['responses'] = $this->question_model->get_app_responses($value['app_id']); 
                $apps_listing_detailed[] = $value;
            }
            $data = array(
                            'status' => TRUE, 
                            'message' => "Applications found.", 
                            'data' => $apps_listing_detailed, 
                        );
        }
        echo json_encode($data);
    }

    public function apply_job_post($applicant_data = array()){
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, Job was not applied.", 
                        'data' =>  array(), 
                    );
        if(!empty($_POST)){
            $user_id = (!empty($this->post('user_id')))?$this->post('user_id'):NULL;
            $job_id = (!empty($this->post('job_id')))?$this->post('job_id'):NULL;
            $salt = (!empty($this->post('salt')))?$this->post('salt'):NULL;
            if (!empty($user_id) || !empty($job_id) || !empty($salt))
            {
                if($this->user_model->verify_front_user($user_id, $salt)){
                    if($this->job_model->verify_active_job($job_id, $user_id)){
                        $applicant_data = array(
                                        'job_id' => $job_id, 
                                        'user_id' => $user_id, 
                                        'job_status' => STATUS_JOB_APPLIED
                                    );
                        $app_id = $this->job_model->apply_job($applicant_data);
                        if(!empty($app_id)){
                            $data = array(
                                        'status' => TRUE, 
                                        'message' => "Applied for job successfully.", 
                                        'data' =>  array('app_id' => $app_id), 
                                    );
                            $replies_data_json = ($this->post('replies_data'))?$this->post('replies_data'):NULL;
                            if(!empty($replies_data_json)){
                                $replies_data = json_decode($replies_data_json,TRUE);

                                $replies_data_array = array();
                                foreach ($replies_data as $key => $value) {
                                    $value['app_id'] = $app_id;
                                    $replies_data_array[] = $value; 
                                }
                                if(!empty($replies_data_array) && is_array($replies_data_array)){
                                    if($this->question_model->insert_replies($replies_data_array)){
                                        // Replies added successfully
                                    }else{
                                        $data['message'] = "Job was applied successfully. Replies were not added.";
                                    }
                                }
                            }
                        }
                    }else{
                         $data['message'] = "Can't apply for this job.";
                    }
                }else{
                     $data['message'] = "User not verified.";
                }
                
            }else{
                $data['message'] = "Parameters missing.";
            }
        }
        echo json_encode($data);
    }

    public function create_post($job_data = array())
    {
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, Job was not created.", 
                        'data' =>  array(), 
                    );

        if(!empty($_POST)){
            $this->form_validation->set_error_delimiters('','');
            $this->form_validation->set_rules('title', 'Title', 'trim|required|min_length[2]|max_length[255]');
            $this->form_validation->set_rules('role_desc', 'Role Description', 'trim|required|min_length[6]|max_length[1000]');
            $this->form_validation->set_rules('job_reqs', 'Job Requirements', 'trim|required|min_length[6]|max_length[1000]');
            $this->form_validation->set_rules('wages', 'Wages per hour', 'trim|numeric|min_length[1]|max_length[11]');
            if ($this->form_validation->run() == TRUE)
            {
                
                $user_id = (!empty($this->post('user_id')))?$this->post('user_id'):NULL;
                $salt = (!empty($this->post('salt')))?$this->post('salt'):NULL;
                if($this->user_model->verify_admin_rights($user_id, $salt)){
                    $job_data = array(
                                    'title' => trim($this->post('title')), 
                                    'role_desc' => trim($this->post('role_desc')), 
                                    'job_reqs' => trim($this->post('job_reqs')), 
                                    'wages' => $this->post('wages'), 
                                    'admin_id' => $user_id, 
                                    'is_active' => JOB_ACTIVE
                                );
                    $job_id = $this->job_model->create_job($job_data);
                    if(!empty($job_id)){
                        $data = array(
                                    'status' => TRUE, 
                                    'message' => "Job was created successfully.", 
                                    'data' =>  array('job_id' => $job_id), 
                                );
                        $questions_data_json = ($this->post('questions_data'))?$this->post('questions_data'):NULL;
                        if(!empty($questions_data_json)){
                            $questions_data = json_decode($questions_data_json,TRUE);

                            $questions_data_array = array();
                            foreach ($questions_data as $key => $value) {
                                $questions_data_array[] = array(
                                                                'job_id' => $job_id, 
                                                                'question_desc' => $value
                                                            ); 
                            }
                            if(!empty($questions_data_array) && is_array($questions_data_array)){
                                if($this->question_model->insert_questions($questions_data_array)){
                                    // Questions added successfully
                                }else{
                                    $data['message'] = "Job was created successfully. Questions were not added.";
                                }
                            }
                        }
                    }
                }else{
                    $data['message'] = "User is not an admin";
                }
                
            }else{
                $data['message'] = validation_errors();
            }
        }
        echo json_encode($data);
    }

    public function change_requisition_post()
    {
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, Job status was not changed.", 
                        'data' =>  array(), 
                    );

        if(!empty($_POST)){
            $job_id = (!empty($this->post('job_id')))?($this->post('job_id')):NULL;
            $user_id = (!empty($this->post('user_id')))?($this->post('user_id')):NULL;
            $salt = (!empty($this->post('salt')))?($this->post('salt')):NULL;
            if($this->user_model->verify_admin_rights($user_id, $salt)){
                $updated_status = $this->job_model->change_requisition($job_id);
                if(!empty($updated_status)){
                    $data = array(
                                'status' => TRUE, 
                                'message' => "Job status changed successfully.", 
                                'data' =>  array('updated_status' => $updated_status['is_active']), 
                            );
                }
            }else{
                $data['message'] = "User is not an admin";
            }
        }
        echo json_encode($data);
    }

    public function change_app_status_post()
    {
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, Application status was not changed.", 
                        'data' =>  array(), 
                    );

        if(!empty($_POST)){
            $app_id = (!empty($this->post('app_id')))?($this->post('app_id')):NULL;
            $new_status = (!empty($this->post('new_status')))?($this->post('new_status')):STATUS_JOB_APPLIED;
            $user_id = (!empty($this->post('user_id')))?($this->post('user_id')):NULL;
            $salt = (!empty($this->post('salt')))?($this->post('salt')):NULL;
            if($this->user_model->verify_admin_rights($user_id, $salt)){
                if($new_status !== STATUS_JOB_APPLIED){
                    $updated_status = $this->job_model->change_application_status($app_id, $new_status);
                    if(!empty($updated_status)){
                        $data = array(
                                    'status' => TRUE, 
                                    'message' => "Application status changed successfully.", 
                                    'data' =>  array('updated_status' => $updated_status['job_status']), 
                                );
                    }
                }else{
                    $data['message'] = "Application status can't be changed to applied.";
                }
            }else{
                $data['message'] = "User is not an admin";
            }
        }
        echo json_encode($data);
    }

}