<?php
include_once dirname(__FILE__).'/../quiz.php';
include_once dirname(__FILE__).'/../user.php';
include_once dirname(__FILE__).'/../subject.php';
include_once dirname(__FILE__).'/../question/log.php';
include_once dirname(__FILE__).'/wrong.php';

include_once dirname(__FILE__).'/../../../../libs/phpexcel/Classes/PHPExcel.php';
include_once dirname(__FILE__).'/../../../../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
require_once dirname(__FILE__).'/../../../../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';

class m_quiz_log extends wls implements dbtable,fileLoad,log{

	public $phpexcel;
	public $id = null;

	public function insert($data){

		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		if(!isset($data['id_user'])){
			$userObj = new m_user();
			$user = $userObj->getMyInfo();
			$data['id_user'] = $user['id'];
			$data['ids_level_user_group'] = $user['group'];
		}
		if(!isset($data['ids_question'])){
			$data['ids_question'] = '0';
		}
		if(!isset($data['date_created'])){
			$data['date_created'] = date('Y-m-d H:i:s');
		}
		$keys = array_keys($data);
		$keys = implode(",",$keys);
		$values = array_values($data);
		$values = implode("','",$values);
		$sql = "insert into ".$pfx."wls_quiz_log (".$keys.") values ('".$values."')";
		mysql_query($sql,$conn);

		return mysql_insert_id($conn);
	}

	public function delete($ids){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();
		$sql = "delete from ".$pfx."wls_quiz_log where id  in (".$ids.");";
		mysql_query($sql,$conn);
		$sql = "delete from ".$pfx."wls_question_log where id_quiz  in (".$ids.");";
		mysql_query($sql,$conn);
	}

	public function update($data){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$id = $data['id'];
		unset($data['id']);
		$keys = array_keys($data);

		$sql = "update ".$pfx."wls_quiz_log set ";
		for($i=0;$i<count($keys);$i++){
			$sql.= $keys[$i]."='".$data[$keys[$i]]."',";
		}
		$sql = substr($sql,0,strlen($sql)-1);
		$sql .= " where id =".$id;
		$res = mysql_query($sql,$conn);

		return $res;
	}

	public function create(){
		$conn = $this->conn();
		$pfx = $this->c->dbprefix;

		$sql = "drop table if exists ".$pfx."wls_quiz_log;";
		mysql_query($sql,$conn);
		$sql = "
			create table ".$pfx."wls_quiz_log(
				 id int primary key auto_increment	
				 
				,date_created datetime 							 
				,id_user int default 0				
				,ids_level_user_group varchar(200) default '' 				
				,ids_question text				

				,id_quiz int default 0					
				,cent float default 0
				,mycent float default 0				
				,count_right int default 0
				,count_wrong int default 0
				,count_giveup int default 0
				,count_total int default 0 				
				,proportion float default 0	
								
				,time_start datetime default '1987-03-18'
				,time_stop datetime default '1987-03-18'
				,time_used int default 0				
	
				,application int default 0			
			) DEFAULT CHARSET=utf8;
			";
		mysql_query($sql,$conn);
		return true;
	}

