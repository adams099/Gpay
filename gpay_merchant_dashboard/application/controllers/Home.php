<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		
		// load model..
		$this->load->model('user_model');
		$this->load->model('user_groups_model');
		$this->load->model('merchant_model');
		$this->load->model('merchant_dashboard_login_model');
		$this->load->model('dashboard_model');
		
		// construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		$merchant_dashboard_login = $this->merchant_dashboard_login_model->get_merchant_login_data($this->session->userdata('username'))->row();
		$merchant = $this->merchant_model->get_merchant($merchant_dashboard_login->merchant_id)->row();
		$this->session->set_userdata('merchant_qris_code', $merchant->merchant_id);
		$this->session->set_userdata('receiving_inst_id', $merchant_dashboard_login->receiving_inst_id);
		$data['action'] = $this->session->userdata('action');
		$data['merchant'] = $merchant;
		$data['merch_summ'] = $this->dashboard_model->get_merchant_summary_data('All',$this->session->userdata('merchant_qris_code'), 'today');
		$data['issuer_summ'] = $this->dashboard_model->get_issuer_summary_data('All', $this->session->userdata('merchant_qris_code'), 'today');
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('home', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;



		$this->load->view('template', $template);
	}

	public function logout()
	{
		// $admin = $this->user_model->get_user_by_id($this->session->userdata('id'))->row();
		// $log_login_data = array(
		// 	'id' => $this->session->userdata('id'),
		// 	'username' => $admin->username,
		// 	'fullname' => $admin->fullname,
		// 	'logout_time' => date("Y-m-d H:i:s")
		// );
		// //////////////////////////////////////////////////////////////////////////////////
		// //Record activity log
        // $role = $this->session->userdata('role');
        // $permission = $this->user_model->get_dsc_permission_and_screen_by_permission_name('logout',$role[0])->row();

        // $record['screen_name'] = $permission->screen;
        // $record['user_action'] = $permission->permission;
		// $record['table_name'] = "admin_user";
        // $record['user_role'] = $permission->user_group;
		// $after = $log_login_data;
		// $record['data_after'] = json_encode($after);
		// $record['user_id'] = $this->session->userdata('id');

		// $this->audit_log_model->insert($record);
		// //////////////////////////////////////////////////////////////////////////////////	

		$this->session->sess_destroy();
		redirect('login/index');
	}

	// ============================== AJAX ============================== //

	public function get_admin_user_role()
	{
		$user_roles = $this->user_groups_model->get_usergroup_by_user_id($this->session->userdata('id'))->result();
		$data['is_operation_manager'] = FALSE;
		$data['is_operation_officer'] = FALSE;
		foreach($user_roles as $role){
			if($role->code == 'operation_manager'){
				$data['is_operation_manager'] = TRUE;
			}
			if($role->code == 'operation_officer'){
				$data['is_operation_officer'] = TRUE;
			}
		}

		echo json_encode($data);
	}
}
