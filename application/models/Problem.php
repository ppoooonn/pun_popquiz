<?php
class Problem extends CI_Model {
	public function __construct()
	{
		$this->load->database();
	}
	public function get_filename($problem_id, $aux=false){
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
		$row = $this->db
		->select(['image_aux','choices'])
		->get_where('problems',[
			'problem_id' => $problem_id
		])->row();
		if(!$row)
			return NULL;
		$hash = $this->image_hash($examinee_id, $problem_id);
		return [
			'image_main' => '/exam/image/'.$hash.'/'.$problem_id,
			'image_aux' => $row->image_aux !== NULL?'/exam/image/'.$hash.'/'.$problem_id.'X':NULL,
			'choices' => $row->choices,
		];
	}

	public function get_seen_problems($quiz_id, $examinee_id){
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
	}
	public function get_admin($problem_id, $image=NULL){
		$row = $this->db
		->select($image?[
			'problem_id','image_main','image_aux','order','choices','correct_choice'
		]:[
			'problem_id','!ISNULL(image_main) as image_main','!ISNULL(image_aux) as image_aux','order','choices','correct_choice'
		])
		->get_where('problems',[
			'problem_id' => $problem_id
		])->row();
		return $row;
	}
	public function create($quiz_id, $payload){
		$payload['quiz_id'] = $quiz_id;
		$this->db
		->insert('problems',$payload);
		return $this->db->insert_id();
	}
	public function edit($problem_id, $data){
		$this->db
		->where('problem_id', $problem_id)
		->update('problems', $data);
		return $this->get_admin($problem_id);
	}
	public function delete($problem_id){
		$this->db
		->delete('problems',[
			'problem_id' => $problem_id
		]);
		return true;
	}

	public function get_scores($quiz_id){
		$result = $this->db
		->select(['examinee.aux1 as id', 'examinee.aux2', 'examinee.aux3', 'examinee.name as name', 'count(answers.answer) as answered', 'sum(answers.answer = problems.correct_choice) as correct'])
		->join('answers','examinee.examinee_id = answers.examinee_id')
		->join('problems','problems.problem_id = answers.problem_id')
		->where('examinee.quiz_id' , $quiz_id)
		->group_by('examinee.examinee_id')
		->get('examinee')->result();
		$output = "ID,Name,สถาบัน,ชั้นปี,Answered,Correct\n";
		foreach ($result as $row){
			$output .= '"'.
				$row->id .'","'.
				$row->name .'","'.
				$row->aux2 .'","'.
				$row->aux3 .'",'.
				$row->answered .','.
				$row->correct ."\n";
		}
		return $output;
	}

	public function get_answers($quiz_id){
		function num2alpha($n){
			for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
				$r = chr($n%26 + 0x41) . $r;
			return $r;
		}
		function abc($x){ return 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[$x-1];}
		$problems = $this->db
		->select([
			'problem_id','correct_choice'
		])
		->order_by('order', 'ASC')
		->order_by('problem_id', 'ASC')
		->get_where('problems',[
			'quiz_id' => $quiz_id
		])->result();

		$examinee = $this->db
		->select([
			'examinee_id',
			'name',
			'aux1',
			'aux2',
			'aux3'
		])
		->order_by('LENGTH(aux1)', 'ASC')
		->order_by('aux1', 'ASC')
		->get_where('examinee',[
			'quiz_id' => $quiz_id
		])->result();

		$answers = $this->db
		->select(['answers.examinee_id','answers.problem_id','answers.answer'])
		->join('answers','problems.problem_id = answers.problem_id')
		->get_where('problems',[
			'problems.quiz_id' => $quiz_id,
		])->result();

		$prob_map = [];

		$output = 'ID,ชื่อ,สถาบัน,ชั้นปี';
		for ($i = 1; $i <= count($problems); $i++){
			$output .= ',ข้อ'.$i.'';
			$prob_map[$problems[$i-1]->problem_id] = $i-1;
		}
		$output .= ",คะแนน\n";
		$dict = [];
		foreach ($answers as $row){
			if($row->answer)
				$dict[$row->examinee_id][$prob_map[$row->problem_id]] = $row->answer;
		}

		$answerrow = '$'.(2+count($examinee));
		$colstart = '$'.num2alpha(4);
		$colend = '$'.num2alpha(count($problems)-1+ 4);
		$rownum = 2;
		foreach ($examinee as $row){
			$output .= '"'.
				$row->aux1 .'","'.
				$row->name .'","'.
				$row->aux2 .'","'.
				$row->aux3 .'"';
			for ($i = 0; $i < count($problems); $i++){
				if(isset($dict[$row->examinee_id]) && isset($dict[$row->examinee_id][$i]))
					$output .= ','.abc($dict[$row->examinee_id][$i]);
				else
					$output .= ',';
			}
			$output .= ',"=SUMPRODUCT(--('.
					$colstart.$rownum.':'.$colend.$rownum.
					'='.
					$colstart.$answerrow.':'.$colend.$answerrow.
					'))"';
			$output .= "\n";
			$rownum++;
		}
		$output .= ',,,เฉลย';
		for ($i = 0; $i < count($problems); $i++){
			$output .= ','.abc($problems[$i]->correct_choice);
		}
		$output .= "\n";

		$output .= ',,,ตอบถูก';
		$rowstart = '$'.(2);
		$rowend = '$'.(count($examinee)-1+2);
		for ($i = 0; $i < count($problems); $i++){
			$col = num2alpha($i+4);
			$output .= ',"=COUNTIF('.
						$col.$rowstart.':'.$col.$rowend.
						','.
						$col.$answerrow.')"';
		}
		$output .= "\n";

		return $output;
	}

	public function time(){
		return $this->input->server('REQUEST_TIME');
	}
}