<?php
class Problem extends CI_Model {
	public function get_filename($problem_id, $aux=false){
		$this->load->database();
		$result = $this->db
		->select($aux?'image_aux':'image_main')
		->get_where('problems',[
			'problem_id' => $problem_id
		]);
		if($result->num_rows()!=1)
			return NULL;
		return $result->row_array()[$aux?'image_aux':'image_main'];
	}
	public function image_hash($examinee_id, $problem_id){
		return substr(hash("sha256", $examinee_id.':'.$problem_id.':'.$this->config->item('salt')),64-16);
	}
	public function get_problem_info($examinee_id, $problem_id){
		$this->load->database();
		$result = $this->db
		->select(['image_aux','choices'])
		->get_where('problems',[
			'problem_id' => $problem_id
		]);
		if($result->num_rows()!=1)
			return NULL;
		$hash = $this->image_hash($examinee_id, $problem_id);
		$row = $result->row();
		return [
			'image_main' => site_url(['exam','image',$hash,$problem_id]),
			'image_aux' => $row->image_aux !== NULL?site_url(['exam','image',$hash,$problem_id.'X']):NULL,
			'choices' => $row->choices,
		];
	}

	public function get_seen_problems($quiz_id, $examinee_id){
		$this->load->database();
		$query = $this->db
		->select(['problems.problem_id', 'answers.examinee_id'])
		->join('answers','problems.problem_id = answers.problem_id and answers.examinee_id = '.(int)($examinee_id),'left')
		->where('problems.quiz_id' , $quiz_id)
		->get('problems');
		$seen = []; $unseen = [];
		foreach ($query->result() as $row){
			if($row->examinee_id === NULL)
				$unseen[] = $row->problem_id;
			else
				$seen[] = $row->problem_id;
		}
		return ['unseen'=>$unseen, 'seen'=>$seen];
	}
}