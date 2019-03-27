<?php

class Admin extends CI_Controller {
	use CiPug;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		if($this->session->admin_login !== NULL)
			redirect('/admin/quiz_list');
		redirect('/admin/login');
	}
	public function login() {
		if($this->session->admin_login !== NULL)
			redirect('/admin/quiz_list');
		if($this->input->post('username') != NULL){
			$this->load->model('admin_user');
			$resp = $this->admin_user->login(
				$this->input->post('username'),
				$this->input->post('password')
			);
			if($resp===true)
				redirect('/admin/quiz-list');
			$this->view($data=[
				'error_msg'=> ($resp)
			]);
		} else
			$this->view();
	}
	public function quiz_list() {
		if($this->session->admin_login === NULL)
			redirect('/admin/login');
		$this->load->model('quiz');
		$quizzes = $this->quiz->list();
		$this->view([
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz' => $quizzes
			]),
		]);
	}
	public function quiz($quiz_id) {
		if($this->session->admin_login === NULL)
			redirect('/admin/login');
		$this->load->model('quiz');
		$quiz = $this->quiz->get((int)$quiz_id);
		if(!$quiz)
			show_404();
		$this->view([
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz' => $quiz
			]),
			'quiz_id' => $quiz->quiz_id
		]);
	}
	public function problems($quiz_id) {
		if($this->session->admin_login === NULL)
			redirect('/admin/login');
		$this->load->model('quiz');
		$quiz = $this->quiz->get((int)$quiz_id);
		if(!$quiz)
			show_404();
		show_404();
		$this->view([
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz' => $quiz
			]),
		]);
	}
	public function api_quiz_list() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$quizzes = $this->quiz->list();
		echo json_encode([
			'server_time' => time(),
			'quiz' => $quizzes
		]);
	}
	public function api_quiz_create() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$success = $this->quiz->create('New Quiz');
		// echo json_encode([
		// 	'success' => true
		// ]);
		$this->api_quiz_list();
	}
	public function api_quiz_delete() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$resp = $this->quiz->delete((int)$this->input->post('quiz_id'));
		if ($resp)
			echo json_encode([
				'success' => true
			]);
		else
			echo json_encode([
				'error' => $resp
			]);
	}
	public function api_quiz_edit($quiz_id) {
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/admin/login');
	}
}
