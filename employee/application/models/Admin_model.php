
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Admin_model extends CI_Model {
   
    public function get_admin($username, $password) {
        $query = $this->db->get_where('admin', array('username' => $username, 'password' => md5($password)));

        if ($query->num_rows() == 1) {
            return $query->row_array();
        } else {
            return false;
        }
    }
    public function check_current_password($admin_id, $current_password) {
        $query = $this->db->get_where('admin', array('id' => $admin_id, 'password' => md5($current_password)));
        return ($query->num_rows() == 1);
    }

    public function update_password($admin_id, $new_password) {
                $this->db->where('id', $admin_id);
        $this->db->update('admin', array('password' => md5($new_password)));
        return $this->db->affected_rows();     }
}

?>

