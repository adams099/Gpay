<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_groups_model extends CI_Model {

	// Controller: admin/users/index
	// Fungsi: ambil semua data User Groups
	function get_all_user_groups($num = NULL, $offset = NULL)
	{
		$this->db->order_by('code', 'asc');
		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get_where('master_std', array('data_group' => 'user_groups'));
	}

	// Controller: 
	// Fungsi: ambil data User Groups berdasarkan name code
	function get_user_group_by_name($name)
	{
		return $this->db->get_where('master_std', array('data_group' => 'user_groups', 'code' => $name));
	}

	// Controller: 
	// Fungsi: ambil data User Groups berdasarkan name code
	function get_user_group_by_user_id_and_code($id, $code)
	{
		return $this->db->get_where('admin_user_group', array('admin_user_id' => $id, 'admin_user_group_code' => $code));
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

	//============================== SEARCH ==============================//

	function get_search_result($search_param, $num = NULL, $offset = NULL)
	{
		if(isset($search_param['admin_user_group_code'])){
			$this->db->like('dsc', $search_param['admin_user_group_code'], 'both');
		}
		
		$this->db->order_by('dsc', 'asc');
		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get_where('master_std', array('data_group' => 'user_groups'));
	}

	//============================== INSERT QUERY ==============================//

	public function insert($data){
		$this->db->insert('admin_user_group', $data);
	}

	//============================== UPDATE QUERY ==============================//

	public function update($data, $id){
		$this->db->where('id', $id);
		$this->db->update('admin_user_group', $data);
	}

	//============================== DELETE QUERY ==============================//
	
	public function delete($id, $code){
		$this->db->delete('admin_user_group', array('admin_user_id' => $id, 'admin_user_group_code' => $code));
	}

	
	//============================== DATATABLE =============================//

	var $table = 'admin_user'; //nama tabel dari database
	var $column_order = array('username'); //field yang ada di table
	var $column_search = array('username'); //field yang diizinkan untuk pencarian
	var $order = array('username' => 'desc'); // default order

	private function _get_datatables_query($code){

		$this->db->from($this->table);
		$this->db->join('admin_user_group', 'admin_user.id = admin_user_group.admin_user_id', 'left');
		$this->db->where(array('admin_user_group.admin_user_group_code' => $code));
		// $this->db->like('username', $this->username,'both');

		$i = 0;

		foreach ($this->column_search as $item){ // looping awal
			if($_POST['search']['value']){ // jika datatable mengirimkan pencarian dengan metode POST

				if($i===0){ // looping awal
					$this->db->group_start(); 
					$this->db->like($item, $_POST['search']['value']);
				}
				else{
					$this->db->or_like($item, $_POST['search']['value']);
				}

				if(count($this->column_search) - 1 == $i) 
					$this->db->group_end(); 
			}
			$i++;
		}

		if(isset($_POST['order'])) {
			$this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
		} 
		else if(isset($this->order)){
			$order = $this->order;
			$this->db->order_by(key($order), $order[key($order)]);
		}
	}

	public function get_datatables($code){
		$this->_get_datatables_query($code);
		if($_POST['length'] != -1)
			$this->db->limit($_POST['length'], $_POST['start']);
		$query = $this->db->get();
		return $query->result();
	}

	public function count_filtered($code){
		$this->_get_datatables_query($code);
		$query = $this->db->get();
		return $query->num_rows();
	}

	public function count_all(){
		$this->db->from($this->table);
		// $this->db->where_in('username', $this->username);
		return $this->db->count_all_results();
	}
}
