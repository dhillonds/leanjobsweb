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
class Users extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('files_model');
        $this->load->library('form_validation');
    }

    public function register_post($user_data = array())
    {
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, User was not created", 
                        'data' =>  array(), 
                    );
        if(!empty($_POST)){
            $this->form_validation->set_error_delimiters('','');
            $this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|callback_is_unique');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[50]');
            $this->form_validation->set_rules('full_name', 'Full Name', 'trim|required|min_length[2]|max_length[255]');
            $this->form_validation->set_rules('phone_num', 'Phone Number', 'trim|required|exact_length[10]');

                if ($this->form_validation->run() == TRUE)
                {
                    $user_data = array(
                                        'email' => trim($this->post('email')), 
                                        'password' => trim($this->post('password')), 
                                        'full_name' => trim($this->post('full_name')), 
                                        'phone_num' => $this->post('phone_num'), 
                                        'is_admin' => NOT_ADMIN, 
                                    );
                    $upload_resume = $this->_upload_resume_function();
                    if($upload_resume['status'] == TRUE){
                        $user_data['res_id'] = $upload_resume['data']['resume_id'];
                    }
                    $upload_profile_pic = $this->_upload_profile_pic_function();
                    if($upload_profile_pic['status'] == TRUE){
                        $user_data['site_media_id'] = $upload_profile_pic['data']['site_media_id'];
                    }
                    $return_data = $this->user_model->create_user($user_data);
                    if(!empty($return_data)){
                        $data = array(
                                        'status' => TRUE, 
                                        'message' => "User was created successfully", 
                                        'data' =>  $return_data, 
                                    );
                    }
                }
                $data['message'] = validation_errors();
        }
        echo json_encode($data);
    }

    public function update_profile_post($user_data = array())
    {
        $data = array(
                        'status' => FALSE, 
                        'message' => "Something went wrong, User data was not updated", 
                        'data' =>  array(), 
                    );
        if(!empty($_POST)){
            $this->form_validation->set_error_delimiters('','');
            //$this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|callback_is_unique');
            $this->form_validation->set_rules('full_name', 'Full Name', 'trim|required|min_length[2]|max_length[255]');
            $this->form_validation->set_rules('phone_num', 'Phone Number', 'trim|required|exact_length[10]');

                if ($this->form_validation->run() == TRUE)
                {
                    $user_id = (!empty($this->post('user_id')))?$this->post('user_id'):NULL;
                    $salt = (!empty($this->post('salt')))?$this->post('salt'):NULL;
                    $user_data = array(
                                        //'email' => trim($this->post('email')), 
                                        'full_name' => trim($this->post('full_name')), 
                                        'phone_num' => $this->post('phone_num')
                                    );
                    $upload_resume = $this->_upload_resume_function();
                    if($upload_resume['status'] == TRUE){
                        $user_data['res_id'] = $upload_resume['data']['resume_id'];
                    }
                    $upload_profile_pic = $this->_upload_profile_pic_function();
                    if($upload_profile_pic['status'] == TRUE){
                        $user_data['site_media_id'] = $upload_profile_pic['data']['site_media_id'];
                    }
                    $return_data = $this->user_model->update_user($user_data, $user_id, $salt);
                    if(!empty($return_data)){
                        if(!empty($user_data['res_id'])){
                            $return_data['resume_path'] = $upload_resume['data']['resume_path'];
                        }
                        if(!empty($user_data['site_media_id'])){
                            $return_data['profile_pic_path'] = $upload_profile_pic['data']['profile_pic_path'];
                        }
                        $data = array(
                                        'status' => TRUE, 
                                        'message' => "User data was updated successfully", 
                                        'data' =>  $return_data, 
                                    );
                    }
                }else{
                    $data['message'] = validation_errors();
                }
        }
        echo json_encode($data);
    }

    public function login_post(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "Not able to login try again later", 
                        'data' =>  array(), 
                    );
        if(!empty($_POST)){
            $this->form_validation->set_error_delimiters('','');
            $this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[50]');
                if($this->form_validation->run() == TRUE){
                    $login_data = array(
                                        'email'     => $this->post('email'), 
                                        'password'  => $this->post('password'),
                                        'is_admin'  => $this->post('is_admin')
                                    );
                    if($return_data = $this->user_model->get_single_user($login_data)){
                        $data = array(
                                        'status' => TRUE, 
                                        'message' => "User logged in successfully", 
                                        'data' =>  $return_data, 
                                    );
                    }else{
                        $data['message'] = "Username or Password is wrong";
                    }
                }
        }
        echo json_encode($data);
    }

    public function upload_resume_post(){
        $data = $this->_upload_resume_function();
        echo json_encode($data);
    }

    public function upload_profile_pic_post(){
        $data = $this->_upload_profile_pic_function();
        echo json_encode($data);
    }

    private function _upload_resume_function(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "Not able to upload resume.", 
                        'data' =>  array(), 
                    );
        if (isset($_FILES['resume_file']['name']) && !empty($_FILES['resume_file']['name'])) {
            //$config['upload_path']          = base_url().'public/uploads/resumes/';
            $config['upload_path']          = './public/uploads/resumes/';
            $config['allowed_types']        = 'pdf';
            $this->load->library('upload');
            $this->upload->initialize($config);
            if ( $this->upload->do_upload('resume_file')){
                $upload_data = $this->upload->data();
                $resume_data = array(
                                    'name' => $upload_data['file_name'], 
                                    'file' => base_url().'public/uploads/resumes/'.$upload_data['file_name'], 
                                    'extension' => $upload_data['file_ext'], 
                                );
                $resume_id = $this->files_model->insert_resume($resume_data);
                $data = array(
                            'status' => TRUE, 
                            'message' => "Resume Uploaded successfully", 
                            'data' =>  array('resume_id' => $resume_id, 'resume_path' => $resume_data['file'] ), 
                        );
            }else{
                $data['message'] = strip_tags($this->upload->display_errors());
            }
        }
        return($data);
    }

    private function _upload_profile_pic_function(){
        $data = array(
                        'status' => FALSE, 
                        'message' => "Not able to upload profile picture.", 
                        'data' =>  array(), 
                    );
        if (isset($_FILES['picture_file']['name']) && !empty($_FILES['picture_file']['name'])) {
            $config['upload_path']          = './public/uploads/profilepictures/';
            $config['allowed_types']        = 'jpeg|jpg|png|gif';
            $this->load->library('upload');
            $this->upload->initialize($config);
            if ( $this->upload->do_upload('picture_file')){
                $upload_data = $this->upload->data();
                $picture_data = array(
                                    'name' => $upload_data['file_name'], 
                                    'file' => base_url().'public/uploads/profilepictures/'.$upload_data['file_name'], 
                                    'extension' => $upload_data['file_ext'], 
                                );
                $site_media_id = $this->files_model->insert_profile_pic($picture_data);
                $data = array(
                            'status' => TRUE, 
                            'message' => "Profile Picture Uploaded successfully", 
                            'data' =>  array('site_media_id' => $site_media_id, 'profile_pic_path' => $picture_data['file']  ), 
                        );
            }else{
                $data['message'] = strip_tags($this->upload->display_errors());
            }
        }
        return($data);
    }

    public function is_unique($email = NULL){
        $this->form_validation->set_message('is_unique', 'Email already registered.');
        if(!empty($email)){
            if($this->user_model->is_unique_email($email)){
                return TRUE;
            }else{
                return FALSE;
            }
        }
        return FALSE;
    }

}
