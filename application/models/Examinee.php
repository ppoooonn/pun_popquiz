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
		->select(['examinee_id','examinee.quiz_id','examinee.name','quiz.enable','quiz.start_time','quiz.duration'])
		->join('quiz', 'examinee.quiz_id = quiz.quiz_id')
		->where('login', $token)
		->get('examinee', 1);
		$row = $query->row();
		if(!$row)
			return 'Invalid Token';
		if(!$row->enable)
			return 'Quiz not available yet';
		if($row->duration!=0 && $row->start_time!=0 && $row->start_time+$row->duration<time())
			return 'Quiz has been over';
		$this->session->examinee_id = $row->examinee_id;
		$this->session->quiz_id = $row->quiz_id;
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
	public function create($data, $token=NULL){
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
			$data['login'] = $token;
		}
		$this->db
		->insert('examinee',$data);
		$data['examinee_id'] = $this->db->insert_id();
		return $data;
	}
	public function update($examinee_id ,$data){
		$resp = $this->db
		->where('examinee_id', $examinee_id)
		->update('examinee', $data);
	}
	public function update_or_create($data){
		// TODO: change to ON DUPLICATE KEY UPDATE
		if(isset($data['examinee_id'])){
			$newdata = $data;
			unset($newdata['examinee_id']);
			$this->update($data['examinee_id'], $data);
			return $data;
		}
		$row = $this->db
		->select(['examinee_id'])
		->get_where('examinee', [
			'name'=> $data['name']
		], 1)->row();
		if($row){
			$newdata = $data;
			unset($newdata['name']);
			$this->update($row->examinee_id, $newdata);
			$data['examinee_id'] = $row->examinee_id;
			return $data;
		}
		return $this->create($data);
	}
	public function list($quiz_id){
		$result = $this->db
		->select([
			'examinee_id',
			'login',
			'name',
			'aux1',
			'aux2',
			'aux3'
		])
		->order_by('examinee_id', 'ASC')
		->get_where('examinee',[
			'quiz_id' => $quiz_id
		])->result();
		// handle exception
		return $result;
	}
	public function delete($quiz_id, $examinee_id){
		$this->db
		->delete('examinee',[
			'quiz_id' => $quiz_id,
			'examinee_id' => $examinee_id
		]);
		return true;
	}
}