	public function importOne($path){
		$conn = $this->conn();
		$pfx = $this->c->dbprefix;

		$objPHPExcel = new PHPExcel();
		$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPReader->setReadDataOnly(true);
		$this->phpexcel = $PHPReader->load($path);

		$currentSheet = $this->phpexcel->getSheetByName($this->lang['main']);
		$allRow = $currentSheet->getHighestRow();
		$allColmun = $currentSheet->getHighestColumn();

		$quizlog = array();
		$id_quiz = 0;
		$id_user = null;
		for($i='A';$i<=$allColmun;$i++){
			if($currentSheet->getCell($i."2")->getValue()==$this->lang['name']){
				$title = $currentSheet->getCell($i.'3')->getValue();
				$sql = "select id from ".$pfx."wls_quiz where title = '".$title."';";
				$res = mysql_query($sql,$conn);
				$temp = mysql_fetch_assoc($res);
				$id_quiz = $temp['id'];
			}
			if($currentSheet->getCell($i."2")->getValue()==$this->lang['username']){
				$value = $currentSheet->getCell($i.'3')->getValue();
				$sql = "select id from ".$pfx."wls_user where username = '".$value."';";
				$res = mysql_query($sql,$conn);
				$temp = mysql_fetch_assoc($res);
				$id_user = $temp['id'];
			}
		}

		$sql = "select
				
				 answer
				,id
				,id_parent
				,markingmethod
				,description
				,cent
				,type
				,option2
				,option3
				,option4				
				,ids_level_knowledge
				
					from ".$pfx."wls_question where id_quiz = ".$id_quiz." order by id;";
		$res = mysql_query($sql,$conn);
		$questions = array();
		$ids_question = '';
		while($temp = mysql_fetch_assoc($res)){
			$temp['myAnswer'] = 'I_DONT_KNOW';
			$questions[] = $temp;
			$ids_question .= $temp['id'].',';
		}
		$ids_question = substr($ids_question,0,strlen($ids_question)-1);

		$currentSheet = $this->phpexcel->getSheetByName($this->lang['quizLog']);
		$allRow = $currentSheet->getHighestRow();
		$allColmun = $currentSheet->getHighestColumn();

		$keys = array();
		for($i='A';$i<=$allColmun;$i++){
			if($currentSheet->getCell($i."2")->getValue()==$this->lang['index']){
				$keys['index'] = $i;
			}
			if($currentSheet->getCell($i."2")->getValue()==$this->lang['myAnswer']){
				$keys['myAnswer'] = $i;
			}
		}

		for($i=3;$i<=$allRow;$i++){
			$value = $currentSheet->getCell($keys['index'].$i)->getValue();
			if($value=='')continue;
			$questions[intval($value)-1]['myAnswer'] = $currentSheet->getCell($keys['myAnswer'].$i)->getValue();
		}

		$logDta = array(
			 'id_quiz'=>$id_quiz
			,'answers'=>$questions
			,'ids_question'=>$ids_question
		);
		if($id_user!=null){
			$logDta['id_user'] = $id_user;
		}
		$this->addLog($logDta);
	}

	public function exportOne($path=null){}

	public function exportAll($path=null){}

	public function importAll($path){}

	public function getList($page=null,$pagesize=null,$search=null,$orderby=null,$columns="*"){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();
		if($page==null)$page = 1;
		if($pagesize==null)$pagesize = 100;

		$where = " where 1 =1  ";
		if($search!=null){
			$keys = array_keys($search);
			for($i=0;$i<count($keys);$i++){
				if($keys[$i]=='id'){
					$where .= " and ".$pfx."wls_quiz_log.id in (".$search[$keys[$i]].") ";
				}
				if($keys[$i]=='id_user'){
					$where .= " and id_user in (".$search[$keys[$i]].") ";
				}
				if($keys[$i]=='id_level_subject'){
					$where .= " and ".$pfx."wls_quiz.id_level_subject in (".$search[$keys[$i]].") ";
				}				
			}
		}
		if($orderby==null)$orderby = " order by id";
		$sql = "select 
			 ".$pfx."wls_quiz_log.id as id
			,".$pfx."wls_quiz_log.id_user
			,".$pfx."wls_quiz_log.cent
			,".$pfx."wls_quiz_log.mycent
			,".$pfx."wls_quiz_log.proportion
			,".$pfx."wls_quiz_log.count_right
			,".$pfx."wls_quiz_log.count_wrong
			,".$pfx."wls_quiz_log.ids_question
			,".$pfx."wls_quiz_log.application
			,".$pfx."wls_quiz.title as title
			from ".$pfx."wls_quiz_log 
				join ".$pfx."wls_quiz on ".$pfx."wls_quiz.id = ".$pfx."wls_quiz_log.id_quiz
			".$where." ".$orderby;

		$sql .= " limit ".($pagesize*($page-1)).",".$pagesize." ";
//		echo $sql;exit();
		$res = mysql_query($sql,$conn);
		$arr = array();
		while($temp = mysql_fetch_assoc($res)){
			$temp['name_application'] = $this->t->formatApplicationType($temp['application']);
			$temp['count_questions'] = count(explode(",", $temp['ids_question']));
			$arr[] = $temp;
		}

		$sql2 = "select count(*) as total
				from ".$pfx."wls_quiz_log 
				join ".$pfx."wls_quiz on ".$pfx."wls_quiz.id = ".$pfx."wls_quiz_log.id_quiz
				 ".$where;
		$res = mysql_query($sql2,$conn);
		$temp = mysql_fetch_assoc($res);
		$total = $temp['total'];

		return array(
			'page'=>$page,
			'data'=>$arr,
			'sql'=>$sql,
			'total'=>$total,
			'pagesize'=>$pagesize,
		);
	}

