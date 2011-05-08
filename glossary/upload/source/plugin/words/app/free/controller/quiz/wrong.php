<?php
include_once dirname(__FILE__).'/../quiz.php';
include_once dirname(__FILE__)."/../../model/user.php";
include_once dirname(__FILE__).'/../../model/quiz/wrong.php';
include_once dirname(__FILE__).'/../../model/quiz/log.php';

class quiz_wrong extends quiz{
	private $m = null;

	function quiz_wrong(){
		parent::wls();		
		$this->m = new m_quiz_wrong();
	}

	public function getList(){
		$page = 1;
		if(isset($_POST['start']))$page = ($_POST['start']+$_POST['limit'])/$_POST['limit'];
		$pagesize = 15;
		if(isset($_POST['limit']))$pagesize = $_POST['limit'];
		$data = $this->m->getList($page,$pagesize);
		$data['totalCount'] = $data['total'];
		echo json_encode($data);
	}

	public function getMyList(){
		$page = 1;
		if(isset($_POST['start']))$page = ($_POST['start']+$_POST['limit'])/$_POST['limit'];
		$pagesize = 15;
		if(isset($_POST['limit']))$pagesize = $_POST['limit'];
		$userObj = new m_user();
		$user = $userObj->getMyInfo();
		$data = $this->m->getList($page,$pagesize,array('id_user'=>$user['id']));
		$data['totalCount'] = $data['total'];
		echo json_encode($data);
	}	
	
	public function importAll(){
		echo '<html>		
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				</head>
				<body>
					'.$this->lang['importExcel'].'
					<form action="wls.php?controller=quiz_wrong&action=saveImportAll" method="post"
					enctype="multipart/form-data">
						<label for="file">'.$this->lang['ExcelFilePath'].'</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" name="submit" value="'.$this->lang['submit'].'" />
					</form>
				</body>
			</html>	';
	}

	public function saveImportAll(){
		if ($_FILES["file"]["error"] > 0){
			$this->error(array('description'=>'error , upload faild'));
		}else{
			$file = $this->cfg->filePath."upload/upload".date('Ymdims').$_FILES["file"]["name"];
			move_uploaded_file($_FILES["file"]["tmp_name"],$file);
			$this->m->importExcel($file);
		}
	}

	public function exportAll(){
		$this->m->id = $_REQUEST['id'];
		$file = $this->m->exportExcel();
		echo "<a href='/".$file."'>".$this->lang['download']."</a>";
	}

	public function delete(){
		$this->m->delete($_POST['ids']);
	}

	public function getAnswers(){
		sleep(1);

		$ques_ = array();
		for($i=0;$i<count($_POST['answersData']);$i++){
			$ques_[$_POST['answersData'][$i]['id']] = $_POST['answersData'][$i]['answer'];
		}
		$questionObj = new m_question();
		$answers = $questionObj->getAnswers($ques_);
		
		$userObj = new m_user();
		$me = $userObj->getMyInfo();
		
		for($i=0;$i<count($answers);$i++){
			if( ($answers[$i]['myAnswer']!=$answers[$i]['answer']) &&
				$answers[$i]['myAnswer']!='I_DONT_KNOW'
			){
				$this->m->insert(array(
					 'id_question'=>$answers[$i]['id']
					,'id_user'=>$me['id']
				));
			}
		}
		
		echo json_encode($answers);
	}

