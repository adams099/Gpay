
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Change_password_model extends CI_Model
{


function get_user_by_id($id)
    {
        $this->db->select('*');
        $this->db->join('master_std', 'master_std.code = admin_user.status_code');
        $this->db->where('master_std.data_group', 'user_status');
        return $this->db->get_where('admin_user', array('admin_user.id' => $id));
    }

function change_password($id,$data)
    {
        $this->db->where('id', $id);
        return $this->db->update('admin_user', $data);
    }
}