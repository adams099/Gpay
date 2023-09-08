<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Ppob_transaction_history extends CI_Controller {

	var $pagination_per_page = 10;
    var $action;
    var $message;

	public function __construct()
	{
		parent::__construct();

		// load library..
		
		// load model..
		$this->load->model('merchant_dashboard_model');
		$this->load->model('ppob_transaction_history_model');
		$this->action = $this->session->userdata('action');
        $this->message = "<div class='alert alert-danger'>You don't have permission to access Transaction</div>";


        // construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}

        // if(!array_key_exists('transaction',$this->action) || !in_array('transaction_search',$this->action['transaction'])){
        //     $this->session->set_flashdata('message', $this->message);
        //     redirect('home/index');
        // }
	}

	public function index()
	{
		// Clear search session lama
		$this->clear_search_session();
		
		// Total rows
		$data['total_rows'] = $this->ppob_transaction_history_model->get_total_row_trans_hist($this->session->userdata('merchant_qris_code'));

		
		// Paging
		$config = $this->pagination_config($data['total_rows']);
		$this->pagination->initialize($config);

		
		// Offset
		$page_number = $this->uri->segment(3);
		$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		
		// Data
		$data['page_number'] = $page_number > 0 ? $page_number : 1;
		$data['action'] = $this->action;
		$data['total_amount'] = $this->ppob_transaction_history_model->get_total_amount($this->session->userdata('merchant_qris_code'),'All');
		$data['total_trans'] = $this->ppob_transaction_history_model->get_total_transaction($this->session->userdata('merchant_qris_code'), 'All', 'today');
		$data['trans_hist'] =  $this->ppob_transaction_history_model->get_trans_hist($this->session->userdata('merchant_qris_code'), $config['per_page'], $offset);
		$data['store'] =  $this->ppob_transaction_history_model->get_store_by_merchant_id($this->session->userdata('merchant_qris_code'));

	
        //------------------------------ TEMPLATING ------------------------------
		$template['content'] = $this->load->view('ppob_transaction/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	// ============================== SEARCH ============================== //

	private function clear_search_session()
	{
		$search_param = array(
			'merchantQrisCode' => null,
			'dateFilter' => null,
			'all' => null,
			'gpay' => null,
			'shopeepay' => null,
			'ovo' => null,
			'linkaja' => null,
			'store' => null,
			'pymt_sts_all' => null,
			'pymt_sts_paid' => null,
			'pymt_sts_refund' => null,
			'pymt_sts_unpaid' => null,
			'pymt_sts_fail' => null,
			'search'=>false
        );
        $this->session->set_userdata('filter_trans_hist', $search_param);
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
			'linkaja' => $this->input->post('linkaja'),
			'store' => $this->input->post('store'),
			'pymt_sts_all' => $this->input->post('pymt_sts_all'),
			'pymt_sts_paid' => $this->input->post('pymt_sts_paid'),
			'pymt_sts_refund' => $this->input->post('pymt_sts_refund'),
			'pymt_sts_unpaid' => $this->input->post('pymt_sts_unpaid'),
			'pymt_sts_fail' => $this->input->post('pymt_sts_fail'),
			'search'=>true
		);
		$this->session->set_userdata('filter_trans_hist', $search_param);

		//////////////////////////////////////////////////////////////////////////////////
		
		redirect('ppob_transaction_history/search');
	
	}

	public function search()
	{
		$search_param = $this->session->userdata('filter_trans_hist');
		
		// Total rows
		$data['total_rows'] = $this->ppob_transaction_history_model->get_total_row_trans_hist_filtered($this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);

		
		// Paging
		$config = $this->search_pagination_config($data['total_rows'],10);
		$this->pagination->initialize($config);

		
		// Offset
		$page_number = $this->uri->segment(3);
		$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		
		
		$data['trans_hist'] = $this->ppob_transaction_history_model->get_data_filtered_by_param($this->session->userdata('merchant_qris_code'), $search_param['dateFilter'], $config['per_page'], $offset );
		$data['total_amount'] = $this->ppob_transaction_history_model->get_total_amount($this->session->userdata('merchant_qris_code'));
		$data['total_trans'] = $this->ppob_transaction_history_model->get_total_transaction_filtered($this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
		$data['store'] =  $this->ppob_transaction_history_model->get_store_by_merchant_id($this->session->userdata('merchant_qris_code'));
		
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('ppob_transaction/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}
	

	// ============================== PAGINATION ============================== //

	private function pagination_config($total_rows)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('ppob_transaction_history/index');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->pagination_per_page;
		$config['uri_segment'] = 3;
		return $config;
	}

	private function search_pagination_config($total_rows, $per_page)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('ppob_transaction_history/search');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = ($per_page > 0 ? $per_page : $this->pagination_per_page);
		$config['uri_segment'] = 3;
		return $config;
	}

	public function export(){
        // if(!in_array('transaction_export',$this->action['transaction'])){
        //     $this->session->set_flashdata('message', $this->message);
        //     redirect('home/index');
        // }
		$search_param = $this->session->userdata('filter_trans_hist');
		$filename = 'Transaction-History-'.date("Ymd_His");

		$spreadsheet = new Spreadsheet();
		// add properties
		$spreadsheet
			->getProperties()
			->setCreator('Gpay')
			->setLastModifiedBy('Gpay')
			->setTitle('Transaction '.date("Ymd_His"))
			->setSubject('Transaction')
			->setDescription('Gpay Transaction '.date("Ymd_His").' created by system');

		// Add header
		$spreadsheet->setActiveSheetIndex(0)
			->setCellValue('A1','No.')
			->setCellValue('B1','Timestamp')
			->setCellValue('C1','Issuer')
			->setCellValue('D1','Terminal ID')
			->setCellValue('E1','Store')
			->setCellValue('F1','Amount')
			->setCellValue('G1','Ref Num')
			->setCellValue('H1','Ext Ref Num')
			->setCellValue('I1','Status');
		;

		// Miscellaneous glyphs, UTF-8
		$i=2;
		$no = 1;
		if($search_param['search'] == true){
			$total_rows = $this->ppob_transaction_history_model->get_total_row_trans_hist_filtered($this->session->userdata('merchant_qris_code'), $search_param['dateFilter']);
			$config = $this->search_pagination_config($total_rows,10);
			$page_number = $this->uri->segment(3);
			$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
			$data = $this->ppob_transaction_history_model->get_data_filtered_by_param($this->session->userdata('merchant_qris_code'), $search_param['dateFilter'], $config['per_page'], $offset);
		}else{
			$total_rows = $this->ppob_transaction_history_model->get_total_row_trans_hist($this->session->userdata('merchant_qris_code'));
			$config = $this->pagination_config($total_rows);
			$page_number = $this->uri->segment(3);
			$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
			$data =  $this->ppob_transaction_history_model->get_trans_hist($this->session->userdata('merchant_qris_code'), $config['per_page'], $offset);
		}
		

		foreach($data as $row) {
			$spreadsheet->setActiveSheetIndex(0)
				->setCellValue('A'.$i, $no)
				->setCellValue('B'.$i, date_format(date_create($row['trx_time']), 'd/m/Y H:i:s'))
				->setCellValue('C'.$i, $row['source_of_fund'])
				->setCellValue('D'.$i, $row['term_id'])
				->setCellValue('E'.$i, $row['store_name'])
				->setCellValue('F'.$i, $row['amount'])
				->setCellValue('G'.$i, $row['edc_refnum'])
				->setCellValue('H'.$i, $row['issuer_refnum'])
				->setCellValue('I'.$i, $row['status']);
			$i++;
			$no++;
		}

		// Rename worksheet
		$spreadsheet->getActiveSheet()->setTitle('Transaction');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$spreadsheet->setActiveSheetIndex(0);

		// Redirect output to a clientâ€™s web browser (Xlsx)
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;filename=".$filename.".xlsx");
		header('Cache-Control: max-age=0');

		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0

		$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
		$writer->save('php://output');
	}

}
