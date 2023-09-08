<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard_model extends CI_Model
{

	public function get_merchant_summary_data($issuer, $merchant_id, $dateFilter)
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
		
		$sql="SELECT DISTINCT(merchant_name), FORMAT(SUM(amount),'N0') AS amount, COUNT(merchant_name) as transCount FROM
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
		om.name AS merchant_name,
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
		source_node IN ( $issuer ) 
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

	public function get_issuer_summary_data($issuer, $merchant_id, $dateFilter)
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
		
		$sql="SELECT DISTINCT(source_of_fund), FORMAT(SUM(amount),'N0') AS amount, COUNT(source_of_fund) as transCount FROM
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
		om.name AS merchant_name,
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
		source_node IN ( $issuer ) 
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

			$retval = $stmt->fetchAll();
			$stmt = null;
			$pdo = null;
			return $retval;
	}

	public function get_sales_summary_data($issuer, $merchant_id, $dateFilter)
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
		
		$sql="SELECT trx_time, amount FROM
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
		om.name AS merchant_name,
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
		source_node IN ( $issuer ) 
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		$dateFilterQuery
		AND otrx.merchant_id=$merchant_id
		) AS myDerivedTable
		ORDER BY trx_time DESC";
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

	public function get_sales_summary_last($issuer, $merchant_id, $dateFilter)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		
		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE()-1 as DATE),' 00:00:00.000')
			AND time_rsp <= concat(CAST(GETDATE()-1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(day, -7 ,DATEADD(wk, DATEDIFF(wk,0,GETDATE()), 0))
			AND time_rsp <= concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_rsp <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
		}
		if ($issuer == "All"){
			$issuer = "'ShopeePayIncoming', 'OvoIncoming', 'GPayApp', 'LinkAjaIncoming'";
		} else {
			$issuer = rtrim($issuer, ", ");
		}
		
		$sql="SELECT trx_time, amount FROM
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
		om.name AS merchant_name,
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
		source_node IN ( $issuer ) 
		AND ((resp_code_rsp = '00' and resp_code_req != '68') OR resp_code_adv='00')
		$dateFilterQuery
		AND otrx.merchant_id=$merchant_id
		) AS myDerivedTable
		ORDER BY trx_time DESC";

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