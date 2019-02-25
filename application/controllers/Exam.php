<?php

class Exam extends CI_Controller {
	use CiPug;

	public function __construct() {
		parent::__construct();

		$this->output->enable_profiler(TRUE);
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
	public function quiz($order=NULL) {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->load->model('problem');
		if($this->session->problem_order === NULL){
			// First time access, generate random problem order
			$problems = $this->problem->get_seen_problems($this->session->quiz_id, $this->session->examinee_id);
			$this->session->problem_order = count($problems['seen'])+1;
			shuffle($problems['unseen']);// TODO: if enable shuffle, quiz timer
			$this->session->problem_list = array_merge($problems['seen'], $problems['unseen']);
		}
		if((int)$order != $this->session->problem_order){
			redirect('/exam/quiz/'.($this->session->problem_order));
		}

		$problem_id = $this->session->problem_list[$this->session->problem_order-1];
		$problem_info = $this->problem->get_problem_info($this->session->examinee_id, $problem_id);

		if($this->input->post('problem') != NULL){
			// TODO: save result
			$this->session->problem_order = $this->session->problem_order + 1;
			if($this->session->problem_order > count($this->session->problem_list))
				redirect('/exam/finish');
			else
				redirect('/exam/quiz/'.($this->session->problem_order));
		} else {
			$this->view([
				'name' => $this->session->name,
				'quiz_title' => $this->session->quiz_title,
				'problem' => [
					'order' => $this->session->problem_order,
					'count' => count($this->session->problem_list),
					'choice_count' => $problem_info['choices'],
					'image' => $problem_info['image_main'],
					'image_large' => $problem_info['image_aux'],
					'timer' => 30,
				]
			]);
		}
	}
	public function finish() {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->view([
			'name' => $this->session->name,
			'quiz_title' => $this->session->quiz_title,
		]);
	}
	public function image($key=NULL, $problem_id=NULL) {
		if($this->session->examinee_id === NULL)
			show_404();
		if($problem_id ==- NULL or $key === NULL)
			show_404();
		$aux = false;
		if($problem_id{-1} == 'X'){
			$aux = true;
			$problem_id = substr($problem_id,0,-1);
		}
		$problem_id = (int)($problem_id);
		$this->load->helper('file');
		$this->load->model('problem');
		if($this->problem->image_hash($this->session->examinee_id, $problem_id) != $key)
			show_404();

		$file = $this->problem->get_filename($problem_id, $aux);
		if($file === NULL)
			show_404();
		$file = $this->config->item('data_path').$file;
		$info = get_file_info($file);
		$this->output->set_content_type(get_mime_by_extension($info['name']))
					->set_header('Content-Length: ' . $info['size'])
					->set_output(file_get_contents($info['server_path']));
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/exam/login');
	}
}
