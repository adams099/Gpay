<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Hello extends CI_Controller {
	public function __construct()
	{
		parent::__construct();

		// load library..
		
        // load model..
        $this->load->model('hello_model');
	}

	//fungsi interface untuk URL index
	public function index()
	{
        //------------------------------ TEMPLATING ------------------------------
        $data['first_name'] = 'Tracy';
        $data['last_name'] = 'Bingham';
        $data['master_std'] = $this->hello_model->get_master_std('user_groups','code')->result();
		$this->load->view('hello', $data);
	}

	public function excel_test()
	{
		$template_filename = 'd:/Workspace/Projects/gpay-c-webadmin/file/report/template.xlsx';
		$input_file = IOFactory::identify($template_filename);
		$reader = IOFactory::createReader($input_file);
		$spsh = $reader->load($template_filename);

		$sheet = $spsh->getActiveSheet();

		// $styleBorder = [
		// 	'borders' => [
		// 		'outline' => [
		// 			'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
		// 			'color' => ['argb' => '000000'],
		// 		],
		// 	],
		// ];
		// $sheet->insertNewRowBefore('3', 3);
		// $sheet->setCellValue('B3', 'Hi Joe');
		// $sheet->getStyle('B3:C5')->applyFromArray($styleBorder);

		$rs = $this->hello_model->get_master_std('user_groups','code')->result();
		$this->spsh_library->writeResultsetToSheet($rs, $sheet, 1, 4);

		$writer = IOFactory::createWriter($spsh, 'Xlsx');
		$writer->save('d:/Workspace/Projects/gpay-c-webadmin/file/report/test.xlsx');

		echo 'File created.&nbsp;<a href="http://localhost:93/file/report/test.xlsx">view</a>';
	}

	public function sms_test()
	{
		$this->global_library->sendSMS('8176850060', 'test 123 from webadmin');
	}
}