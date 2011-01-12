<?php
include_once dirname(__FILE__).'/../quiz.php';

class quiz_wrong extends quiz{
	private $m = null;

	function quiz_wrong(){
		parent::wls();
		include_once $this->c->license.'/model/quiz/wrong.php';
		$this->m = new m_quiz_worng();
	}

	public function jsonList(){
		$page = 1;
		if(isset($_POST['start']))$page = ($_POST['start']+$_POST['limit'])/$_POST['limit'];
		$pagesize = 15;
		if(isset($_POST['limit']))$pagesize = $_POST['limit'];
		$data = $this->m->getList($page,$pagesize);
		$data['totalCount'] = $data['total'];
		echo json_encode($data);
	}

	public function viewUpload(){
		echo '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">		
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				</head>
				<body>
					导入EXCEL
					<form action="wls.php?controller=quiz_wrong&action=saveUpload" method="post"
					enctype="multipart/form-data">
						<label for="file">EXCEL文件:</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" name="submit" value="提交" />
					</form>
				</body>
			</html>		
		';
	}

	public function saveUpload(){
		if ($_FILES["file"]["error"] > 0){
			$this->error(array('description'=>'文件上传错误'));
		}else{
			move_uploaded_file($_FILES["file"]["tmp_name"],dirname(__FILE__)."/../../../../file/upload/upload".date('Ymdims').$_FILES["file"]["name"]);
			//			$this->m->create();
			$this->m->importExcel(dirname(__FILE__)."/../../../../file/upload/upload".date('Ymdims').$_FILES["file"]["name"]);
		}

	}

	public function saveUpdate(){
		$data = array(
			'id'=>$_POST['id'],
		$_POST['field']=>$_POST['value']
		);
		if($this->m->update($data)){
			echo "已经更新";
		}else{
			echo "更新失败";			
		}
	}

	public function viewExport(){
		$this->m->id = $_REQUEST['id'];
		$file = $this->m->exportExcel();
		echo "<a href='/".$file."'>下载</a>";
	}

	public function getOne(){
		$id = $_POST['id'];
		echo json_encode($this->m->getList(1,1,array('id'=>$id)));
	}

	public function delete(){
		$this->m->delete($_POST['id']);
	}

