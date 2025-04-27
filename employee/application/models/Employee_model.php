<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Employee_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        

    }

    public function create_employee($data, $imported_data = array()) {
        var_dump($data);
    $this->db->insert('employees', $data);
}


        public function get_all_employees() {
        $query = $this->db->get('employees');
        return $query->result_array();
    }
    

    public function get_employee_by_id($id) {
        $query = $this->db->get_where('employees', array('id' => $id));
        return $query->row_array();
    }
    
    public function update_employee($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('employees', $data);
    }

    public function read_employees() {
                $query = $this->db->get('employees');

        return $query->result();      }

    public function delete_employee($id) {
        $this->db->where('id', $id);
        return $this->db->delete('employees');
    }
  
    public function authenticate($username, $password) {
                        
                $defaultUsername = 'admin';
        $defaultPassword = 'admin123';

        if ($username === $defaultUsername && $password === $defaultPassword) {
            return true;         } else {
            return false;         }
    }
}