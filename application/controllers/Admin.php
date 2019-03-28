<?php

class Admin extends CI_Controller {
	use CiPug;

	public function __construct() {
		parent::__construct();
		// $this->output->enable_profiler(TRUE);
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

		$this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $quizzes
		]));
	}
	public function api_quiz_create() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$success = $this->quiz->create('New Quiz');
		$this->api_quiz_list();
	}
	public function api_quiz_delete() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$resp = $this->quiz->delete((int)$this->input->post('quiz_id'));

		$this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(($resp===true)?
        	[
				'success' => true
			]:[
				'error' => $resp
			]
    	));
	}
	public function api_quiz_edit() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$quiz_id = (int)$this->input->post('quiz_id');
		$this->load->model('quiz');
		$resp = $this->quiz->edit(
			$quiz_id,
			[
				'title' => $this->input->post('title')?:'',
				'shuffle_flag' => boolval($this->input->post('shuffle_flag')),
				'enable' => boolval($this->input->post('enable')),
				'start_time' => (int)$this->input->post('start_time'),
				'duration' => (int)$this->input->post('duration'),
				'problem_time' => (int)$this->input->post('problem_time'),
				'instruction' => $this->input->post('instruction')?:'',
		]);
		$quizzes = $this->quiz->list();

		$this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $resp
		]));
	}
	public function api_quiz_enable() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$quiz_id = (int)$this->input->post('quiz_id');
		$this->load->model('quiz');
		$resp = $this->quiz->edit(
			$quiz_id,
			[
				'enable' => boolval($this->input->post('enable')),
		]);
		$quizzes = $this->quiz->list();

		$this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $resp
		]));
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/admin/login');
	}
}
