<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		
		// load model..
		$this->load->model('user_model');
		$this->load->model('user_groups_model');
		
		// construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('reports/index', NULL, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}
}
