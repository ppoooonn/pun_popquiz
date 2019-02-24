<?php

class Exam extends CI_Controller {
	use CiPug;

	public function __construct() {
		parent::__construct();
	}

	public function login() {
		if($this->session->examinee_id !== NULL)
			redirect('/exam/lounge');
		if($this->input->post('token') != NULL){
			$this->load->model('examinee');
			$success = $this->examinee->login(
				$this->input->post('token')
			);
			if($success)
				redirect('/exam/lounge');
			$this->view($data=[
				'error_msg'=> ('Invalid token')
			]);
		} else
			$this->view();
	}
	public function lounge() {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->load->model('quiz');
		$quiz = $this->quiz->get($this->session->quiz_id);
		$this->view([
			'name' => $this->session->name,
			'quiz_title' => $this->session->quiz_title,
			'script_vars' => json_encode([
				'start_time' => $quiz->start_time,
				'server_time' => time(),
			]),
			'instruction' => $quiz->instruction,
		]);
	}
	public function quiz() {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->view([
			'name' => $this->session->name,
			'quiz_title' => $this->session->quiz_title,
			'problem' => [
				'order' => 1,
				'count' => 3,
				'id' => '0010',
				'choice_count' => 4,
				'image' => base_url('static/img/test2.png'),
				'image_large' => base_url('static/img/test2_l.png'),
				'timer' => 30,
			]
		]);
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/exam/login');
	}
}
