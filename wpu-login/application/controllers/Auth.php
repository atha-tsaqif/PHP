<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	public function index()
	{

		if($this->session->userdata('email')) {
			redirect('user');
		}
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');

		if ($this->form_validation->run() == false) {
			$data['title'] = 'Nafrozen | Murah Mudah Cepat';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/login');
			$this->load->view('templates/auth_footer');
		} else {
			// validasi success
			$this->_login();
		}
	}

	private function _login()
	{
		if (isset($_COOKIE['varkey']) && isset($_COOKIE['key'])) {
			$varkey = $_COOKIE['varkey'];
			$key = $_COOKIE['key'];


			$result = mysqli_query($conn, "SELECT name FROM user WHERE id = $varkey");
			$user = mysqli_fetch_assoc($result);

			if ($key === hash('sha256', $user['name'])) {
				$_SESSION['login'] = true;
			}
		}

		if (isset($_SESSION['login'])) {
			header("location: http://localhost/wpu-login/user");
		}

		$email = $this->input->post('email');
		$password = $this->input->post('password');

		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		// jika usernya ada
		if ($user) {
			// jika usernya aktif
			if ($user['is_active'] == 1) {
				// cek password
				if (password_verify($password, $user['password'])) {
					if (isset($_POST['remember'])) {
						setcookie('verkey', $user['id'], time() + ( 60 * 60 * 24 * 30));
						setcookie('key', hash('sha256', $user['name'], time() + ( 60 * 60 * 24 * 30)));
						$data = [
							'email' => $user['email'],
							'role_id' => $user['role_id']
						];
						$this->session->set_userdata($data);
						if ($user['role_id'] == 1) {
							redirect('admin');
						} else {
							redirect('user');
						}
					} else {
						// remember me tidak di ceklis
						$data = [
							'email' => $user['email'],
							'role_id' => $user['role_id']
						];
						$this->session->set_userdata($data);
						if ($user['role_id'] == 1) {
							redirect('admin');
						} else {
							redirect('user');
						}
					}
				} else {
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong password!</div>');
					redirect('auth');
				}
			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">This email has not been activated!</div>');
				redirect('auth');
			}
		} else {
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">email is not registered!</div>');
			redirect('auth');
		}
	}

	public function registration()
	{
		if($this->session->userdata('email')) {
			redirect('user');
		}
		$this->form_validation->set_rules('name', 'Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
			'is_unique' => 'This email has already registered, please login'
		]);
		$this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[7]|matches[password2]', [
			'matches' => "password don't match!",
			'min_length' => 'Password too short! at least 7'
		]);
		$this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');


		if ($this->form_validation->run() == false) {
			$data['title'] = 'Nafrozen | murah mudah cepat';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/registration');
			$this->load->view('templates/auth_footer');
		} else {
			$email = $this->input->post('email', true);
			$data = [
				'name' => htmlspecialchars($this->input->post('name', true)),
				'email' => htmlspecialchars($email),
				'image' => 'default.jpg',
				'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
				'role_id' => 2,
				'is_active' => 0,
				'date_created' => time()
			];

			// siapkan token
			$token = base64_encode(random_bytes(32));
			$user_token = [
				'email' => $email,
				'token' => $token,
				'date_created' => time()
			];

			$this->db->insert('user', $data);
			$this->db->insert('user_token', $user_token);

			$this->_sendEmail($token, 'verify');

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">We have sent you an email to please check your email to activate your account!</div>');
			redirect('auth');
		}
	}


	private function _sendEmail($token, $type)
	{
		 $config = [
            'protocol'  => 'smtp',
            'smtp_host' => 'ssl://smtp.gmail.com',
            'smtp_user' => 'Atha.3417@gmail.com',
            'smtp_pass'   => 'htmlcssjsphp',
            'smtp_port'   => 465,
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'newline' => "\r\n"
        ];

        $this->load->library('email', $config);
        $this->load->library('email',$config);  
		$this->email->initialize($config); 

        $this->email->from('Atha.3417@gmail.com', 'Nafrozen food');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
        	$this->email->subject('Account Verification');
        	$this->email->message('Click this link to verify your account : <a href="'.base_url() . 'auth/verify?email=' .$this->input->post('email') .'&token=' . urlencode($token) . '">Activate</a>');
        } else if ($type == 'forgot') {
        	$this->email->subject('Reset Password');
        	$this->email->message('Click this link to verify your account : <a href="'.base_url() . 'auth/resetpassword?email=' .$this->input->post('email') .'&token=' . urlencode($token) . '">Reset Password</a>');
        }

        if ($this->email->send()) {
        	return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }

	}



	public function verify()
	{
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		if ($user) {
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();

			if ($user_token) {
				if(time() - $user_token['date_created'] < (60 * 60 * 24)) {
					$this->db->set('is_active', 1);
					$this->db->where('email', $email);
					$this->db->update('user');

					$this->db->delete('user_token', ['email' => $email]);

					$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">'. $email .' has been activated! please login!</div>');
					redirect('auth');
				} else {

					$this->db->delete('user', ['email' => $email]);
					$this->db->delete('user_token', ['email' => $email]);

					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account verification failed! Token expired!</div>');
					redirect('auth');
				}
			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account verification failed! Wrong token!</div>');
				redirect('auth');
			}

		} else {
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Account verification failed! Wrong email!</div>');
			redirect('auth');
		}
	}


	public function logout()
	{
		setcookie('varkey', '', time() - 3600);
		setcookie('key', '', time() - 3600);

		$this->session->unset_userdata('email');
		$this->session->unset_userdata('role_id');
		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">You have been logged out!</div>');
		redirect('auth');
	}


	public function blocked()
	{
		$this->load->view('auth/blocked');
	}


	public function forgotPassword()
	{
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		if ($this->form_validation->run() == false) {
			$data['title'] = 'Forgot Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/forgot-password');
			$this->load->view('templates/auth_footer');			
		} else {
			$email = $this->input->post('email');
			$user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

			if ($user) {
				$token = base64_encode(random_bytes(32));
				$user_token = [
					'email' => $email,
					'token' => $token,
					'date_created' => time()
				];

				$this->db->insert('user_token', $user_token);
				$this->_sendEmail($token, 'forgot');


				$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Please check your email to reset your password!</div>');
				redirect('auth');
			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email is not registered or activated!</div>');
				redirect('auth');
			}
		}
	}


	public function resetPassword()
	{
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		if ($user) {
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
			if ($user_token) {
				$this->session->set_userdata('reset_email', $email);
				$this->changePassword();
			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password failed! Wrong token!</div>');
				redirect('auth');
			}
		} else {
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password failed! Wrong email!</div>');
			redirect('auth');
		}
	}

	public function changePassword()
	{
		if (!$this->session->userdata('reset_email')) {
			redirect('auth');
		}

		$this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[7]|matches[password2]');
		$this->form_validation->set_rules('password2', 'Repeat Password', 'trim|required|matches[password1]');
		if ($this->form_validation->run() == false) {
			$data['title'] = 'Forgot Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/change-password');
			$this->load->view('templates/auth_footer');	
		} else {
			$password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
			$email = $this->session->userdata('reset_email');

			$this->db->set('password', $password);
			$this->db->where('email', $email);
			$this->db->update('user');

			$this->session->unset_userdata('reset_email');

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password changed! please login!</div>');
			redirect('auth');
		}
	}
}
