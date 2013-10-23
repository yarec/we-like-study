<?php
class exam_paper {
        
	public static function callFunction(){
		$function = $_REQUEST['function'];
		$executor = $_REQUEST['executor'];
		$session = $_REQUEST['session'];
	
		$t_return = array(
				"status"=>"2"
				,"msg"=>"access denied"
				,"executor"=>$executor
				,"session"=>$session
		);
		
		if(trim($function) ==""){
			//
		}		
		else if($function =="loadConfig"){
			$t_return = exam_paper::loadConfig($executor);
		}	
		else if($function == "grid"){
			$action = "600101";
			if(basic_user::checkPermission($executor, $action, $session)){
				$sortname = "id";
				$sortorder = "asc";
				if(isset($_REQUEST['sortname'])){
					$sortname = $_REQUEST['sortname'];
				}
				if(isset($_REQUEST['sortorder'])){
					$sortorder = $_REQUEST['sortorder'];
				}
	
				$t_return = exam_paper::grid(
						$_REQUEST['search']
						,$_REQUEST['pagesize']
						,$_REQUEST['page']
						,$executor
						,$sortname
						,$sortorder
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="add"){
			$action = "120221";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::add(
						$_REQUEST['data']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="modify"){
			$action = "120221";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::modify(
						$_REQUEST['data']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="remove"){
			$action = "600123";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::remove(
						$_REQUEST['ids']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="view"){
			$action = "600102";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::view(
						$_REQUEST['id']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		
		else if($function =="questions"){
			$action = "600190";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::questions(
						$_REQUEST['paper_id']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="submit"){
			$action = "600190";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper::submit(
						 $_REQUEST['paper_id']
						,$_REQUEST['json']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="upload"){
			$action = "600111";
			if(basic_user::checkPermission($executor, $action, $session)){
				$file = "../file/upload/paper/".rand(10000, 99999)."_".$_FILES["file"]["name"];
				move_uploaded_file($_FILES["file"]["tmp_name"],$file);
				$t_return = exam_paper::upload($file,$executor);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="download"){
			$action = "600112";
			if(basic_user::checkPermission($executor, $action, $session)){
				$id = $_REQUEST['id'];
				$t_return = exam_paper::download($id);
			}else{
				$t_return['action'] = $action;
			}
		}				

		return $t_return;
	}
	
	public static function loadConfig($executor) {
		$conn = tools::getConn();
		$config = array();
	
		$sql = "select code,value from basic_parameter where reference = 'exam_paper__type' order by code";
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['exam_paper__type'] = $data;
		
		$sql = "select code,value from basic_parameter where reference = 'exam_paper__status'  order by code";
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['exam_paper__status'] = $data;		
	
		$session = basic_user::getSession($executor);
		$session = $session['data'];
		if($session['user_type']=='10'||$session['user_type']=='30'){
			$sql = "select code,extend4 as value from basic_memory where type = '4' and extend5 = 'exam_subject__code' order by code";
		}
		if($session['user_type']=='20'){
			$sql = "select code,name as value from exam_subject where code in (select subject_code from exam_subject_2_group where group_code = '".$session['group_code']."'); ";
		}
	
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$len = strlen($temp['code']);
			for($i=1;$i<$len/2;$i++){
				$temp['value'] = "--".$temp['value'];
			}
			$data[] = $temp;
		}
		$config['subject_code'] = $data;
		
		return $config;
	}

	private static function search($search,$executor){
		$sql_where = " where exam_paper.type = '10' ";
	
		$search=json_decode2($search,true);
		$search_keys = array_keys($search);
		for($i=0;$i<count($search);$i++){
            if($search_keys[$i]=='title' && trim($search[$search_keys[$i]])!='' ){
                $sql_where .= " and title like '%".$search[$search_keys[$i]]."%' ";
            }
            if($search_keys[$i]=='subject_code' && trim($search[$search_keys[$i]])!='' ){
                $sql_where .= " and subject_code = '".$search[$search_keys[$i]]."' ";
            } 
            if($search_keys[$i]=='time_created__big' && trim($search[$search_keys[$i]])!='' ){
            	$sql_where .= " and time_created <= '".$search[$search_keys[$i]]."' ";
            }
            if($search_keys[$i]=='time_created__small' && trim($search[$search_keys[$i]])!='' ){
            	$sql_where .= " and time_created >= '".$search[$search_keys[$i]]."' ";
            }                           
            if($search_keys[$i]=='creater_code' && trim($search[$search_keys[$i]])!='' ){
                $sql_where .= " and creater_code = '".$search[$search_keys[$i]]."' ";
            }  
            if($search_keys[$i]=='creater_group_code' && trim($search[$search_keys[$i]])!='' ){
            	$sql_where .= " and creater_group_code = '".$search[$search_keys[$i]]."' ";
            }            
            if($search_keys[$i]=='type' && trim($search[$search_keys[$i]])!='' ){
            	$sql_where .= " and type = '".$search[$search_keys[$i]]."' ";
            }              
            if($search_keys[$i]=='status' && trim($search[$search_keys[$i]])!='' ){
                $sql_where .= " and status = '".$search[$search_keys[$i]]."' ";
            } 
		}
		$session = basic_user::getSession($executor);
		$session = $session['data'];
		if($session['user_type']=='10'){
			if($session['group_code']!='10'){
				$sql_where .= " and exam_paper.cost = 0 ";
			}
		}if($session['user_type']=='20'){
			$sql_where .= " and subject_code in (select subject_code from exam_subject_2_group where group_code = '".$session['group_code']."' )";
		}
		if($session['user_type']=='30'){
			$sql_where .= " and exam_paper.creater_code = '".$_REQUEST['executor']."'";
		}
		
	
		return $sql_where;
	}	
	
    public static function grid(
    	 $search
    	,$pagesize
    	,$page
    	,$executor
    	,$sortname
    	,$sortorder){
    	
    	//数据库连接口,在一次服务端访问中,数据库必定只连接一次,而且不会断开
    	$conn = tools::getConn();
    	
    	$sql_where = exam_paper::search($search, $executor);
    	$sql_order = " order by exam_paper.".$sortname." ".$sortorder." ";
    	
    	$sql = tools::getSQL("exam_paper__grid");
    	$sql .= $sql_where." ".$sql_order." limit ".(($page-1)*$pagesize).", ".$pagesize;
    	
    	$res = mysql_query($sql,$conn);
    	$data = array();
    	while($temp = mysql_fetch_assoc($res)){
    		$data[] = $temp;
    	}
    	
    	$sql_total = "select count(*) as total FROM exam_paper ".$sql_where;
    	$res = mysql_query($sql_total,$conn);
    	$total = mysql_fetch_assoc($res);
    	
    	$returnData = array(
    			'Rows'=>$data
    			,'Total'=>$total['total']
    			,'sql'=>str_replace("\t", " ",str_replace("\n", " ", $sql))
    	);
    	
    	return $returnData;
    }
        
	public static function remove($ids=NULL,$executor=NULL){
		$conn = tools::getConn();
		$ids = explode(",", $ids);
		for($i=0;$i<count($ids);$i++){
		    $sql = "delete from exam_paper where id = '".$ids[$i]."' ;";
		    mysql_query($sql,$conn);
		    $sql = "delete from exam_question where paper_id = '".$ids[$i]."' ;";
		    mysql_query($sql,$conn);		    
		}
		
		return  array(
			'status'=>1
		    ,'msg'=>'OK'
		);
	}
	
	public static function questions($paper_id=NULL,$executor=NULL){
        $conn = tools::getConn();
	    
	    $data = exam_paper::view($paper_id);
	    $cost = $data['data']['cost'];
	    
	    if($cost>0){
			$sql = "";
    	    if(tools::$systemType=='DZX'){
    	        $pfx = tools::$dzxConfig['db']['1']['tablepre'];
    	        $sql = " update ".$pfx."common_member_count set extcredits2 = extcredits2 - ".$cost." where extcredits2 >= ".$cost." and uid in ( select uid from ".$pfx."common_member where username = '".$executor."'   )";    
    	    }else{
                $sql = " update basic_user set money = money - ".$cost." , credits = credits + 3 where username = '".$executor."' and money >= ".$cost."  ;";
    	    }
            mysql_query($sql,$conn);
            $count = mysql_affected_rows($conn);
            if($count==0){
                return array(
                    'status'=>'2'
                    ,'msg'=>'no money'
					,'error'=>mysql_error()
					,'sql'=>$sql
                );
            }
	    }
        
        $sql = tools::getSQL("exam_paper__questions");            
        $sql = str_replace("__paper_id__", $paper_id, $sql);
        
        $res = mysql_query($sql,$conn);
        $data = array();
        while($temp = mysql_fetch_assoc($res)){
            $data[] = $temp;
        }

        return array(
             'data'=>$data
            ,'msg'=>'ok'
            ,'status'=>'1'
        ); 
	}
	
	public static function view($id=NULL){
	    $conn = tools::getConn();
        $sql = tools::getSQL("exam_paper__view");            
        $sql = str_replace("__id__", $id, $sql);

        $res = mysql_query($sql,$conn);
        $data = mysql_fetch_assoc($res);

        return array(
            'data'=>$data
            ,'msg'=>'ok'
            ,'status'=>'1'
           
        ); 
	}
	
	public static function modify($data=NULL,$executor=NULL){
	    $t_data = json_decode2($data,true);	    

	    $conn = tools::getConn();
        $sql = "update exam_paper set cost = '".$t_data['cost']."' where id = ".$t_data['id'];
        $res = mysql_query($sql,$conn);
        if($res==FALSE){
	        return array(
	             'msg'=>'sql wrong'
	            ,'sql'=>$sql
	            ,'status'=>'2'
	        );
        }else{
	        return array(
	            'status'=>'1'
	            ,'msg'=>'OK'
	        );
        }
	}
	
	public static function submit($paper_id=NULL,$json=NULL,$executor=NULL){
		$t_return = array();
		
		$t_return = exam_paper::checkMyAnswers(json_decode2($json,true), $paper_id);
		$session = basic_user::getSession($executor);
		$session = $session['data'];
		if($session['user_type']=='20'){
			$paperlog = exam_paper::addPaperLog($paper_id,$t_return['result'],$executor);
			exam_paper::calculateKnowledge($t_return['answers'],$paperlog['id'],$paper_id,$executor);
			exam_paper::addWrongs($t_return['answers'],$paperlog['id'], $executor);
		}
    
		$t_return['status']=1;
		return $t_return;    
	}
	
	public static function checkMyAnswers($myAnswers,$paper_id){
		$t_return = array(
			'result'=>array(
				 'right'=>0
				,'wrong'=>0
				,'giveup'=>0
				,'total'=>0					
				
				,'cent'=>0
				,'mycent'=>0
				,'mycent_objective'=>0
			)
			,'answers'=>array()
			,'myAnswers'=>$myAnswers
		);
		$conn = tools::getConn();
		$sql_answers = tools::getSQL("exam_paper__submit_check");
        $sql_answers = str_replace("__id__", $paper_id, $sql_answers);
        $res = mysql_query($sql_answers,$conn);
        $answers = array();
        $index_myanswers = 0;
        while($temp = mysql_fetch_assoc($res)){
        	$temp['mycent'] = 0;
        	if($temp['type']=="1"||$temp['type']=="2"||$temp['type']=="3"){
        		$t_return['result']['cent'] += $temp['cent'];
        		if($myAnswers[$index_myanswers]['myanswer']=='I_DONT_KNOW'){
        			$temp['result']=4;
        			$t_return['result']['giveup']++;
        		}
        		else if($temp['answer']==$myAnswers[$index_myanswers]['myanswer']){
        			$temp['result']=1;
        			$t_return['result']['right']++;
        			$t_return['result']['mycent'] += $temp['cent'];
        			$temp['mycent'] = $temp['cent'];
        		}
        		else{
        			$t_return['result']['wrong']++;
        			$temp['result']=0;
        		}
        		$t_return['result']['total']++;
        	}
        	else if($temp['type']=="4"||$temp['type']=="6"){
        		$temp['result']=2;
        		$t_return['result']['mycent_objective'] +=$temp['cent'];        		
        	}else{
        		$temp['result']=3;
        	}
        	$temp['myanswer'] = $myAnswers[$index_myanswers]['myanswer'];
        	$index_myanswers++;
        	$answers[] = $temp;
        }
        $t_return['answers'] = $answers;
        
		return $t_return;
	}
	
	public static function calculateKnowledge($answers,$paper_log__id,$paper__id,$executor,$type='10'){
		$t_return = array();
		$session = basic_user::getSession($executor);
		$session = $session['data'];
		$conn = tools::getConn();
		$result_knowledge = array();
		for($i=0;$i<count($answers);$i++){
			if($answers[$i]['result']==1){
				$knowledge = $answers[$i]['knowledge'];
				$a_knowledge = explode(",", $knowledge);
				for($i2=0;$i2<count($a_knowledge);$i2++){
					if(isset($result_knowledge[$a_knowledge[$i2]])){
						$result_knowledge[$a_knowledge[$i2]]['right']++;
					}else{
						$result_knowledge[$a_knowledge[$i2]] = array(
							'right'=>1
							,'wrong'=>0
						);
					}
				}
			}
			if($answers[$i]['result']==0){
				$knowledge = $answers[$i]['knowledge'];
				$a_knowledge = explode(",", $knowledge);
				for($i2=0;$i2<count($a_knowledge);$i2++){
					if(isset($result_knowledge[$a_knowledge[$i2]])){
						$result_knowledge[$a_knowledge[$i2]]['wrong']++;
					}else{
						$result_knowledge[$a_knowledge[$i2]] = array(
							'right'=>0
							,'wrong'=>1
						);
					}
				}
			}
		}

		$id = tools::getTableId("exam_subject_2_user_log",FALSE);
		$status = ($type=='10')?'10':'20';
		$kyes = array_keys($result_knowledge);
		mysql_query("START TRANSACTION;",$conn);
		
		for($i=0;$i<count($result_knowledge);$i++){
			$id++;
			$proportion = 0;

			$data__exam_subject_2_user_log = array(
				'subject_code'=>$kyes[$i]
				,'count_positive'=>$result_knowledge[$kyes[$i]]['right']
				,'count_negative'=>$result_knowledge[$kyes[$i]]['wrong']
				,'id'=>$id
				,'creater_code'=>$executor
				,'creater_group_code'=>$session['group_code']
				,'paper_id'=>$paper__id
				,'paper_log_id'=>$paper_log__id
				,'proportion'=>($result_knowledge[$kyes[$i]]['right']/($result_knowledge[$kyes[$i]]['right']+$result_knowledge[$kyes[$i]]['wrong']))*100
				,'type'=>$type
				,'status'=>$status
			);
			
			$keys = array_keys($data__exam_subject_2_user_log);
			$keys = implode(",",$keys);
			$values = array_values($data__exam_subject_2_user_log);
			$values = implode("','",$values);
			$sql = "insert into exam_subject_2_user_log (".$keys.") values ('".$values."')";
			mysql_query($sql,$conn);
		}
		mysql_query("COMMIT;",$conn);
		tools::updateTableId("exam_subject_2_user_log");
		
		return $t_return;
	}
	
	public static function addWrongs($answers,$paper_log__id,$executor,$type='10'){
		$t_return = array();
		$conn = tools::getConn();
		mysql_query("START TRANSACTION;",$conn);
		$id = tools::getTableId("exam_question_log_wrongs",FALSE);
		$status = ($type=='10')?'10':'20';
		for($i=0;$i<count($answers);$i++){
			if($answers[$i]['result']==0){				
				$id++;
				$sql = "insert into exam_question_log_wrongs(id,question_id,paper_log_id,creater_code,type,status) values ('".$id."','".$answers[$i]['id']."','".$paper_log__id."','".$executor."','".$type."','".$status."')";
				mysql_query($sql,$conn);
			}
		}
		
		mysql_query("COMMIT;",$conn);
		tools::updateTableId("exam_question_log_wrongs");
		return $t_return;
	}
	
	public static function addQuestionLog($answers,$paper_log_id,$executor){
		$t_return = array();
		$conn = tools::getConn();
		mysql_query("START TRANSACTION;",$conn);
		$exam_question_log__id = tools::getTableId("exam_paper_log",FALSE);
		for($i=0;$i<count($answers);$i++){
			$exam_question_log__id++;
			$data___exam_question_log = array(
				'id'=>$exam_question_log__id
				,'paper_log_id'=>$paper_log_id
				,'question_id'=>$answers[$i]['id']
				,'myanswer'=>$answers[$i]['myanswer']
				,'mycent'=>$answers[$i]['mycent']
			);
			
			$keys = array_keys($data___exam_question_log);
			$keys = implode(",",$keys);
			$values = array_values($data___exam_question_log);
			$values = implode("','",$values);
			$sql = "insert into exam_question_log (".$keys.") values ('".$values."')";
			mysql_query($sql,$conn);
		}
		mysql_query("COMMIT;",$conn);
		return $t_return;
	}	
	
	public static function addPaperLog($paper_id,$result,$executor){
		$t_return = array();
		$conn = tools::getConn();
	    $sql = "update exam_paper set count_used = count_used + 1 where id = ".$paper_id;
	    mysql_query($sql,$conn);
	    
	    $paper_log['id'] = tools::getTableId("exam_paper_log",TRUE);
	    $paper_log['paper_id'] = $paper_id;
	    $paper_log['mycent'] = $result['mycent'];
	    $paper_log['mycent_objective'] = $result['mycent_objective'];
	    $paper_log['count_right'] = $result['right'];
	    $paper_log['count_wrong'] = $result['wrong'];
	    $paper_log['status'] = '10';
	    $paper_log['creater_code'] = $executor;
	    $session = basic_user::getSession($executor);
	    $session = $session['data'];
	    $paper_log['creater_group_code'] = $session['group_code'];
	    $paper_log['type'] = '10';
	    
	    if(($paper_log['count_right'] + $paper_log['count_wrong'])!=0){
	    	$paper_log['proportion'] =  floor (( $paper_log['count_right'] * 100 ) / ($paper_log['count_right'] + $paper_log['count_wrong']));
	    }
	    
	    $keys = array_keys($paper_log);
	    $keys = implode(",",$keys);
	    $values = array_values($paper_log);
	    $values = implode("','",$values);
	    $sql = "insert into exam_paper_log (".$keys.") values ('".$values."')";
	    $res4 = mysql_query($sql,$conn);
	    
		return $paper_log;
	}
	
	public static function upload_img(){
	    if (($_FILES["file"]["size"] > 2000000)){
	        exit();
	    }
        if ($_FILES["file"]["error"] > 0){            
            exit();
        }	    
        $type = $_FILES['file']['type'];
        if($type!='image/jpeg' && $type!='image/gif' &&$type!='image/x-png'&&$type!='image/png'){
    		return array(
    		    'status'=>'2'
    		    ,'msg'=>$type
    		); 
        }
        
        $file = "../file/upload/paper/img/".$_REQUEST['executor']."_".rand(10000, 99999)."_".$_FILES["file"]["name"];
        move_uploaded_file($_FILES["file"]["tmp_name"],$file);    
        
		return array(
		    'status'=>'1'
		    ,'file'=>$file
		);        
	} 
	
	public static function formatStr($str){
		if($str==NULL)return NULL;
		$str = str_replace("\n", "<br/>", $str);
        $str = str_replace("\r", "<br/>", $str);
        $str = str_replace("'", "&acute;", $str);
        $str = str_replace("\"", "&quot;", $str);
        return $str;
	} 	
	
	public static $phpexcel = NULL;	
	public static function upload($file,$executor){  
		$conn = tools::getConn();
		$conn2 = tools::getConn(TRUE);
		$session = basic_user::getSession($executor);
		$session = $session['data'];
		tools::readIl8n();	    

        if(exam_paper::$phpexcel==NULL){
        	include_once '../libs/phpexcel/Classes/PHPExcel.php';
        	include_once '../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
        	include_once '../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
        	$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
        	$PHPReader->setReadDataOnly(true);
        	$phpexcel = $PHPReader->load($file);
        	exam_paper::$phpexcel = $phpexcel;
        }   
        $phpexcel = exam_paper::$phpexcel;

        $sheetname = "exam_paper";
        $currentSheet = $phpexcel->getSheetByName($sheetname);
	    if($currentSheet==null){
	        return array(
	             'msg'=>tools::$LANG['basic_normal']['missingSheet']." ".$sheetname
	            ,'status'=>'2'
	        );
	    }        

	    $subject_code = $currentSheet->getCell('A2')->getValue();
	    $sql__check_paper_subject = "select code from exam_subject where code like '".$subject_code."%' order by code ";;
	    $arr__subject = array();
	    $res = mysql_query($sql__check_paper_subject,$conn2);
	    while ($temp = mysql_fetch_assoc($res)){
	    	$arr__subject[] = $temp['code'];
	    }	    
	    if(count($arr__subject)==0){
	    	return array(
    			'msg'=>tools::$LANG['exam_paper']['no_such_subject']." ".$subject_code
    			,'status'=>'2'
	    	);
	    }

	    $sql__question_type2 = "select code from basic_parameter where reference = 'exam_question__type2'";
	    $arr__question_type2 = array();
	    $res = mysql_query($sql__question_type2,$conn2);
	    while ($temp = mysql_fetch_assoc($res)){
	    	$arr__question_type2[] = $temp['code'];
	    }   
	    
	    $exam_paper__id = tools::getTableId("exam_paper",TRUE);
        $data__exam_paper = array(
             'id'=>$exam_paper__id
            ,'subject_code'=>$subject_code
            ,'title'=>$currentSheet->getCell('B2')->getValue()
            ,'cost'=>$currentSheet->getCell('C2')->getValue()
            ,'type'=>10
            ,'status'=>10
            ,'cent'=>0
            ,'cent_subjective'=>0
            ,'cent_objective'=>0
            ,'count_question'=>0
            ,'count_subjective'=>0
            ,'count_objective'=>0
            ,'creater_code'=>$executor
            ,'creater_group_code'=>$session['group_code']
        );
		
        $sheetname = "exam_question";
        $currentSheet = $phpexcel->getSheetByName($sheetname);
	    if($currentSheet==null){
	        return array(
	             'msg'=>tools::$LANG['basic_normal']['missingSheet']." ".$sheetname
	            ,'status'=>'2'
	        );
	    }
	    
        $row = $currentSheet->getHighestRow();        
        $questions = array();
        $index = 0;
        $exam_question__id = tools::getTableId("exam_question",FALSE);
        for($i=2;$i<=$row;$i++){
            $index ++;
            $index_ = $currentSheet->getCell('A'.$i)->getValue();
            if($index_==NULL || !is_numeric($index_) || ($index_*1)!=$index ){
            	return array(
            			'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",A"
            			,'status'=>'2'
            	);
            }
            
            $id_parent = $currentSheet->getCell('B'.$i)->getValue();
            if($id_parent==NULL){
            	$id_parent = 0;
            }else{
            	if($id_parent*1 > count($questions)){
            		return array(
            				'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",B"
            				,'status'=>'2'
            		);
            	}
                $id_parent = $questions[$id_parent-$questions[0]['index']]['id'];
            }
                 
            $cent = $currentSheet->getCell('C'.$i)->getValue();
            if($cent==NULL){
            	$cent = 0;
            }else{
            	if(!is_numeric($cent)){
            		return array(
            				'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",C"
            				,'status'=>'2'
            		);
            	}
            }
            
            $type2 = $currentSheet->getCell('D'.$i)->getValue();
            if($type2==NULL){
            	$type2 = 0;
            }else{
            	if(!in_array($type2, $arr__question_type2)){
            		return array(
            				'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",D"
            				,'status'=>'2'
            		);
            	}
            }
            
            $type = $currentSheet->getCell('E'.$i)->getValue();
            if( ($type==NULL) || !in_array($type, array('1','2','3','4','5','6','7')) ){
            	return array(
            			'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",E ".$type
            			,'status'=>'2'
            	);
            }           
            
            $exam_question__subject = $currentSheet->getCell('F'.$i)->getValue();
            if($exam_question__subject!=$subject_code){
            	return array(
            			'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",F"
            			,'status'=>'2'
            	);
            }
            
            $title = $currentSheet->getCell('G'.$i)->getValue();
            $title = exam_paper::formatStr($title); 
            
            $option_length = $currentSheet->getCell('H'.$i)->getValue();
            if($option_length==NULL){
            	$option_length = 0;
            }else{
            	if(!is_numeric($option_length)){
            		return array(
            				'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",H"
            				,'status'=>'2'
            		);
            	}
            }      

            $option_1 = $currentSheet->getCell('I'.$i)->getValue();
            $option_1 = exam_paper::formatStr($option_1);
            $option_2 = $currentSheet->getCell('J'.$i)->getValue();
            $option_2 = exam_paper::formatStr($option_2);
            $option_3 = $currentSheet->getCell('K'.$i)->getValue();
            $option_3 = exam_paper::formatStr($option_3);
            $option_4 = $currentSheet->getCell('L'.$i)->getValue();
            $option_4 = exam_paper::formatStr($option_4);
            $option_5 = $currentSheet->getCell('M'.$i)->getValue();
            $option_5 = exam_paper::formatStr($option_5);
            $option_6 = $currentSheet->getCell('N'.$i)->getValue();
            $option_6 = exam_paper::formatStr($option_6);
            $option_7 = $currentSheet->getCell('O'.$i)->getValue();
            $option_7 = exam_paper::formatStr($option_7);
            
            $answer = $currentSheet->getCell('P'.$i)->getValue();
            $answer = exam_paper::formatStr($answer);       
            $description = $currentSheet->getCell('Q'.$i)->getValue();
            $description = exam_paper::formatStr($description);                      
            
            $knowledge = $currentSheet->getCell('R'.$i)->getValue();
            if(in_array($type,array('1','2','3'))){
            	$arr__knowledge = explode(",", $knowledge);
            	for($i2=0;$i2<count($arr__knowledge);$i2++){
            		$k = $arr__knowledge[$i2];
            		if(!in_array($k, $arr__subject)){
            			return array(
            					'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",R"
            					,'status'=>'2'
            			);
            		}
            	}
            }  
            
            $difficulty = $currentSheet->getCell('S'.$i)->getValue();
            if($difficulty==NULL){
            	$difficulty = 0;
            }else{
            	if(!is_numeric($difficulty)){
            		return array(
            				'msg'=>tools::$LANG['basic_normal']['cellError'].": ".$i.",S"
            				,'status'=>'2'
            		);
            	}
            }            
            
            $exam_question__id ++;
            $data__exam_question = array(
                 'id'=>$exam_question__id
                ,'id_parent'=>$id_parent
                ,'cent'=>$cent
            	,'type2'=>$type2
                ,'type'=>$type
                ,'subject_code'=>$exam_question__subject
                ,'title'=>$title
                ,'option_length'=>$option_length
                ,'option_1'=>$option_1
                ,'option_2'=>$option_2
                ,'option_3'=>$option_3
                ,'option_4'=>$option_4
                ,'option_5'=>$option_5
                ,'option_6'=>$option_6
                ,'option_7'=>$option_7
                ,'answer'=>$answer
                ,'description'=>$description
                ,'knowledge'=>$knowledge
                ,'difficulty'=>$difficulty
                ,'path_listen'=>$currentSheet->getCell('T'.$i)->getValue()
                ,'path_img'=>$currentSheet->getCell('U'.$i)->getValue()
                ,'paper_id'=>$exam_paper__id
                ,'index'=>$currentSheet->getCell('A'.$i)->getValue()
            	,'creater_code'=>$executor
            	,'creater_group_code'=>$session['group_code']
            );
            
            if(in_array($type,array('1','2','3'))){
                $data__exam_paper['count_objective'] ++;
                $data__exam_paper['count_question'] ++;
                $data__exam_paper['cent'] += $data__exam_question['cent'];
                $data__exam_paper['cent_objective'] += $data__exam_question['cent'];
            }
            else if(in_array($type,array('4','6'))){
                $data__exam_paper['count_subjective'] ++;
                $data__exam_paper['count_question'] ++;
                $data__exam_paper['cent'] += $data__exam_question['cent'];
                $data__exam_paper['cent_subjective'] += $data__exam_question['cent'];
            }  

            $questions[] = $data__exam_question;
        }   
        
        unset($data__exam_question['index']);
        $keys = array_keys($data__exam_question);
        $keys = implode(",",$keys);
        mysql_query("START TRANSACTION;",$conn);
        for($i=0;$i<count($questions);$i++){
            unset($questions[$i]['index']);
            $values = array_values($questions[$i]);
            $values = implode("','",$values);    
            $sql_q = "insert into exam_question (".$keys.") values ('".$values."')";
            $res = mysql_query($sql_q,$conn);
            if($res==FALSE){
            	mysql_query("ROLLBACK;",$conn);
        		return array(
        		     'status'=>'2'
        		    ,'msg'=>"question wrong , line ".$i
        		    ,'sql_q'=>$sql_q
        		    ,'error'=>mysql_error()
        		);
            }
        }
        
        $keys = array_keys($data__exam_paper);
        $keys = implode(",",$keys);
        $values = array_values($data__exam_paper);
        $values = implode("','",$values);    
        $sql = "insert into exam_paper (".$keys.") values ('".$values."')";
        $res = mysql_query($sql,$conn);
        if($res==FALSE){
        	mysql_query("ROLLBACK;",$conn);
        	return array(
        			'status'=>'2'
        			,'msg'=>"question wrong , line ".$i
        			,'sql_q'=>$sql_q
        			,'error'=>mysql_error()
        	);
        }

        mysql_query("COMMIT;",$conn);        
        tools::updateTableId("exam_paper");
        tools::updateTableId("exam_question");
        
		return array(
		     'status'=>'1'
		    ,'msg'=>"ok"
		    ,'paper_id'=>$data__exam_paper['id']
			,'data'=>$data__exam_paper
		);
	}
	
	public static function download($id=NULL){

        include_once '../libs/phpexcel/Classes/PHPExcel.php';
        include_once '../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
        include_once '../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        exam_paper::$phpexcel = $objPHPExcel;
        $conn = tools::getConn();
        $paper = exam_paper::view($id);
        
		$objPHPExcel->createSheet();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('exam_paper');
		tools::readIl8n();
		$objPHPExcel->getActiveSheet()->setCellValue('A1', tools::$LANG['exam_subject']['exam_subject']);
		$objPHPExcel->getActiveSheet()->setCellValue('A2', "  ".$paper['data']['subject_code']);
		$objPHPExcel->getActiveSheet()->setCellValue('B1', tools::$LANG['basic_normal']['title']);
		$objPHPExcel->getActiveSheet()->setCellValue('B2', $paper['data']['title']);
		$objPHPExcel->getActiveSheet()->setCellValue('C1', tools::$LANG['exam_paper']['cost']);
		$objPHPExcel->getActiveSheet()->setCellValue('C2', $paper['data']['cost']);		

		$objPHPExcel->createSheet();
		$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setTitle('exam_question');	
		
		$objPHPExcel->getActiveSheet()->setCellValue('A1', tools::$LANG['basic_normal']['id'] );
		$objPHPExcel->getActiveSheet()->setCellValue('B1', tools::$LANG['exam_paper']['id_parent'] );
		$objPHPExcel->getActiveSheet()->setCellValue('C1', tools::$LANG['exam_paper']['cent'] );
		$objPHPExcel->getActiveSheet()->setCellValue('D1', tools::$LANG['exam_paper']['type2']);
		$objPHPExcel->getActiveSheet()->setCellValue('E1', tools::$LANG['basic_normal']['type']);
		$objPHPExcel->getActiveSheet()->setCellValue('F1', tools::$LANG['exam_subject']['exam_subject'] );
		$objPHPExcel->getActiveSheet()->setCellValue('G1', tools::$LANG['basic_normal']['title']);
		$objPHPExcel->getActiveSheet()->setCellValue('H1', tools::$LANG['exam_paper']['option_length']);
		$objPHPExcel->getActiveSheet()->setCellValue('I1', tools::$LANG['exam_paper']['option'].'A');
		$objPHPExcel->getActiveSheet()->setCellValue('J1', tools::$LANG['exam_paper']['option'].'B');
		$objPHPExcel->getActiveSheet()->setCellValue('K1', tools::$LANG['exam_paper']['option'].'C');
		$objPHPExcel->getActiveSheet()->setCellValue('L1', tools::$LANG['exam_paper']['option'].'D');
		$objPHPExcel->getActiveSheet()->setCellValue('M1', tools::$LANG['exam_paper']['option'].'E');
		$objPHPExcel->getActiveSheet()->setCellValue('N1', tools::$LANG['exam_paper']['option'].'F');
		$objPHPExcel->getActiveSheet()->setCellValue('O1', tools::$LANG['exam_paper']['option'].'G');
		$objPHPExcel->getActiveSheet()->setCellValue('P1', tools::$LANG['exam_paper']['answer']);
		$objPHPExcel->getActiveSheet()->setCellValue('Q1', tools::$LANG['exam_paper']['description']);
		$objPHPExcel->getActiveSheet()->setCellValue('R1', tools::$LANG['exam_paper']['knowledge']);
		$objPHPExcel->getActiveSheet()->setCellValue('S1', tools::$LANG['exam_paper']['difficulty']);
		$objPHPExcel->getActiveSheet()->setCellValue('T1', tools::$LANG['exam_paper']['path_listen']);
		$objPHPExcel->getActiveSheet()->setCellValue('U1', tools::$LANG['exam_paper']['path_img']);		
		
		$sql = "select * from exam_question where paper_id = ".$id." order by id";	
        $res = mysql_query($sql,$conn);
        $data = array();
        $index = 2;
        $question_id_first = 0;
        while($temp = mysql_fetch_assoc($res)){
        	if($question_id_first==0)$question_id_first = $temp['id'];
        	$id_parent = $temp['id_parent'];
        	if($id_parent!=0)$id_parent = $id_parent - $question_id_first + 1;
    		$objPHPExcel->getActiveSheet()->setCellValue('A'.$index, ($temp['id']-$question_id_first + 1));
    		$objPHPExcel->getActiveSheet()->setCellValue('B'.$index, $id_parent);
    		$objPHPExcel->getActiveSheet()->setCellValue('C'.$index, $temp['cent']);
    		$objPHPExcel->getActiveSheet()->setCellValue('D'.$index, $temp['type2']);
    		$objPHPExcel->getActiveSheet()->setCellValue('E'.$index, $temp['type']);
    		$objPHPExcel->getActiveSheet()->setCellValue('F'.$index, "  ".$temp['subject_code']);
    		$objPHPExcel->getActiveSheet()->setCellValue('G'.$index, $temp['title']);
    		$objPHPExcel->getActiveSheet()->setCellValue('H'.$index, $temp['option_length']);
    		$objPHPExcel->getActiveSheet()->setCellValue('I'.$index, $temp['option_1']);
    		$objPHPExcel->getActiveSheet()->setCellValue('J'.$index, $temp['option_2']);
    		$objPHPExcel->getActiveSheet()->setCellValue('K'.$index, $temp['option_3']);
    		$objPHPExcel->getActiveSheet()->setCellValue('L'.$index, $temp['option_4']);
    		$objPHPExcel->getActiveSheet()->setCellValue('M'.$index, $temp['option_5']);
    		$objPHPExcel->getActiveSheet()->setCellValue('N'.$index, $temp['option_6']);
    		$objPHPExcel->getActiveSheet()->setCellValue('O'.$index, $temp['option_7']);
    		$objPHPExcel->getActiveSheet()->setCellValue('P'.$index, $temp['answer']);
    		$objPHPExcel->getActiveSheet()->setCellValue('Q'.$index, $temp['description']);
    		$objPHPExcel->getActiveSheet()->setCellValue('R'.$index, $temp['knowledge']);
    		$objPHPExcel->getActiveSheet()->setCellValue('S'.$index, $temp['difficulty']);
    		$objPHPExcel->getActiveSheet()->setCellValue('T'.$index, $temp['path_listen']);
    		$objPHPExcel->getActiveSheet()->setCellValue('U'.$index, $temp['path_img']);
    		$index ++;
        }
        
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$file =  "../file/download/".date('YmdHis').".xls";
		$objWriter->save($file);

		return array(
		    'status'=>'1'
		    ,'file'=>$file
		);
	}
	
	public static function data4test($total,$a_times,$delete=FALSE){
		$t_return = array("status"=>"1","msg"=>"");
		$conn = tools::getConn();
		$total_ = 0;
		
		$sql = "select * from basic_user where type = '30'";
		$res = mysql_query($sql,$conn);
		$a_teachers = array();
		while($temp = mysql_fetch_assoc($res)){
			$a_teachers[] = array(
				 'username'=>$temp['username']
				,'group_code'=>$temp['group_code']
			);
		}	
		
		if($delete){		
			$sql = "delete from exam_paper";
			mysql_query($sql,$conn);
			$sql = "delete from exam_question";
			mysql_query($sql,$conn);
			tools::initMemory();
		}
		
		$exam_paper__id = tools::getTableId("exam_paper",false);
		$exam_question__id = tools::getTableId("exam_question",false);
		mysql_query("START TRANSACTION;",$conn);
		
		$year = substr($a_times[0], 0,4);
		$sql_where_subject = "";
		if($year=="2013"){
			$sql_where_subject = " where type = '20' and ( (code like '8432-03__') or (code like '8432-02__') or (code like '8432-01__'))";
		}elseif($year=="2012"){
			$sql_where_subject = " where type = '20' and ( (code like '8432-03__') or (code like '8432-02__'))";
		}elseif($year=="2011"){
			$sql_where_subject = " where type = '20' and ( (code like '8432-03__') )";
		}
		$sql = "select * from exam_subject ".$sql_where_subject;
		$res = mysql_query($sql,$conn);

		$a_subject = array();
		while($temp = mysql_fetch_assoc($res)){
			$a_subject[] = array(
					'code'=>$temp['code']
					,'id'=>$temp['id']
					,'name'=>$temp['name']
			);
		}		
		
		for($i2=0;$i2<count($a_subject);$i2++){
			$sql_knowledge = "select * from exam_subject where type = '30' and code like '".$a_subject[$i2]['code']."-____'";
			//echo $sql_knowledge;
			$res_knowledge = mysql_query($sql_knowledge,$conn);
			$a_knowledge = array();
			while($temp = mysql_fetch_assoc($res_knowledge)){
				$a_knowledge[] = $temp['code'];
			}
			//echo json_encode($a_knowledge);
			for($i=0;$i<count($a_times);$i++){
				$exam_paper__id++;
				$teacher = $a_teachers[rand(0,(count($a_teachers)-1))];
				$data__exam_paper = array(
						'subject_code'=>$a_subject[$i2]['code']
						,'title'=>"模拟试卷".$a_times[$i]."--".$a_subject[$i2]['code']
						,'cost'=>rand(0, 10)
						,'cent'=>100
						,'cent_subjective'=>100
						,'cent_objective'=>0
						,'count_question'=>50
						,'count_subjective'=>50
						,'count_objective'=>0	
						,'directions'=>"试卷说明,说明内容可能很长"
						,'id'=>$exam_paper__id
						,'creater_code'=>$teacher['username']
						,'creater_group_code'=>$teacher['group_code']
						,'type'=>10
						,'status'=>10
						,'remark'=>"exam_paper__data4test"
						,'time_created'=>$a_times[$i]
				);
				$keys = array_keys($data__exam_paper);
				$keys = implode(",",$keys);
				$values = array_values($data__exam_paper);
				$values = implode("','",$values);
				$sql = "insert into exam_paper (".$keys.") values ('".$values."')";
				mysql_query($sql,$conn);
				$total_++;
								
				for($i3=0;$i3<50;$i3++){
					$exam_question__id++;
					$knowledge = "";
					$question_title = "题目标题";
					$question_title_length = rand(5, 40);
					for($i4=0;$i4<$question_title_length;$i4++){
						$question_title.="很长";
					}
					$question_option = "选项";
					$question_option_length = rand(3,6);
					for($i5=0;$i5<$question_option_length;$i5++){
						$question_option.= "很长";
					}
					$question_description = "解题思路";
					$question_description_length = rand(5,40);
					for($i6=0;$i6<$question_description_length;$i6++){
						$question_description.= "很长";
					}
					$question_knowledge = "";
					$question_knowledge_start = rand(0,count($a_knowledge)-3);
					//echo json_encode($a_knowledge).$a_times[$i].$a_subject[$i2]['code']."<br/>";
					for($i7=0;$i7<3;$i7++){
						$question_knowledge.= $a_knowledge[$question_knowledge_start+$i7].",";
					}
					$question_knowledge = substr($question_knowledge, 0,strlen($question_knowledge)-1);
					$question_path_img = (rand(0,100)>50)?"0":"../file/test/a".rand(1,10).".jpg";
					$data__exam_question = array(
						'subject_code'=>$a_subject[$i2]['code']
						,'cent'=>2
						,'title'=>$question_title
						,'option_length'=>4
						,'option_1'=>$question_option."A"
						,'option_2'=>$question_option."B"
						,'option_3'=>$question_option."C"
						,'option_4'=>$question_option."D"
						,'answer'=>'A'
						,'description'=>$question_description
						,'knowledge'=>$question_knowledge
						,'difficulty'=>rand(0, 4)
						,'path_img'=>$question_path_img
						,'layout'=>2
						,'paper_id'=>$exam_paper__id
						,'id'=>$exam_question__id
						,'creater_code'=>$teacher['username']
						,'creater_group_code'=>$teacher['group_code']
						,'type'=>rand(1,3)
						,'status'=>1
						,'remark'=>'exam_paper__data4test'
					);
					
					$keys = array_keys($data__exam_question);
					$keys = implode(",",$keys);
					$values = array_values($data__exam_question);
					$values = implode("','",$values);
					$sql = "insert into exam_question (".$keys.") values ('".$values."')";
					mysql_query($sql,$conn);
					$total_++;					

					if($total_>=$total){
						mysql_query("COMMIT;",$conn);
						$t_return['msg']="Total ".$total_;
						return $t_return;
					}				
				}
				
			}			
		}
		
		mysql_query("COMMIT;",$conn);
		
		tools::updateTableId("exam_paper");
		tools::updateTableId("exam_question");
		$t_return['msg']="Table exam_paper and exam_question added row in total ".$total_.". Now the id in exam_paper is ".$exam_paper__id." and id in exam_question is ".$exam_question__id.". Date from ".$a_times[0]." to ".end($a_times);
		
		return $t_return;
	}
}