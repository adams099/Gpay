<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction_model extends CI_Model {

	function get_transaction_hist_octo()
	{
	//load CI instance
	$CI = & get_instance();
	$pdo = $CI->global_library->open_pdo_office();

	$sql = "SELECT TOP 100 source_of_fund, trx_time, trx_type, amount, term_id, merchant_id, store_name, edc_refnum, issuer_refnum, date_settle, flag 
			FROM (SELECT CASE
					WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
					WHEN source_node = 'OvoIncoming' THEN 'OVO'
					WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
					ELSE 'GPay'
					END AS source_of_fund, CONVERT(CHAR(23),time_req,121) AS trx_time, 
					CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					ELSE 'refund'
					END AS trx_type, otrx.term_id, otrx.merchant_id, os.name AS store_name,
					FORMAT(amount_tran_req,'N0') AS amount, reff_number_2 AS edc_refnum,
					JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
					resp_code_rsp AS response_code,
					ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
					CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
					END AS flag,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle
					FROM  
					office_trans otrx WITH (NOLOCK) 
					JOIN
					office_terminal ot ON otrx.term_id = ot.term_id JOIN
					office_store os ON ot.store_id = os.store_id JOIN
					office_merchant om ON otrx.merchant_id = om.merchant_id 					
					WHERE 
					tran_type IN ('50','00') AND
					source_node IN ('ShopeePayIncoming','OvoIncoming','GPayApp','LinkAjaIncoming') AND
					((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00') AND
					time_req >= '2020-06-01 00:00:00.000' AND
					time_rsp <= '2020-12-29 23:59:59.999' 
				) AS transTable 
				WHERE QRType != '11'
				ORDER BY trx_time DESC";

	 
				$stmt = $pdo->prepare($sql);
		

				$retval = $stmt->fetchAll();
				$stmt = null;
				$pdo = null;
				return $retval;
	}
	
	function get_all_transactions($num = NULL, $offset = NULL)
	{
		$this->db->select('transaction.id, m1.dsc as trans_status_code_dsc, transaction.terminal_id, terminal.terminal_name, transaction.customer_id, transaction.card_no, m2.dsc as trans_type_code_dsc, transaction.start_datetime, transaction.end_datetime, transaction.amount, transaction.balance, store.store_name, transaction.reference_number, merchant.merchant_name, transaction.trans_type_code, (select merchant_name from merchant where merchant.id = transaction.customer_id AND transaction.card_no is null) as company_agent_name, transaction.trans_note, transaction.is_posting, m1.dsc2 as trx_code_dsc, m1.note as trx_code_number');
		$this->db->order_by('transaction.id', 'DESC');
		$this->db->join('master_std as m1', 'm1.data_group = \'trans_status\' and transaction.trans_status_code = m1.code', 'LEFT');
		$this->db->join('master_std as m2', 'm2.data_group = \'trans_type\' and transaction.trans_type_code = m2.code', 'LEFT');
		$this->db->join('terminal', 'transaction.terminal_id = terminal.id', 'LEFT');
		$this->db->join('store', 'terminal.store_id = store.id', 'LEFT');
		$this->db->join('admin_user', 'transaction.agent_id = admin_user.id', 'LEFT');
		$this->db->join('merchant', 'transaction.agent_merchant_id = merchant.id', 'LEFT');
		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get('transaction');
	}

	//============================== SEARCH ==============================//

	function get_search_result($search_param, $num = NULL, $offset = NULL)
	{
		$this->db->select('transaction.id, m1.dsc as trans_status_code_dsc, transaction.terminal_id, terminal.terminal_name, transaction.customer_id, transaction.card_no, m2.dsc as trans_type_code_dsc, transaction.start_datetime, transaction.end_datetime, transaction.amount, transaction.balance, store.store_name, transaction.reference_number, merchant.merchant_name, transaction.trans_type_code, (select merchant_name from merchant where merchant.id = transaction.customer_id AND transaction.card_no is null) as company_agent_name, transaction.trans_note, transaction.is_posting, m1.dsc2 as trx_code_dsc, m1.note as trx_code_number');
		$this->db->order_by('transaction.id', 'DESC');
		$this->db->join('master_std as m1', 'm1.data_group = \'trans_status\' and transaction.trans_status_code = m1.code', 'LEFT');
		$this->db->join('master_std as m2', 'm2.data_group = \'trans_type\' and transaction.trans_type_code = m2.code', 'LEFT');
		$this->db->join('terminal', 'transaction.terminal_id = terminal.id', 'LEFT');
		$this->db->join('store', 'terminal.store_id = store.id', 'LEFT');
		$this->db->join('admin_user', 'transaction.agent_id = admin_user.id', 'LEFT');
		$this->db->join('merchant', 'transaction.agent_merchant_id = merchant.id', 'LEFT');

		if (isset($search_param['transaction_identifier']) && !empty($search_param['transaction_identifier'])) {
			$this->db->where('transaction.id', $search_param['transaction_identifier']);
		}
		if (isset($search_param['terminal_id']) && !empty($search_param['terminal_id'])){
			$this->db->where('terminal_name', $search_param['terminal_id']);
		}
		if(isset($search_param['card_number']) && !empty($search_param['card_number'])){
			$this->db->like('card_no', $search_param['card_number'], 'both');
		}		

		// tabel customer first_name dan sure_name
		// tabel transaction customer_id
		if(isset($search_param['user']) && !empty($search_param['user'])){
			$search_user = $this->db->query('SELECT * FROM customer WHERE lower(first_name) LIKE \'%'.strtolower($search_param['user']).'%\' OR lower(sure_name) LIKE \'%'.strtolower($search_param['user']).'%\'')->result();
			$search_user_id = array();
			foreach ($search_user as $row)
			{
				array_push ($search_user_id,$row->id);
			}

			if(!empty($search_user)) {
				$this->db->where_in('customer_id', $search_user_id);
			}
			else {
				$this->db->where('customer_id', 0);
			}
		}	

		if(isset($search_param['reference_number']) && !empty($search_param['reference_number'])){
			$this->db->like('reference_number', $search_param['reference_number'], 'both');
		}

		// start date dan end date
		if (isset($search_param['start_date']) && !empty($search_param['start_date'])){
			$this->db->where('start_datetime >=', $search_param['start_date'].' 00:00:00');
		}
		if (isset($search_param['end_date']) && !empty($search_param['end_date'])){
			$this->db->where('end_datetime <=', $search_param['end_date'].' 23:59:59');
		}

		// operation = trans_type_code
		// pas balik ke html selected nya ilang
		if (isset($search_param['operation']) && !empty($search_param['operation'])){
			// $this->db->like('trans_type_code', $search_param['operation'], 'both');
			$this->db->where('trans_type_code =', $search_param['operation']);
		}

		// table transaction memiliki terminal_id
		// company == table merchant
		if(isset($search_param['company']) && !empty($search_param['company'])){
			// $search_terminal = $this->db->where('merchant_id', $search_param['company'])->get('terminal');
			$search_terminal = $this->db->query('select terminal.id from terminal left join store on terminal.store_id = store.id left join merchant on store.merchant_id = merchant.id where store.merchant_id = '.$search_param['company'])->result();
			
			$search_terminal_id = array();
			foreach ($search_terminal as $row)
			{
				array_push ($search_terminal_id,$row->id);
			}

			if(isset($search_param['company_selected_not']) && !empty($search_param['company_selected_not'])){
				if(!empty($search_terminal)) {
					$this->db->where_not_in('terminal_id', $search_terminal_id);
					$this->db->or_where('terminal_id', null);
				}
			}
			else {
				if(!empty($search_terminal)) {
					$this->db->where_in('terminal_id', $search_terminal_id);
				}
				else {
					$this->db->where('terminal_id', -1);
				}
			}
		}	

		// agent_company == is_agent = 'Y' ga kepake is_agent nya, mungkin lebih baik buat daftar nya di view
		if(isset($search_param['agent_company']) && !empty($search_param['agent_company'])){

			$search_user_agent = $this->db->query('select admin_user.id from admin_user left join merchant on admin_user.agent_merchant_id = merchant.id where admin_user.agent_merchant_id ='.$search_param['agent_company'])->result();
			$search_user_agent_id = array();
			foreach ($search_user_agent as $row)
			{
				array_push ($search_user_agent_id,$row->id);
			}

			if(!empty($search_user_agent)) {
				$this->db->where_in('agent_id', $search_user_agent_id);
			}
			else {
				$this->db->where('agent_id', -1);
			}
		}	

		// companysite == table store
		if(isset($search_param['companysite']) && !empty($search_param['companysite'])){
			$search_terminal = $this->db->query('select terminal.id from terminal where store_id = '.$search_param['companysite'])->result();
			
			$search_terminal_id = array();
			foreach ($search_terminal as $row)
			{
				array_push ($search_terminal_id,$row->id);
			}

			if(isset($search_param['companysite_selected_not']) && !empty($search_param['companysite_selected_not'])){
				if(!empty($search_terminal)) {
					$this->db->where_not_in('terminal_id', $search_terminal_id);
					$this->db->or_where('terminal_id', null);
				}
				else {

				}
			}
			else {
				if(!empty($search_terminal)) {
					$this->db->where_in('terminal_id', $search_terminal_id);
				}
				else {
					$this->db->where('terminal_id', -1);
				}
			}
		}
		
		$this->db->order_by('transaction.id', 'DESC');
		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get('transaction');
	}


    function get_all_company_agent() {
        return $this->db->select('*')->where('is_agent','Y')->get('merchant');
    }

    function get_all_company() {
        return $this->db->select('*')->where('is_agent','N')->get('merchant');
	}

	function get_all_company_site(){
		return $this->db->select('*')->where('is_del','N')->get('store');
	}
	
	function get_all_transaction_type() {
        return $this->db->select('*')->where(array('data_group' => 'trans_type', 'is_active' => 'Y'))->get('master_std');
	}

	function get_transaction_for_export($search_param, $num = NULL, $offset = NULL)
	{
		$this->db->select('transaction.id, \'\' as partner, m1.dsc as trans_status_code_dsc, terminal.terminal_name, transaction.card_no, m2.dsc as trans_type_code_dsc, (CASE WHEN transaction.trans_type_code = \'1\' THEN merchant.merchant_name WHEN transaction.trans_type_code = \'7\' THEN merchant.merchant_name WHEN transaction.trans_type_code = \'2\' THEN (select merchant_name from merchant where merchant.id = transaction.customer_id AND transaction.card_no is null) ELSE store.store_name END) as company_or_store_name, transaction.start_datetime, transaction.end_datetime, transaction.amount, transaction.is_posting, m1.dsc2 as trx_code_dsc, m1.note as trx_code_number');
		$this->db->order_by('transaction.id', 'DESC');
		$this->db->join('master_std as m1', 'm1.data_group = \'trans_status\' and transaction.trans_status_code = m1.code', 'LEFT');
		$this->db->join('master_std as m2', 'm2.data_group = \'trans_type\' and transaction.trans_type_code = m2.code', 'LEFT');
		$this->db->join('terminal', 'transaction.terminal_id = terminal.id', 'LEFT');
		$this->db->join('store', 'terminal.store_id = store.id', 'LEFT');
		$this->db->join('admin_user', 'transaction.agent_id = admin_user.id', 'LEFT');
		$this->db->join('merchant', 'transaction.agent_merchant_id = merchant.id', 'LEFT');

		if (isset($search_param['transaction_identifier']) && !empty($search_param['transaction_identifier'])) {
			$this->db->where('id', $search_param['transaction_identifier']);
		}
		if (isset($search_param['terminal_id']) && !empty($search_param['terminal_id'])){
			$this->db->where('terminal_id', $search_param['terminal_id']);
		}
		if(isset($search_param['card_number']) && !empty($search_param['card_number'])){
			$this->db->like('card_no', $search_param['card_number'], 'both');
		}		

		// tabel customer first_name dan sure_name
		// tabel transaction customer_id
		if(isset($search_param['user']) && !empty($search_param['user'])){
			$search_user = $this->db->or_like(
                    array('first_name' => $search_param['user'],
                    'sure_name' => $search_param['user']),
                    'both'
			    )
                ->get('customer')
                ->result() ;
			
			$search_user_id = array();
			foreach ($search_user as $row)
			{
				array_push ($search_user_id,$row->id);
			}

			if(!empty($search_user)) {
				$this->db->where_in('customer_id', $search_user_id);
			}
			else {
				$this->db->where('customer_id', 0);
			}
		}	

		if(isset($search_param['reference_number']) && !empty($search_param['reference_number'])){
			$this->db->like('reference_number', $search_param['reference_number'], 'both');
		}

		// start date dan end date
		if (isset($search_param['start_date']) && !empty($search_param['start_date'])){
			$this->db->where('start_datetime >=', $search_param['start_date'].' 00:00:00');
		}
		if (isset($search_param['end_date']) && !empty($search_param['end_date'])){
			$this->db->where('end_datetime <=', $search_param['end_date'].' 23:59:59');
		}

		// operation = trans_type_code
		// pas balik ke html selected nya ilang
		if (isset($search_param['operation']) && !empty($search_param['operation'])){
			$this->db->like('trans_type_code', $search_param['operation'], 'both');
		}

		// table transaction memiliki terminal_id
		// company == table merchant
		if(isset($search_param['company']) && !empty($search_param['company'])){
			// $search_terminal = $this->db->where('merchant_id', $search_param['company'])->get('terminal');
			$search_terminal = $this->db->query('select terminal.id from terminal left join store on terminal.store_id = store.id left join merchant on store.merchant_id = merchant.id where store.merchant_id = '.$search_param['company'])->result();
			
			$search_terminal_id = array();
			foreach ($search_terminal as $row)
			{
				array_push ($search_terminal_id,$row->id);
			}

			if(isset($search_param['company_selected_not']) && !empty($search_param['company_selected_not'])){
				if(!empty($search_terminal)) {
					$this->db->where_not_in('terminal_id', $search_terminal_id);
					$this->db->or_where('terminal_id', null);
				}
				else {

				}
			}
			else {
				if(!empty($search_terminal)) {
					$this->db->where_in('terminal_id', $search_terminal_id);
				}
				else {
					$this->db->where('terminal_id', -1);
				}
			}
		}	

		// agent_company == is_agent = 'Y' ga kepake is_agent nya, mungkin lebih baik buat daftar nya di view
		if(isset($search_param['agent_company']) && !empty($search_param['agent_company'])){
			$search_terminal = $this->db->query('select terminal.id from terminal left join store on terminal.store_id = store.id left join merchant on store.merchant_id = merchant.id where store.merchant_id = '.$search_param['agent_company'])->result();
			$search_terminal_id = array();
			foreach ($search_terminal as $row)
			{
				array_push ($search_terminal_id,$row->id);
			}

			if(!empty($search_terminal)) {
				$this->db->where_in('terminal_id', $search_terminal_id);
			}
			else {
				$this->db->where('terminal_id', -1);
			}
		}	

		// companysite == table store
		if(isset($search_param['companysite']) && !empty($search_param['companysite'])){
			$search_terminal = $this->db->query('select terminal.id from terminal where store_id = '.$search_param['companysite'])->result();
			
			$search_terminal_id = array();
			foreach ($search_terminal as $row)
			{
				array_push ($search_terminal_id,$row->id);
			}

			if(isset($search_param['companysite_selected_not']) && !empty($search_param['companysite_selected_not'])){
				if(!empty($search_terminal)) {
					$this->db->where_not_in('terminal_id', $search_terminal_id);
					$this->db->or_where('terminal_id', null);
				}
				else {

				}
			}
			else {
				if(!empty($search_terminal)) {
					$this->db->where_in('terminal_id', $search_terminal_id);
				}
				else {
					$this->db->where('terminal_id', -1);
				}
			}
		}

		if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
		return $this->db->get('transaction');
	}
}
