<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Mandiri_va_transaction_history extends CI_Controller {

	var $pagination_per_page = 25;
    var $action;
    var $message;

	public function __construct()
	{
		parent::__construct();

		// load library..
		
		// load model..
		$this->load->model('merchant_dashboard_login_model');
		$this->load->model('mandiri_va_transaction_history_model');
		$this->action = $this->session->userdata('action');
        $this->message = "<div class='alert alert-danger'>You don't have permission to access Transaction</div>";


        // construct script..
		if($this->session->userdata('logged_in') == false){
			redirect('login/index');
		}
	}

	public function index()
	{
		$merchant_dashboard_id = $this->session->userdata('id');
		$merchant_id = $this->session->userdata('merchant_qris_code'); 
		$receiving_inst_id = '88367';
		// Clear search session lama
		$this->clear_search_session();
		
		// Total rows
		$data['total_rows'] = $this->mandiri_va_transaction_history_model->get_total_row_trans_hist($merchant_id, $receiving_inst_id, $merchant_dashboard_id);

		// Paging
		$config = $this->pagination_config($data['total_rows']);
		$this->pagination->initialize($config);

		// Offset
		$page_number = $this->uri->segment(3);
		$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		// $showing_data_count = $offset+$this->pagination_per_page > $data['total_rows'] ? $data['total_rows']: ($offset+$this->pagination_per_page);

		$data_count_begin = ($page_number <= 0) ? 1 : ($this->pagination_per_page * ($page_number-1)) + 1;
		if($page_number == 0 && $this->pagination_per_page > $data['total_rows']){
			$data_count_last = $data['total_rows'];
		}elseif($page_number == 0){
			$data_count_last = ($this->pagination_per_page * ($page_number+1));
		}elseif(($this->pagination_per_page * $page_number) > $data['total_rows']){
			$data_count_last = $data['total_rows'];
		}else{
			$data_count_last = ($this->pagination_per_page * $page_number);
		}
		
		// Data
		$data['page_number'] = $page_number > 0 ? $page_number : 1;
		$data['action'] = $this->action;
		$data['trans_hist'] =  $this->mandiri_va_transaction_history_model->get_va_history($merchant_id, $receiving_inst_id, $config['per_page'], $offset, $merchant_dashboard_id);
		$data['store'] =  $this->mandiri_va_transaction_history_model->get_store_by_merchant_id($merchant_id);
		// $data['offset'] = "Showing ".strval($showing_data_count)." Of ".strval($data['total_rows']);
		$data['offset'] = "Showing ".strval($data_count_begin)." to ".strval($data_count_last)." out of ".$data['total_rows'];
		
        //------------------------------ TEMPLATING ------------------------------
		$template['content'] = $this->load->view('mandiri_va_transaction/index', $data, true);
		// $template['content'] = $this->load->view('undermaintenance');

		$css_plugin = array();
		$template['css_plugin'] = $css_plugin;

		$js_plugin = array();
		$template['js_plugin'] = $js_plugin;

		$this->load->view('template', $template);
	}

	public function detail(){
		return $this->mandiri_va_transaction_history_model->get_detil_va_history("3978900000000003");
		
	}
	// ============================== SEARCH ============================== //

	private function clear_search_session()
	{
		$search_param = array(
			'tgl_awal' => date("Y-m-d 00:00:00.000"),
			'tgl_akhir' => date("Y-m-d 23:59:59.000"),
			'customer_name' => null,
			'mobile_phone_no' => null,
			'customer_email' => null,
			'va_number' => null,
			'payment_all' => null,
			'payment_paid' => null,
			'payment_unpaid' => null,
			'amount_sort' => null,
			'timestamp_sort' => 'desc',
			'va_all' => null,
			'va_mandiri' => null,
			'va_bni' => null,
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
			'tgl_awal' => $this->input->post('tgl_awal'),
			'tgl_akhir' => $this->input->post('tgl_akhir'),
			'customer_name' => $this->input->post('customer_name'),
			'mobile_phone_no' => $this->input->post('mobile_phone_no'),
			'customer_email' => $this->input->post('customer_email'),
			'va_number' => $this->input->post('va_number'),
			'payment_all' => $this->input->post('payment_all'),
			'payment_paid' => $this->input->post('payment_paid'),
			'payment_unpaid' => $this->input->post('payment_unpaid'),
			'amount_sort' => $this->input->post('amount_sort'),
			'timestamp_sort' => $this->input->post('timestamp_sort'),
			'va_all' => $this->input->post('va_all'),
			'va_bni' => $this->input->post('va_bni'),
			'va_mandiri' => $this->input->post('va_mandiri'),
			'search'=>true
		);
		$this->session->set_userdata('filter_trans_hist', $search_param);
		
		redirect('mandiri_va_transaction_history/search');
	
	}

	public function search()
	{
		$search_param = $this->session->userdata('filter_trans_hist');
		$merchant_id = $this->session->userdata('merchant_qris_code');
		$receiving_inst_id = '88367';
		$merchant_dashboard_id = $this->session->userdata('id');
		
		// Total rows
		$data['total_rows'] = $this->mandiri_va_transaction_history_model->get_total_row_trans_hist_filtered($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id);

		
		// Paging
		$config = $this->search_pagination_config($data['total_rows'],$this->pagination_per_page);
		$this->pagination->initialize($config);

		
		// Offset
		$page_number = $this->uri->segment(3);
		$offset = $page_number > 0 ? ($page_number * $config['per_page']) - $config['per_page'] : 0;
		// $showing_data_count = $offset+$this->pagination_per_page > $data['total_rows'] ? $data['total_rows']: ($offset+$this->pagination_per_page);
		$data_count_begin = ($page_number <= 0) ? 1 : ($this->pagination_per_page * ($page_number-1)) + 1;
		if($page_number == 0 && $this->pagination_per_page > $data['total_rows']){
			$data_count_last = $data['total_rows'];
		}elseif($page_number == 0){
			$data_count_last = ($this->pagination_per_page * ($page_number+1));
		}elseif(($this->pagination_per_page * $page_number) > $data['total_rows']){
			$data_count_last = $data['total_rows'];
		}else{
			$data_count_last = ($this->pagination_per_page * $page_number);
		}
		
		
		$data['trans_hist'] =  $this->mandiri_va_transaction_history_model->get_va_history_filtered($search_param, $merchant_id, $receiving_inst_id, $config['per_page'], $offset, $merchant_dashboard_id );
		$data['store'] =  $this->mandiri_va_transaction_history_model->get_store_by_merchant_id($merchant_id);
		// $data['offset'] = "Showing ".strval($showing_data_count)." Of ".strval($data['total_rows']);
		$data['offset'] = "Showing ".strval($data_count_begin)." to ".strval($data_count_last)." out of ".$data['total_rows'];
		
		// ------------------------------ TEMPLATING ------------------------------ //
		$template['content'] = $this->load->view('mandiri_va_transaction/index', $data, true);

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
		$config['base_url'] = site_url('mandiri_va_transaction_history/index');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->pagination_per_page;
		$config['uri_segment'] = 3;
		return $config;
	}

	private function search_pagination_config($total_rows, $per_page)
	{
		$config = $this->global_library->global_pagination_config();
		$config['base_url'] = site_url('mandiri_va_transaction_history/search');
		$config['total_rows'] = $total_rows;
		$config['per_page'] = ($per_page > 0 ? $per_page : $this->pagination_per_page);
		$config['uri_segment'] = 3;
		return $config;
	}

	public function export(){
       
		$search_param = $this->session->userdata('filter_trans_hist');
		$filename = 'Virtual Account Transaction-History-'.date("Ymd_His");
		$merchant_id = $this->session->userdata('merchant_qris_code');
		$receiving_inst_id = '88367';
		$merchant_dashboard_id = $this->session->userdata('id');

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
			->setCellValue('A1','Invoice')
			->setCellValue('B1','Mobile Phone No')
			->setCellValue('C1','VA Number')
			->setCellValue('D1','Customer Name')
			->setCellValue('E1','Customer Email')
			->setCellValue('F1','Timestamp')
			->setCellValue('G1','Amount')
			->setCellValue('H1','Payment Channel')
			->setCellValue('I1','Bank Issuer')
			->setCellValue('J1','Status');
		;

		// Miscellaneous glyphs, UTF-8
		$i=2;
		$data=  $this->mandiri_va_transaction_history_model->get_va_history_export($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id);

		foreach($data as $row) {

			$spreadsheet->setActiveSheetIndex(0)
				->setCellValueExplicit('A'.$i, $row['order_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
				->setCellValue('B'.$i, $row['cust_phone'])
				->setCellValueExplicit('C'.$i,$row['va_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING)
				->setCellValue('D'.$i, $row['customer_name'])
				->setCellValue('E'.$i, $row['cust_email'])
				->setCellValue('F'.$i, date_format(date_create($row['time_trx']), 'd/m/Y H:i:s'))
				->setCellValue('G'.$i, str_replace(',','',$row['amount']))
				->setCellValue('H'.$i, $row['bca_payment_channel'])
				->setCellValue('I'.$i, $row['bank_issuer'])
				->setCellValue('J'.$i, $row['response_message']);
			$i++;
		}

		// Rename worksheet
		$spreadsheet->getActiveSheet()->setTitle('Transaction');
		// $spreadsheet->getActiveSheet()->getStyle('A1:A1000')->getNumberFormat()
		// ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
		$spreadsheet->getActiveSheet()->getStyle('B1:B1000')->getNumberFormat()
    	->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
		

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


	public function export_csv(){
    
	
		// $data=  $this->mandiri_va_transaction_history_model->get_va_history();

		$search_param = $this->session->userdata('filter_trans_hist');
		$merchant_id = $this->session->userdata('merchant_qris_code');
		$receiving_inst_id = '88367';
		$merchant_dashboard_id = $this->session->userdata('id');
		$data=  $this->mandiri_va_transaction_history_model->get_va_history_export($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id);
		$delimiter = ",";
		// $filename = "Transaction-History-VA-" . date('Y-m-d') . ".csv";
		$filename = 'Mandiri Virtual Account Transaction-History-'.date("Ymd_His").".csv";


		//create a file pointer
		$f = fopen('php://memory', 'w');

		//set column headers
		$fields = array('Invoice', 'Mobile Phone No', 'VA Number', 'Customer_Name', 'Customer_Email', 'Timestamp', 'Amount', 'Payment_Channel', 'Bank Issuer', 'Status');
		fputcsv($f, $fields, $delimiter);
		
		//output each row of the data, format line as csv and write to file pointer
		foreach($data as $row) {
			$lineData = array($row['order_number'], $row['cust_phone'], $row['va_number'], $row['customer_name'], $row['cust_email'], date_format(date_create($row['time_trx']), 'd/m/Y H:i:s'), str_replace(',','',$row['amount']), $row['bca_payment_channel'],$row['bank_issuer'], $row['response_message']);
			fputcsv($f, $lineData, $delimiter);
		}
	
		//move back to beginning of file
		fseek($f, 0);
		
		//set headers to download file rather than displayed
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '";');
		
		//output all remaining data on a file pointer
		fpassthru($f);
		
		exit;
	}

	
	
}
