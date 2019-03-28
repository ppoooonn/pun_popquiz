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
			'create_time' => time(),
		]);
		return $this->db->insert_id();
	}
	public function get($quiz_id, $instruction=false){
		$cols = ['quiz_id', 'title', 'shuffle_flag', 'enable', 'start_time', 'problem_time', 'duration'];
		if($instruction)
			$cols[] = 'instruction';
		$result = $this->db
		->select($cols)
		->get_where('quiz',[
			'quiz_id' => $quiz_id
		])->row();
		// handle exception
		return $result;
	}
	public function edit($quiz_id, $data){
		$resp = $this->db
		->where('quiz_id', $quiz_id)
		->update('quiz', $data);
		return $this->get($quiz_id, true);
	}
	public function list(){
		$result = $this->db
		->select([
			'quiz_id',
			'title',
			'enable',
			'start_time',
			'duration'
		])
		->order_by('quiz_id', 'DESC')
		->get('quiz')->result();
		// handle exception
		return $result;
	}
	public function delete($quiz_id){
		$this->db
		->delete('quiz',[
			'quiz_id' => $quiz_id
		]);
		return true;
	}
}