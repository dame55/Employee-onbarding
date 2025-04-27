<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    private $admin_data;     private $employees = array();
    
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('form');
        $this->load->helper('url');
        $this->load->library('form_validation');
        $this->load->database();
        $this->load->model('admin_model');


                $this->admin_data = array('admin_id' => null);         $this->employees = array();
    }

    public function login() {
        $this->load->view('admin/login');
    }

    public function authenticate() {
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == FALSE) {
            $this->load->view('admin/login');
        } else {
            $username = $this->input->post('username');
            $password = $this->input->post('password');

            $user = $this->admin_model->get_admin($username, $password);

            if ($user) {
                $this->session->set_userdata('admin_id', $user['id']);
                $this->session->set_userdata('admin_username', $user['username']);

                redirect('admin/dashboard');             } else {
                $data['error'] = 'Invalid username or password';
                $this->load->view('admin/login', $data);
            }
        }
    }
    
   
    public function dashboard() {
        
      
            $this->load->view('admin/dashboard');
       
    }
    
    
    




public function change_language($language)
{
        $allowed_languages = ['english', 'amharic', 'oromo'];
    if (!in_array($language, $allowed_languages)) {
                redirect('admin/dashboard');
    }

        $this->session->set_userdata('language', $language);

        $this->config->set_item('language', $language);

        redirect('admin/dashboard');
}


















    public function create_employee() {
        $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
    
        $this->load->view('admin/create_employee');
    }
    public function save_created_employee() {
        $this->load->model('employee_model');
    
                $employee_data = array(
            'name' => $this->input->post('name'),
            'email' => $this->input->post('email'),
            'phone' => $this->input->post('phone'),
            'job_title' => $this->input->post('job_title'),
            'salary' => $this->input->post('salary'),
            'hire_date' => $this->input->post('hire_date'),
        );
    
                if (empty($employee_data['name'])) {
                        redirect('admin/create_employee?error=name_required');
            return;
        }
    
                var_dump($employee_data);
    
                if (isset($_FILES['importFile']) && $_FILES['importFile']['error'] === UPLOAD_ERR_OK) {
                        $imported_data = $this->processImportedData($_FILES['importFile']['tmp_name']);
    
                        var_dump($imported_data);
        } else {
                        echo 'File not uploaded or an error occurred.';
            $imported_data = array();         }
    
                $this->employee_model->create_employee($employee_data, $imported_data);
    
                redirect('admin/dashboard');
    }
    

   
    
    
    
        public function read_employees() {
        $this->load->model('employee_model');
        $data['employees'] = $this->employee_model->get_all_employees();
        $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
    
        $this->load->view('admin/read_employees', $data);
    }
    
  



    public function update_employee() {
        $this->load->helper('url');
        $this->load->library('form_validation');
        $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
    
        $this->form_validation->set_rules('id', 'Employee ID', 'required');
            
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('admin/update_employee');
        } else {
            $this->load->model('Employee_model');
    
            $data = array(
                'name' => $this->input->post('name'),
                'email' => $this->input->post('email'),
                'phone' => $this->input->post('phone'),
                'job_title' => $this->input->post('job_title'),
                'salary' => $this->input->post('salary'),
                'hire_date' => $this->input->post('hire_date')
            );
    
            $id = $this->input->post('id');
    
            if ($this->Employee_model->update_employee($id, $data)) {
                                redirect('admin/read_employees');             } else {
                                log_message('error', 'Failed to update employee with ID ' . $id);
                            }
        }
    }
    






    public function delete_employee() {
        $this->load->helper('url');
        $this->load->model('Employee_model');
        $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
    
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $this->input->post('id');

                        $existing_employees = $this->Employee_model->read_employees();

                        $existing_employee = array_filter($existing_employees, function ($employee) use ($id) {
                return $employee->id == $id;
            });

            if (empty($existing_employee)) {
                                log_message('error', 'Employee with ID ' . $id . ' not found');
                            }

                        if ($this->Employee_model->delete_employee($id)) {
                                redirect('admin/read_employees');             } else {
                                log_message('error', 'Failed to delete employee with ID ' . $id);
                            }
        } else {
                        $this->load->view('admin/delete_employee');
        }
    }




    public function change_password() {
                $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
        if (!$this->session->userdata('admin_id')) {
            redirect('admin/login');         }
    
        $this->load->library('form_validation');
    
        $this->form_validation->set_rules('current_password', 'Current Password', 'required');
        $this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[6]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');
    
        if ($this->form_validation->run() == FALSE) {
                        $this->load->view('admin/change_password');
        } else {
            $admin_id = $this->session->userdata('admin_id');
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password');
    
                        $is_current_password_correct = $this->admin_model->check_current_password($admin_id, $current_password);
    
            if ($is_current_password_correct) {
                                $affected_rows = $this->admin_model->update_password($admin_id, $new_password);
    
                if ($affected_rows > 0) {
                                        $this->session->sess_destroy();
    
                                        redirect('admin/change_password_success');
                } else {
                                        $data['error'] = 'Password update failed.';
                    $this->load->view('admin/change_password', $data);
                }
            } else {
                $data['error'] = 'Incorrect current password';
                $this->load->view('admin/change_password', $data);
            }
        }
    }
    



    public function change_password_success() {
        $language = $this->session->userdata('language') ?? 'english';
        $this->lang->load($language, $language);
        $this->load->view('admin/change_password_success');
    }
    
    public function save_imported_data() {
        $importedData = $this->input->post('importedData');
    
                $rows = explode("\n", $importedData);
        $dataToInsert = array();
    
                $headerSkipped = false;
    
        foreach ($rows as $row) {
                        if (empty($row)) {
                continue;
            }
    
            $columns = str_getcsv($row);
    
                        if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }
    
                        if (count($columns) === 6) {
                $dataToInsert[] = array(
                    'name' => $columns[0],
                    'email' => $columns[1],
                    'phone' => $columns[2],
                    'job_title' => $columns[3],
                    'salary' => $columns[4],
                    'hire_date' => $columns[5],
                );
            } else {
                                echo json_encode(['status' => 'error', 'message' => 'Invalid CSV data format']);
                return;
            }
        }
    
                foreach ($dataToInsert as $row) {
            $this->db->insert('employees', $row);
        }
    
                if ($this->db->affected_rows() > 0) {
                        echo json_encode(['status' => 'success']);
        } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to insert data.']);
        }
    }
    


    public function logout() {
                $this->session->unset_userdata('admin_data');

                redirect('admin/login');
    }

}
