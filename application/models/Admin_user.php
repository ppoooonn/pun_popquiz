<?php
class Admin_user extends CI_Model {

	public function __construct()
	{
		$this->load->database();
	}
	public function login($username, $password){
		$query = $this->db
		->select(['admin_id', 'password'])
		->where([
			'username' => $username
		])
		->get('admin', 1);
		$row = $query->row();
		if(!$row or !password_verify($username.$password, $row->password))
			return 'Incorrect username or password';
		$this->session->admin_login = $row->admin_id;
		return true;
	}
	public function create($username, $password){ // clean username
		$query = $this->db
		->get_where('admin', [
			'username' => $username
		], 1);
		if ($query->num_rows() != 0) {
			return 'username exists';
		}
		$this->db
		->insert('admin',[
			'username' => $username,
			'password' => password_hash($username.$password, PASSWORD_DEFAULT)
		]);
		return true;
	}
}