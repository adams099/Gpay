<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		
		// load model..
		$this->load->model('user_model');
		$this->load->model('user_groups_model');
		$this->load->model('dashboard_model');
		
		// construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		// Clear search session lama
		$this->clear_search_session();
		
		$data['merch_summ'] = $this->dashboard_model->get_merchant_summary_data('All',$this->session->userdata('merchant_qris_code'), 'today');
		$data['issuer_summ'] = $this->dashboard_model->get_issuer_summary_data('All', $this->session->userdata('merchant_qris_code'), 'today');
		$data['sales_summ'] = $this->dashboard_model->get_sales_summary_data('All', $this->session->userdata('merchant_qris_code'), 'today');
		$data['sales_summ_ystrdy'] = $this->dashboard_model->get_sales_summary_last('All', $this->session->userdata('merchant_qris_code'), 'today');
		$data['time']="Today";
		$data['format'] = "HH:mm";
		$data['last_time'] = "Yesterday";
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('dashboard/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	private function clear_search_session()
    {
        $search_param = array(
			'merchantQrisCode' => null,
			'dateFilter' => null,
			'all' => null,
			'gpay' => null,
			'shopeepay' => null,
			'ovo' => null,
			'linkaja' => null
        );
        $this->session->set_userdata('filter_dashboard', $search_param);
	}
	
	public function submit_search()
    {
		// Clear search session lama
		$this->clear_search_session();

		// Ambil data input dan set ke session sebagai parameter search
		$search_param = array(
			'merchantQrisCode' => $this->input->post('merchantQrisCode'),
			'dateFilter' => $this->input->post('dateFilter'),
			'all' => $this->input->post('all'),
			'gpay' => $this->input->post('gpay'),
			'shopeepay' => $this->input->post('shopeepay'),
			'ovo' => $this->input->post('ovo'),
			'linkaja' => $this->input->post('linkaja')
		);
		$this->session->set_userdata('filter_dashboard', $search_param);

		//////////////////////////////////////////////////////////////////////////////////
		
		redirect('dashboard/filter');
	
	}
	
	public function filter()
	{
		$search_param = $this->session->userdata('filter_dashboard');
		$issuerArray =  array($search_param['gpay'],$search_param['shopeepay'] ,$search_param['ovo'] ,$search_param['linkaja']);
		$issuer = "";
		if ($search_param['all'] == "All"){
			$data['sales_summ_ystrdy'] = $this->dashboard_model->get_sales_summary_last($search_param['all'], $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['merch_summ'] = $this->dashboard_model->get_merchant_summary_data($search_param['all'], $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['issuer_summ'] = $this->dashboard_model->get_issuer_summary_data($search_param['all'], $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['sales_summ'] = $this->dashboard_model->get_sales_summary_data($search_param['all'], $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			
		} else {
			foreach ($issuerArray as $issue){
				
				if ($issue == "GPay"){
					$issuer = "'GPayApp',";
					
				} else if ($issue == "ShopeePay"){
					$issuer .= " 'ShopeePayIncoming',";
					
				} else if ($issue == "Ovo"){
					$issuer .= " 'OvoIncoming',";
					
				} else if ($issue == "LinkAja"){
					$issuer .= " 'LinkAjaIncoming',";
					
				}
			}

			$data['merch_summ'] = $this->dashboard_model->get_merchant_summary_data($issuer, $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['issuer_summ'] = $this->dashboard_model->get_issuer_summary_data($issuer, $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['sales_summ'] = $this->dashboard_model->get_sales_summary_data($issuer, $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$data['sales_summ_ystrdy'] = $this->dashboard_model->get_sales_summary_last($issuer, $this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			
		}
		if ($search_param['dateFilter'] == "today"){
			$data['time'] = "Today";
			$data['last_time'] = "Yesterday";
			$data['format'] = "HH:mm";
		} else if ($search_param['dateFilter'] == "weekly") {
			$data['time'] = "This Week";
			$data['last_time'] = "Last Week";
			$data['format'] = "DD-MM-YY";
		} else if ($search_param['dateFilter'] == "monthly"){
			$data['time'] = "This Month";
			$data['last_time'] = "Last Month";
			$data['format'] = "DD-MM-YY";
		}
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('dashboard/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}
}
