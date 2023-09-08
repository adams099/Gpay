<?php

//field
//timestamp
//issuer
//terminal ID
//Store
//amount
//Ref num


defined('BASEPATH') OR exit('No direct script access allowed');

class Ppob_transaction_history_model extends CI_Model {

	public function get_total_amount($merchant_id)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";

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
				tran_type,
				CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					WHEN tran_type = '39' THEN 'advice'
					ELSE 'refund'
				END AS trx_type,
				otrx.term_id,
				otrx.merchant_id,
				om.name as merchant_name,
				os.name AS store_name,
				amount_tran_req AS amount,
				reff_number_2 AS edc_refnum,
				resp_code_rsp AS response_code,
				CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
				END AS flag,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
				CASE 
					WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
					WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
					END AS status,
				CASE
					WHEN DATALENGTH(node_data_rsp) > 0 THEN
						CASE
							WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
							WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
							WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
							WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
							WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
							WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
						END
					ELSE ''
				END AS issuer_refnum
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_terminal ot ON otrx.term_id = ot.term_id JOIN
				office_store os ON ot.store_id = os.store_id JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id 
				WHERE 
				tran_type in ('39','50') 
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
	
	public function get_total_transaction($merchant_id, $dateFilter)
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
		FROM
				(SELECT                                          
				CASE
				WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
				WHEN source_node = 'OvoIncoming' THEN 'OVO'
				WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
				ELSE 'GPay'
				END AS source_of_fund,
				CONVERT(CHAR(23),time_req,121) AS trx_time, 
				tran_type,
				CASE 
					WHEN tran_type IN ('50','00') THEN 'payment'
					WHEN tran_type = '39' THEN 'advice'
					ELSE 'refund'
				END AS trx_type,
				otrx.term_id,
				otrx.merchant_id,
				om.name as merchant_name,
				os.name AS store_name,
				amount_tran_req AS amount,
				reff_number_2 AS edc_refnum,
				resp_code_rsp AS response_code,
				CASE 
					WHEN resp_code_adv = '00' THEN '1'
					ELSE '0'
				END AS flag,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
				CASE 
					WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
					WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
					WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
					WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
				END AS status,
				CASE
					WHEN DATALENGTH(node_data_rsp) > 0 THEN
						CASE
						WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
							WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
							WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
							WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
							WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
							WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
						END
					ELSE ''
				END AS issuer_refnum
				FROM  
				office_trans otrx WITH (NOLOCK) 
				JOIN
				office_terminal ot ON otrx.term_id = ot.term_id JOIN
				office_store os ON ot.store_id = os.store_id JOIN
				office_merchant om ON otrx.merchant_id = om.merchant_id        
				WHERE 
				tran_type in ('39','50')
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
		tran_type,
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			WHEN tran_type = '39' THEN 'advice'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		om.name as merchant_name,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		resp_code_rsp AS response_code,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
			END AS status,
		CASE
			WHEN DATALENGTH(node_data_rsp) > 0 THEN
				CASE
					WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
					WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
				END
			ELSE ''
		END AS issuer_refnum
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		tran_type in ('39','50')
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		AND otrx.merchant_id='4419999'
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

	public function get_store_by_merchant_id($merchant_id)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$sql = "SELECT store_id, name, email
		FROM office_store
		WHERE merchant_id = $merchant_id";
		
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
		tran_type,
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			WHEN tran_type = '39' THEN 'advice'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		resp_code_rsp AS response_code,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
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
	public function get_data_filtered_by_param($merchant_id, $dateFilter, $num = NULL, $offset = NULL)
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
		tran_type,
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			WHEN tran_type = '39' THEN 'advice'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		om.name as merchant_name,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		resp_code_rsp AS response_code,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
			END AS status,
		CASE
			WHEN DATALENGTH(node_data_rsp) > 0 THEN
				CASE
					WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
					WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
				END
			ELSE ''
		END AS issuer_refnum
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		tran_type in ('39','50')
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		$dateFilterQuery
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

	public function get_total_row_trans_hist_filtered($merchant_id, $dateFilter)
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
		tran_type,
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			WHEN tran_type = '39' THEN 'advice'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		om.name as merchant_name,
		os.name AS store_name,
		FORMAT(amount_tran_req,'N0') AS amount,
		reff_number_2 AS edc_refnum,
		resp_code_rsp AS response_code,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
			END AS status,
		CASE
			WHEN DATALENGTH(node_data_rsp) > 0 THEN
				CASE
					WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
					WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
				END
			ELSE ''
		END AS issuer_refnum
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		tran_type in ('39','50')
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		$dateFilterQuery
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
	
	public function get_total_transaction_filtered($merchant_id, $dateFilter)
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
		FROM
				(SELECT                                          
		CASE
		WHEN source_node = 'ShopeePayIncoming' THEN 'ShopeePay'
		WHEN source_node = 'OvoIncoming' THEN 'OVO'
		WHEN source_node = 'LinkAjaIncoming' THEN 'LinkAja'
		ELSE 'GPay'
		END AS source_of_fund,
		CONVERT(CHAR(23),time_req,121) AS trx_time, 
		tran_type,
		CASE 
			WHEN tran_type IN ('50','00') THEN 'payment'
			WHEN tran_type = '39' THEN 'advice'
			ELSE 'refund'
		END AS trx_type,
		otrx.term_id,
		otrx.merchant_id,
		om.name as merchant_name,
		os.name AS store_name,
		amount_tran_req AS amount,
		reff_number_2 AS edc_refnum,
		resp_code_rsp AS response_code,
		CASE 
			WHEN resp_code_adv = '00' THEN '1'
			ELSE '0'
		END AS flag,
		COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS date_settle,
		CASE 
			WHEN tran_type = '50' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '50' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '50' and resp_code_rsp <> '00' THEN 'failed'
			WHEN tran_type = '39' and resp_code_rsp = '00' THEN 'paid'
			WHEN tran_type = '39' and resp_code_rsp in ('68','') THEN 'unpaid'
			WHEN tran_type = '39' and resp_code_rsp <> '00' THEN 'failed'
			END AS status,
		CASE
			WHEN DATALENGTH(node_data_rsp) > 0 THEN
				CASE
					WHEN receiving_inst_id in ('711001','711002','711003','711005','711006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('712001','712002','712003','712005','712006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.serial_number')
					WHEN receiving_inst_id in ('713001','713002','713003','713005','713006') and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.reference_no')
					WHEN receiving_inst_id = '900502' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900501' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.pln_reference_number')
					WHEN receiving_inst_id = '900801' and resp_code_rsp = '00' THEN JSON_VALUE(CAST(REPLACE(concat(N'',node_data_rsp,''),'&','&amp;') AS xml).value('(/Iso8583Xml/F61)[1]','nvarchar(MAX)'),'$.switch_refnum')
				END
			ELSE ''
		END AS issuer_refnum
		FROM  
		office_trans otrx WITH (NOLOCK) 
		JOIN
		office_terminal ot ON otrx.term_id = ot.term_id JOIN
		office_store os ON ot.store_id = os.store_id JOIN
		office_merchant om ON otrx.merchant_id = om.merchant_id        
		WHERE 
		tran_type in ('39','50')
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