	public function getAnswers(){
		sleep(2);

		$ques = $_POST['answersData'];
		$ques_ = array();
		$id_question = '';
		for($i=0;$i<count($ques);$i++){
			$ques_[$ques[$i]['id']] = $ques[$i]['answer'];
			$id_question .= $ques[$i]['id'].",";
		}

		$answers = $this->m->getAnswers($ques_);
		echo json_encode($answers);

		$id_question = substr($id_question,0,strlen($id_question)-1);

		$id = $_POST['id'];
		$item = $this->m->getList(1,1,array('id'=>$id),null,'id,id_level_subject');
		$item = $item['data'][0];

		include_once $this->c->license.'/model/quiz/log.php';
		$obj = new m_quiz_log();
		$data = array(
			'date_created'=>date('Y-m-d H:i:s'),
			'id_question'=>$id_question,
			'id_level_subject'=>$item['id_level_subject'],
			'id_quiz_wrong'=>$id,
			'time_start'=>$_POST['time']['start'],
			'time_stop'=>$_POST['time']['stop'],
			'time_used'=>$_POST['time']['used'],
		);
		$id_quiz_log = $obj->insert($data);

		$count_right = 0;
		$count_wrong = 0;
		$count_giveup = 0;
		$cent = 0;
		$mycent = 0;
		$count_total = count($answers);
		
		$user = $this->getMyUser();
		
		for($i=0;$i<count($answers);$i++){
			unset($answers[$i]['description']);
			if($answers[$i]['myAnswer']=='I_DONT_KNOW'){
				$answers[$i]['correct'] = 2;
				$count_giveup ++;
			}else if($answers[$i]['myAnswer']==$answers[$i]['answer']){
				$answers[$i]['correct'] = 1;
				$count_right ++;
				$mycent += $answers[$i]['cent'];
			}else{
				include_once dirname(__FILE__).'/../../model/quiz/wrong.php';
				$obj_ = new m_quiz_worng();

				$wrong = array(
					'id_question' => $answers[$i]['id'],
					'id_quiz_wrong' => $id,
					'id_level_subject' => $item['id_level_subject'],
					'id_user'=>$user['id'],
					'date_created'=>date('Y-m-d H:i:s'),
				);
				$obj_->insert($wrong);				
				$answers[$i]['correct'] = 0;
				$count_wrong ++;
			}
			$cent += $answers[$i]['cent'];
			$answers[$i]['id_question'] = $answers[$i]['id'];
			$answers[$i]['date_created'] = date('Y-m-d H:i:s');
			unset($answers[$i]['id']);
			$answers[$i]['id_quiz_log'] = $id_quiz_log;
			$answers[$i]['id_quiz_wrong'] = $id;
			$answers[$i]['id_level_subject'] = $item['id_level_subject'];
		}
		include_once $this->c->license.'/model/question/log.php';
		$obj_ = new m_question_log();
		$obj_->insertMany($answers);

		$data = array(
			'id'=>$id_quiz_log,
			'count_right'=>$count_right,
			'count_wrong'=>$count_wrong,
			'count_giveup'=>$count_giveup,
			'count_total'=>$count_total,
			'proportion'=>0,
			'cent'=>$cent,
			'mycent'=>$mycent,
		);
		if(($count_right+$count_wrong)>0){
			$data['proportion'] = $count_right/($count_right+$count_wrong);
		}
		$obj->update($data);

		$this->m->id = $id;
		$this->m->cumulative('count_used');
		$this->m->mycent = $mycent;
		$this->m->cumulative('score');
	}

	public function viewOne(){
		$html = "
<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"../libs/ext_3_2_1/resources/css/ext-all.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"../libs/star-rating/jquery.rating.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->c->license."/view/wls.css\" />	
<script type=\"text/javascript\"
	src=\"../libs/jquery-1.4.2.js\"></script>	
<script type=\"text/javascript\"
	src=\"../libs/ext_3_2_1/adapter/jquery/ext-jquery-adapter.js\"></script>
<script type=\"text/javascript\"
	src=\"../libs/jqueryextend.js\"></script>	
<!--  
<script type=\"text/javascript\"
	src=\"../libs/ext_3_2_1/adapter/ext/ext-base.js\"></script>	
-->
<script type=\"text/javascript\"
	src=\"../libs/ext_3_2_1/ext-all.js\"></script>
<script type=\"text/javascript\"
	src=\"../libs/star-rating/jquery.rating.pack.js\"></script>		
	
<script type=\"text/javascript\" src=\"".$this->c->license."/view/il8n.js\"></script>
<script type=\"text/javascript\" src=\"".$this->c->license."/view/wls.js\"></script>
<script type=\"text/javascript\" src=\"".$this->c->license."/view/quiz.js\"></script>
<script type=\"text/javascript\" src=\"".$this->c->license."/view/quiz/wrong.js\"></script>
<script type=\"text/javascript\" src=\"".$this->c->license."/view/question.js\"></script>
<script type=\"text/javascript\" src=\"".$this->c->license."/view/question/choice.js\"></script>


<script type=\"text/javascript\">
var quiz_wrong;
Ext.onReady(function(){
	quiz_wrong = new wls.quiz.wrong();
	
	quiz_wrong.id = ".$_REQUEST['id'].";
	quiz_wrong.naming = 'quiz_wrong';
	quiz_wrong.initLayout();
	quiz_wrong.ajaxIds(\"quiz_wrong.ajaxQuestions('quiz_wrong.addQuestions()');\");
});
</script>
</head>
<body>

</body>
</html>
		";
		echo $html;
	}
}
?>