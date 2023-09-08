<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		$this->load->library('session');
		$this->load->library('Recaptcha');
		// load model..
        $this->load->model('merchant_dashboard_login_model');
        $this->load->model('user_model');
        $this->load->model('audit_log_model');
        
		
		// construct script..
		if($this->session->userdata('logged_in') == true){
			redirect('home/index');
		}
    }
    
	function get_receiving_inst_id_data_from_id($merchant_dashboard_id)
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
        $data_array = array();
        foreach($retval as $data){
            array_push($data_array, $data['receiving_inst_id']);
        }

		$stmt = null;
		$pdo = null;
		return $data_array;
    }
    
	public function index()
	{
		$data = array(
            'action' => site_url('login'),
            'username' => set_value('username'),
            'password' => set_value('password'),
            'captcha' => $this->recaptcha->getWidget(), // menampilkan recaptcha
            'script_captcha' => $this->recaptcha->getScriptTag(), // javascript recaptcha ditaruh di head
        );
		$this->load->view('login', $data);
	}

	public function validate_login()
	{
		// Validasi input
		$this->form_validation->set_rules('username', 'Username', 'htmlentities|required|strip_tags|trim|xss_clean');
		$this->form_validation->set_rules('password', 'Password', 'htmlentities|required|strip_tags|trim|xss_clean');
		
		if($this->form_validation->run() == FALSE){
			$message = "<div class='alert alert-danger'>" . validation_errors() . "</div>";
			$this->session->set_flashdata('message', $message);
			redirect('login/index');
		}
		else{
            
			$username = $this->input->post('username');
			$password = $this->global_library->custom_password_hash($this->input->post('password'));
			$message = '';

            $valid_psw_hash = "";
            $merchant_dashboard_id = "";
            
            $merchant_data = $this->merchant_dashboard_login_model->get_merchant_login_data($username);
            if ($row = $merchant_data->row()) {
                $merchant_dashboard_id = $row->id;
                $valid_psw_hash = $row->password_hash;
                $dt_suspend_login = $row->dt_suspend_login;
                $failed_login_count = $row->failed_login_count;
                $username = $row->username;
                $receiving_inst_id = $row->receiving_inst_id;

                $receiving_inst_id_array = $this->get_receiving_inst_id_data_from_id($row->id);

            } else {
                $message .= '<li class="infomsg">Login failed</li>';
                $this->session->set_flashdata('message', $message);
                redirect('login/index');
            }
            $stmt = null;
            
            $params = [$merchant_dashboard_id];
            $suspend = $this->merchant_dashboard_login_model->check_suspend($merchant_dashboard_id);
            if ($row = $suspend->row()) {
                $message .= '<li class="infomsg">Account suspended</li>';
                $this->session->set_flashdata('message', $message);
                redirect('login/index');
            }
            $stmt = null;

            // if ($suspend != null) {                
            //     $device_id = null;//testing purpose only
            //     $login_token = $this->generateUUID();
            //     $params = [$device_id, $login_token, $merchant_dashboard_id];
            //     // Update last login merchant
            //     $update_data = array(
            //         'dt_suspend_login' => NULL,
            //         'failed_login_count' => 0,
            //         'device_id' => $device_id,
            //         'login_token' => $login_token,
                    
            //     );
            //     $this->merchant_dashboard_login_model->update_merchant_dashboard_login($update_data, $merchant_dashboard_id);
                

            //     $retval = array('status' => '0', 'data' => array('customer_id' => $merchant_dashboard_id, 'login_token' => $login_token));
            // }else {
              
            // }

			if(isset($merchant_data)){
                $merchant_row = $merchant_data->row();
				if(hash_equals($password, $merchant_row->password_hash)){
					// Login success

                    // get role
                    $_role = $this->user_model->get_user_permission_by_id($merchant_dashboard_id)->result();
                    
                    //sbu. merchant, and store level
                    $_role_sbu = $this->user_model->get_user_level_sbu_by_id($merchant_dashboard_id)->result();
                    $_role_merchant = $this->user_model->get_user_level_merchant_by_id($merchant_dashboard_id)->result();
                    $_role_qris_online_merchant = $this->user_model->get_user_level_qris_online_merchant_by_id($merchant_dashboard_id)->result();
                    $_role_store = $this->user_model->get_user_level_store_by_id($merchant_dashboard_id)->result();
                    // $role = array();
                    $action = array();

                    foreach($_role as $rol){
                        // if(!in_array($rol->merchant_group_code,$role)){
                        //     array_push($role,$rol->merchant_group_code);
                        // }
                        
                        //check if user have merchant id by qris
                        if($rol->menu_name == "qris_online"){
                            if(count($_role_qris_online_merchant) == 0 ) continue;
                        }

                        if(!array_key_exists($rol->menu_name,$action)) {
                            $action[$rol->menu_name] = array(
                                $rol->action_name
                            );
                        }else if( !in_array($rol->action_name,$action[$rol->menu_name]) ){
                          array_push($action[$rol->menu_name],$rol->action_name);
                        }
                    }

                    if(in_array('88367',$receiving_inst_id_array) || in_array('34181',$receiving_inst_id_array) || in_array('34892',$receiving_inst_id_array) ){
                        $action['mandiri_va_transaction_history'] = array('mandiri_va_transaction_history_search',);
                    }

                    if(in_array('39789',$receiving_inst_id_array)){
                        $action['transaction_history_va'] = array('trx_hist_va_search',);
                    }

                    $login_data = array(
                        'id' => $merchant_row->id,
                        'username' => $merchant_row->username,
                        'receiving_inst_id'=> $merchant_row->receiving_inst_id,
                        'receiving_inst_id_array' => $receiving_inst_id_array,
                        'action'=> $action,
                        'sbu_level' => $_role_sbu,
                        'merchant_level' => $_role_merchant,
                        'qris_online_merchant_level' => $_role_qris_online_merchant,
                        'store_level' => $_role_store,
                        'logged_in' => TRUE
                    );
					$this->session->set_userdata($login_data);
					redirect('home/index');
				}
				else{
					$message = '<li class="errormsg">Wrong password</li>';
				}
			}
			else{
				$message = '<li class="errormsg">Unknown username: ' . $username . '</li>';
			}

			// Login failed
			$message .= '<li class="infomsg">Login failed</li>';
			$this->session->set_flashdata('message', $message);
			redirect('login/index');
		}
	}
	
	public function refresh(){
        // Captcha configuration
        $config = array(
            'img_path'      => 'captcha_images/',
            'img_url'       => base_url().'captcha_images/',
            'font_path'     => 'system/fonts/texb.ttf',
            'img_width'     => '160',
            'img_height'    => 50,
            'word_length'   => 8,
            'font_size'     => 18
        );
        $captcha = create_captcha($config);
        
        // Unset previous captcha and set new captcha word
        $this->session->unset_userdata('captchaCode');
        $this->session->set_userdata('captchaCode',$captcha['word']);
        
        // Display captcha image
        echo $captcha['image'];
	}
	
	public function googleCaptachStore(){
        $data = array('name' => $this->input->post('name'),
                      'email' => $this->input->post('email'), 
                      'mobile_number' => $this->input->post('mobile_number'), 
                     );
        $recaptchaResponse = trim($this->input->post('g-recaptcha-response'));
        $userIp=$this->input->ip_address();
        // $secret='6LdroioaAAAAAEixnQHdIBov8Qy574KSgiQLVUWr'; // ini adalah Secret key yang didapat dari google, silahkan disesuaikan
        $secret='6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
        $credential = array(
              'secret' => $secret,
              'response' => $this->input->post('g-recaptcha-response')
          );
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($credential));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
 
        $status= json_decode($response, true);
 
        if($status['success']){ 
            $this->validate_login();
        }else{
            $message = "<div class='alert alert-danger'>Please input the captcha</div>";
			$this->session->set_flashdata('message', $message);
			redirect('login/index');
        }
        redirect(base_url('login'));
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

}
