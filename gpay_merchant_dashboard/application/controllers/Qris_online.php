<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Qris_online extends CI_Controller {

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
		$this->load->model('qris_model');
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
		
		// var_dump($this->session->userdata('store_level'));
		// exit();

		$search_param = array(
			'refnum'=>null,
			'tranno'=>null,
			'amount_fr'=>null,
			'amount_to'=>null,
			'source_of_fund'=>null,
			'status' => array("All"),
			'tgl_awal' => date("Y-m-d")." 00:00:00",
			'tgl_akhir' =>date("Y-m-d")." 23:59:59",
			'store' => $this->getDefaultStoreByUser()
        );
        $this->session->set_userdata('filter_trans_hist', $search_param);

		// Total rows
		// $data['total_data'] = $this->transaction_history_model->get_total_row_trans_hist_filtered($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status']);
		// $data['total_rows'] = $data['total_data']["num_row"];
		$data['total_amounts'] = 0;
		
		// Paging
		// $config = $this->pagination_config($data['total_rows']);
		// $this->pagination->initialize($config);

		
		// Offset
		// $page_number = $this->uri->segment(3);
		// $offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		
		// Data
		// $data['page_number'] = $page_number > 0 ? $page_number : 1;
		// var_dump($this->session->userdata('merchant_level'));
		// exit();
		$data['action'] = $this->action;
		$data['trans_hist'] = $this->qris_model->get_data_filtered_by_param($this->session->userdata('qris_online_merchant_level'), $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'], $search_param['tranno']);
		
		// $data['total_amount'] = $this->transaction_history_model->get_total_amount($this->session->userdata('merchant_qris_code'),$issuer);
		$data['merchant'] =  $this->qris_model->get_merchant_by_userdata($this->session->userdata('qris_online_merchant_level'));
		$data['store'] =  $this->qris_model->get_store_by_merchant_id($this->session->userdata('qris_online_merchant_level'));
		$data['total_amounts'] = $this->getTotalAmounts($data['trans_hist']);
		
		// var_dump($data['total_data']);
		// exit();
        //------------------------------ TEMPLATING ------------------------------
		$template['content'] = $this->load->view('qris_online/index', $data, true);

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
			'tranno'=>null,
			'search'=>false
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
			'tranno'=>$this->input->post('tranno'),
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
			'search'=>true
		);
		if(empty($this->input->post('store_filter')))  $search_param['store'] = $this->getDefaultStoreByUser(); 
		else $search_param['store'] = $this->input->post('store_filter');

		if(empty($this->input->post('status_filter')))  $search_param['status'] = array("All"); 
		else $search_param['status'] = $this->input->post('status_filter');

		$data_store = array();
		$this->session->set_userdata('filter_trans_hist', $search_param);

		//////////////////////////////////////////////////////////////////////////////////
		
		redirect('qris_online/search');
	
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

		
		// Total rows
		// $data['total_data'] = $this->transaction_history_model->get_total_row_trans_hist_filtered($search_param['store'], $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status']);
		// $data['total_rows'] = $data['total_data']["num_row"];
		$data['total_amounts'] = 0;

		
		// Paging
		// $config = $this->search_pagination_config($data['total_rows'], self::PAGINATION);
		// $this->pagination->initialize($config);

		
		// Offset
		// $page_number = $this->uri->segment(3);
		// $offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		// echo ($offset);
		// exit();
		
		$data['trans_hist'] = $this->qris_model->get_data_filtered_by_param($this->session->userdata('qris_online_merchant_level'), $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'], $search_param['tranno'] );
		// $data['total_amount'] = $this->transaction_history_model->get_total_amount($this->session->userdata('merchant_qris_code'),$issuer);
		$data['total_trans'] = $this->qris_model->get_total_transaction_filtered($this->session->userdata('merchant_qris_code'), $issuer, $search_param['dateFilter']);
		$data['merchant'] =  $this->qris_model->get_merchant_by_userdata($this->session->userdata('qris_online_merchant_level'));
		$data['store'] =  $this->qris_model->get_store_by_merchant_id($this->session->userdata('qris_online_merchant_level'));
		
		$data['total_amounts'] = $this->getTotalAmounts($data['trans_hist']);
		// $data['store'] =  $this->transaction_history_model->get_store_by_merchant_id($this->session->userdata('merchant_qris_code'));
		
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('qris_online/index', $data, true);

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	public function getTotalAmounts($data){
		if(count($data) > 0){
			$amount = 0;
			foreach($data as $key=>$value){
				$amount += $value["RAW_AMOUNT"];
			}
			return $amount;
		}
		return 0;
	}
	

	// ============================== PAGINATION ============================== //

	private function pagination_config($total_rows)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('qris_online/index');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->pagination_per_page;
		$config['uri_segment'] = 3;
		return $config;
	}

	private function search_pagination_config($total_rows, $per_page)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('qris_online/search');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = ($per_page > 0 ? $per_page : $this->pagination_per_page);
		$config['uri_segment'] = 3;
		return $config;
	}

	public function export(){
        
		$search_param = $this->session->userdata('filter_trans_hist');
		$filename = 'Transaction-History-QRIS-Online-'.date("Ymd_His");

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
			->setCellValue('E1','Tran No')
			->setCellValue('F1','Ref Num')
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
		$data=  $this->qris_model->get_data_filtered_by_param($this->session->userdata('qris_online_merchant_level'), $search_param['tgl_awal'], $search_param['tgl_akhir'], $search_param['amount_fr'], $search_param['amount_to'], $search_param['refnum'], $search_param['status'], $search_param['source_of_fund'], $search_param['tranno'] );

		foreach($data as $row) {
			$spreadsheet->setActiveSheetIndex(0)
				->setCellValue('A'.$i, $row['TRX_TYPE'])
				->setCellValue('B'.$i, $row['TRX_ROLE'])
				->setCellValue('C'.$i,$row['SOURCE_OF_FUND'])
				->setCellValue('D'.$i, date_format(date_create($row['TIME_REQ']), 'd/m/Y H:i:s'))
				->setCellValueExplicit('E'.$i, $row['TRAN_NO'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
				->setCellValueExplicit('F'.$i, $row['REFF_NO'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
				->setCellValue('G'.$i, $row['STORE_NAME'])
				->setCellValue('H'.$i, $row['TERMINAL_ID'])
				->setCellValue('I'.$i, $row['AMOUNT'])
				->setCellValue('J'.$i, $row['MDR_FEE'])
				->setCellValue('K'.$i, $row['FEE'])
				->setCellValue('L'.$i, $row['AMOUNT_SETTLE'])
				->setCellValue('M'.$i, $row['status']);
			$i++;
		}

		// Rename worksheet
		$spreadsheet->getActiveSheet()->setTitle('Transaction');
		// $spreadsheet->getActiveSheet()->getStyle('A1:A1000')->getNumberFormat()
    	// ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
		// $spreadsheet->getActiveSheet()->getStyle('B1:B1000')->getNumberFormat()
    	// ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
		

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
