<?php
class Quiz extends CI_Model {
	public function __construct()
	{
		$this->load->database();
	}
	public function create($title, $enable=false, $problem_time=30, $shuffle=true, $instruction='', $start_time=0){
		$this->db
		->insert('quiz',[
			'title' => $title,
			'enable' => $enable,
			'problem_time' => $problem_time,
			'shuffle_flag' => $shuffle,
			'instruction' => $instruction,
			'start_time' => $start_time,
		]);
		return $this->db->insert_id();
	}
	public function get($quiz_id){
		$result = $this->db
		->get_where('quiz',[
			'quiz_id' => $quiz_id
		])->row();
		// handle exception
		return $result;
	}
}