	public function viewQuiz(){
		$html = "
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/ext-all.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/".$this->cfg->theme."\" />	
	
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->license."/view/wls.css\" />	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jquery-1.4.2.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/adapter/jquery/ext-jquery-adapter.js\"></script>
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jqueryextend.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/ext-all.js\"></script>	
	
<script type=\"text/javascript\" src=\"wls.php?controller=system&action=translateIniToJsClass\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/wls.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz/wrong.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/choice.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/check.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/multichoice.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/big.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/blank.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/mixed.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/question/depict.js\"></script>

<script type=\"text/javascript\">
var quiz_wrong;
Ext.onReady(function(){
	quiz_wrong = new wls.quiz.wrong();
	quiz_wrong.naming = 'quiz_wrong';
	quiz_wrong.initLayout();
	quiz_wrong.ajaxIds(\"quiz_wrong.ajaxQuestions('quiz_wrong.addQuestions()');\");
});
</script>
</head>
<body>

</body>
</html>";
		echo $html;
	}
	
	public function viewGetMyList(){
$str = "
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/ext-all.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/".$this->cfg->theme."\" />	

<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->license."/view/modules.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->license."/view/wls.css\" />			
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jquery-1.4.2.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/adapter/jquery/ext-jquery-adapter.js\"></script>
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jqueryextend.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/ext-all.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/ext-lang-zh_CN.js\"></script>

<script type=\"text/javascript\" src=\"wls.php?controller=system&action=translateIniToJsClass\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/wls.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/user.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz/wrong.js\"></script>


<script type=\"text/javascript\">
var me = new wls.user();
";
if(!isset($_SESSION)){
	session_start();
}
$str .= "me.myUser.access = '".$_SESSION['wls_user']['access']."';\n";
$str .= "me.myUser.access2 = ".json_encode($_SESSION['wls_user']['access2']).";\n";
$str .= "me.myUser.group = '".$_SESSION['wls_user']['group']."';\n";
$str .= "me.myUser.subject = '".$_SESSION['wls_user']['subject']."';\n";
$str .= "me.myUser.username = '".$_SESSION['wls_user']['username']."';\n";
$str .= "me.myUser.money = '".$_SESSION['wls_user']['money']."';\n";
$str .= "me.myUser.id = '".$_SESSION['wls_user']['id']."';\n";
$str .= "me.myUser.photo = '".$_SESSION['wls_user']['photo']."';\n";
$str .= "
var obj;
Ext.onReady(function(){
	Ext.QuickTips.init(); 
	obj = new wls.quiz.wrong();
	var obj2 = obj.getMyList('qd_w_q_p_l')
	obj2.render(Ext.getBody());
});
</script>
</head>
<body scroll='no'>

</body>
</html>
		";

	echo $str;
	}
	
	public function viewGetList(){
echo "
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/ext-all.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->libsPath."ext_3_2_1/resources/css/".$this->cfg->theme."\" />	

<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->license."/view/modules.css\" />
<link rel=\"stylesheet\" type=\"text/css\"
	href=\"".$this->cfg->license."/view/wls.css\" />			
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jquery-1.4.2.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/adapter/jquery/ext-jquery-adapter.js\"></script>
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."jqueryextend.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/ext-all.js\"></script>	
<script type=\"text/javascript\"
	src=\"".$this->cfg->libsPath."ext_3_2_1/ext-lang-zh_CN.js\"></script>

<script type=\"text/javascript\" src=\"wls.php?controller=system&action=translateIniToJsClass\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/wls.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/user.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz.js\"></script>
<script type=\"text/javascript\" src=\"".$this->cfg->license."/view/quiz/wrong.js\"></script>


<script type=\"text/javascript\">
var me = new wls.user();
";
if(!isset($_SESSION)){
	session_start();
}
echo "me.myUser.access = '".$_SESSION['wls_user']['access']."';\n";
echo "me.myUser.access2 = ".json_encode($_SESSION['wls_user']['access2']).";\n";
echo "me.myUser.group = '".$_SESSION['wls_user']['group']."';\n";
echo "me.myUser.subject = '".$_SESSION['wls_user']['subject']."';\n";
echo "me.myUser.username = '".$_SESSION['wls_user']['username']."';\n";
echo "me.myUser.money = '".$_SESSION['wls_user']['money']."';\n";
echo "me.myUser.id = '".$_SESSION['wls_user']['id']."';\n";
echo "me.myUser.photo = '".$_SESSION['wls_user']['photo']."';\n";
echo "
var obj;
Ext.onReady(function(){
	Ext.QuickTips.init(); 
	obj = new wls.quiz.wrong();
	var obj2 = obj.getList('qd_w_q_p_l')
	obj2.render(Ext.getBody());
});
</script>
</head>
<body scroll='no'>

</body>
</html>
		";
	}
	
	public function getOne(){
		echo $this->m->getMyWrongsId();
	}
}
?>