<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class VA_transaction_history_model extends CI_Model {

	//========== VA MERCHANT GROUPING (FOR "GPAY" USER ONLY)
	
	function get_va_merchant_group_data_from_id($merchant_dashboard_id)
	{
        //load CI instance
		$CI = & get_instance();
        $pdo = $CI->global_library->open_pdo_office();
        
        $sql = "SELECT b.merchant_dashboard_id, a.merchant_id, a.receiving_inst_id 
        FROM merchant_dashboard_login_va_merchant_group_mapping a
        LEFT JOIN merchant_dashboard_login_va_merchant_group_role b ON a.merchant_group_role = b.merchant_group_role
        WHERE b.merchant_dashboard_id = ".$merchant_dashboard_id;

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

	public function get_va_history($merchant_id, $receiving_inst_id, $num = NULL, $offset = NULL, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		$multi_merchant_flag = false;

		if(!empty($merchant_dashboard_id)){
			$merchant_group_data = $this->get_va_merchant_group_data_from_id($merchant_dashboard_id);
			$group_data_count = count($merchant_group_data);
			if($group_data_count > 0){
				$multi_merchant_flag = true;
			}else{
				$multi_merchant_flag = false;
			}
		}
		if($multi_merchant_flag){
			$sql = "";
			$first_sql_flag = true;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '39789'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "WITH sw_trans_filtered   
							AS  
							(  
							select *
							from sw_trans
							where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id' 
							AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
							AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')  
							group by receiving_inst_id, account_number_1)
							) 
					SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					cust_va_number AS va_number,
					cust_name AS customer_name,
					CONVERT(CHAR(23),time_paid,121) AS time_trx,
					FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
					cust_email AS cust_email,
					cust_phone AS cust_phone,
					mapemall_order_number AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN len(bca_refnum) > 0 THEN 'Paid'
						WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
						WHEN flag = '0' THEN 'Unpaid'
						WHEN flag = '2' THEN 'Canceled'
						ELSE null
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM mapemall_va_trans 
					LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
					LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) 
					AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $part_sql." UNION ". $sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '39993'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)
					AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '7137'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					CONCAT(receiving_inst_id, account_number_1) AS va_number,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
					account_number_1 AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BNI' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  
					AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}

				}
			}
		
			$sql = $sql." ORDER BY time_trx DESC OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
		}else{ 
			if($receiving_inst_id == '39789'){
				$sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'
						AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
						AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')   
						group by receiving_inst_id, account_number_1)
						) 
				SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) 
				AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				ORDER BY time_paid DESC
				OFFSET $offset ROWS
				FETCH NEXT $num ROWS ONLY";
			}
			elseif($receiving_inst_id == '39993'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) 
				AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				ORDER BY time_rsp DESC
				OFFSET $offset ROWS
				FETCH NEXT $num ROWS ONLY";
			}
			elseif($receiving_inst_id == '7137'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				CONCAT(receiving_inst_id, account_number_1) AS va_number,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
				account_number_1 AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BNI' AS bank_issuer 
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  
				AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				ORDER BY time_rsp DESC
				OFFSET $offset ROWS
				FETCH NEXT $num ROWS ONLY";
			}
		}

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

	public function get_total_row_trans_hist($merchant_id, $receiving_inst_id, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		$multi_merchant_flag = false;

		if(!empty($merchant_dashboard_id)){
			$merchant_group_data = $this->get_va_merchant_group_data_from_id($merchant_dashboard_id);
			$group_data_count = count($merchant_group_data);
			if($group_data_count > 0){
				$multi_merchant_flag = true;
			}else{
				$multi_merchant_flag = false;
			}
		}
		if($multi_merchant_flag){
			$sql = "";
			$first_sql_flag = true;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '39789'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$first_sql = "WITH sw_trans_filtered   
					AS  
					(  
					select *
					from sw_trans
					where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '39789' 
					AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
					AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')   
					group by receiving_inst_id, account_number_1)
					)";

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					cust_va_number AS va_number,
					cust_name AS customer_name,
					CONVERT(CHAR(23),time_paid,121) AS time_trx,
					FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
					cust_email AS cust_email,
					cust_phone AS cust_phone,
					mapemall_order_number AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN len(bca_refnum) > 0 THEN 'Paid'
						WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
						WHEN flag = '0' THEN 'Unpaid'
						WHEN flag = '2' THEN 'Canceled'
						ELSE null
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM mapemall_va_trans 
					LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
					LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)
					AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $first_sql. "SELECT count(*) as num_rows FROM (" . $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $first_sql. $sql. " UNION ".$part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '39993'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) 
					AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = "SELECT count(*) AS num_rows FROM (".$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql. " UNION ". $part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '7137'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					CONCAT(receiving_inst_id, account_number_1) AS va_number,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
					account_number_1 AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BNI' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) 
					AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
					AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
					
					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = "SELECT count(*) AS num_rows FROM (".$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql. " UNION ". $part_sql;
					}
				}
			}
			$sql = $sql.") AS myDerivedTable";
		}else{ 
			if($receiving_inst_id == '39789'){
				$sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id' 
						AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
						AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')   
						group by receiving_inst_id, account_number_1)
						) 
				SELECT 
				COUNT(*) AS 'num_rows'
				FROM(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) 
				AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				) AS myDerivedTable";
			}
			elseif($receiving_inst_id == '39993'){
				$sql = "SELECT 
				COUNT(*) AS 'num_rows'
				FROM(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon
				,'BCA' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) 
				AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				) AS myDerivedTable";

			}
			elseif($receiving_inst_id == '7137'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				CONCAT(receiving_inst_id, account_number_1) AS va_number,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
				account_number_1 AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon
				,'BNI' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  
				AND time_rsp >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_rsp <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				) AS myDerivedTable";
			}
		}

		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}
		if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$num_row = $row['num_rows'];
		}else{
			$num_row = 0;
		}
		$stmt = null;
		$pdo = null;
		return $num_row;
	}
	
	//==========FILTERED
	public function get_va_history_filtered($search_param, $merchant_id, $receiving_inst_id, $num = NULL, $offset = NULL, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		$multi_merchant_flag = false;

		if(!empty($merchant_dashboard_id)){
			$merchant_group_data = $this->get_va_merchant_group_data_from_id($merchant_dashboard_id);
			$group_data_count = count($merchant_group_data);
			if($group_data_count > 0){
				$multi_merchant_flag = true;
			}else{
				$multi_merchant_flag = false;
			}
		}
		if($multi_merchant_flag){
			$sql = "";
			$first_sql_flag = true;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '39789'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$first_sql = "WITH sw_trans_filtered   
							AS  
							(  
							select *
							from sw_trans
							where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'";
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
					}		
					$first_sql = $first_sql. " group by receiving_inst_id, account_number_1))";


					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					cust_va_number AS va_number,
					cust_name AS customer_name,
					CONVERT(CHAR(23),time_paid,121) AS time_trx,
					FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
					cust_email AS cust_email,
					cust_phone AS cust_phone,
					mapemall_order_number AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN len(bca_refnum) > 0 THEN 'Paid'
						WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
						WHEN flag = '0' THEN 'Unpaid'
						WHEN flag = '2' THEN 'Canceled'
						ELSE null
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM mapemall_va_trans 
					LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
					LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND len(bca_refnum) > 0";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND flag = '0'";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $first_sql.$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $first_sql.$part_sql." UNION ". $sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '39993'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}


					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '7137'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					CONCAT(receiving_inst_id, account_number_1) AS va_number,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
					account_number_1 AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BNI' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}

				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$sql = $sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$sql = $sql. " time_trx ASC";
					}
					else{
						$sql = $sql. " time_trx DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$sql = $sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$sql = $sql. " mapemall_va_trans.amount ASC";
					}
					else{
						$sql = $sql. " mapemall_va_trans.amount DESC";
					}
				}
			}
			else{
				//default order by
				$sql = $sql. " ORDER BY time_trx DESC";
			}
			if(isset($num) && isset($offset)){
				$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
		}else{ 
			if($receiving_inst_id == '39789'){
				$first_sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'";

				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
				}
						
				$first_sql =$first_sql. " group by receiving_inst_id, account_number_1))";
						
				$part_sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) ";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$part_sql = $part_sql. " AND len(bca_refnum) > 0";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$part_sql = $part_sql. " AND flag = '0'";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$part_sql = $part_sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$part_sql = $part_sql. " time_paid ASC";
						}
						else{
							$part_sql = $part_sql. " time_paid DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$part_sql = $part_sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$part_sql = $part_sql. " mapemall_va_trans.amount ASC";
						}
						else{
							$part_sql = $part_sql. " mapemall_va_trans.amount DESC";
						}
					}
				}
				else{
					//default order by
					$part_sql = $part_sql. " ORDER BY time_paid DESC";
				}
				if(isset($num) && isset($offset)){
					$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}
				$sql = $first_sql.$part_sql;
			}
			elseif($receiving_inst_id == '39993'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$sql = $sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$sql = $sql. " time_rsp ASC";
						}
						else{
							$sql = $sql. " time_rsp DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$sql = $sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$sql = $sql. " amount_tran_req ASC";
						}
						else{
							$sql = $sql. " amount_tran_req DESC";
						}
					}
				}else{
					//default order by
					$sql = $sql. " ORDER BY time_rsp DESC";
				}
				if(isset($num) && isset($offset)){
					$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}

			}
			elseif($receiving_inst_id == '7137'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				CONCAT(receiving_inst_id, account_number_1) AS va_number,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.customer_email') AS cust_email,
				account_number_1 AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BNI' AS bank_issuer 
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$sql = $sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$sql = $sql. " time_rsp ASC";
						}
						else{
							$sql = $sql. " time_rsp DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$sql = $sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$sql = $sql. " amount_tran_req ASC";
						}
						else{
							$sql = $sql. " amount_tran_req DESC";
						}
					}
				}else{
					//default order by
					$sql = $sql. " ORDER BY time_rsp DESC";
				}
				if(isset($num) && isset($offset)){
					$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}
			}
		}

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

	public function get_total_row_trans_hist_filtered($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		$multi_merchant_flag = false;

		if(!empty($merchant_dashboard_id)){
			$merchant_group_data = $this->get_va_merchant_group_data_from_id($merchant_dashboard_id);
			$group_data_count = count($merchant_group_data);
			if($group_data_count > 0){
				$multi_merchant_flag = true;
			}else{
				$multi_merchant_flag = false;
			}
		}
		if($multi_merchant_flag){
			$sql = "";
			$first_sql_flag = true;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '39789'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$first_sql = "WITH sw_trans_filtered   
					AS  
					(  
					select *
					from sw_trans
					where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '39789'";

					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
					}
					
					$first_sql = $first_sql." group by receiving_inst_id, account_number_1))";

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					cust_va_number AS va_number,
					cust_name AS customer_name,
					CONVERT(CHAR(23),time_paid,121) AS time_trx,
					FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
					cust_email AS cust_email,
					cust_phone AS cust_phone,
					mapemall_order_number AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN len(bca_refnum) > 0 THEN 'Paid'
						WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
						WHEN flag = '0' THEN 'Unpaid'
						WHEN flag = '2' THEN 'Canceled'
						ELSE null
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM mapemall_va_trans 
					LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
					LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
					(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND len(bca_refnum) > 0";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND flag = '0'";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $first_sql. "SELECT count(*) as num_rows FROM (" . $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $first_sql. $sql. " UNION ".$part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '39993'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  AND
					(sw_trans.merchant_id= '$merchant_id' OR sw_trans.merchant_id IS NULL)";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = "SELECT count(*) AS num_rows FROM (".$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql. " UNION ". $part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '7137'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					CONCAT(receiving_inst_id, account_number_1) AS va_number,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
					account_number_1 AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BNI' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  AND
					(sw_trans.merchant_id= '$merchant_id' OR sw_trans.merchant_id IS NULL)";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = "SELECT count(*) AS num_rows FROM (".$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql. " UNION ". $part_sql;
					}
				}
			}
			$sql = $sql.") AS myDerivedTable";
		}else{ 
			if($receiving_inst_id == '39789'){
				$first_sql = "WITH sw_trans_filtered   
				AS  
				(  
				select *
				from sw_trans
				where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id' ";
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
				}
				$first_sql = $first_sql." group by receiving_inst_id, account_number_1))";
				$part_sql = "SELECT 
				COUNT(*) AS 'num_rows'
				FROM
				(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
				(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$part_sql = $part_sql. " AND len(bca_refnum) > 0";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$part_sql = $part_sql. " AND flag = '0'";
						}
					}
				}
				$part_sql = $part_sql. ") AS myDerivedTable";
				$sql = $first_sql.$part_sql;
			}
			elseif($receiving_inst_id == '39993'){
				$sql = "SELECT 
				COUNT(*) AS 'num_rows'
				FROM
				(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  AND
				(sw_trans.merchant_id= '$merchant_id' OR sw_trans.merchant_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}
				$sql = $sql. ") AS myDerivedTable";
			}
			elseif($receiving_inst_id == '7137'){
				$sql = "SELECT 
				COUNT(*) AS 'num_rows'
				FROM
				(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				CONCAT(receiving_inst_id, account_number_1) AS va_number,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
				account_number_1 AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BNI' AS bank_issuer 
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL)  AND
				(sw_trans.merchant_id= '$merchant_id' OR sw_trans.merchant_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}

				$sql = $sql. ") AS myDerivedTable";
			}
		}
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();

		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}
		if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$num_row = $row['num_rows'];
		}else{
			$num_row = 0;
		}
		$stmt = null;
		$pdo = null;
		return $num_row;
	}

	public function get_va_history_export($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		
		$multi_merchant_flag = false;

		if(!empty($merchant_dashboard_id)){
			$merchant_group_data = $this->get_va_merchant_group_data_from_id($merchant_dashboard_id);
			$group_data_count = count($merchant_group_data);
			if($group_data_count > 0){
				$multi_merchant_flag = true;
			}else{
				$multi_merchant_flag = false;
			}
		}
		if($multi_merchant_flag){
			$sql = "";
			$first_sql_flag = true;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '39789'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$first_sql = "WITH sw_trans_filtered   
							AS  
							(  
							select *
							from sw_trans
							where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'";
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
					}		
					$first_sql = $first_sql. " group by receiving_inst_id, account_number_1))";


					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					cust_va_number AS va_number,
					cust_name AS customer_name,
					CONVERT(CHAR(23),time_paid,121) AS time_trx,
					FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
					cust_email AS cust_email,
					cust_phone AS cust_phone,
					mapemall_order_number AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN len(bca_refnum) > 0 THEN 'Paid'
						WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
						WHEN flag = '0' THEN 'Unpaid'
						WHEN flag = '2' THEN 'Canceled'
						ELSE null
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM mapemall_va_trans 
					LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
					LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND len(bca_refnum) > 0";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND flag = '0'";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $first_sql.$part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $first_sql.$part_sql." UNION ". $sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '39993'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BCA' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}


					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '7137'){
					$receiving_inst_id = $merchant_group['receiving_inst_id'];
					$merchant_id = $merchant_group['merchant_id'];

					$part_sql = "SELECT
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
					CONCAT(receiving_inst_id, account_number_1) AS va_number,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_email') AS cust_email,
					account_number_1 AS cust_phone,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
					CONVERT(VARCHAR,time_req,112) AS credit_date,
					FORMAT(1000,'N0') AS cost_bca,
					COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
					JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
					CASE 
						WHEN resp_code_rsp = '00' THEN 'Paid'
						WHEN resp_code_rsp = '01' THEN 'Failed'
						ELSE 'Failed'
					END AS response_message,    
					CASE WHEN resp_code_adv = '00'
						THEN '1' 
						ELSE '0' 
					END AS flag_recon,
					'BNI' AS bank_issuer
					FROM sw_trans 
					LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
					WHERE 
					(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
					(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
					(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

					if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
					}
					if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
					}
					if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
						$part_sql = $part_sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
					}
					if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
						$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
					}
					if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
						$part_sql = $part_sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
					}
					if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
						$part_sql = $part_sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
					}
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
							//do nothing, get all data
						}else{
							if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
								$part_sql = $part_sql. " AND resp_code_rsp = '00'";
							}
							if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
								$part_sql = $part_sql. " AND resp_code_rsp != '00'";
							}
							if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
								$part_sql = $part_sql. " AND resp_code_rsp IS NULL";
							}
						}
					}

					if($first_sql_flag){
						$first_sql_flag = false;
						$sql = $part_sql;
					}else{
						$first_sql_flag = false;
						$sql = $sql." UNION ". $part_sql;
					}

				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$sql = $sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$sql = $sql. " time_trx ASC";
					}
					else{
						$sql = $sql. " time_trx DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$sql = $sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$sql = $sql. " mapemall_va_trans.amount ASC";
					}
					else{
						$sql = $sql. " mapemall_va_trans.amount DESC";
					}
				}
			}
			else{
				//default order by
				$sql = $sql. " ORDER BY time_trx DESC";
			}
			if(isset($num) && isset($offset)){
				$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
		}else{ 
			if($receiving_inst_id == '39789'){
				$first_sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'";

				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
				}
						
				$first_sql =$first_sql. " group by receiving_inst_id, account_number_1))";
						
				$part_sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$part_sql = $part_sql. " AND len(bca_refnum) > 0";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$part_sql = $part_sql. " AND flag = '0'";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$part_sql = $part_sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$part_sql = $part_sql. " time_paid ASC";
						}
						else{
							$part_sql = $part_sql. " time_paid DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$part_sql = $part_sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$part_sql = $part_sql. " mapemall_va_trans.amount ASC";
						}
						else{
							$part_sql = $part_sql. " mapemall_va_trans.amount DESC";
						}
					}
				}
				else{
					//default order by
					$part_sql = $part_sql. " ORDER BY time_paid DESC";
				}
				if(isset($num) && isset($offset)){
					$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}
				$sql = $first_sql.$part_sql;
			}
			elseif($receiving_inst_id == '39993'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber') AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$sql = $sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$sql = $sql. " time_rsp ASC";
						}
						else{
							$sql = $sql. " time_rsp DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$sql = $sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$sql = $sql. " amount_tran_req ASC";
						}
						else{
							$sql = $sql. " amount_tran_req DESC";
						}
					}
				}else{
					//default order by
					$sql = $sql. " ORDER BY time_rsp DESC";
				}
				if(isset($num) && isset($offset)){
					$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}

			}
			elseif($receiving_inst_id == '7137'){
				$sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				CONCAT(receiving_inst_id, account_number_1) AS va_number,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.customer_name') AS customer_name,
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
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.customer_email') AS cust_email,
				account_number_1 AS cust_phone,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.data.reference_number') AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN resp_code_rsp = '00' THEN 'Paid'
					WHEN resp_code_rsp = '01' THEN 'Failed'
					ELSE 'Failed'
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BNI' AS bank_issuer 
				FROM sw_trans 
				LEFT JOIN sw_nodes ON sw_trans.node_id_in = sw_nodes.node_id		   
				WHERE 
				(sw_trans.tran_type='50' OR sw_trans.tran_type IS NULL) AND
				(sw_nodes.node_name='BNI' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans.receiving_inst_id ='$receiving_inst_id' OR sw_trans.receiving_inst_id IS NULL) ";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerName')) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.CustomerNumber')) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$sql = $sql. " AND LOWER(JSON_VALUE(CAST(REPLACE(CAST(node_data_rsp as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F48)[1]','varchar(max)'), '$.cust_info.cust_email')) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(CONCAT(receiving_inst_id, account_number_1)) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$sql = $sql. " AND time_rsp >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$sql = $sql. " AND time_rsp <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$sql = $sql. " AND resp_code_rsp = '00'";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$sql = $sql. " AND resp_code_rsp != '00'";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$sql = $sql. " AND resp_code_rsp IS NULL";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$sql = $sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$sql = $sql. " time_rsp ASC";
						}
						else{
							$sql = $sql. " time_rsp DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$sql = $sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$sql = $sql. " amount_tran_req ASC";
						}
						else{
							$sql = $sql. " amount_tran_req DESC";
						}
					}
				}else{
					//default order by
					$sql = $sql. " ORDER BY time_rsp DESC";
				}
				if(isset($num) && isset($offset)){
					$sql = $sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}
			}
		}

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

	//==========FILTERED


	public function get_detil_va_history($va_number)
	{
		$receiving_inst_id=substr($va_number,0,5);
		$account_number_1=substr($va_number,5);
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		
        $sql = "SELECT
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
		receiving_inst_id = $receiving_inst_id AND
		account_number_1 = $account_number_1 AND
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-01-03 00:00:00.000' AND
		time_req <= '2021-03-22 23:59:59.999'
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

	public function get_total_transaction($merchant_id, $issuer, $dateFilter)
    {
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_req <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_req <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
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
		AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
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

	public function get_total_row_trans_hist_old($merchant_id)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";
		$sql = "SELECT COUNT(*) as num_row
		FROM  
		office_trans WITH (NOLOCK)  
		WHERE 
		tran_type='50' AND
		source_node='BCA' AND
		(resp_code_rsp='00' OR resp_code_adv='00') AND
		time_req >= '2021-02-03 00:00:00.000' AND
		time_req <= '2021-02-03 23:59:59.999'";

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

	public function get_total_amount_filtered($merchant_id, $dateFilter)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_office();
		$merchant_id = "'".$merchant_id."'";

		$dateFilterQuery = "";

		if ($dateFilter == 'today'){
			$dateFilterQuery = "AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_req <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_req <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
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
			AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
		} else if($dateFilter == 'weekly'){
			$dateFilterQuery = "AND time_req >=  concat(DATEADD(DAY, 1 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 00:00:00.000')
			AND time_req <= concat(DATEADD(DAY, 7 - DATEPART(WEEKDAY, GETDATE()), CAST(GETDATE() AS DATE)),' 23:59:59.999')";
		} else if($dateFilter == 'monthly'){
			$dateFilterQuery = "AND time_req >=  DATEADD(m, DATEDIFF(m, 0, CAST(GETDATE() as DATE)), 0)
			AND time_req <= concat(CONVERT(date, CAST(eomonth(GETDATE()) AS datetime)),' 23:59:59.999')";
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

	public function get_va_history_unpaid_only($merchant_id, $receiving_inst_id, $num = NULL, $offset = NULL, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		 
			if($receiving_inst_id == '39789'){
				$sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'
						AND merchant_id = '$merchant_id'  
						AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
						AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')   
						group by receiving_inst_id, account_number_1)
						) 
				SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				flag = '0' AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
				(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)
				AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				ORDER BY time_paid DESC
				OFFSET $offset ROWS
				FETCH NEXT $num ROWS ONLY";
			}
		
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

	public function get_total_row_trans_hist_unpaid($merchant_id, $receiving_inst_id, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();

	
			if($receiving_inst_id == '39789'){
				$sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id' 
						AND merchant_id = '$merchant_id'  
						AND time_req >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000') 
						AND time_req <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')   
						group by receiving_inst_id, account_number_1)
						) 
				SELECT 
				COUNT(*) AS 'num_rows'
				FROM(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				flag = '0' AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
				(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)
				AND time_paid >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
				AND time_paid <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
				) AS myDerivedTable";
			}
			

		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();
		
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}
		if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$num_row = $row['num_rows'];
		}else{
			$num_row = 0;
		}
		$stmt = null;
		$pdo = null;
		return $num_row;
	}

	public function get_total_row_trans_hist_unpaid_filtered($search_param, $merchant_id, $receiving_inst_id, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
	
		
			if($receiving_inst_id == '39789'){
				$first_sql = "WITH sw_trans_filtered   
				AS  
				(  
				select *
				from sw_trans
				where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id' ";
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
				}
				$first_sql = $first_sql." group by receiving_inst_id, account_number_1))";
				$part_sql = "SELECT 
				COUNT(*) AS 'num_rows'
				FROM
				(SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				flag = '0' AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
				(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$part_sql = $part_sql. " AND len(bca_refnum) > 0";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$part_sql = $part_sql. " AND flag = '0'";
						}
					}
				}
				$part_sql = $part_sql. ") AS myDerivedTable";
				$sql = $first_sql.$part_sql;
			}
			
		
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute();

		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}
		if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$num_row = $row['num_rows'];
		}else{
			$num_row = 0;
		}
		$stmt = null;
		$pdo = null;
		return $num_row;
	}

	public function get_va_history_unpaid_filtered($search_param, $merchant_id, $receiving_inst_id, $num = NULL, $offset = NULL, $merchant_dashboard_id = NULL)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		$multi_merchant_flag = false;

		
		 
			if($receiving_inst_id == '39789'){
				$first_sql = "WITH sw_trans_filtered   
						AS  
						(  
						select *
						from sw_trans
						where tran_nr IN (select max(tran_nr) from sw_trans where receiving_inst_id = '$receiving_inst_id'";

				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$first_sql = $first_sql. " AND time_req >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$first_sql = $first_sql. " AND time_req <= '". strval($search_param['tgl_akhir'])."'";
				}
						
				$first_sql =$first_sql. " group by receiving_inst_id, account_number_1))";
						
				$part_sql = "SELECT
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.Reference') AS refnum_bca,
				cust_va_number AS va_number,
				cust_name AS customer_name,
				CONVERT(CHAR(23),time_paid,121) AS time_trx,
				FORMAT(mapemall_va_trans.amount,'N0') AS amount,
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
				cust_email AS cust_email,
				cust_phone AS cust_phone,
				mapemall_order_number AS order_number,
				CONVERT(VARCHAR,time_req,112) AS credit_date,
				FORMAT(1000,'N0') AS cost_bca,
				COALESCE(NULLIF(date_settlement_out,''), CONVERT(VARCHAR,DATEADD(DAY,1,time_req),112)) AS debet_cost_date,
				JSON_VALUE(CAST(REPLACE(CAST(node_data_req as varchar(max)),'&','&amp;')  AS xml).value('(/Iso8583Xml/F61)[1]','varchar(max)'), '$.RequestID') AS bca_request_id,
				CASE 
					WHEN len(bca_refnum) > 0 THEN 'Paid'
					WHEN auth_tran is null and node_data_req is not null THEN 'Failed'
					WHEN flag = '0' THEN 'Unpaid'
					WHEN flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,    
				CASE WHEN resp_code_adv = '00'
					THEN '1' 
					ELSE '0' 
				END AS flag_recon,
				'BCA' AS bank_issuer
				FROM mapemall_va_trans 
				LEFT JOIN sw_trans_filtered ON sw_trans_filtered.receiving_inst_id = SUBSTRING(mapemall_va_trans.cust_va_number,1,5) AND sw_trans_filtered.account_number_1 = SUBSTRING(mapemall_va_trans.cust_va_number,6,11)
				LEFT JOIN sw_nodes ON sw_trans_filtered.node_id_in = sw_nodes.node_id		   
				WHERE 
				flag = '0' AND
				(sw_nodes.node_name='BCA' OR sw_nodes.node_name IS NULL) AND	
				(sw_trans_filtered.receiving_inst_id ='$receiving_inst_id' OR sw_trans_filtered.receiving_inst_id IS NULL)  AND
				(sw_trans_filtered.merchant_id= '$merchant_id' OR sw_trans_filtered.merchant_id IS NULL)";

				if (isset($search_param['customer_name']) && !empty($search_param['customer_name'])){
					$part_sql = $part_sql. " AND LOWER(cust_name) LIKE '%". strtolower(strval($search_param['customer_name'])) ."%'";
				}
				if (isset($search_param['mobile_phone_no']) && !empty($search_param['mobile_phone_no'])){
					$part_sql = $part_sql. " AND LOWER(cust_phone) LIKE '%". strtolower(strval($search_param['mobile_phone_no'])) ."%'";
				}
				if (isset($search_param['customer_email']) && !empty($search_param['customer_email'])){
					$part_sql = $part_sql. " AND LOWER(cust_email) LIKE '%". strtolower(strval($search_param['customer_email'])) ."%'";
				}
				if (isset($search_param['va_number']) && !empty($search_param['va_number'])){
					$part_sql = $part_sql. " AND LOWER(cust_va_number) LIKE '%". strtolower(strval($search_param['va_number'])) ."%'";
				}
				if (isset($search_param['tgl_awal']) && !empty($search_param['tgl_awal'])){
					$part_sql = $part_sql. " AND time_paid >= '". strval($search_param['tgl_awal'])."'";
				}
				if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
					$part_sql = $part_sql. " AND time_paid <= '". strval($search_param['tgl_akhir'])."'";
				}
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}
				if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_failed']) && !empty($search_param['payment_failed'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
					if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
						//do nothing, get all data
					}else{
						if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
							$part_sql = $part_sql. " AND len(bca_refnum) > 0";
						}
						if((isset($search_param['payment_failed']) && !empty($search_param['payment_failed']))){
							$part_sql = $part_sql. " AND auth_tran IS NULL AND node_data_req IS NOT NULL";
						}
						if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
							$part_sql = $part_sql. " AND flag = '0'";
						}
					}
				}

				if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
					$part_sql = $part_sql. " ORDER BY";
					if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
						if($search_param['timestamp_sort'] == 'asc'){
							$part_sql = $part_sql. " time_paid ASC";
						}
						else{
							$part_sql = $part_sql. " time_paid DESC";
						}
						if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
							$part_sql = $part_sql. ","; 
						}
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						if($search_param['amount_sort'] == 'asc'){
							$part_sql = $part_sql. " mapemall_va_trans.amount ASC";
						}
						else{
							$part_sql = $part_sql. " mapemall_va_trans.amount DESC";
						}
					}
				}
				else{
					//default order by
					$part_sql = $part_sql. " ORDER BY time_paid DESC";
				}
				if(isset($num) && isset($offset)){
					$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
				}
				$sql = $first_sql.$part_sql;
			}
			

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

	public function update_flag_unpaid($order_number)
	{
		//load CI instance
		$CI = & get_instance();
		$pdo = $CI->global_library->open_pdo_operational();
		

		$update_query = "UPDATE mapemall_va_trans set flag = '2' where mapemall_order_number =?";
		 
		$stmt= $pdo->prepare($update_query);
		$result = $stmt->execute([$order_number]);	
			
		if (!$result) {
			$stmt = null;
			$pdo = null;
			$retval = "error";
			echo $retval;
		}

		$retval = $stmt->rowCount() > 0;
		$stmt = null;
		$pdo = null;
		return $retval;
	}
}