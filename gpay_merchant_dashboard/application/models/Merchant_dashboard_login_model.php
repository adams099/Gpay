<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Merchant_dashboard_login_model extends CI_Model
{
    //============================== USER VALIDATION QUERY ==============================//
    //fungsi : ambil data role user yang login
    public function get_admin_user_data($id)
    {
        $this->db->select('admin_user.id, admin_user.username, admin_user.agent_merchant_id, admin_user_group.admin_user_group_code, merchant.merchant_name, merchant.topup_balance');
        $this->db->join('admin_user_group', 'admin_user.id = admin_user_group.admin_user_id', 'left');
        $this->db->join('merchant', 'admin_user.agent_merchant_id = merchant.id', 'left');
        $this->db->where('admin_user.id', $id);
        return $this->db->get('admin_user');
    }

    //============================== PHONE NO MERCHANT DASHBOARD LOGIN ==============================//
    //fungsi : cek mobile no merchant app
    public function check_number($mobile_phone_no)
    {
        $this->db->select('username, password_hash')
            ->from('merchant_dashboard_login');
        $this->db->where('username', $mobile_phone_no);
        return $this->db->get();
    }

    //============================== GET MERCHANT LOGIN DATA ==============================//
    //fungsi : ambil data merchant yang login web
    public function get_merchant_login_data($username)
    {
        $this->db->distinct();
        $this->db->select('id, username, merchant_id, password_hash, dt_suspend_login, failed_login_count ,receiving_inst_id')
            ->from('merchant_dashboard_login');
        $this->db->where('username', $username);
        $this->db->where('status', '1');
        return $this->db->get();
    }

    //============================== CEK AKUN SUSPENDED ==============================//
    //fungsi : cek akun login apakah suspend atau tidak
    public function check_suspend($id)
    {
        $this->db->select('id')
            ->from('merchant_dashboard_login');
        $this->db->where('id', $id);
        $this->db->where('dt_suspend_login' >= 'CURRENT_TIMESTAMP');
        return $this->db->get();
    }

    //============================== UI DATA QUERY ==============================//
    //fungsi : ambil semua merchant type agent berdasar id yang login
    public function get_all_merchant_topup_request()
    {
        $this->db->select('merchant_topup_request.id, merchant_topup_request.merchant_topup_request_status_code, merchant.id AS merchant_id, merchant.merchant_name, merchant_topup_request.amount, master_std.dsc, merchant_topup_request.dt_create, merchant_topup_request.dt_approve, merchant_topup_request.note')
            ->from('merchant_topup_request')
            ->order_by('merchant_topup_request.id', 'DESC');
        $this->db->join('merchant', 'merchant_topup_request.merchant_id = merchant.id', 'left');
        $this->db->join('master_std', 'merchant_topup_request.merchant_topup_request_status_code = master_std.code');
        $this->db->where('master_std.data_group=', 'merchant_request_topup_status');
        return $this->db->get();
    }

   

    

    //============================== VALIDATION + GET DATA QUERY ==============================//
    //fungsi : validasi merchant yang dipilih ada
	function get_company_by_id($id)
	{
		if($id != ""){
            $this->db->where(array('id' => $id, 'is_agent' => 'Y'));
            return $this->db->get('merchant');
        }
        else{
            return 0;
        }
    }

    public function update_merchant_dashboard_login($data, $id){
		$this->db->where('id', $id);
		$this->db->update('merchant_dashboard_login', $data);
    }

    
    //============================== INSERT TRANSACTION QUERY ==============================//

    public function insert_merchant_topup_transaction($data){
        //load CI instance
        $this->CI = & get_instance();

        $this->db->set('start_datetime', 'NOW()');
        $this->db->set('end_datetime', 'NOW()');
        $this->db->insert('transaction', $data);
        $id = $this->db->insert_id();
        $update_data = array('reference_number' => $this->CI->global_library->transaction_id_hash_to_base_36($id));
        $this->db->update('transaction', $update_data);
    }

    //============================== SEARCH ==============================//

	function get_search_result($search_param, $num = NULL, $offset = NULL)
	{
        $this->db->select('merchant_topup_request.id, merchant_topup_request.merchant_topup_request_status_code, merchant.id AS merchant_id, merchant.merchant_name, merchant_topup_request.amount, master_std.dsc, merchant_topup_request.dt_create, merchant_topup_request.dt_approve, merchant_topup_request.note');
        $this->db->join('merchant', 'merchant_topup_request.merchant_id = merchant.id', 'left');
		$this->db->join('master_std', 'merchant_topup_request.merchant_topup_request_status_code = master_std.code');
        $this->db->from('merchant_topup_request');
        
		if(!empty($search_param['company'])){
			$this->db->like('LOWER(merchant.merchant_name)', strtolower($search_param['company']), 'both');
        }
		if (isset($search_param['request_date']) && !empty($search_param['request_date'])){
            $this->db->where('merchant_topup_request.dt_create >=', $search_param['request_date'].' 00:00:00');
            $this->db->where('merchant_topup_request.dt_create <=', $search_param['request_date'].' 23:59:59');
		}
		if (isset($search_param['approved_date']) && !empty($search_param['approved_date'])){
            $this->db->where('merchant_topup_request.dt_approve >=', $search_param['approved_date'].' 00:00:00');
			$this->db->where('merchant_topup_request.dt_approve <=', $search_param['approved_date'].' 23:59:59');
		}
		if(!empty($search_param['request_status_code'])){
			$this->db->where('merchant_topup_request.merchant_topup_request_status_code', $search_param['request_status_code']);
		}
		$this->db->where(array('master_std.data_group=' => 'merchant_request_topup_status'));
		$this->db->order_by('merchant_topup_request.id', 'DESC');
		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get();
    }

}