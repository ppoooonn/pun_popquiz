<?php

class Cli extends CI_Controller {
	public function __construct() {
		parent::__construct();
		if(!is_cli())
			show_404();
	}
	public function add_quiz($title, $enable=false, $problem_time=30, $shuffle=true, $instruction='', $start_time=0)) {
		$this->load->model('quiz');
		echo $this->quiz->create($title, $enable, $problem_time, $shuffle, $instruction, $start_time);
	}

	public function add_examinee($quiz_id, $name, $token=NULL) {
		$this->load->model('examinee');
		echo $this->examinee->create($quiz_id, $name, $token)?'Success':'Failed';
	}
}
