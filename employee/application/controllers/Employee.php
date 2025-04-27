<?php
class Employee extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('employee_model');
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->helper('url');
       
    }
    
    public function index() {
                $this->load->view('employee_registration_form');
    }

    public function register() {
                $this->form_validation->set_rules('name', 'Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('job_title', 'Job Title', 'required');
        $this->form_validation->set_rules('salary', 'Salary', 'required|numeric');
        $this->form_validation->set_rules('hire_date', 'Hire Date', 'required');

        if ($this->form_validation->run() == FALSE) {
                        $this->load->view('employee_registration_form');
        } else {
                        $data = array(
                'name' => $this->input->post('name'),
                'email' => $this->input->post('email'),
                'phone' => $this->input->post('phone'),
                'job_title' => $this->input->post('job_title'),
                'salary' => $this->input->post('salary'),
                'hire_date' => $this->input->post('hire_date'),
            );

            $employee_id = $this->employee_model->add_employee($data);

            if ($employee_id) {
                                redirect('employee/success','refresh');
            } else {
                                $this->load->view('employee_registration_form', array('error' => 'Registration failed.'));
            }
        }
    
    }

    public function success() {
                $this->load->view('registration_success');
    }
}
