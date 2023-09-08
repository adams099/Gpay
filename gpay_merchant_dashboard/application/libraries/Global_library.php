<?php
defined('BASEPATH') or exit('No direct script access allowed');

use SendGrid\Mail\Mail;
use SendGrid\Exception;

class Global_library
{

    function __construct()
    {
        //load CI instance..
        $this->CI = &get_instance();

        //load model
        $this->CI->load->model('approval_sequential_number_model');
        $this->CI->load->model('global_library_model');

        //construct script..

        //initialize session and language library..
    }

    public function get_ip_address()
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } else if (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    public function custom_password_hash($password)
    {
        $secret = $this->CI->config->item('secret_key');
        $hash = hash_hmac('sha256', $password, $secret);
        return $hash;
    }

    public function global_pagination_config()
    {
        $config['first_link']        = ' &laquo; ';
        $config['last_link']        = ' &raquo; ';
        $config['next_link']        = ' &rsaquo; ';
        $config['prev_link']        = ' &lsaquo; ';
        $config['full_tag_open']    = '<tr>';
        $config['full_tag_close']    = '</tr>';
        $config['num_tag_open']        = '<td class="rich-datascr-inact">';
        $config['num_tag_close']    = '</td>';
        $config['cur_tag_open']        = '<td class="rich-datascr-act">';
        $config['cur_tag_close']    = '</td>';
        $config['prev_tag_open']    = '<td class="rich-datascr-button">';
        $config['prev_tag_close']    = '</td>';
        $config['next_tag_open']    = '<td class="rich-datascr-button">';
        $config['next_tag_close']    = '</td>';
        $config['first_tag_open']    = '<td class="rich-datascr-button">';
        $config['first_tag_close']    = '</td>';
        $config['last_tag_open']    = '<td class="rich-datascr-button">';
        $config['last_tag_close']    = '</td>';
        $config['use_page_numbers'] = TRUE;
        return $config;
    }

    public function open_pdo_core(){
		$dsn = $this->CI->config->item("pdo_database_core_dsn");
		$db_user = $this->CI->config->item("pdo_database_core_user");
		$db_psw = $this->CI->config->item("pdo_database_core_password");
		$options = [
			PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
		];
		$conn = new PDO($dsn, $db_user, $db_psw, $options);
		return $conn;
    }

    public function open_pdo_office(){
		$dsn = $this->CI->config->item("pdo_database_office_dsn");
		$db_user = $this->CI->config->item("pdo_database_office_user");
		$db_psw = $this->CI->config->item("pdo_database_office_password");
		$options = [
			PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
		];
		$conn = new PDO($dsn, $db_user, $db_psw, $options);
		return $conn;
    }

    public function open_pdo_operational(){
		$dsn = $this->CI->config->item("pdo_database_operational_dsn");
		$db_user = $this->CI->config->item("pdo_database_operational_user");
		$db_psw = $this->CI->config->item("pdo_database_operational_password");
		$options = [
			PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
		];
		$conn = new PDO($dsn, $db_user, $db_psw, $options);
		return $conn;
    }

    // Lakukan composer update dulu di console karna membutuhkan Lib sendGrid
    //
    // Function to send email description.
    // 1. From : String
    // 2. To : String
    // 3. Subject : String. Subject string terletak di config/gpay.php
    // 4. filename : String nama file. Ex : customer_top_up.html
    // 5. contents : Array dengan key dan value.
    //      Ex : array(
    //              "customerName" => "andre",
    //              "customerPhoneNumber" => "081231231111"
    //      )
    //
    // Cara memanggil function send_email
    // Ex : $this->global_library->send_email(
    //      'noreply@gpay.id',
    //      'customer@domain.com',
    //      'Customer Top Up Successful',
    //      'customer_top_up.html',
    //      array(
    //          "customerName" => "andre",
    //          "customerPhoneNumber" => "081231231111"
    //      )
    // );


    public function send_email($tos, $subject, $filename, $contents)
    {
        if (empty($tos)) {
            return false;
        }

        $main_path_of_email = $this->CI->config->item("main_path_of_email");
        $file_content = file_get_contents($main_path_of_email . $filename, "r");
        $from_email = $this->CI->config->item('no_reply_email');
        $from_name = $this->CI->config->item('no_reply_name');

        if (key_exists('customerName', $contents)) {
            $file_content = str_replace('#customerName', $contents['customerName'], $file_content);
        }

        if (key_exists('currency', $contents)) {
            $file_content = str_replace('#currency', $contents['currency'], $file_content);
        }

        if (key_exists('amountTopUp', $contents)) {
            $file_content = str_replace('#amountTopUp', $contents['amountTopUp'], $file_content);
        }
        if (key_exists('amountCashOut', $contents)) {
            $file_content = str_replace('#amountCashOut', $contents['amountCashOut'], $file_content);
        }
        if (key_exists('amount_top_up', $contents)) {
            $file_content = str_replace('#amount_top_up', $contents['amount_top_up'], $file_content);
        }
        if (key_exists('customerPhoneNumber', $contents)) {
            $file_content = str_replace('#customerPhoneNumber', $contents['customerPhoneNumber'], $file_content);
        }

        if (key_exists('adminName', $contents)) {
            $file_content = str_replace('#adminName', $contents['adminName'], $file_content);
        }

        if (key_exists('agentName', $contents)) {
            $file_content = str_replace('#agentName', $contents['agentName'], $file_content);
        }

        if (key_exists('requestDate', $contents)) {
            $file_content = str_replace('#requestDate', $contents['requestDate'], $file_content);
        }

        if (key_exists('status', $contents)) {
            $file_content = str_replace('#status', $contents['status'], $file_content);
        }

        if (key_exists('customerServiceEmail', $contents)) {
            $file_content = str_replace('#customerServiceEmail', $contents['customerServiceEmail'], $file_content);
        }

        if (key_exists('agentName', $contents)) {
            $file_content = str_replace('#agentName', $contents['agentName'], $file_content);
        }

        if (key_exists('gpayPhone', $contents)) {
            $file_content = str_replace('#gpayPhone', $contents['gpayPhone'], $file_content);
        }
        if (key_exists('websiteUrlGpay', $contents)) {
            $file_content = str_replace('#websiteUrlGpay', $contents['websiteUrlGpay'], $file_content);
        }

        if (key_exists('maxBalance', $contents)) {
            $file_content = str_replace('#maxBalance', $contents['maxBalance'], $file_content);
        }

        if (key_exists('requestStatus', $contents)) {
            $file_content = str_replace('#requestStatus', $contents['requestStatus'], $file_content);
        }

        if (key_exists('reason', $contents)) {
            $file_content = str_replace('#reason', $contents['reason'], $file_content);
        }

        if (key_exists('merchantName', $contents)) {
            $file_content = str_replace('#merchantName', $contents['merchantName'], $file_content);
        }

        if (key_exists('totalRecords', $contents)) {
            $file_content = str_replace('#totalRecords', $contents['totalRecords'], $file_content);
        }

        if (key_exists('totalRecordDeleted', $contents)) {
            $file_content = str_replace('#totalRecordDeleted', $contents['totalRecordDeleted'], $file_content);
        }

        if (key_exists('totalRecordUpdated', $contents)) {
            $file_content = str_replace('#totalRecordUpdated', $contents['totalRecordUpdated'], $file_content);
        }

        if (key_exists('requestType', $contents)) {
            $file_content = str_replace('#requestType', $contents['requestType'], $file_content);
        }

        if (key_exists('userGroup', $contents)) {
            $file_content = str_replace('#userGroup', $contents['userGroup'], $file_content);
        }

        if (key_exists('userName', $contents)) {
            $file_content = str_replace('#userName', $contents['userName'], $file_content);
        }

        if (key_exists('terminalId', $contents)) {
            $file_content = str_replace('#terminalId', $contents['terminalId'], $file_content);
        }

        if (key_exists('terminalName', $contents)) {
            $file_content = str_replace('#terminalName', $contents['terminalName'], $file_content);
        }

        if (key_exists('companysiteName', $contents)) {
            $file_content = str_replace('#companysiteName', $contents['companysiteName'], $file_content);
        }

        if (key_exists('companyName', $contents)) {
            $file_content = str_replace('#companyName', $contents['companyName'], $file_content);
        }

        if (key_exists('merchantAppLink', $contents)) {
            $file_content = str_replace('#merchantAppLink', $contents['merchantAppLink'], $file_content);
        }

        if (key_exists('note', $contents)) {
            $file_content = str_replace('#note', $contents['note'], $file_content);
        }

        if (key_exists('downloadLink', $contents)) {
            $file_content = str_replace('#downloadLink', $contents['downloadLink'], $file_content);
        }

        if (key_exists('customerDefaultPin', $contents)) {
            $file_content = str_replace('#customerDefaultPin', $contents['customerDefaultPin'], $file_content);
        }

        if (key_exists('faqLink', $contents)) {
            $file_content = str_replace('#faqLink', $contents['faqLink'], $file_content);
        }

        if (key_exists('phoneNumber', $contents)) {
            $file_content = str_replace('#phoneNumber', $contents['phoneNumber'], $file_content);
        }

        $email = new Mail();
        $email->setFrom($from_email, $from_name);
        $email->setSubject($subject);
        foreach ($tos as $to) {
            $email->addTo($to);
        }

        $email->addContent("text/html", $file_content);


        // $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY')); // Use PHP Environment
        $sendgrid = new SendGrid($this->CI->config->item('sendgrid_key')); //API Key

        if ($sendgrid->send($email)) {
            return true;
        } else {
            return false;
        }
    }

    //firebase push messaging, untuk kirim message ke mobilephone, besamaan dengan email sending 
    //$firebase_tokens berisi array firebase_token dari database yang related ke customer yang ingin dikirim (bisa lebih dari satu)
    //$body_title dan $body_message berisi text string
    // type 1 untuk redirect ke profile, jika type 2 redirect ke transaction history ( FIXED sudah deal dengan MobileForce)
    public function firebase_push_message($firebase_tokens, $title_message, $body_message, $redirect_type)
    {
        $api_url = $this->CI->config->item('firebase_api_url');
        $api_key = $this->CI->config->item('firebase_api_key');

        $json_body_message_data = array(
            'title' => $title_message,
            'body' => $body_message,
            'type' => $redirect_type,
            'click_action' => 'OPEN_MAIN'
        );

        // $json_body_message_notification = array(
        //     'title' => $title_message,
        //     'body' => array('message' => $body_message, 'type' => $redirect_type )
        // );

        // $curl_data_arr = array(
        //     'registration_ids' => $firebase_tokens,
        //     'data' => $json_body_message_data,
        //     'notification' => $json_body_message_notification
        // );

        $curl_data_arr = array(
            'registration_ids' => $firebase_tokens,
            'data' => $json_body_message_data
        );

        // curl method
        $curl_data_json = json_encode($curl_data_arr);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data_json);

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: ' . $api_key,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($curl_data_json)
            )
        );

        $curl_result = curl_exec($ch);
        curl_close($ch);

        $curl_result_arr = json_decode($curl_result, true);
        if ($curl_result_arr) {
            if (array_key_exists('success', $curl_result_arr)) {
                if ($curl_result_arr['success'] == 1) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        // curl method

        //http post

        // $options = array(
        //     'http' => array(
        //         'header' => "Content-Type: application/json\r\n"."Authorization: ".$api_key."\r\n",
        //         'method' => 'POST',
        //         'content' => json_encode($curl_data_arr)
        //     )
        // );
        // $context = stream_context_create($options);
        // $result = file_get_contents($api_url, false, $context);
        // return $result;
    }

    public function sequential_number()
    {

        $array_code = $this->CI->config->item('sequential_number_format');


        // get mili sec
        $milisec = microtime(true);
        $milisec = substr($milisec, strpos($milisec, ".") + 1);
        $milisec = str_split($milisec);
        foreach ($milisec as $key => $value) {
            $milisec[$key] = $array_code[$value];
        }
        $milisec = str_replace('|', '', implode("|", $milisec));
        $milisec = str_pad($milisec, 4, "0", STR_PAD_LEFT);



        do {
            // reset sequential
            $data = $this->CI->approval_sequential_number_model->select()->row();

            if (empty($data)) {
                $data = new stdClass();
                $data->sequential = '1';
                $data->date = date('Y-m-d H:i:s');
            }

            $seq_number = $data->sequential;

            $date1 = date('H', strtotime($data->date));
            $date2 = date('H');
            $diff = ltrim($date2, '0') - ltrim($date1, '0');
            if ($date2 == '00' || $diff != 0) {
                $this->CI->approval_sequential_number_model->alter_table();
                $new_number = $seq_number = 1;
                $new_number = str_pad($new_number, 7, "0", STR_PAD_LEFT);
            } else {
                $new_number = $seq_number + 1;
                $new_number = str_pad($new_number, 7, "0", STR_PAD_LEFT);
            }

            $flag = $this->CI->approval_sequential_number_model->insert();
        } while ($flag == false);

        // create code
        $year = date('Y');
        $month = $array_code[ltrim(date('m'), '0')];
        $day = $array_code[ltrim(date('d'), '0')];
        $hour = $array_code[ltrim(date('H'), '0')];
        $minute = $array_code[ltrim(date('i'), '0')];
        $second = $array_code[ltrim(date('s'), '0')];

        $code = $year . $month . $day . $hour . $minute . $second . $milisec . $new_number;

        return $code;
    }

    // function untuk hash transaction ID yang berbentuk decimal ke base 36 lalu dipadding zero jika digit tidak mencapai 6 digit.
    public function transaction_id_hash_to_base_36($id)
    {
        $length = 6;
        $retval = base_convert($id, 10, 36);
        $id_length = strlen((string) $retval);
        if ($id_length < 6) {
            $retval = str_pad($retval, 6, "0", STR_PAD_LEFT);
        }
        return $retval;
    }

    public function curlGet($url)
    {
        $retval = "";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
        $retval = curl_exec($ch);
        curl_close($ch);

        return $retval;
    }

    public function generateUUID()
    {
        mt_srand((float) microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8)
            . substr($charid, 8, 4)
            . substr($charid, 12, 4)
            . substr($charid, 16, 4)
            . substr($charid, 20, 12);
        return $uuid;
    }

    public function sendSMS($mbl_phone_no, $msg)
    {
        $retval = "";

        if ($this->CI->config->item('sms_provider') == 'jatis') {
            $domain = $this->CI->config->item('jatis_sms_domain');
            $userid = $this->CI->config->item('jatis_sms_userid');
            $password = $this->CI->config->item('jatis_sms_password');
            $sender = $this->CI->config->item('jatis_sms_sender');
            $division = $this->CI->config->item('jatis_sms_division');
            $batchname = $this->CI->config->item('jatis_sms_batchname');
            $uploadby = $this->CI->config->item('jatis_sms_uploadby');
            $channel = $this->CI->config->item('jatis_sms_channel');

            $url = $domain . "index.ashx?userid=" . $userid . "&password=" . $password . "&sender=" . $sender .
                "&division=" . $division . "&batchname=" . $batchname . "&uploadby=" . $uploadby . "&channel=" . $channel .
                "&msisdn=62" . $mbl_phone_no . "&message=" . urlencode($msg);

            $retval = $this->curlGet($url);
            $this->CI->global_library_model->insert_jatis_otp_sms_log($mbl_phone_no, $msg, $retval);

            return $retval;
        } elseif ($this->CI->config->item('sms_provider') == 'sprint') {
            $domain =  $this->CI->config->item('sprint_sms_domain');
            $type =  $this->CI->config->item('sprint_sms_type');
            $sender_id =  $this->CI->config->item('sprint_sms_sender_id');
            $username =  $this->CI->config->item('sprint_sms_username');
            $password =  $this->CI->config->item('sprint_sms_password');

            $datetime = date_create();
            $datetime_string = $datetime->format('Y-m-d H:i:s');
            $timestamp_string = $datetime->format('U');;
            $ref_id = $this->generateUUID();
            $signature = hash('sha256', $username . $password . $timestamp_string, false);

            $curl_data_arr = array(
                'type' => $type,
                'username' => $username,
                'ref_id' => $ref_id,
                'time' => $timestamp_string,
                'signature' => $signature,
                'subject' => $msg,
                'sender_id' => $sender_id,
                'channel' => array(
                    'sms' => array(
                        'message' => $msg,
                        'msisdn' => '62' . $mbl_phone_no,
                    ),
                ),
            );

            $curl_data_json = json_encode($curl_data_arr);
            $ch = curl_init($domain);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data_json);

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                )
            );

            $retval = curl_exec($ch);
            curl_close($ch);

            $headers = 'type=' . $type . '&sender_id=' . $sender_id . '&time=' . $timestamp_string;
            $this->CI->global_library_model->insert_sprint_otp_sms_log($mbl_phone_no, $headers, $msg, $retval);

            return $retval;
        }
    }
}
