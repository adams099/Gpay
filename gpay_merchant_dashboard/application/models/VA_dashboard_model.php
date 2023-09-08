<?php
defined('BASEPATH') or exit('No direct script access allowed');

class VA_dashboard_model extends CI_Model
{

	public function get_list_va_dash()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
        $sql = "SELECT TOP 10
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
		account_number_1 AS mobile_phone_no,
		CONCAT(receiving_inst_id, account_number_1) AS va_number,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName') AS customer_name,
		CONVERT(CHAR(23),time_req,121) AS time_trx,
		FORMAT(amount_tran_req,'N0') AS amount,
		CASE
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6010' THEN 'Teller'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6011' THEN 'ATM'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6012' THEN 'POS/EDC'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6013' THEN 'AutoDebit'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6014' THEN 'Internet Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6015' THEN 'Kiosk'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6016' THEN 'Phone Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6017' THEN 'Mobile Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6018' THEN 'LLG'
			ELSE 'Other'
		END AS bca_payment_channel,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CurrencyCode') AS currency,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS refnum_gpay,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email') AS cust_email,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_phone') AS cust_phone,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.order_number') AS order_number,
		CONVERT(VARCHAR,time_req,112) AS credit_date,
		FORMAT(1000,'N0') AS cost_bca,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
		reff_number_2 AS bca_request_id,
		CASE 
			WHEN resp_code_rsp = '00' THEN 'Success'
			WHEN resp_code_rsp = '01' THEN 'Failed'
			ELSE 'Failed'
		END AS response_message,    
		CASE WHEN resp_code_adv = '00'
			THEN '1' 
			ELSE '0' 
		END AS flag_recon
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'
		ORDER BY id DESC";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}

	public function get_list_va_bca()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
        $sql = "SELECT TOP 5
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
		account_number_1 AS mobile_phone_no,
		CONCAT(receiving_inst_id, account_number_1) AS va_number,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName') AS customer_name,
		CONVERT(CHAR(23),time_req,121) AS time_trx,
		FORMAT(amount_tran_req,'N0') AS amount,
		CASE
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6010' THEN 'Teller'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6011' THEN 'ATM'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6012' THEN 'POS/EDC'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6013' THEN 'AutoDebit'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6014' THEN 'Internet Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6015' THEN 'Kiosk'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6016' THEN 'Phone Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6017' THEN 'Mobile Banking'
			WHEN JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.ChannelType') = '6018' THEN 'LLG'
			ELSE 'Other'
		END AS bca_payment_channel,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CurrencyCode') AS currency,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS refnum_gpay,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email') AS cust_email,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_phone') AS cust_phone,
		JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.order_number') AS order_number,
		CONVERT(VARCHAR,time_req,112) AS credit_date,
		FORMAT(1000,'N0') AS cost_bca,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
		reff_number_2 AS bca_request_id,
		CASE 
			WHEN resp_code_rsp = '00' THEN 'Success'
			WHEN resp_code_rsp = '01' THEN 'Failed'
			ELSE 'Failed'
		END AS response_message,    
		CASE WHEN resp_code_adv = '00'
			THEN '1' 
			ELSE '0' 
		END AS flag_recon
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'
		ORDER BY id DESC";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}

public function get_sum_va()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
        $sql = "SELECT CONVERT(nvarchar, CAST(SUM(amount_tran_req) AS money), 1) as sum
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}

public function get_count_trx()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
        $sql = "SELECT count(id) as count_trx
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}
public function get_by_status_new()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();

        $sql = "SELECT count(id) as count_trx,
		CONVERT(nvarchar, CAST(SUM(amount_tran_req) AS money), 1) as sum
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}
public function get_by_status_fail()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();

        $sql = "SELECT count(id) as count_trx,
		CONVERT(nvarchar, CAST(SUM(amount_tran_req) AS money), 1) as sum
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='01' OR resp_code_adv='00') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}
public function get_by_status_void()
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
	

        $sql = "SELECT count(id) as count_trx,
		CONVERT(nvarchar, CAST(SUM(amount_tran_req) AS money), 1) as sum
		FROM  
		office_trans WITH (NOLOCK) 
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND		
		receiving_inst_id ='39789' AND
		merchant_id='BCAVA00001'and
		(resp_code_rsp='02' OR resp_code_adv='02') AND
		time_req >= '2021-06-21 11:00:00.000' AND
		time_rsp <= '2021-06-24 23:59:59.999'";

	 
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->fetchAll();
		$stmt = null;
		$pdo = null;
		return $retval;
}
}