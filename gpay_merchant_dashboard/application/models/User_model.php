<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    // Controller: admin/users/index
    // Fungsi: ambil semua data user
    function get_all_users($num = NULL, $offset = NULL)
    {
        $this->db->select('admin_user.id, admin_user.username, admin_user.fullname, admin_user.agent_merchant_id, admin_user.is_pos_user, admin_user.status_code, master_std.dsc AS user_status');
        $this->db->join('master_std', 'master_std.code = admin_user.status_code');
        $this->db->where('master_std.data_group', 'user_status');
        $this->db->order_by('username', 'asc');
        if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
        return $this->db->get('admin_user');
    }

    // Controller: admin/users/edit
    // Fungsi: ambil data user berdasarkan id
    function get_user_by_id($id)
    {
        $this->db->select('admin_user.id, admin_user.username, admin_user.fullname, admin_user.email, admin_user.agent_merchant_id, admin_user.is_pos_user, admin_user.status_code, master_std.dsc AS user_status');
        $this->db->join('master_std', 'master_std.code = admin_user.status_code');
        $this->db->where('master_std.data_group', 'user_status');
        return $this->db->get_where('admin_user', array('admin_user.id' => $id));
    }

    // Controller: login/validate_login
    // Fungsi: ambil data user berdasarkan username
    function get_user_by_username($username)
    {
        $this->db->where('status_code', '1');
        return $this->db->get_where('admin_user', array('LOWER(username)' => strtolower($username)));
    }

    // Controller: admin/users/save_edit
    // Fungsi: ambil data user berdasarkan username
    function get_user_by_email($email)
    {
        $this->db->where('status_code', '1');
        return $this->db->get_where('admin_user', array('LOWER(email)' => strtolower($email)));
    }

        // Controller: admin/transaction/insert
    // Fungsi: ambil data user dengan detail agent 
    function get_user_agent_terminal_data_by_id($id)
    {
        $this->db->select('terminal.id');
        $this->db->where('admin_user.status_code', '1');
        $this->db->join('merchant','admin_user.agent_merchant_id = merchant.id');
        $this->db->join('store','merchant.id = store.merchant_id');
        $this->db->join('terminal','store.id = terminal.store_id');
        return $this->db->get_where('admin_user', array('admin_user.id' => $id));
    }

    // Controller: admin/users/index, admin/users/search, admin/users/details
    // Fungsi: ambil semua data companies
    function get_all_agent_companies()
    {
        $this->db->order_by('merchant_name', 'asc');
        return $this->db->get_where('merchant', array('is_agent' => 'Y'));
    }

    // Controller: admin/users/index, admin/users/search, admin/users/details
    // Fungsi: ambil semua data user group
    function get_all_user_groups()
    {
        $this->db->order_by('dsc', 'asc');
        return $this->db->get_where('master_std', array('data_group' => 'user_groups'));
    }

    // Controller: admin/users/save_add_usergroup, admin/users/delete_usergroup
    // Fungsi: ambil user group berdasarkan code
    function get_usergroup_by_code($code){
        return $this->db->get_where('master_std', array('data_group' => 'user_groups', 'code' => $code));
    }

    // Controller: admin/users/details
    // Fungsi: ambil user group berdasarkan user id
    function get_usergroup_by_user_id($user_id)
    {
        $this->db->select('master_std.dsc, master_std.code');
        $this->db->join('master_std', 'master_std.code = admin_user_group.admin_user_group_code');
        $this->db->where('master_std.data_group', 'user_groups');
        $this->db->order_by('master_std.dsc', 'asc');
        return $this->db->get_where('admin_user_group', array('admin_user_group.admin_user_id' => $user_id));
    }

    // Controller: admin/users/details
    // Fungsi: ambil user group yang belum di assign berdasarkan user id
    function get_unassigned_usergroup_by_user_id($user_id)
    {
        $user_groups = $this->get_usergroup_by_user_id($user_id)->result();
        $array = array();
        foreach($user_groups as $user_group){
            array_push($array, $user_group->code);
        }

        if(count($array) > 0) $this->db->where_not_in('code', $array);
        return $this->db->order_by('dsc', 'asc')->get_where('master_std', array('data_group' => 'user_groups'));
    }

    // Controller: admin/users/details
    // Fungsi: ambil description dari type admin user 
    function get_user_management_type_by_code($code){
        return $this->db->get_where('master_std', array('data_group'=>'approval_request_type', 'code'=>$code));
    }


    //============================== SEARCH ==============================//

    function get_search_result($search_param, $num = NULL, $offset = NULL)
    {
        $this->db->distinct();
        $this->db->select('admin_user.id, admin_user.username, admin_user.fullname, admin_user.agent_merchant_id, admin_user.is_pos_user, admin_user.status_code, master_std.dsc AS user_status');
        $this->db->join('master_std', 'master_std.code = admin_user.status_code');
        $this->db->where('master_std.data_group', 'user_status');

        // Search username
        if(!empty($search_param['username'])){
            $this->db->like('LOWER(username)', strtolower($search_param['username']), 'both');
        }

        // Search pos username
        if(!empty($search_param['pos_username'])){
            $this->db->like('LOWER(username)', strtolower($search_param['pos_username']), 'both');
            $this->db->where('is_pos_user', 'Y');
        }

        // Search user group
        if(!empty($search_param['user_group'])){
            $this->db->join('admin_user_group', 'admin_user_group.admin_user_id = admin_user.id');
            $this->db->where('LOWER(admin_user_group.admin_user_group_code)', strtolower($search_param['user_group']));
        }

        // Search agent company
        if(!empty($search_param['agent_company'])){
            $this->db->join('merchant', 'merchant.id = admin_user.agent_merchant_id');
            $this->db->where('admin_user.agent_merchant_id', $search_param['agent_company']);
        }
        
        //$this->db->order_by($search_param['sort_by']);
        $this->db->order_by('username', 'asc');
        if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
        return $this->db->get('admin_user');
    }

    //============================== INSERT QUERY ==============================//

    public function insert($data){
        $this->db->insert('admin_user', $data);
        $id = $this->db->insert_id();
        return $id;
    }

    public function insert_admin_user_group($data){
        $this->db->insert('admin_user_group', $data);
    }

    //============================== UPDATE QUERY ==============================//

    public function update($data, $id){
        $this->db->where('id', $id);
        $this->db->update('admin_user', $data);
    }
    
    public function disable_user($id){
        $this->db->where('id', $id);
        $this->db->update('admin_user', array('status_code' => '2'));
    }

    public function enable_user($id){
        $this->db->where('id', $id);
        $this->db->update('admin_user', array('status_code' => '1'));
    }

    //============================== DELETE QUERY ==============================//

    public function delete_admin_user_group($user_id, $user_group){
        $this->db->delete('admin_user_group', array('admin_user_id' => $user_id, 'admin_user_group_code' => $user_group));
    }


    public function get_user_permission_by_id($id){
        return $this->db
            ->where('id',$id)
            ->join('merchant_dashboard_group','merchant_dashboard_login.id = merchant_dashboard_group.merchant_dashboard_login_id','right')
            ->join('merchant_dashboard_screen_permission','merchant_dashboard_group.merchant_group_code = merchant_dashboard_screen_permission.merchant_group_code','right')
            ->join('merchant_dashboard_screen_action','merchant_dashboard_screen_permission.action_name = merchant_dashboard_screen_action.action_name','left')
            ->get('merchant_dashboard_login');
    }

    
    public function get_user_level_sbu_by_id($id){
        return $this->db
            ->select('
                merchant_dashboard_login_sbu.merchant_dashboard_login_id as "login_dashboard",
                merchant_dashboard_login_sbu.merchant_dashboard_login_sbu as "sbu_id"
            ')
            ->where('merchant_dashboard_login_id',$id)
            ->join('merchant_dashboard_login','merchant_dashboard_login.id = merchant_dashboard_login_sbu.merchant_dashboard_login_id')
            ->get('merchant_dashboard_login_sbu');
    }

    public function get_user_level_merchant_by_id($id){
        return $this->db
            ->select('
            merchant_dashboard_login_merchant.merchant_dashboard_login_sbu as "sbu_id",
            merchant_dashboard_login_merchant.merchant_dashboard_login_merchant as "merchant_id",
            merchant_dashboard_login_merchant.merchant_type
            ')
            ->where('id',$id)
            ->where('merchant_dashboard_login_merchant.merchant_type','ma')
            ->join('merchant_dashboard_login_sbu','merchant_dashboard_login.id = merchant_dashboard_login_sbu.merchant_dashboard_login_id')
            ->join('merchant_dashboard_login_merchant','merchant_dashboard_login_merchant.merchant_dashboard_login_sbu = merchant_dashboard_login_sbu.merchant_dashboard_login_sbu')
            ->get('merchant_dashboard_login');
    }

    public function get_user_level_qris_online_merchant_by_id($id){
        return $this->db
            ->select('
            merchant_dashboard_login_merchant.merchant_dashboard_login_sbu as "sbu_id",
            merchant_dashboard_login_merchant.merchant_dashboard_login_merchant as "merchant_id",
            merchant_dashboard_login_merchant.merchant_type
            ')
            ->where('id',$id)
            ->where('merchant_dashboard_login_merchant.merchant_type','qo')
            ->join('merchant_dashboard_login_sbu','merchant_dashboard_login.id = merchant_dashboard_login_sbu.merchant_dashboard_login_id')
            ->join('merchant_dashboard_login_merchant','merchant_dashboard_login_merchant.merchant_dashboard_login_sbu = merchant_dashboard_login_sbu.merchant_dashboard_login_sbu')
            ->get('merchant_dashboard_login');
    }

    public function get_user_level_store_by_id($id){
        return $this->db
            ->select('
            merchant_dashboard_login_merchant.merchant_dashboard_login_sbu as "sbu_id",
            office_store.merchant_id as "merchant_id",
            office_store.store_id as "store_id"
            ')
            ->where('id',$id)
            ->join('merchant_dashboard_login_sbu','merchant_dashboard_login.id = merchant_dashboard_login_sbu.merchant_dashboard_login_id')
            ->join('merchant_dashboard_login_merchant','merchant_dashboard_login_merchant.merchant_dashboard_login_sbu = merchant_dashboard_login_sbu.merchant_dashboard_login_sbu')
            ->join('office_store','office_store.merchant_id = merchant_dashboard_login_merchant.merchant_dashboard_login_merchant')
            ->get('merchant_dashboard_login');
    }

    public function get_dsc_permission_and_screen_by_permission_name($permission,$role){
        return $this->db
            ->select('
                (select dsc from master_std where screen_action.action_name = master_std.code and master_std.data_group = \'user_permission\') as "permission",
                (select dsc from master_std where screen_action.menu_name = master_std.code and master_std.data_group = \'screen\') as "screen",
                (select dsc from master_std where master_std.code = \''.$role.'\' and master_std.data_group = \'user_groups\') as "user_group"
            ')
            ->where('action_name',$permission)
            ->get('screen_action');
    }

    public function get_all_user_and_usergroup_by_usergroup_code($usergroupcode){
         $data = $this->db
             ->distinct()
             ->select('
                admin_user.email as email
             ')
             ->where_in('admin_user_group.admin_user_group_code',$usergroupcode)
             ->where('status_code','1')
             ->join('admin_user_group','admin_user.id = admin_user_group.admin_user_id')
             ->get('admin_user')
             ->result();

        $result = array();
        foreach ($data as $dat){
            if($dat->email != ''){
                array_push($result,$dat->email);
            }
        }
        $result = array_filter($result);
        return $result;
    }

}
