<?php
class Examinee extends CI_Model {
	const TOKEN_CHARS = 'ybndrfg8ejkmcpqxot1uwisza345h769';
	const TOKEN_LENGTH = 8;
	const TOKEN_RETRY_COUNT = 5;

	public function __construct()
	{
		$this->load->database();
	}
	public function login($token){
		$query = $this->db
		->select(['examinee_id','examinee.quiz_id','examinee.name','quiz.title','quiz.enable','quiz.shuffle_flag','quiz.problem_time','quiz.start_time','quiz.end_time'])
		->join('quiz', 'examinee.quiz_id = quiz.quiz_id')
		->where('login', $token)
		->get('examinee', 1);
		$row = $query->row();
		if(!$row)
			return 'Invalid Token';
		if(!$row->enable)
			return 'Quiz not available yet';
		if($row->end_time!=0 && $row->end_time<time())
			return 'Quiz has been over';
		$this->session->examinee_id = $row->examinee_id;
		$this->session->quiz_id = $row->quiz_id;
		$this->session->quiz_title = $row->title;
		$this->session->quiz_shuffle = $row->shuffle_flag;
		$this->session->quiz_start_time = $row->start_time;
		$this->session->quiz_timer = $row->problem_time;
		$this->session->name = $row->name;
		return true;
	}
	private function _generate_token(){
		$token = '';
		for($i=0;$i<self::TOKEN_LENGTH;$i++){
			$token .= self::TOKEN_CHARS[random_int(0,strlen(self::TOKEN_CHARS)-1)];
		}
		return $token;
	}
	public function create($quiz_id, $name, $token=NULL){
		if($token===NULL){
			for($retry = 0;$retry < self::TOKEN_RETRY_COUNT;$retry++){
				$token = $this->_generate_token();
				$query = $this->db
				->select(['examinee_id'])
				->get_where('examinee', [
					'login'=> $token
				], 1);
				if($query->num_rows() === 0)
					break;
			}
			if($retry == self::TOKEN_RETRY_COUNT)
				return 0;
		}
		$this->db
		->insert('examinee',[
			'quiz_id' => $quiz_id,
			'login' => $token,
			'name' => $name
		]);
		return $this->db->insert_id();
	}
}