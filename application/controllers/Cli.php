<?php

class Cli extends CI_Controller {
	public function __construct() {
		parent::__construct();
		if(!is_cli())
			show_404();
	}
	public function add_quiz($title, $enable=false, $problem_time=30, $shuffle=true, $instruction='', $start_time=0) {
		$this->load->model('quiz');
		echo $this->quiz->create($title, $enable, $problem_time, $shuffle, $instruction, $start_time);
	}

	public function add_examinee($quiz_id, $name, $token=NULL) {
		$this->load->model('examinee');
		echo $this->examinee->create($quiz_id, $name, $token)?'Success':'Failed';
	}
	public function add_admin($username, $password) {
		$this->load->model('admin_user');
		echo $this->admin->create($username, $password);
	}

	public function get_image_key($examinee_id, $problem_id) {
		$this->load->model('problem');
		print_r( site_url(['exam','image',$this->problem->image_hash($examinee_id, $problem_id),$problem_id]));
	}

	public function get_seen_problems($quiz_id, $examinee_id) {
		$this->load->model('problem');
		print_r( $this->problem->get_seen_problems($quiz_id, $examinee_id));
	}
}
