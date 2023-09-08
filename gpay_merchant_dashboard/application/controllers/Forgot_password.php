<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forgot_password extends CI_Controller {

	public function __construct(){
		parent::__construct();
		
		// load library..
		$this->load->library('session');
		$this->load->library('Recaptcha');
		// load model..
		$this->load->model('user_model');
		$this->load->model('audit_log_model');
		
		// construct script..
		if($this->session->userdata('logged_in') == true){
			redirect('home/index');
		}
		// Load the captcha helper
        //m$this->load->helper('captcha');
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
		$this->load->view('forgot_password', $data);
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

			$admin = $this->user_model->get_user_by_username($username)->row();
			if(isset($admin)){
				if(hash_equals($password, $admin->password_hash)){
					// Login success

                    // get role
                    $_role = $this->user_model->get_user_permission_by_id($admin->id)->result();
                    $role = array();
                    $action = array();

                    foreach($_role as $rol){
                        if(!in_array($rol->admin_user_group_code,$role)){
                            array_push($role,$rol->admin_user_group_code);
                        }

                        if(!array_key_exists($rol->menu_name,$action)) {
                            $action[$rol->menu_name] = array(
                                $rol->action_name
                            );
                        }else if( !in_array($rol->action_name,$action[$rol->menu_name]) ){
                            array_push($action[$rol->menu_name],$rol->action_name);
                        }
                    }

                    $login_data = array(
                        'id' => $admin->id,
                        'username' => $admin->username,
                        'fullname' => $admin->fullname,
                        'role' => $role,
                        'action'=> $action,
                        'logged_in' => TRUE
                    );

					$log_login_data = array(
						'id' => $admin->id,
						'username' => $admin->username,
						'fullname' => $admin->fullname,
						'login_time' => date("Y-m-d H:i:s")
					);
					//////////////////////////////////////////////////////////////////////////////////
					//Record activity log
                    $permission = $this->user_model->get_dsc_permission_and_screen_by_permission_name('login',$role[0])->row();

                    $record['screen_name'] = $permission->screen;
					$record['user_action'] = $permission->permission;
					$record['table_name'] = "admin_user";
					$record['user_role'] = $permission->user_group;
					$after = $log_login_data;
					$record['data_after'] = json_encode($after);
					$record['user_id'] = $admin->id;

					$this->audit_log_model->insert($record);
					//////////////////////////////////////////////////////////////////////////////////	

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
        $secret='6Lfb2yYTAAAAACqKLZ3PWzknHdA1cNkZFbDAgx3X'; // ini adalah Secret key yang didapat dari google, silahkan disesuaikan
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
}
