<?php

class Admin extends CI_Controller {

	public function __construct() {
		parent::__construct();
		// $this->output->enable_profiler(TRUE);
	}

	public function index() {
		if($this->session->admin_login !== NULL)
			redirect('/admin/quiz_list');
		redirect('/admin/login');
	}
	public function login() {
		$r = $this->input->get('r');
		$redirect = $r ? '/'.$r  : '/admin/quiz_list';
		if($this->session->admin_login !== NULL)
			redirect($redirect);
		if($this->input->post('username') != NULL){
			$this->load->model('admin_user');
			$resp = $this->admin_user->login(
				$this->input->post('username'),
				$this->input->post('password')
			);
			if($resp===true)
				redirect($redirect);
			$this->load->view('admin/login',$data=[
				'error_msg'=> ($resp),
				'r' => $r
			]);
		} else
			$this->load->view('admin/login',$data=[
				'error_msg'=> NULL,
				'r' => NULL
			]);
	}
	public function quiz_list() {
		if($this->session->admin_login === NULL)
			redirect('/admin/login?r='.uri_string());
		$this->load->model('quiz');
		$quizzes = $this->quiz->list();
		$this->load->view('admin/quiz_list',[
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz' => $quizzes
			]),
		]);
	}
	public function quiz($quiz_id) {
		if($this->session->admin_login === NULL)
			redirect('/admin/login?r='.uri_string());
		$this->load->model('quiz');
		$quiz = $this->quiz->get((int)$quiz_id, true);
		if(!$quiz)
			show_404();
		$this->load->view('admin/quiz',[
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz' => $quiz
			]),
			'quiz_id' => $quiz->quiz_id
		]);
	}
	public function problems($quiz_id) {
		if($this->session->admin_login === NULL)
			redirect('/admin/login?r='.uri_string());
		$this->load->model('quiz');
		$quiz = $this->quiz->get((int)$quiz_id);
		if(!$quiz)
			show_404();
		$this->load->model('problem');
		$problems = $this->problem->list((int)$quiz_id);
		$this->load->view('admin/problems',[
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz_id' => $quiz->quiz_id,
				'problems' => $problems
			]),
			'quiz' => $quiz
		]);
	}
	public function examinee($quiz_id) {
		if($this->session->admin_login === NULL)
			redirect('/admin/login?r='.uri_string());
		$this->load->model('quiz');
		$quiz = $this->quiz->get((int)$quiz_id);
		if(!$quiz)
			show_404();
		$this->load->model('examinee');
		$examinee = $this->examinee->list((int)$quiz_id);
		$this->load->view('admin/examinee',[
			'script_vars' => json_encode([
				'server_time' => time(),
				'quiz_id' => $quiz->quiz_id,
				'examinee' => $examinee
			]),
			'quiz' => $quiz
		]);
	}

	public function image($problem_id) {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$problem_id = (string) $problem_id;
		if($problem_id === NULL or $problem_id === '')
			show_404();
		$aux = false;
		if($problem_id{-1} == 'X'){
			$aux = true;
			$problem_id = substr($problem_id,0,-1);
		}
		$problem_id = (int)($problem_id);
		$this->load->helper('file');
		$this->load->model('problem');

		$file = $this->problem->get_filename($problem_id, $aux);
		if($file === NULL)
			show_404();
		$file = $this->config->item('data_path').$file;
		$info = get_file_info($file);
		$this->output->set_content_type(get_mime_by_extension($info['name']))
					->set_header('Content-Length: ' . $info['size'])
					->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', $info['date']).' GMT')
					->set_header('Cache-Control: no-cache')
					->set_header('Pragma: no-cache')
					->set_header('Expires: '.gmdate('D, d M Y H:i:s', time()+30*60).' GMT');
		if(@strtotime($this->input->server('HTTP_IF_MODIFIED_SINCE')) == $info['date'])
			$this->output->set_status_header(304);
		else
			$this->output->set_output(file_get_contents($info['server_path']));
	}
	public function scores($quiz_id) {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('problem');
		$this->output->set_content_type('text/csv')
					->set_header('Content-Disposition: attachment; filename="score.csv"')
					->set_output("\xEF\xBB\xBF".$this->problem->get_scores((int)$quiz_id));
	}
	public function examinee_download($quiz_id) {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('examinee');
		$examinee = $this->examinee->list((int)$quiz_id);
		$output = "ID,Name,Login,สถาบัน,ชั้นปี\n";
		foreach ($examinee as $row){
			$output .= '"'.
				$row->aux1 .'","'.
				$row->name .'","'.
				$row->login .'","'.
				$row->aux2 .'","'.
				$row->aux3 ."\"\n";
		}
		$this->output->set_content_type('text/csv')
					->set_header('Content-Disposition: attachment; filename="examinee.csv"')
					->set_output("\xEF\xBB\xBF".$output);
	}


	public function upload() {
		// TODO: multi-user
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		if(!@$_FILES['file'])
			show_error('No file.',400);
		$quiz_id = (string) $this->input->post('quiz_id');
		$problem_id = (string) $this->input->post('problem_id');
		if(!$problem_id)
			$problem_id = 'new';
		$aux = false;

		$this->load->helper('file');
		$config['upload_path']   = $this->config->item('data_path').'temp/';
		$config['file_name'] = 'Q'.$quiz_id.'p'.$problem_id.'f';
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		$config['overwrite'] = $problem_id=='new'?FALSE:TRUE;
		$this->load->library('upload', $config);

		if($this->upload->do_upload('file'))
			$out = ['url' => '/admin/upload_preview/'.$this->upload->data('file_name'),
					'filename' => $this->upload->data('file_name')];
		else
			$out = ['error' => $this->upload->display_errors()];
		$this->output
		->set_content_type('application/json')
		->set_output(json_encode($out));
	}

	public function upload_preview($file) {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->helper('file');
		if($file === NULL or $file === '')
			show_404();
		$file = $this->config->item('data_path').'temp/'.$file;
		$info = get_file_info($file);
		if(!$info)
			show_404();
		$this->output->set_content_type(get_mime_by_extension($info['name']))
					->set_header('Content-Length: ' . $info['size'])
					->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', $info['date']).' GMT')
					->set_header('Cache-Control: no-cache')
					->set_header('Pragma: no-cache')
					->set_header('Expires: '.gmdate('D, d M Y H:i:s', time()+30*60).' GMT');
		if(@strtotime($this->input->server('HTTP_IF_MODIFIED_SINCE')) == $info['date'])
			$this->output->set_status_header(304);
		else
			$this->output->set_output(file_get_contents($info['server_path']));
	}

	// API
	public function api_problem_set() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);

		$quiz_id = (int)$this->input->post('quiz_id'); // TODO: check exists
		$problem_id = (int)$this->input->post('problem_id');
		$image_main = $this->input->post('image_main')?:'';
		if(!$problem_id && (!$quiz_id || $image_main == ''))
			show_error('Bad param', 400);
		$image_aux = $this->input->post('image_aux')?:'';
		$choices = (int)$this->input->post('choices');
		$correct_choice = (int)$this->input->post('correct_choice');

		$this->load->model('problem');
		$froot = $this->config->item('data_path');

		$payload = [];
		if($choices)
			$payload['choices'] = $choices;
		if($correct_choice){ // TODO: check > choice
			$payload['correct_choice'] = $correct_choice;
		}
		if(!$problem_id){
			$payload['image_main'] = '';
			$problem_id = $this->problem->create($quiz_id, $payload);
			$oldprob = NULL;
		}else{
			$oldprob = $this->problem->get_admin($problem_id, true);
		}
		if($image_main){
			if(strpos($image_main, '/') !== false)
				show_error('Slash in filename.', 400);
			$temppath = $froot.'temp/'.$image_main;
			if(!file_exists($temppath))
				show_error('File not found.', 400);
			@mkdir($froot.'Q'.$quiz_id);
			$image_main = 'Q'.$quiz_id.'/p'.$problem_id.'f.'.pathinfo($image_main, PATHINFO_EXTENSION);
			if($oldprob && $oldprob->image_main && $oldprob->image_main != $froot.$image_main)
				@unlink($froot.$oldprob->image_main);
			rename($temppath, $froot.$image_main);
			$payload['image_main'] = $image_main;
		}
		if($image_aux){
			if($image_aux == '/remove/')
				$payload['image_aux'] = NULL;
			else {
				if(strpos($image_aux, '/') !== false)
					show_error('Slash in filename.', 400);
				$temppath = $froot.'temp/'.$image_aux;
				if(!file_exists($temppath))
					show_error('File not found.', 400);
				$image_aux = 'Q'.$quiz_id.'/p'.$problem_id.'x.'.pathinfo($image_aux, PATHINFO_EXTENSION);
				if($oldprob && $oldprob->image_aux && $oldprob->image_aux != $froot.$image_aux)
					@unlink($froot.$oldprob->image_aux);
				rename($temppath, $froot.$image_aux);
				$payload['image_aux'] = $image_aux;
			}
		}

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'problem' => $this->problem->edit($problem_id, $payload) //TODO: send feedback
		]));
	}
	public function api_problem_delete() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);

		$this->load->model('problem');
		$row = $this->problem->get_admin((int)$this->input->post('problem_id'),true);
		if(!$row)
			show_error('Bad param',400);
		if($row->image_main)
			@unlink($this->config->item('data_path').$row->image_main);
		if($row->image_aux)
			@unlink($this->config->item('data_path').$row->image_aux);
		$resp = $this->problem->delete((int)$this->input->post('problem_id'));

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode(($resp===true)?
			[
				'success' => true
			]:[
				'error' => $resp
			]
		));
	}
	public function api_preview_cancel() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$filename = $this->input->post('filename')?:'';
		if($filename == '' or strpos($filename, '/') !== false)
			show_error('Bad filename.', 400);
		@unlink($this->config->item('data_path').'temp/'.$filename);
	}
	public function api_examinee_list() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('examinee');
		$examinee = $this->examinee->list((int)$this->input->post('quiz_id'));

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'examinee' => $examinee
		]));
	}
	public function api_examinee_create() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('examinee');
		$success = $this->examinee->create([
			'quiz_id' => (int)$this->input->post('quiz_id'),
			'name' => $this->input->post('name')?:'',
			'aux1' => $this->input->post('aux1')?:'',
			'aux2' => $this->input->post('aux2')?:'',
			'aux3' => $this->input->post('aux3')?:'',
		]);
		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'examinee' => $success
		]));
	}
	public function api_examinee_delete() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('examinee');

		$examinee_ids = json_decode($this->input->post('examinee_id'));
		foreach($examinee_ids as $id){
			$this->examinee->delete((int)$this->input->post('quiz_id'), (int)$id);
		}
		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'success' => true
		]));
	}
	public function api_quiz_list() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$quizzes = $this->quiz->list();

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $quizzes
		]));
	}
	public function api_quiz_create() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$success = $this->quiz->create('New Quiz');
		$this->api_quiz_list();
	}
	public function api_quiz_delete() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$this->load->model('quiz');
		$resp = $this->quiz->delete((int)$this->input->post('quiz_id'));

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode(($resp===true)?
			[
				'success' => true
			]:[
				'error' => $resp
			]
    	));
	}
	public function api_quiz_edit() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$quiz_id = (int)$this->input->post('quiz_id');
		$this->load->model('quiz');
		$resp = $this->quiz->edit(
			$quiz_id,
			[
				'title' => $this->input->post('title')?:'',
				'shuffle_flag' => boolval($this->input->post('shuffle_flag')),
				'enable' => boolval($this->input->post('enable')),
				'start_time' => (int)$this->input->post('start_time'),
				'duration' => (int)$this->input->post('duration'),
				'problem_time' => (int)$this->input->post('problem_time'),
				'instruction' => $this->input->post('instruction')?:'',
		]);
		$quizzes = $this->quiz->list();

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $resp
		]));
	}
	public function api_quiz_enable() {
		if($this->session->admin_login === NULL)
			show_error('Not logged in.', 403);
		$quiz_id = (int)$this->input->post('quiz_id');
		$this->load->model('quiz');
		$resp = $this->quiz->edit(
			$quiz_id,
			[
				'enable' => boolval($this->input->post('enable')),
		]);
		$quizzes = $this->quiz->list();

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'server_time' => time(),
			'quiz' => $resp
		]));
	}
	public function logout() {
		$this->session->sess_destroy();
		redirect('/admin/login');
	}
}