	public function addLog($whatHappened){

		$answers = $whatHappened['answers'];
		$id_quiz = $whatHappened['id_quiz'];
		$ids_question = $whatHappened['ids_question'];
		$id_user = null;
		if(isset($whatHappened['id_user'])){
			$id_user = $whatHappened['id_user'];
		}

		if($id_user==null){
			$userObj = new m_user();
			$me = $userObj->getMyInfo();
			$id_user = $me['id'];
		}

		$data = array(
			 'ids_question'=>$ids_question
			,'id_quiz'=>$id_quiz
			,'id_user'=>$id_user
		);
		$id_quiz_log = $this->insert($data);

		$count_right = 0;
		$count_wrong = 0;
		$count_giveup = 0;
		$cent = 0;
		$mycent = 0;
		$count_total = count($answers);

		$quesLogObj = new m_question_log();
		for($i=0;$i<count($answers);$i++){
			if($answers[$i]['type']==4||
			$answers[$i]['type']==5||
			$answers[$i]['type']==6||
			($answers[$i]['type']==7&&$answers[$i]['id_parent']==0))continue;
			$cent += $answers[$i]['cent'];
			$quesLogData = array(
				 'id_quiz'=>$id_quiz
				,'id_quiz_log'=>$id_quiz_log
				,'myAnswer'=>$answers[$i]['myAnswer']
				,'answer'=>$answers[$i]['answer']
				,'id_question'=>$answers[$i]['id']
				,'id_user'=>$id_user
				,'cent'=>$answers[$i]['cent']
				,'type'=>$answers[$i]['type']
				,'markingmethod'=>$answers[$i]['markingmethod']
			);
			$result = $quesLogObj->addLog($quesLogData);
			if($result==1){
				$count_right++;
				$mycent += $answers[$i]['cent'];
			}
			if($result==0)$count_wrong++;
			if($result==-1)$count_giveup++;
		}

		$this->update(array(
			 'count_right'=>$count_right
			,'count_wrong'=>$count_wrong
			,'count_giveup'=>$count_giveup
			,'count_total'=>$count_total
			,'proportion'=>( ($count_right*100)/($count_right+$count_wrong) )
			,'mycent'=>$mycent
			,'cent'=>$cent
			,'id'=>$id_quiz_log
		));

		$quizObj = new m_quiz();
		$quizObj->id_quiz = $id_quiz;
		$quizObj->mycent = $mycent;
		$quizObj->cumulative('score');
		$quizObj->cumulative('count_used');
	}

	/**
	 * Read log from cache first. 
	 * If cache do not exist, then create the cache ,then delete the question_log from table. 
	 * */
	public function getLogAnswers(){		
		$cacheFilePath = $this->c->filePath.'cache/quizlog/'.$this->id.'.json';
		if(file_exists($cacheFilePath)){	
			$content = file( $cacheFilePath );		
			$content = implode("\n", $content);
			return json_decode($content,true);		
		}	
		
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();		
		
		$sql = "select application,id,ids_question from ".$pfx."wls_quiz_log where id = ".$this->id;
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp['application']==0){
			$sql = "select
			
			".$pfx."wls_question.id as id_question,
			".$pfx."wls_question_log.myanswer
			 from ".$pfx."wls_question,".$pfx."wls_question_log
			 
			 where ".$pfx."wls_question.id = ".$pfx."wls_question_log.id_question
			 order by  ".$pfx."wls_question.id
			 

			  ";					
		}else{
			$sql = "select id,ids_question,myanswer from ".$pfx."wls_question_log where id_quiz_log = ".$this->id." order by id_question;";
		}
		$res = mysql_query($sql,$conn);
		if($res==false || mysql_fetch_assoc($res)==false ){
			$this->error("Question Log do not exist,about the quizlog ".$this->id);
			return false;
		}else{
			$arr = array();
			while($temp = mysql_fetch_assoc($res)){
				if($temp['myanswer']==''||$temp['myanswer']==null)$temp['myanswer'] = 'I_DONT_KNOW';
				$arr[$temp['id_question']] = $temp['myanswer'];
			}
			
			$content = json_encode($arr);
			$handle=fopen($cacheFilePath,"a");
			fwrite($handle,$content);
			fclose($handle);
			
			$sql = "delete from ".$pfx."wls_question_log where id_quiz_log = ".$this->id;
			mysql_query($sql,$conn);
	
			return $arr;
		}
	}
}
?>