<?php
class Problem extends CI_Model {
	public function get_filename($problem_id, $aux=false){
		$this->load->database();
		$row = $this->db
		->select($aux?'image_aux':'image_main')
		->get_where('problems',[
			'problem_id' => $problem_id
		])->row_array();
		if(!$row)
			return NULL;
		return $row[$aux?'image_aux':'image_main'];
	}
	public function image_hash($examinee_id, $problem_id){
		return substr(hash("sha256", $examinee_id.':'.$problem_id.':'.$this->config->item('salt')),64-16);
	}
	public function get_problem_info($examinee_id, $problem_id){
		$this->load->database();
		$row = $this->db
		->select(['image_aux','choices'])
		->get_where('problems',[
			'problem_id' => $problem_id
		])->row();
		if(!$row)
			return NULL;
		$hash = $this->image_hash($examinee_id, $problem_id);
		return [
			'image_main' => site_url(['exam','image',$hash,$problem_id]),
			'image_aux' => $row->image_aux !== NULL?site_url(['exam','image',$hash,$problem_id.'X']):NULL,
			'choices' => $row->choices,
		];
	}

	public function get_seen_problems($quiz_id, $examinee_id){
		$this->load->database();
		$query = $this->db
		->select(['problems.problem_id', 'answers.start_time', 'answers.submit_time'])
		->join('answers','problems.problem_id = answers.problem_id and answers.examinee_id = '.$this->db->escape($examinee_id),'left')
		->where('problems.quiz_id' , $quiz_id)
		->order_by('problems.order', 'ASC')
		->get('problems');
		$seen = []; $unseen = []; $started = [];
		foreach ($query->result() as $row){
			if($row->start_time === NULL)
				$unseen[] = $row->problem_id;
			elseif($row->submit_time === NULL)
				$started[] = $row->problem_id;
			else
				$seen[] = $row->problem_id;
		}
		return ['unseen'=>$unseen, 'seen'=>$seen, 'started'=>$started];
	}

	public function start_problem($examinee_id, $problem_id, $problem_timer=30, $loading_timer=60){
		$this->load->database();
		$row = $this->db
		->select(['start_time', 'loaded_time', 'submit_time'])
		->get_where('answers',[
			'examinee_id' => $examinee_id,
			'problem_id' => $problem_id
		])->row();
		if(!$row){
			// First time
			$start_time = $this->time();
			$this->db->insert('answers',[
				'examinee_id' => $examinee_id,
				'problem_id' => $problem_id,
				'start_time' => $start_time
			]);
			return ['start_time' => $start_time, 'loaded_time' => NULL];
		}else{
			if($row->submit_time !== NULL)
				return false;
			if($row->loaded_time !== NULL and $row->loaded_time < $this->time() - $problem_timer){
				// Time limit exceed
				$this->discard_answer($examinee_id, $problem_id);
				return false;
			}
			// TODO: config discard long load answer
			// if($row->start_time < $this->time() - $problem_timer - $loading_timer){
			// }
			return ['start_time' => $row->start_time, 'loaded_time' => $row->loaded_time];
		}
	}

	private function discard_answer($examinee_id, $problem_id){
		$this->db->where([
			'examinee_id' => $examinee_id,
			'problem_id' => $problem_id
		])->update('answers',[
			'answer' => 0,
			'submit_time' => $this->time()
		]);
	}

	public function save_answer($examinee_id, $problem_id, $answer, $problem_timer=30, $loading_timer=60){
		$this->load->database();
		$row = $this->db
		->select(['start_time', 'loaded_time', 'submit_time'])
		->get_where('answers',[
			'examinee_id' => $examinee_id,
			'problem_id' => $problem_id
		])->row();
		if(!$row){
			return false;
		}else{
			if($row->submit_time !== NULL) // Submitted
				return false;
			if($row->loaded_time !== NULL and $row->loaded_time < $this->time() - $problem_timer){
				// Time limit exceed
				$this->discard_answer($examinee_id, $problem_id);
				return false;
			}
			// TODO: discard long load answer
			$this->db->where([
				'examinee_id' => $examinee_id,
				'problem_id' => $problem_id
			])->update('answers',[
				'answer' => $answer,
				'submit_time' => $this->time()
			]);
			return true;
		}
	}

	public function load_problem($examinee_id, $problem_id, $loading_timer=60){
		$this->load->database();
		$row = $this->db
		->select(['loaded_time'])
		->get_where('answers',[
			'examinee_id' => $examinee_id,
			'problem_id' => $problem_id
		])->row();
		if(!$row){
			return false;
		}else{
			if($row->loaded_time !== NULL){
				return false;
			}
			$this->db->where([
				'examinee_id' => $examinee_id,
				'problem_id' => $problem_id
			])->update('answers',[
				'loaded_time' => $this->time()
			]);
			return true;
		}
	}

	public function list($quiz_id){
		$this->load->database();
		$result = $this->db
		->select([
			'problem_id','!ISNULL(image_main) as image_main','!ISNULL(image_aux) as image_aux','order','choices','correct_choice'
		])
		->order_by('order', 'ASC')
		->order_by('problem_id', 'ASC')
		->get_where('problems',[
			'quiz_id' => $quiz_id
		])->result();
		// handle exception
		return $result;

		// if(!$row)
		// 	return NULL;
		// $hash = $this->image_hash($examinee_id, $problem_id);
		// return [
		// 	'image_main' => site_url(['exam','image',$hash,$problem_id]),
		// 	'image_aux' => $row->image_aux !== NULL?site_url(['exam','image',$hash,$problem_id.'X']):NULL,
		// 	'choices' => $row->choices,
		// ];
	}

	public function time(){
		return $this->input->server('REQUEST_TIME');
	}
}