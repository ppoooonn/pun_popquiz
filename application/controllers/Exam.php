<?php

class Exam extends CI_Controller {
	use CiPug;

	public function __construct() {
		parent::__construct();

		// $this->output->enable_profiler(TRUE);
	}

	public function index() {
		if($this->session->examinee_id !== NULL)
			redirect('/exam/lounge');
		redirect('/exam/login');
	}
	public function login() {
		if($this->session->examinee_id !== NULL)
			redirect('/exam/lounge');
		if($this->input->post('token') != NULL){
			$this->load->model('examinee');
			$resp = $this->examinee->login(
				$this->input->post('token')
			);
			if($resp===true)
				redirect('/exam/lounge');
			$this->load->view('exam/login',$data=[
				'error_msg'=> ($resp)
			]);
		} else
			$this->load->view('exam/login',$data=[
				'error_msg'=> NULL
			]);
	}
	public function lounge() {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->load->model('quiz');
		$quiz = $this->quiz->get($this->session->quiz_id, true);
		$this->load->view('exam/lounge',[
			'name' => $this->session->name,
			'quiz_title' => $quiz->title,
			'script_vars' => json_encode([
				'start_time' => $quiz->start_time,
				'server_time' => time(),
			]),
			'instruction' => $quiz->instruction,
		]);
	}
	public function problem($order=NULL) {
		$examinee_id = $this->session->examinee_id;
		if($examinee_id === NULL)
			redirect('/exam/login');
		$this->load->model('problem');
		if($this->session->problem_count === NULL){
			// First time access
			$this->load->model('quiz');
			$quiz = $this->quiz->get($this->session->quiz_id);
			if($quiz->start_time > time())
				redirect('/exam/lounge');
			$this->session->quiz_title = $quiz->title;
			$this->session->quiz_timer = $quiz->problem_time;

			// Generate random problem order
			$problems = $this->problem->get_seen_problems($this->session->quiz_id, $examinee_id);
			$this->session->problem_count = count($problems['seen'])+count($problems['unseen']);
			if($quiz->shuffle_flag)
				shuffle($problems['unseen']);
			$this->session->problem_list = array_merge($problems['started'], $problems['unseen']);
		}
		if(empty($this->session->problem_list))
			redirect('/exam/finish');
		$problem_order = $this->session->problem_count - count($this->session->problem_list) + 1;
		if((int)$order != $problem_order){
			redirect('/exam/problem/'.($problem_order));
		}

		$problem_id = $this->session->problem_list[0];
		$problem_info = $this->problem->get_problem_info($examinee_id, $problem_id);
		// TODO: combine query?
		$time = $this->problem->start_problem($examinee_id, $problem_id, $this->session->quiz_timer + 5);

		if($time === false or (int)($this->input->post('problem')) === $problem_order){
			$this->problem->save_answer($examinee_id, $problem_id, (int)($this->input->post('choice')?:0), $this->session->quiz_timer + 5);

			// Next problem
			$this->session->problem_list = array_slice($this->session->problem_list, 1);
			if(empty($this->session->problem_list))
				redirect('/exam/finish');
			else
				redirect('/exam/problem/'.($problem_order + 1));
		} else {
			$this->load->view('exam/problem',[
				'name' => $this->session->name,
				'quiz_title' => $this->session->quiz_title,
				'problem' => [
					'order' => $problem_order,
					'count' => $this->session->problem_count,
					'choice_count' => $problem_info['choices'],
					'image' => $problem_info['image_main'],
					'image_large' => $problem_info['image_aux'],
				],
				'script_vars' => json_encode([
					'end_time' => $time['loaded_time'] !== NULL?(
						$time['loaded_time'] + $this->session->quiz_timer):(
						$time['start_time'] + 60 + $this->session->quiz_timer),
					'server_time' => time(),
					'problem_timer' => $this->session->quiz_timer,
					'problem_order' => $problem_order,
				]),
				'abc'=> function($x){ return 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[$x-1];}
			]);
		}
	}
	public function problem_loaded($order=NULL) {
		if($this->session->examinee_id === NULL)
			show_error('Not logged in.', 403);
		if(!$this->session->problem_list)
			show_error('Not started yet.', 400);
		$problem_order = $this->session->problem_count - count($this->session->problem_list) + 1;
		if((int)($this->input->post('problem')?:$order) === $problem_order){
			$this->load->model('problem');
			$problem_id = $this->session->problem_list[0];
			$this->problem->load_problem($this->session->examinee_id, $problem_id);
		} else show_404();
	}
	public function finish() {
		if($this->session->examinee_id === NULL)
			redirect('/exam/login');
		$this->load->view('exam/finish',[
			'name' => $this->session->name,
			'quiz_title' => $this->session->quiz_title,
		]);
	}
	public function image($key=NULL, $problem_id=NULL) {
		if($this->session->examinee_id === NULL)
			show_error('Not logged in.', 403);
		$problem_id = (string) $problem_id;
		$key = (string) $key;
		if($problem_id === NULL or $problem_id === '' or $key === NULL)
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
					->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', $info['date']).' GMT')
					->set_header('Cache-Control: no-cache')
					->set_header('Pragma: no-cache')
					->set_header('Expires: '.gmdate('D, d M Y H:i:s', time()+30*60).' GMT');
		if(@strtotime($this->input->server('HTTP_IF_MODIFIED_SINCE')) == $info['date'])
			$this->output->set_status_header(304);
		else
			$this->output->set_output(file_get_contents($info['server_path']));
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/exam/login');
	}
}
