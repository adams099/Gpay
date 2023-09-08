<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		
		// load model..
		$this->load->model('user_model');
		$this->load->model('user_groups_model');
		$this->load->model('merchant_model');
		$this->load->model('merchant_dashboard_login_model');
		
		// construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		$data['merchant'] = $this->merchant_model->get_by_merchant_qris_code($this->session->userdata('merchant_qris_code'))->row();

		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('account/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	private function clear_param()
	{
		$param = array(
			'old_password' => null,
			'new_password' => null,
			'confirm_password' => null
        );
        $this->session->set_userdata('change_pass_param', $param);
	}

	public function submit_change()
	{
		// Clear search session lama
		//$this->clear_param();

		// Ambil data input dan set ke session sebagai parameter search
		$param = array(
			'old_password' => $this->input->post('old_password'),
			'new_password' => $this->input->post('new_password'),
			'confirm_password' => $this->input->post('confirm_password'),
			'search'=>true
		);
		$this->session->set_userdata('change_pass_param', $param);
		
		redirect('account/change');
	}
	public function change()
	{
		$param = $this->session->userdata('change_pass_param');
		$username = $this->session->userdata('username');

		//validasi old password
		$merchant_data = $this->merchant_dashboard_login_model->get_merchant_login_data($username);
		$old_pass_hash = $this->global_library->custom_password_hash($param['old_password']);

		if ($row = $merchant_data->row()) {
			$merchant_dashboard_id = $row->id;
		}
		if(isset($merchant_data)){
			$merchant_row = $merchant_data->row();
			if(hash_equals($old_pass_hash, $merchant_row->password_hash)){
				//validasi confirm password
				if($param['new_password']==$param['confirm_password']){
				$password = $this->global_library->custom_password_hash($param['new_password']);
				//update password
				$update_data = array(
                    'password_hash' => $password,
                );
                $this->merchant_dashboard_login_model->update_merchant_dashboard_login($update_data, $merchant_dashboard_id);
				$this->session->sess_destroy();
				redirect('login/index');
				}else {
					$message = "<div class='alert alert-danger'>Old password not match with our system</div>";
 					$this->session->set_flashdata('message', $message);
 					redirect('account/index');
				}
			} else {
				$message = "<div class='alert alert-danger'>New password not match with confrim password</div>";
				$this->session->set_flashdata('message', $message);
				redirect('account/index');
			}
		}

		
	}
}
