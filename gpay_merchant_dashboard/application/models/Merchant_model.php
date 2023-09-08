<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Merchant_model extends CI_Model{

     //============================== GET MERCHANT DATA  ==============================//
    //fungsi : ambil data merchant yang login web
    public function get_merchant($id)
    {
        $this->db->distinct();
        $this->db->select('merchant_id, name, phone, email')
            ->from('office_merchant');
        $this->db->where('merchant_id', $id);
        return $this->db->get();
    }

    function get_by_merchant_qris_code($merchant_qris_code = NULL, $num = NULL, $offset = NULL)
    {
        $this->db->select('merchant_id, name, address, phone, email, owner_name');
        $this->db->where('merchant_id', $merchant_qris_code);
        $this->db->order_by('merchant_id', 'asc');
        if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
        return $this->db->get('office_merchant');
    }
}