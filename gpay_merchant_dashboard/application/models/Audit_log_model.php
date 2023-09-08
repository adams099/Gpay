<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_log_model extends CI_Model {

	function get_all_audit_log_by_search($search_param, $num = NULL, $offset = NULL,$export = false)
	{	
		$this->db->select('
		    admin_user_activity_log.*,
		    admin_user.username');
		
		if(!empty($search_param['table_name'])){
			$this->db->where('table_name', $search_param['table_name']);
		}

		if(!empty($search_param['screen_name'])){
			$this->db->where('screen_name', $search_param['screen_name']);
		}

		if(!empty($search_param['start_interval']) && !empty($search_param['end_interval'])){
			$this->db->where('log_datetime >=', $search_param['start_interval'] . ' 00:00:00');
			$this->db->where('log_datetime <=', $search_param['end_interval'] . ' 23:59:59');
		}

		$this->db->join('admin_user', 'admin_user.id = admin_user_activity_log.user_id');

		if(!empty($search_param['username'])){
			$this->db->where('admin_user.username', $search_param['username']);
		}

		$this->db->order_by('log_datetime', 'desc');

		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);

		// if($export == false){
        //     $this->db->limit(10);
        // }

		return $this->db->get('admin_user_activity_log');
	}

	function get_audit_log_by_id($id)
	{
		$this->db->select('*, admin_user_activity_log.id as id');
		$this->db->join('admin_user', 'admin_user.id = admin_user_activity_log.user_id');
		return $this->db->get_where('admin_user_activity_log', array('admin_user_activity_log.id' => $id));
	}

	function get_related_screen_name($table)
	{
		$this->db->distinct();
		$this->db->select('screen_name');
		return $this->db->get_where('admin_user_activity_log', array('table_name' => $table));
	}

	//============================== INSERT QUERY ==============================//

	public function insert($record){
		$data = array(
			'screen_name' => $record['screen_name'],
			'user_action' => $record['user_action'],
			'table_name' => $record['table_name'],
			'data_after' => $record['data_after'],
			'user_id' => $record['user_id']
		);

		if(!isset($record['user_role'])){
			$data['user_role'] = $this->session->userdata('role')[0];
		}
		else{
			$data['user_role'] = $record['user_role'];
		}

		if(isset($record['data_before'])){
			$data['data_prior'] = $record['data_before'];
		}

		$this->db->insert('admin_user_activity_log', $data);
	}

}
