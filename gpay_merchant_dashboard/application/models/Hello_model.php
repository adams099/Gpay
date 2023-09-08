<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hello_model extends CI_Model {
    public function get_master_std($data_group, $order_col)
    {
        $sql = "select code,dsc,note from master_std where data_group=? order by ".$order_col;
        $param = array($data_group);
        $retval = $this->db->query($sql, $param);
        return $retval;
    }
}