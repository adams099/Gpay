<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qris_model extends CI_Model {

	public function get_total_amount($merchant_id, $issuer)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";

		
		if ($issuer == "All"){
			$issuer = "'ShopeePayIncoming', 'OvoIncoming', 'GPayApp', 'LinkAjaIncoming'";
		} else {
			$issuer = rtrim($issuer, ", ");
		}
        $sql = "SELECT DISTINCT(merchant_name), FORMAT(SUM(amount),'N0') AS amount, COUNT(merchant_name) as transCount
		FROM
				(SELECT                                          
				CASE
				WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
				WHEN source_node = 'OvoIncoming' THEN 'OVO'
				WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
				ELSE 'GPay'
				END AS source_of_fund,
				CONVERT(CHAR(23),time_req,121) AS trx_time, 
				CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					ELSE 'refund'
				END AS trx_type,
				om.name AS merchant_name,
				otrx.term_id,
				otrx.merchant_id,
				os.name AS store_name,
				amount_tran_req AS amount,
				reff_number_2 AS edc_refnum,
				JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
				resp_code_rsp AS response_code,
				ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
				CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
				END AS flag,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
				CASE 
					WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
					WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
					END AS status
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_terminal ot ON otrx.term_id = ot.term_id JOIN
				office_store os ON ot.store_id = os.store_id JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id        
				WHERE 
				source_node IN ($issuer) 
				AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
				AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
				AND time_rsp <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')
				AND otrx.merchant_id=$merchant_id
				) AS myDerivedTable
				GROUP BY merchant_name
				ORDER BY merchant_name DESC";

	 
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
	
	public function get_total_transaction($merchant_id, $issuer, $dateFilter)
    {
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_rsp <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_rsp <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
		}

		if ($issuer == "All"){
			$issuer = "'ShopeePayIncoming', 'OvoIncoming', 'GPayApp', 'LinkAjaIncoming'";
		} else {
			$issuer = rtrim($issuer, ", ");
		}

        $sql = "SELECT DISTINCT(merchant_name), FORMAT(SUM(amount),'N0') AS amount, COUNT(merchant_name) as transCount
		FROM
				(SELECT                                          
				CASE
				WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
				WHEN source_node = 'OvoIncoming' THEN 'OVO'
				WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
				ELSE 'GPay'
				END AS source_of_fund,
				CONVERT(CHAR(23),time_req,121) AS trx_time, 
				CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					ELSE 'refund'
				END AS trx_type,
				om.name AS merchant_name,
				otrx.term_id,
				otrx.merchant_id,
				os.name AS store_name,
				amount_tran_req AS amount,
				reff_number_2 AS edc_refnum,
				JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
				resp_code_rsp AS response_code,
				ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
				CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
				END AS flag,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
				CASE 
					WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
					WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
					END AS status
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_terminal ot ON otrx.term_id = ot.term_id JOIN
				office_store os ON ot.store_id = os.store_id JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id        
				WHERE 
				source_node IN ($issuer) 
				AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
				$dateFilterQuery
				AND otrx.merchant_id=$merchant_id
				) AS myDerivedTable
				GROUP BY merchant_name
				ORDER BY merchant_name DESC";

	 
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
	
	public function get_trans_hist($merchant_id,$num = NULL, $offset = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$sql = "SELECT source_of_fund, trx_time, trx_type, amount, term_id, merchant_id, store_name, edc_refnum, issuer_refnum, date_settle, flag, status
		FROM
		(SELECT                                          
		CASE
		WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
		WHEN source_node = 'OvoIncoming' THEN 'OVO'
		WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
		ELSE 'GPay'
		END AS source_of_fund,
		CONVERT(CHAR(23),time_req,121) AS trx_time, 
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
		resp_code_rsp AS response_code,
		ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
			END AS status
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		source_node IN ('ShopeePayIncoming','OvoIncoming','GPayApp','LinkAjaIncoming') 
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		AND time_req >= concat(CAST(GETDATE() as DATE),' 00:00:00.000')
		AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
		AND otrx.merchant_id=$merchant_id
		) AS myDerivedTable
		ORDER BY trx_time DESC
		OFFSET $offset ROWS
		FETCH NEXT $num ROWS ONLY";

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

	public function get_merchant_by_userdata($merchant_id_arr)
	{
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "";
		foreach($merchant_id_arr as $key=>$value){
			if($key > 0){
				$merchant_id .= ",";
			}
			$merchant_id .= "'".$value->merchant_id."'";
		}
		$sql = "SELECT merchant_id, name
		FROM office_merchant
		WHERE merchant_id in (".$merchant_id.")";
		
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

	public function get_store_by_merchant_id($merchant_id_arr)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "";
		foreach($merchant_id_arr as $key=>$value){
			if($key > 0){
				$merchant_id .= ",";
			}
			$merchant_id .= "'".$value->merchant_id."'";
		}
		$sql = "SELECT store_id, name, merchant_id
		FROM office_store
		WHERE merchant_id in (".$merchant_id.")";
		
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

	public function get_total_row_trans_hist($merchant_id)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$sql = "SELECT 
		COUNT(*) AS 'Num Rows'
		FROM
		(SELECT                                          
		CASE
		WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
		WHEN source_node = 'OvoIncoming' THEN 'OVO'
		WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
		ELSE 'GPay'
		END AS source_of_fund,
		CONVERT(CHAR(23),time_req,121) AS trx_time, 
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
		resp_code_rsp AS response_code,
		ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
			END AS status
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		source_node IN ('ShopeePayIncoming','OvoIncoming','GPayApp','LinkAjaIncoming') 
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		AND time_req >= concat(CAST(GETDATE() as DATE),' 00:00:00.000')
		AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
		AND otrx.merchant_id=$merchant_id
		) AS myDerivedTable
		GROUP BY source_of_fund
		ORDER BY source_of_fund DESC";

		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();

		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$data = $stmt->fetchAll();
		$num_row = 0;
		foreach($data as $d){
			foreach($d as $a){
				$num_row = $num_row + $a; 
			}
		}
		$stmt = null;
		$pdo = null;
		return $num_row;
	}
//==========FILTERED
	public function get_data_filtered_by_param($merchant_id_arr, $tgl_awal, $tgl_akhir, $amount_fr, $amount_to, $refnum, $status_arr, $source_of_fund, $tranno)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "";
		foreach($merchant_id_arr as $key=>$value){
			if($key > 0){
				$merchant_id .= ",";
			}
			$merchant_id .= "'".$value->merchant_id."'";
		}
		
		$status = "";
		foreach($status_arr as $key=>$value){
			if($key > 0){
				$status .= ",";
			}
			//If store filter is null
				$status .= "'".$value."'";
		}

		$filter_data = "";
		$filterQuery = "";
		$dateFilterQuery = "";
		
		
		// if(strlen($tgl_awal) > 0 && strlen($tgl_akhir) > 0){
		// 	$dateFilterQuery = " AND time_req >= '".$tgl_awal."' AND time_rsp <= '".$tgl_akhir."' ";
		// }
		if(strlen($tgl_awal) > 0) $dateFilterQuery = " AND time_req >= '".$tgl_awal."' ";
		if(strlen($tgl_akhir) > 0) $dateFilterQuery = $dateFilterQuery." AND time_rsp <= '".$tgl_akhir."' ";

		if(strlen($amount_fr) > 0) $filterQuery = " AND RAW_AMOUNT >= '".$amount_fr."' ";
		if(strlen($amount_to) > 0) $filterQuery = $filterQuery." AND RAW_AMOUNT <= '".$amount_to."' ";
		
		if(strlen($tranno) > 0) $filterQuery = " AND TRAN_NO = '".$tranno."' ";

		if(strlen($refnum) > 0) $filterQuery = " AND REFF_NO = '".$refnum."' ";

		if(strlen($source_of_fund) > 0) $filterQuery = " AND SOURCE_OF_FUND like '%".$source_of_fund."%' ";

		if(strlen($status) > 0){
			if($status_arr[0] != "All"){ 
				$filterQuery = " AND [status] in (".$status.") ";
			}
		}

		if(strlen($filterQuery) > 0){
			$filter_data = " WHERE id is not null ".$filterQuery;
		}

		
		

		$query ="SELECT 
		*
		FROM
		( ".$this->getEmoneyQuery($merchant_id, $dateFilterQuery).
		" UNION ".
		$this->getJalinQuery($merchant_id, $dateFilterQuery).
		" ) AS myDerivedTable ".
		$filter_data.
		" ORDER BY time_req DESC ";

		

		$stmt = $pdo->prepare($query);
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

	public function get_transaction_history_export($merchant_id_arr, $tgl_awal, $tgl_akhir, $amount_fr, $amount_to, $refnum, $status_arr)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
		$merchant_id = "";
		foreach($merchant_id_arr as $key=>$value){
			if($key > 0){
				$merchant_id .= ",";
			}
			$merchant_id .= "'".$value->merchant_id."'";
		}
		
		$status = "";
		foreach($status_arr as $key=>$value){
			if($key > 0){
				$status .= ",";
			}
			//If store filter is null
				$status .= "'".$value."'";
		}

		$filter_data = "";
		$dateFilterQuery = "";
		$amountFilterQuery = "";
		$refnumFilterQuery = "";
		$statusFilterQuery = "";
		
		if(strlen($tgl_awal) > 0) $dateFilterQuery = " AND time_req >= '".$tgl_awal."' ";
		if(strlen($tgl_akhir) > 0) $dateFilterQuery = $dateFilterQuery." AND time_rsp <= '".$tgl_akhir."' ";

		if(strlen($amount_fr) > 0) $amountFilterQuery = " AND RAW_AMOUNT >= '".$amount_fr."' ";
		if(strlen($amount_to) > 0) $amountFilterQuery = $amountFilterQuery." AND RAW_AMOUNT <= '".$amount_to."' ";
		
		if(strlen($refnum) > 0) $refnumFilterQuery = " AND REFF_NO = '".$refnum."' ";
		
		if(strlen($status) > 0){
			if($status[0] == "All"){ 
				$statusFilterQuery = " AND [status] in (".$status.") ";
			}
		}

		if(strlen($amountFilterQuery) > 0 || strlen($refnumFilterQuery) > 0  || strlen($statusFilterQuery) > 0){
			$filter_data = " WHERE id is not null ".$amountFilterQuery.$refnumFilterQuery.$statusFilterQuery;
		}

		if(strlen($amountFilterQuery) > 0 || strlen($refnumFilterQuery) > 0){
			$filter_data = " WHERE id is not null ".$amountFilterQuery.$refnumFilterQuery;
		}

		$query ="SELECT 
		*
		FROM
		( ".$this->getEmoneyQuery($merchant_id, $dateFilterQuery).
		" UNION ".
		$this->getJalinQuery($merchant_id, $dateFilterQuery).
		" ) AS myDerivedTable ".
		$filter_data;

		$stmt = $pdo->prepare($query);
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

	public function getEmoneyQuery($merchant_id, $dateFilterQuery){
		$filter_merchant = "";
		if(strlen($merchant_id) > 0) $filter_merchant = " and om.merchant_id in (".$merchant_id.")";

		return "select
		id as ID, 
		case
			when tran_type = '20' then 'refund'
			else 'payment'
		end as TRX_TYPE,
		'On-Us' as TRX_ROLE,
		'GPay' as SOURCE_OF_FUND,
		format(time_req,'yyyy-MM-dd HH:mm:ss') as TIME_REQ,
		cast(replace(concat(N'',node_data_req,''),'&','and') as xml).value('(/Iso8583Xml/F37)[1]','nvarchar(MAX)') as TRAN_NO,
		case
			when isnull(json_value(cast(replace(concat(N'',node_data_req,''),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') != '12' then reff_number_2 
			when tran_type = '20' and isnull(json_value(cast(replace(concat(N'',node_data_req,''),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') = '12' 
				then json_value(cast(replace(concat(N'',node_data_rsp,''),'&','and') as xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.approvalCode') 
			else json_value(cast(replace(concat(N'',node_data_req,''),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode')
		end as REFF_NO,
		'Gpay' as MERCHANT_ACQUIRER,
		os.name as STORE_NAME,
		ot.term_id as TERMINAL_ID,
		case
			when tran_type = '20' then format(amount_tran_req * -1,'N2') 
			else format(amount_tran_req,'N2')
		end as AMOUNT,
		case
			when tran_type = '20' then amount_tran_req * -1
			else amount_tran_req
		end as RAW_AMOUNT,
		case
			when fee_to_iss != 0 then concat((fee_to_iss * 100 / amount_tran_req),'%')
			else '0%'
		end as MDR_FEE,
		case
			when (tran_type = '20' and fee_to_iss != 0) then format(ROUND(fee_to_iss, 0, 1) * -1, 'N2')
			when fee_to_iss != 0 then format(ROUND(fee_to_iss, 0, 1), 'N2')
			else format(ROUND(cast(substring(settlement_fee,2,8) as int), 0, 1), 'N2')
		end as FEE,
		case
			when (tran_type = '20' and fee_to_iss != 0) then format((amount_tran_req - ROUND(fee_to_iss, 0, 1)) * -1, 'N2')
			when (tran_type = '20' and fee_to_iss = 0) then format((amount_tran_req - ROUND(cast(substring(settlement_fee,2,8) as int), 0, 1)) * -1, 'N2')
			when fee_to_iss != 0 then format(amount_tran_req - ROUND(fee_to_iss, 0, 1), 'N2')
			else format(amount_tran_req - ROUND(cast(substring(settlement_fee,2,8) as int), 0, 1), 'N2')
		end as AMOUNT_SETTLE,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
			WHEN tran_type = '20' and resp_code_rsp <> '00' THEN 'failed'
			else 'failed'
		END AS status
		from  
		office_trans ot with (nolock) 
		join office_store os on ot.store_id = os.store_id 
		join office_merchant om on ot.merchant_id = om.merchant_id
		
		where 
		tran_type in ('00','20') and
		(
			(source_node = 'GpayApp' and dest_node = 'QRISOnlinePayment')
			or
			(source_node = 'QRISOnlinePayment' and dest_node = 'GpayHost')
		)
		".$filter_merchant."
		".$dateFilterQuery."";
	}

	public function getJalinQuery($merchant_id, $dateFilterQuery){
		$filter_merchant = "";
		if(strlen($merchant_id) > 0) $filter_merchant = " and office_trans.merchant_id in (".$merchant_id.")";

		return "
		select
			office_trans.ID,
			TRX_TYPE,
			TRX_ROLE,
			case 
				when source_nns.product_name IS NULL then 'OTHERS'
				else source_nns.product_name
			end
			as SOURCE_OF_FUND,
			format(time_req,'yyyy-MM-dd HH:mm:ss') as TIME_REQ,
			PAYMENT_NO,
			reff_number_2 as REFF_NO,
			case 
				when merchant_nns.product_name IS NULL then 'OTHERS'
				else merchant_nns.product_name
			end
			as MERCHANT_ACQUIRER,
			Replace(STORE_NAME, '&amp;', '&') as STORE_NAME,
			TERMINAL_ID,
			case
				when TRX_ROLE = 'Off-Us Outgoing' then format(AMOUNT,'N2') 
				when TRX_ROLE = 'Off-Us Incoming' then format(PARTNER_AMOUNT,'N2')
			end as AMOUNT,
			PARTNER_AMOUNT as RAW_AMOUNT,
			MDR_FEE,
			format(ROUND(FEE, 0, 1),'N2') as FEE,
			case
			when TRX_ROLE = 'Off-Us Outgoing' then format(AMOUNT-ROUND(FEE, 0, 1),'N2') 
			when TRX_ROLE = 'Off-Us Incoming' then format(PARTNER_AMOUNT-ROUND(FEE, 0, 1),'N2')
			end as AMOUNT_SETTLE,
			CASE 
				WHEN tran_type = '26' and resp_code_rsp = '00' THEN 'paid'
				WHEN tran_type = '26' and resp_code_rsp <> '00' THEN 'failed'
				WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
				WHEN tran_type = '20' and resp_code_rsp <> '00' THEN 'failed'
			END AS status
			from  
			(	select
			id as ID,
			case
				when tran_type = '20' then 'refund'
				else 'payment'
			end as TRX_TYPE,	
			case
				when (source_node = 'GpayApp' and dest_node = 'JalinOutgoing') or (source_node = 'JalinIncoming' and dest_node = 'GpayHost') then 'Off-Us Outgoing'
				when (source_node = 'JalinIncoming' and dest_node = 'QRISOnlinePayment') or (source_node = 'QRISOnlinePayment' and dest_node = 'JalinOutgoing') then 'Off-Us Incoming'
			end as TRX_ROLE,
			isnull(substring(json_value(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','varchar(MAX)'),'$.customerPan'),0,9),'') as SOURCE_OF_FUND,
			isnull(substring(json_value(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','varchar(MAX)'),'$.merchantPan'),0,9),'') as MERCHANT_ACQUIRER,
			json_value(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','varchar(MAX)'),'$.merchantCity') as MERCHANT_CITY,
			json_value(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','&amp;') as xml).value('(/Iso8583Xml/F55)[1]','varchar(MAX)'),'$.merchantName') as STORE_NAME,
			time_req,
			isnull(json_value(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','varchar(MAX)'),'$.merchantCriteria'),'') as MERCHANT_CRITERIA,
			resp_code_rsp as RESPONSE_CODE,
			term_id as TERMINAL_ID,
			date_settlement_out as DATE_SETTLE,		
			case
				when (source_node = 'JalinIncoming' and dest_node = 'QRISOnlinePayment') and tran_type = '20' then amount_tran_req * -1
				when (source_node = 'QRISOnlinePayment' and dest_node = 'JalinOutgoing') then amount_tran_req
				else 0
			end as AMOUNT,
			case
				when (source_node = 'QRISOnlinePayment' and dest_node = 'JalinOutgoing') and tran_type = '20' then amount_tran_req * -1
				when (source_node = 'JalinIncoming' and dest_node = 'QRISOnlinePayment') then amount_tran_req
				else 0
			end as PARTNER_AMOUNT,
			concat(ROUND(((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) * 100 / amount_tran_req), 2, 0),'%') as MDR_FEE,
			case
				when (tran_type = '20' and (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0) then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) * -1 as float)
				when (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0 then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) as float)
				else cast(cast(substring(settlement_fee,2,8) as int) as float)
			end as FEE,
			case
				when (tran_type = '20' and (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0) then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 * -1 as float)
				when (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0 then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 as float)
				else cast(format(cast(substring(settlement_fee,2,8) as int) / 1.1,'N2') as float)
			end as DPP,
			case
				when (tran_type = '20' and (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0) then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 / 100 * 10 * -1 as float)
				when (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0 then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 / 100 * 10 as float)
				else cast(cast(substring(settlement_fee,2,8) as int) / 1.1 / 100 * 10 as float)
			end as PPN,
			case
				when (tran_type = '20' and (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0) then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 / 100 * 2 * -1 as float)
				when (fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) != 0 then cast((fee_to_swt + fee_to_acq + fee_to_iss + fee_standard + fee_service ) / 1.1 / 100 * 2 as float)
				else cast(cast(substring(settlement_fee,2,8) as int) / 1.1 / 100 * 2 as float)
			end as PPH,
			concat(isnull(replace(substring(cast(Replace(cast(node_data_rsp as NVarchar(MAX)),'&','and') as xml).value('(/Iso8583Xml/F28)[1]','varchar(MAX)'),1,10),'.00',''),'0'),'.00 C') as Convenience_Fee,		
			cast(Replace(cast(node_data_req as NVarchar(MAX)),'&','&amp;') as xml).value('(/Iso8583Xml/F63)[1]','varchar(MAX)') as PAYMENT_NO,
			reff_number_2,
			resp_code_rsp,
			amount_sett_rsp as RECEIVED_FROM_PARTNER,
			tran_type
			from
			office_trans with (nolock)
				left join office_store on office_trans.store_id = office_store.store_id 
				
				where 
				tran_type in ('26','20')
				and (
					(source_node = 'JalinIncoming' and dest_node = 'QRISOnlinePayment')
					or
					(source_node = 'QRISOnlinePayment' and dest_node = 'JalinOutgoing')
					)
		".$filter_merchant."
		".$dateFilterQuery."
		) as office_trans 
		left join office_nns source_nns on office_trans.SOURCE_OF_FUND = source_nns.nns 
		left join office_nns merchant_nns on office_trans.MERCHANT_ACQUIRER = merchant_nns.nns ";
	}


	public function getEDCQuery($merchant_id, $dateFilterQuery){
		$filter_merchant = "";
		if(strlen($merchant_id) > 0) $filter_merchant = " and om.merchant_id in (".$merchant_id.")";

		return "
		select
			id as ID,
			TRX_TYPE,
			'U-EDC' as TRX_ROLE,
			SOURCE_OF_FUND,
			format(time_req,'yyyy-MM-dd HH:mm:ss') as TIME_REQ,
			REFF_NO,
			KODE_STORE,
			SOURCE_OF_FUND as MERCHANT_ACQUIRER,
			STORE_NAME,
			TERMINAL_ID,
			format(PARTNER_AMOUNT,'N2') as AMOUNT,
			PARTNER_AMOUNT as RAW_AMOUNT,
			MDR_FEE as MDR_FEE,
			format(cast(ROUND(FEE, 0, 1) as decimal(18,2)), 'N2') as FEE,
			format(PARTNER_AMOUNT-ROUND(FEE, 0, 1),'N2') as AMOUNT_SETTLE,
			CASE 
				WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
				WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
				WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
				WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
				WHEN tran_type = '20' and resp_code_rsp <> '00' THEN 'failed'
			END AS status
			FROM
			(select 
				id, 
				case 
					when tran_type = '50' then 'payment'
					else 'refund'
				end as TRX_TYPE,                                        
				case
					when ((tran_type = '20' and dest_node = 'ShopeePayOutgoing') or (tran_type = '50' and source_node = 'ShopeePayIncoming')) then 'ShopeePay'
					when ((tran_type = '20' and dest_node = 'OvoOutgoing') or (tran_type = '50' and source_node = 'OvoIncoming')) then 'OVO'
					when ((tran_type = '20' and dest_node = 'LinkAjaOutgoing') or (tran_type = '50' and source_node = 'LinkAjaIncoming')) then 'LinkAja'
					else 'GO-PAY'
				end as SOURCE_OF_FUND,
				time_req,
				resp_code_rsp,
				format(time_req,'yyyy-MM-dd') as TRX_DATE,
				format(time_req,'HH:mm:ss') as TRX_TIME,  
				case
					when tran_type = '20' then json_value(cast(replace(concat(N'',node_data_rsp,''),'&','and') as xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.approvalCode')
					else json_value(cast(replace(concat(N'',node_data_req,''),'&','and') as xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode')
				end as REFF_NO,
				os.kode_store as KODE_STORE,
				os.city as MERCHANT_CITY,
				om.merchant_qris_criteria as MERCHANT_CRITERIA,
				os.name as STORE_NAME,
				otrx.term_id as TERMINAL_ID,
				coalesce(nullif(date_settlement_out,''), convert(varchar,dateadd(day,1,time_req),112)) as DATE_SETTLE,
				case
					when tran_type = '20' then amount_tran_req * -1
					else amount_tran_req
				end as PARTNER_AMOUNT,
				concat(((fee_to_iss + fee_to_acq) * 100 / amount_tran_req),'%') as MDR_FEE,
				case
					when (tran_type = '20' and (fee_to_iss + fee_to_acq) != 0) then cast((fee_to_iss + fee_to_acq) * -1 as float)
					when (fee_to_iss + fee_to_acq) != 0 then cast((fee_to_iss + fee_to_acq) as float)
					else cast(format(cast(substring(settlement_fee,2,8) as int), 'N2') as float)
				end as FEE,
				case
					when (tran_type = '20' and (fee_to_iss + fee_to_acq) != 0) then cast((fee_to_iss + fee_to_acq) / 1.1 * -1 as float)
					when (fee_to_iss + fee_to_acq) != 0 then cast((fee_to_iss + fee_to_acq) / 1.1 as float)
					else cast(format(cast(substring(settlement_fee,2,8) as int) / 1.1,'N2') as float)
				end as DPP,
				case
					when (tran_type = '20' and fee_to_iss != 0) then cast((fee_to_iss + fee_to_acq) / 1.1 / 100 * 10 * -1 as float)
					when (fee_to_iss + fee_to_acq) != 0 then cast((fee_to_iss + fee_to_acq) / 1.1 / 100 * 10 as float)
					else cast(format(cast(substring(settlement_fee,2,8) as int) / 1.1 / 100 * 10,'N2') as float)
				end as PPN,
				case
					when (tran_type = '20' and (fee_to_iss + fee_to_acq) != 0) then cast((fee_to_iss + fee_to_acq) / 1.1 / 100 * 2 * -1 as float)
					when (fee_to_iss + fee_to_acq) != 0 then cast((fee_to_iss + fee_to_acq) / 1.1 / 100 * 2 as float)
					else cast(format(cast(substring(settlement_fee,2,8) as int) / 1.1 / 100 * 2,'N2') as float)
				end as PPH,
				amount_sett_rsp as RECEIVED_FROM_PARTNER,
				resp_code_rsp as RESPONSE_CODE,
				tran_type

				from  
				office_trans otrx with (nolock) 
				join office_terminal ot on otrx.term_id = ot.term_id 
				join office_store os on ot.store_id = os.store_id 
				join office_merchant om on otrx.merchant_id = om.merchant_id 

				where 
				tran_type in ('50','20') and
				(source_node in ('ShopeePayIncoming','OvoIncoming','LinkAjaIncoming','GoPayIncoming') or 
				dest_node in ('ShopeePayOutgoing','LinkAjaOutgoing','OvoOutgoing','GoPayOutgoing'))
				".$filter_merchant."
				".$dateFilterQuery."
			) as office_trans ";
	}
	

	public function get_total_row_trans_hist_filtered($merchant_id_arr, $tgl_awal, $tgl_akhir, $amount_fr, $amount_to, $refnum, $status_arr)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
		$merchant_id = "";
		foreach($merchant_id_arr as $key=>$value){
			if($key > 0){
				$merchant_id .= ",";
			}
			$merchant_id .= "'".$value->merchant_id."'";
		}

		$status = "";
		foreach($status_arr as $key=>$value){
			if($key > 0){
				$status .= ",";
			}
			//If store filter is null
				$status .= "'".$value."'";
		}
		
		$filter_data = "";
		$dateFilterQuery = "";
		$amountFilterQuery = "";
		$refnumFilterQuery = "";
		$statusFilterQuery = "";
		
		
		// if(strlen($tgl_awal) > 0 && strlen($tgl_akhir) > 0){
		// 	$dateFilterQuery = " AND time_req >= '".$tgl_awal."' AND time_rsp <= '".$tgl_akhir."' ";
		// }
		if(strlen($tgl_awal) > 0) $dateFilterQuery = " AND time_req >= '".$tgl_awal."' ";
		if(strlen($tgl_akhir) > 0) $dateFilterQuery = $dateFilterQuery." AND time_rsp <= '".$tgl_akhir."' ";

		if(strlen($amount_fr) > 0) $amountFilterQuery = " AND RAW_AMOUNT >= '".$amount_fr."' ";
		if(strlen($amount_to) > 0) $amountFilterQuery = $amountFilterQuery." AND RAW_AMOUNT <= '".$amount_to."' ";

		if(strlen($refnum) > 0) $refnumFilterQuery = " AND REFF_NO = '".$refnum."' ";

		if(strlen($status) > 0){
			if($status[0] == "All"){ 
				$statusFilterQuery = " AND [status] in (".$status.") ";
			}
		}
				
		
		if(strlen($amountFilterQuery) > 0 || strlen($refnumFilterQuery) > 0  || strlen($statusFilterQuery) > 0){
			$filter_data = " WHERE id is not null ".$amountFilterQuery.$refnumFilterQuery.$statusFilterQuery;
		}

		$query ="SELECT 
		COUNT(*) AS 'num_row', 
		format(sum(RAW_AMOUNT), 'N2') as total_amount
		FROM
		( ".$this->getEmoneyQuery($merchant_id, $dateFilterQuery).
		" UNION ".
		$this->getJalinQuery($merchant_id, $dateFilterQuery).
		" ) AS myDerivedTable ".
		$filter_data;

		
		// var_dump($query);
		// exit();
		$stmt = $pdo->prepare($query);
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
		return $retval[0];
	}

	public function get_total_amount_filtered($merchant_id, $dateFilter)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";

		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_rsp <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_rsp <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
		}


        $sql = "SELECT DISTINCT(merchant_name), FORMAT(SUM(amount),'N0') AS amount, COUNT(merchant_name) as transCount
		FROM (SELECT om.name as merchant_name,
				amount_tran_req AS amount
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id 					
				WHERE 
				tran_type IN ( '50', '00' ) 
				AND otrx.merchant_id=$merchant_id
				$dateFilterQuery
			) AS transTable
			GROUP BY merchant_name 
			ORDER BY merchant_name ASC";

	 
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
	
	public function get_total_transaction_filtered($merchant_id, $issuer, $dateFilter)
    {
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";

		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_rsp <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_rsp <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
		}

		if ($issuer == "All"){
			$issuer = "'ShopeePayIncoming', 'OvoIncoming', 'GPayApp', 'LinkAjaIncoming'";
		} else {
			$issuer = rtrim($issuer, ", ");
		}

        $sql = "SELECT DISTINCT(merchant_name), FORMAT(SUM(amount),'N0') AS amount, COUNT(merchant_name) as transCount
		FROM
				(SELECT                                          
				CASE
				WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
				WHEN source_node = 'OvoIncoming' THEN 'OVO'
				WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
				ELSE 'GPay'
				END AS source_of_fund,
				CONVERT(CHAR(23),time_req,121) AS trx_time, 
				CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					ELSE 'refund'
				END AS trx_type,
				om.name AS merchant_name,
				otrx.term_id,
				otrx.merchant_id,
				os.name AS store_name,
				amount_tran_req AS amount,
				reff_number_2 AS edc_refnum,
				JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.approvalCode') AS issuer_refnum,
				resp_code_rsp AS response_code,
				ISNULL(JSON_VALUE(CAST(REPLACE(concat(N'',node_data_req,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F55)[1]','nvarchar(MAX)'),'$.qrtype'),'12') AS QRType,
				CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
				END AS flag,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
				CASE 
					WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '00' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
					WHEN tran_type = '20' and resp_code_rsp = '00' THEN 'refund'
					END AS status
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_terminal ot ON otrx.term_id = ot.term_id JOIN
				office_store os ON ot.store_id = os.store_id JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id        
				WHERE 
				source_node IN ($issuer) 
				AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
				$dateFilterQuery
				AND otrx.merchant_id=$merchant_id
				) AS myDerivedTable
				GROUP BY merchant_name
				ORDER BY merchant_name DESC";

	 
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