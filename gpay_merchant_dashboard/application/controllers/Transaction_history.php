<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Transaction_history extends CI_Controller {

	const PAGINATION = 20;
	private $pagination_per_page = 20;
    var $action;
    var $message;

	public function __construct()
	{
		parent::__construct();

		// load library..
		
        $this->load->model('user_model');
		// load model..
		$this->load->model('merchant_dashboard_login_model');
		$this->load->model('transaction_history_model');
		$this->action = $this->session->userdata('action');
        $this->message = "<div class='alert alert-danger'>You don't have permission to access Transaction</div>";


        // construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		// Clear search session lama
		$this->clear_search_session();
		
		$search_param = array(
			'refnum'=>null,
			'amount_fr'=>null,
			'amount_to'=>null,
			'source_of_fund'=>null,
			'status' => array("All"),
			'tgl_awal' => date("Y-m-d")." 00:00:00",
			'tgl_akhir' =>date("Y-m-d")." 23:59:59",
			'store' => $this->getDefaultStoreByUser(),
			'sof' => null,
        );
        $this->session->set_userdata('filter_trans_hist', $search_param);

		
		$data['total_amounts'] = 0;
		
		$data['action'] = $this->action;
		$data['total_amounts'] = $this->transaction_history_model->getTotalAmount($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund']);
		$data['merchant'] =  $this->transaction_history_model->get_merchant_by_userdata($this->session->userdata('merchant_level'));
		$data['store'] =  $this->transaction_history_model->get_store_by_merchant_id($this->session->userdata('merchant_level'));
		$data['sbu'] =  $this->transaction_history_model->get_sbu_name();
		$data['merch_group'] =  $this->transaction_history_model->get_merchant_group();
		$data['sof'] =  $this->transaction_history_model->get_product_name_nns();
		
		//------------------------------ TEMPLATING ------------------------------
		$template['content'] = $this->load->view('transaction/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	public function getDefaultStoreByUser(){
		$store_id = array();
		foreach($this->session->userdata('store_level') as $key=>$value){
			array_push($store_id, $value->store_id);
		}
		return $store_id;
	}

	// ============================== SEARCH ============================== //

	private function clear_search_session()
	{
		$search_param = array(
			'merchantQrisCode' => null,
			'dateFilter' => null,
			'tgl_awal' => null,
			'tgl_akhir' =>null,
			'amount_fr'=>null,
			'amount_to'=>null,
			'all' => null,
			'gpay' => null,
			'shopeepay' => null,
			'ovo' => null,
			'linkaja' => null,
			'merchant' => null,
			'payment' => null,
			'store' => null,
			'source_of_fund' => null,
			'pymt_sts_all' => null,
			'pymt_sts_paid' => null,
			'pymt_sts_refund' => null,
			'pymt_sts_unpaid' => null,
			'pymt_sts_fail' => null,
			'refnum'=>null,
			'search'=>false,
			'sof' => null
        );
        $this->session->set_userdata('filter_trans_hist', $search_param);
	}

	public function submit_search()
    {
		// Clear search session lama
		$this->clear_search_session();
		$store = array();

		// Ambil data input dan set ke session sebagai parameter search
		$search_param = array(
			'merchantQrisCode' => $this->input->post('merchantQrisCode'),
			'dateFilter' => $this->input->post('dateFilter'),
			'tgl_awal' => $this->input->post('tgl_awal'),
			'tgl_akhir' => $this->input->post('tgl_akhir'),
			'amount_fr'=>$this->input->post('amount_fr'),
			'amount_to'=>$this->input->post('amount_to'),
			'refnum'=>$this->input->post('refnum'),
			'all' => $this->input->post('all'),
			'gpay' => $this->input->post('gpay'),
			'shopeepay' => $this->input->post('shopeepay'),
			'ovo' => $this->input->post('ovo'),
			'linkaja' => $this->input->post('linkaja'),
			'merchant' => $this->input->post('merchant_filter'),
			'status' => null,
			'store' => null,
			'source_of_fund' => $this->input->post('source_of_fund'),
			'pymt_sts_all' => $this->input->post('pymt_sts_all'),
			'pymt_sts_paid' => $this->input->post('pymt_sts_paid'),
			'pymt_sts_refund' => $this->input->post('pymt_sts_refund'),
			'pymt_sts_unpaid' => $this->input->post('pymt_sts_unpaid'),
			'pymt_sts_fail' => $this->input->post('pymt_sts_fail'),
			'search'=>true,
			'sof' => $this->input->post('sof')
		);
		if(empty($this->input->post('store_filter')))  $search_param['store'] = $this->getDefaultStoreByUser(); 
		else $search_param['store'] = $this->input->post('store_filter');

		// if(empty($this->input->post('status_filter')))  $search_param['status'] = array("All"); 
		// else $search_param['status'] = $this->input->post('status_filter');

		// jika status keduanya checked maka value'nya All, payment, refund
		$statusArr1 = array('payment', 'refund');
		$statusArr2 = array('refund', 'payment');
		$inputStatus = $this->input->post('status_filter');
		if ($inputStatus == $statusArr1 || $inputStatus == $statusArr2) {
			$search_param['status'] = array("All", "payment", "refund");
		} else {
			$search_param['status'] = $inputStatus;
		}

		// Default filter date
		// $secondNow = date('H:i:s');
		$dateStart = date('Y-m-d' . " 00:00:00");
		$dateEnd = date('Y-m-d' . " 23:59:59");
		if (empty($this->input->post('tgl_awal')))  $search_param['tgl_awal'] = $dateStart;
		else $search_param['tgl_awal'] = $this->input->post('tgl_awal');
		if (empty($this->input->post('tgl_akhir')))  $search_param['tgl_akhir'] = $dateEnd;
		else $search_param['tgl_akhir'] = $this->input->post('tgl_akhir');

		$data_store = array();
		$this->session->set_userdata('filter_trans_hist', $search_param);

		//////////////////////////////////////////////////////////////////////////////////
		
		redirect('transaction_history/search');
	
	}

	public function search()
	{
		$search_param = $this->session->userdata('filter_trans_hist');
		$issuerArray =  array($search_param['gpay'],$search_param['shopeepay'] ,$search_param['ovo'] ,$search_param['linkaja']  );
		$issuer = "";
		if (count($issuerArray) == 4){
			$issuer = 'All';
		}
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

		$data['total_amounts'] = 0;
		$data['trans_hist'] = $this->transaction_history_model->get_data_filtered_by_param_v2($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'],0 );
		$data['total_trans'] = $this->transaction_history_model->get_total_transaction_filtered($this->session->userdata('merchant_qris_code'), $issuer, $search_param['dateFilter']);
		$data['merchant'] =  $this->transaction_history_model->get_merchant_by_userdata($this->session->userdata('merchant_level'));
		$data['store'] =  $this->transaction_history_model->get_store_by_merchant_id($this->session->userdata('merchant_level'));
		$data['sbu'] =  $this->transaction_history_model->get_sbu_name();
		$data['merch_group'] =  $this->transaction_history_model->get_merchant_group();
		$data['sof'] =  $this->transaction_history_model->get_product_name_nns();
		
		$data['total_amounts'] = $this->transaction_history_model->getTotalAmount($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund']);
		
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('transaction/index', $data, true);

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
		$config['base_url'] = site_url('transaction_history/index');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->pagination_per_page;
		$config['uri_segment'] = 3;
		return $config;
	}

	private function search_pagination_config($total_rows, $per_page)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('transaction_history/search');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = ($per_page > 0 ? $per_page : $this->pagination_per_page);
		$config['uri_segment'] = 3;
		return $config;
	}

	public function export(){
        
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
			->setCellValue('A1','Trx Type')
			->setCellValue('B1','Trx Role')
			->setCellValue('C1','Source of Fund')
			->setCellValue('D1','Timestamp')
			->setCellValue('E1','Ref Num')
			->setCellValue('F1','Store Code')
			->setCellValue('G1','Store')
			->setCellValue('H1','Terminal ID')
			->setCellValue('I1','Amount')
			->setCellValue('J1','MDR Fee')
			->setCellValue('K1','Fee')
			->setCellValue('L1','Amount Settle')
			->setCellValue('M1','Status');
		;

		// Miscellaneous glyphs, UTF-8
		$i=2;
		$data=  $this->transaction_history_model->get_data_filtered_by_param_v2($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'],0 );
		
		foreach($data as $row) {
			$spreadsheet->setActiveSheetIndex(0)
				->setCellValue('A'.$i, $row['trans_type'])
				->setCellValue('B'.$i, $row['trx_role'])
				->setCellValue('C'.$i,$row['source_of_fund'])
				->setCellValue('D'.$i, date_format(date_create($row['trans_time']), 'd/m/Y H:i:s'))
				->setCellValue('E'.$i, $row['rrn'])
				->setCellValue('F'.$i, $row['store_code'])
				->setCellValue('G'.$i, $row['store_name'])
				->setCellValue('H'.$i, $row['terminal_qris_code'])
				->setCellValue('I'.$i, $row['amount'])
				->setCellValue('J'.$i, $row['fee'])
				->setCellValue('K'.$i, $row['mdr'])
				->setCellValue('L'.$i, $row['amount_settle'])
				->setCellValue('M'.$i, $row['status']);
			$i++;
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

	public function transList(){
    	$search_param = $this->session->userdata('filter_trans_hist');
		// POST data
		$postData = $this->input->post();
   		// Get data
		$data = $this->transaction_history_model->getTransHist($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'] ,$postData, $search_param['sof']);
   
		echo json_encode($data);
	 }
}
