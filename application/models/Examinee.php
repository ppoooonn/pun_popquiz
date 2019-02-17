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
		->select(['examinee_id','examinee.quiz_id','examinee.name','quiz.title'])
		->join('quiz', 'examinee.quiz_id = quiz.quiz_id')
		->where('login', $token)
		->get('examinee', 1);
		$row = $query->row();
		if(!$row)
			return false;
		$this->session->examinee_id = $row->examinee_id;
		$this->session->quiz_id = $row->quiz_id;
		$this->session->quiz_title = $row->title;
		$this->session->name = $row->name;
		return true;
	}
	private function _generate_token(){
		$token = '';
		for($i=0;$i<self::TOKEN_LENGTH;$i++){
			$token .= self::TOKEN_CHARS[random_int(0,strlen(self::TOKEN_CHARS)-1)];
		}
		// $token = 'yyyy';
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