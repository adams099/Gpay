<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mandiri_va_transaction_history_model extends CI_Model {

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
			$prefix_array = array();
			$bank_array = array();
			//create with table query for payments
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367' || $merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					array_push($prefix_array, $merchant_group['receiving_inst_id']);
					if($merchant_group['receiving_inst_id'] == '88367'){
						array_push($bank_array, 'mandiri');
					}elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
						array_push($bank_array, 'bni');
					}
				}
			}
			if(count($bank_array) > 0){
				$va_bank_query_array = "";
				$first_id_query = true;
				for($i=0;$i<count(array_unique($bank_array));$i++){
					if($first_id_query){
						$va_bank_query_array = $va_bank_query_array. "('".$bank_array[$i]."'";
						$first_id_query = false;
					}else{
						$va_bank_query_array = $va_bank_query_array.",'".$bank_array[$i]."'";
					}
				}
				$va_bank_query_array = $va_bank_query_array.")";
			}
			//finish with table query, add normal query

			$sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK)    
			WHERE 
			vbt.bank_name in $va_bank_query_array 
			AND time_created >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_created <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
			ORDER BY time_created DESC OFFSET $offset ROWS FETCH NEXT $num ROWS ONLY";
		}else{
			$bank_name = "";

			if($receiving_inst_id == '88367'){
				$bank_name = 'mandiri';
			}elseif($receiving_inst_id == '34181' || $receiving_inst_id == '34892'){
				$bank_name = 'bni';
			}

			$sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name = '$bank_name'
			AND time_created >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_created <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
			ORDER BY time_created DESC OFFSET $offset ROWS FETCH NEXT $num ROWS ONLY";

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
			$count_sql = "SELECT COUNT(*) AS 'num_rows' FROM(";
			$prefix_array = array();
			$bank_array = array();
			//create with table query for payments
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367' || $merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					array_push($prefix_array, $merchant_group['receiving_inst_id']);
					if($merchant_group['receiving_inst_id'] == '88367'){
						array_push($bank_array, 'mandiri');
					}elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
						array_push($bank_array, 'bni');
					}
				}
			}
			if(count($bank_array) > 0){
				$va_bank_query_array = "";
				$first_id_query = true;
				for($i=0;$i<count(array_unique($bank_array));$i++){
					if($first_id_query){
						$va_bank_query_array = $va_bank_query_array. "('".$bank_array[$i]."'";
						$first_id_query = false;
					}else{
						$va_bank_query_array = $va_bank_query_array.",'".$bank_array[$i]."'";
					}
				}
				$va_bank_query_array = $va_bank_query_array.")";
			}
			//finish with table query, add normal query
			$sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 		   
			WHERE 
			vbt.bank_name in $va_bank_query_array 
			AND time_created >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_created <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')";
			$sql =$count_sql.$sql.") AS myDerivedTable";
		}else{
			$bank_name = "";

			if($receiving_inst_id == '88367'){
				$bank_name = 'mandiri';
			}elseif($receiving_inst_id == '34181' || $receiving_inst_id == '34892'){
				$bank_name = 'bni';
			}

			$sql = "SELECT COUNT(*) AS 'num_rows' FROM(select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name = '$bank_name' 
			AND time_created >=  concat(CAST(GETDATE() as DATE),' 00:00:00.000')
			AND time_created <= concat(CAST(GETDATE()+1 as DATE),' 23:59:59.999')
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
			$prefix_array = array();
			$bank_array = array();
			
			//filter merchant group data based on selected VA filter
			$flag_mandiri = true;
			$flag_bni = true;
			if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri'])) || (isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
				if (isset($search_param['va_all']) && !empty($search_param['va_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri']))){
						$flag_mandiri = true;
					}else{
						$flag_mandiri = false;
					}
					if((isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
						$flag_bni = true;
					}else{
						$flag_bni = false;
					}
				}
			}
			$merchant_index = 0;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367'){
					if($flag_mandiri == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					if($flag_bni == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				$merchant_index = $merchant_index + 1;
			}

			//create with table query for payments
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367' || $merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					array_push($prefix_array, $merchant_group['receiving_inst_id']);
					if($merchant_group['receiving_inst_id'] == '88367'){
						array_push($bank_array, 'mandiri');
					}elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
						array_push($bank_array, 'bni');
					}
				}
			}
			if(count($bank_array) > 0){
				$va_bank_query_array = "";
				$first_id_query = true;
				for($i=0;$i<count(array_unique($bank_array));$i++){
					if($first_id_query){
						$va_bank_query_array = $va_bank_query_array. "('".$bank_array[$i]."'";
						$first_id_query = false;
					}else{
						$va_bank_query_array = $va_bank_query_array.",'".$bank_array[$i]."'";
					}
				}
				$va_bank_query_array = $va_bank_query_array.")";
			}

			//finish with table query, add normal query
			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK)    
			WHERE 
			vbt.bank_name in $va_bank_query_array";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$part_sql = $part_sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$part_sql = $part_sql. " time_created ASC";
					}
					else{
						$part_sql = $part_sql. " time_created DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$part_sql = $part_sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$part_sql = $part_sql. " vbt.amount ASC";
					}
					else{
						$part_sql = $part_sql. " vbt.amount DESC";
					}
				}
			}
			else{
				//default order by
				$part_sql = $part_sql. " ORDER BY time_created DESC";
			}
			if(isset($num) && isset($offset)){
				$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
			$sql = $part_sql;

		}else{
			$bank_name = "";

			if($receiving_inst_id == '88367'){
				$bank_name = 'mandiri';
			}elseif($receiving_inst_id == '34181' || $receiving_inst_id == '34892'){
				$bank_name = 'bni';
			}

			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name = '$bank_name'";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$part_sql = $part_sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$part_sql = $part_sql. " time_created ASC";
					}
					else{
						$part_sql = $part_sql. " time_created DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$part_sql = $part_sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$part_sql = $part_sql. " vbt.amount ASC";
					}
					else{
						$part_sql = $part_sql. " vbt.amount DESC";
					}
				}
			}
			else{
				//default order by
				$part_sql = $part_sql. " ORDER BY time_created DESC";
			}
			if(isset($num) && isset($offset)){
				$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
			$sql = $part_sql;
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
			$count_sql = "SELECT COUNT(*) AS 'num_rows' FROM(";
			$prefix_array = array();
			$bank_array = array();
			
			//filter merchant group data based on selected VA filter
			$flag_mandiri = true;
			$flag_bni = true;
			if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri'])) || (isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
				if (isset($search_param['va_all']) && !empty($search_param['va_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri']))){
						$flag_mandiri = true;
					}else{
						$flag_mandiri = false;
					}
					if((isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
						$flag_bni = true;
					}else{
						$flag_bni = false;
					}
				}
			}
			$merchant_index = 0;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367'){
					if($flag_mandiri == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					if($flag_bni == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				$merchant_index = $merchant_index + 1;
			}

			//create with table query for payments
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367' || $merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					array_push($prefix_array, $merchant_group['receiving_inst_id']);
					if($merchant_group['receiving_inst_id'] == '88367'){
						array_push($bank_array, 'mandiri');
					}elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
						array_push($bank_array, 'bni');
					}
				}
			}
			if(count($bank_array) > 0){
				$va_bank_query_array = "";
				$first_id_query = true;
				for($i=0;$i<count(array_unique($bank_array));$i++){
					if($first_id_query){
						$va_bank_query_array = $va_bank_query_array. "('".$bank_array[$i]."'";
						$first_id_query = false;
					}else{
						$va_bank_query_array = $va_bank_query_array.",'".$bank_array[$i]."'";
					}
				}
				$va_bank_query_array = $va_bank_query_array.")";
			}
			//finish with table query, add normal query
			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name in $va_bank_query_array";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			$sql =$count_sql.$part_sql.") AS myDerivedTable";
		}else{
			$bank_name = "";

			if($receiving_inst_id == '88367'){
				$bank_name = 'mandiri';
			}elseif($receiving_inst_id == '34181' || $receiving_inst_id == '34892'){
				$bank_name = 'bni';
			}

			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name = '$bank_name'";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			$sql = $count_sql.$part_sql." ) AS myDerivedTable";
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
			$prefix_array = array();
			$bank_array = array();
			
			//filter merchant group data based on selected VA filter
			$flag_mandiri = true;
			$flag_bni = true;
			if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri'])) || (isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
				if (isset($search_param['va_all']) && !empty($search_param['va_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['va_mandiri']) && !empty($search_param['va_mandiri']))){
						$flag_mandiri = true;
					}else{
						$flag_mandiri = false;
					}
					if((isset($search_param['va_bni']) && !empty($search_param['va_bni']))){
						$flag_bni = true;
					}else{
						$flag_bni = false;
					}
				}
			}
			$merchant_index = 0;
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367'){
					if($flag_mandiri == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					if($flag_bni == false){
						unset($merchant_group_data[$merchant_index]);
					}
				}
				$merchant_index = $merchant_index + 1;
			}

			//create with table query for payments
			foreach($merchant_group_data as $merchant_group){
				if($merchant_group['receiving_inst_id'] == '88367' || $merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
					array_push($prefix_array, $merchant_group['receiving_inst_id']);
					if($merchant_group['receiving_inst_id'] == '88367'){
						array_push($bank_array, 'mandiri');
					}elseif($merchant_group['receiving_inst_id'] == '34181' || $merchant_group['receiving_inst_id'] == '34892'){
						array_push($bank_array, 'bni');
					}
				}
			}
			if(count($bank_array) > 0){
				$va_bank_query_array = "";
				$first_id_query = true;
				for($i=0;$i<count(array_unique($bank_array));$i++){
					if($first_id_query){
						$va_bank_query_array = $va_bank_query_array. "('".$bank_array[$i]."'";
						$first_id_query = false;
					}else{
						$va_bank_query_array = $va_bank_query_array.",'".$bank_array[$i]."'";
					}
				}
				$va_bank_query_array = $va_bank_query_array.")";
			}

			//finish with table query, add normal query
			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK) 	   
			WHERE 
			vbt.bank_name in $va_bank_query_array";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$part_sql = $part_sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$part_sql = $part_sql. " time_created ASC";
					}
					else{
						$part_sql = $part_sql. " time_created DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$part_sql = $part_sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$part_sql = $part_sql. " vbt.amount ASC";
					}
					else{
						$part_sql = $part_sql. " vbt.amount DESC";
					}
				}
			}
			else{
				//default order by
				$part_sql = $part_sql. " ORDER BY time_created DESC";
			}
			if(isset($num) && isset($offset)){
				$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
			$sql = $part_sql;

		}else{
			$bank_name = "";

			if($receiving_inst_id == '88367'){
				$bank_name = 'mandiri';
			}elseif($receiving_inst_id == '34181' || $receiving_inst_id == '34892'){
				$bank_name = 'bni';
			}

			$part_sql = "select 
				vbt.bank_refnum as refnum_bca,
				vbt.cust_va_number as va_number,
				vbt.cust_name as customer_name,
				CONVERT(CHAR(23),time_created,121) AS time_trx,
				FORMAT(vbt.amount,'N0') AS amount,
				'Other' as bca_payment_channel,
				'' as currency,
				'' as reference_number,
				vbt.cust_email,
				vbt.cust_phone,
				vbt.client_order_number as order_number,
				'' as credit_date,
				'' as cost_bca,
				'' as cost_date,
				'' as bca_request_id,
				CASE 
					WHEN len(bank_refnum) > 0 THEN 'Paid'
					WHEN vbt.flag = '0' THEN 'Unpaid'
					WHEN vbt.flag = '2' THEN 'Canceled'
					ELSE null
				END AS response_message,
				bank_name as bank_issuer,
				time_created
				from dbo.va_bridge_trans vbt WITH (NOLOCK)    
			WHERE 
			vbt.bank_name = '$bank_name'";
					
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
				$part_sql = $part_sql. " AND time_created >= '". strval($search_param['tgl_awal'])."'";
			}
			if (isset($search_param['tgl_akhir']) && !empty($search_param['tgl_akhir'])){
				$part_sql = $part_sql. " AND time_created <= '". strval($search_param['tgl_akhir'])."'";
			}
			if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
				//do nothing, get all data
			}
			if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid'])) || (isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
				if (isset($search_param['payment_all']) && !empty($search_param['payment_all'])){
					//do nothing, get all data
				}else{
					if((isset($search_param['payment_paid']) && !empty($search_param['payment_paid']))){
						$part_sql = $part_sql. " AND len(bank_refnum) > 0";
					}
					if((isset($search_param['payment_unpaid']) && !empty($search_param['payment_unpaid']))){
						$part_sql = $part_sql. " AND vbt.flag = '0'";
					}
				}
			}

			if((isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])) || (isset($search_param['amount_sort']) && !empty($search_param['amount_sort']))){
				$part_sql = $part_sql. " ORDER BY";
				if(isset($search_param['timestamp_sort']) && !empty($search_param['timestamp_sort'])){
					if($search_param['timestamp_sort'] == 'asc'){
						$part_sql = $part_sql. " time_created ASC";
					}
					else{
						$part_sql = $part_sql. " time_created DESC";
					}
					if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
						$part_sql = $part_sql. ","; 
					}
				}
				if(isset($search_param['amount_sort']) && !empty($search_param['amount_sort'])){
					if($search_param['amount_sort'] == 'asc'){
						$part_sql = $part_sql. " vbt.amount ASC";
					}
					else{
						$part_sql = $part_sql. " vbt.amount DESC";
					}
				}
			}
			else{
				//default order by
				$part_sql = $part_sql. " ORDER BY time_created DESC";
			}
			if(isset($num) && isset($offset)){
				$part_sql = $part_sql. " OFFSET ".$offset." ROWS FETCH NEXT ".$num." ROWS ONLY";
			}
			$sql = $part_sql;
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

}