<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Approval_sequential_number_model extends CI_Model
{

    public function select(){
        return $this->db
            ->order_by('date','desc')
            ->get('approval_sequential_number');
    }
    public function insert(){
        $this->db->insert('approval_sequential_number',array(
            'date' => date('Y-m-d H:i:s')
        ));

        $error = $this->db->error();
        if ( ! empty($error['code'])) {
            return false;
        }else{
            return true;
        }
    }

    public function alter_table(){
        $this->db->query('ALTER SEQUENCE approval_sequential_number_sequential_seq RESTART WITH 1');
    }

}