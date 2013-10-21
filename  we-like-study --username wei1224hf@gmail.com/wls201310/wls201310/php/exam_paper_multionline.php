<?php
class exam_paper_multionline {
        
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
			$t_return = exam_paper_multionline::loadConfig($executor);
		}	
		else if($function == "grid"){
			$action = "600201";
			if(basic_user::checkPermission($executor, $action, $session)){
				$sortname = "exam_paper_multionline.time_start";
				$sortorder = "asc";
				if(isset($_REQUEST['sortname'])){
					$sortname = $_REQUEST['sortname'];
				}
				if(isset($_REQUEST['sortorder'])){
					$sortorder = $_REQUEST['sortorder'];
				}
	
				$t_return = exam_paper_multionline::grid(
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
				$t_return = exam_paper_multionline::add(
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
				$t_return = exam_paper_multionline::modify(
						$_REQUEST['data']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="remove"){
			$action = "600223";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper_multionline::remove(
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
				$t_return = exam_paper_multionline::view(
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
				$t_return = exam_paper_multionline::questions(
						$_REQUEST['paper_id']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="submit"){
			$action = "600291";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper_multionline::submit(
						 $_REQUEST['paper_id']
						,$_REQUEST['json']
						,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="close"){
			$action = "600290";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = exam_paper_multionline::close(
						$_REQUEST['pid']
				);
			}else{
				$t_return['action'] = $action;
			}
		}

		return $t_return;
	}
	
	public static function loadConfig($executor) {
		$conn = tools::getConn();
		$config = array();
	
		$sql = "select code,value from basic_parameter where reference = 'exam_paper__type' and code not in ('1','9')  order by code";
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['type'] = $data;
	
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
		$config['exam_subject__code'] = $data;
	
		$sql = "select code,value from basic_parameter where reference = 'exam_paper_log__type' order by code";
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['exam_paper_log__type'] = $data;
	
		$sql = "select code,value from basic_parameter where reference = 'exam_paper_log__status' and code not in ('1','9')  order by code";
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['exam_paper_log__status'] = $data;
	
		return $config;
	}

	private static function search($search,$executor){
		$sql_where = " where 1=1 ";
	
		$search=json_decode2($search,true);
		$search_keys = array_keys($search);
		for($i=0;$i<count($search);$i++){
            if($search_keys[$i]=='title' && trim($search[$search_keys[$i]])!='' ){
                $sql_where .= " and exam_paper.title like '%".$search[$search_keys[$i]]."%' ";
            }
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
    	
    	$sql_where = exam_paper_multionline::search($search, $executor);
    	$session = basic_user::getSession($executor);
    	$session = $session['data'];
    	
    	
    	$sql = tools::getSQL("exam_paper_multionline__grid");
    	$sql_total = "select count(*) as total FROM exam_paper_log LEFT JOIN exam_paper ON exam_paper_log.paper_id = exam_paper.id ".$sql_where;
    	
    	if($session['user_type']=='20'){
    		$sql = tools::getSQL("exam_paper_multionline__grid_student");
    		$sql_where .= " and exam_paper_log.creater_code = '".$executor."' ";
    		$sql_total = "select count(*) as total FROM
			exam_paper_log
			INNER JOIN exam_paper_multionline ON exam_paper_log.paper_id = exam_paper_multionline.paper_id
			LEFT JOIN exam_paper ON exam_paper_log.paper_id = exam_paper.id ".$sql_where;
    	}
    	else if($session['user_type']=='30'){
    		$sql_where .= " and exam_paper.creater_code = '".$executor."' ";
    	}    	
    	$sql = str_replace("__WHERE__", $sql_where, $sql);
    	$sql = str_replace("__ORDER__", $sortname." ".$sortorder , $sql);
    	$sql = str_replace("__PAGESIZE__",$pagesize, $sql);
    	$sql = str_replace("__OFFSET__", $pagesize*($page-1), $sql);
    	
    	$res = mysql_query($sql,$conn);
    	$data = array();
    	while($temp = mysql_fetch_assoc($res)){
    		$data[] = $temp;
    	}    	
    	
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
        if (!basic_user::checkPermission("4122")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }	     	    
	    if($ids==NULL)$ids = $_REQUEST['ids'];
	    if($executor==NULL)$executor = $_REQUEST['executor'];
		$conn = tools::getConn();
		$ids = explode(",", $ids);
		for($i=0;$i<count($ids);$i++){
		    $sql = "delete from exam_paper_multionline where paper_id = '".$ids[$i]."' ;";
		    mysql_query($sql,$conn);		    
		    $sql = "delete from exam_paper where id = '".$ids[$i]."' ;";
		    mysql_query($sql,$conn);
		    $sql = "delete from exam_paper_log where paper_id = '".$ids[$i]."' ;";
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
	    if (!basic_user::checkPermission("4190")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }	    
	    if($paper_id==NULL) $paper_id = $_REQUEST['paper_id'];
	    if($executor==NULL) $executor = $_REQUEST['executor'];
        $conn = tools::getConn();
	    
	    $data = exam_paper_multionline::view($paper_id);
	    $cost = $data['data']['cost'];

        $sql = " update basic_user set money = money - ".$cost." , credits = credits + 3 where username = '".$executor."' and money >= ".$cost."  ;";
        mysql_query($sql,$conn);
        $count = mysql_affected_rows($conn);
        if($count==0){
            return array(
                'status'=>'2'
                ,'msg'=>'no money'
            );
        }
        
        $sql = tools::getConfigItem("exam_paper_multionline__questions");            
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
        $sql = tools::getSQL("exam_paper_multionline__view");            
        $sql = str_replace("__paper_id__", $id, $sql);
       
        $res = mysql_query($sql,$conn);
        $data = mysql_fetch_assoc($res);

        return array(
            'data'=>$data
            ,'msg'=>'ok'
            ,'status'=>'1'
            ,'sql'=>$sql
        ); 
	}
	
	public static function submit($paper_id=NULL,$json=NULL,$executor=NULL){	
	    $conn = tools::getConn();
	    $t_return = array();
	    
	    $data_m = exam_paper_multionline::view($paper_id);
        $data_m = $data_m['data'];
	    if($data_m['time_stop']<date('Y-m-d H:i:s') || $data_m['time_start']>date('Y-m-d H:i:s')){
            return array(
                 'msg'=>tools::readIl8n('exam_paper_multionline','timeWrong')
                ,'status'=>'2'
            ); 
	    }
	    $sql = "select status,id from exam_paper_log where paper_id = '".$paper_id."' and creater_code = '".$executor."';";
        $res = mysql_query($sql,$conn);
        $data = mysql_fetch_assoc($res);
        $logid = $data['id'];
        if($data['status']!='30'){
    	    $msg = tools::readIl8n('exam_paper_multionline','doneAlready');
    	    $msg = str_replace("__time_submitted__", $data['time_created'], $msg);
            return array(
                 'msg'=>$msg
                ,'status'=>'2'
            ); 
        }
        
		$t_return = exam_paper::checkMyAnswers(json_decode2($json,true), $paper_id);
		exam_paper::calculateKnowledge($t_return['answers'],$logid,$paper_id,$executor,'20');
		exam_paper::addWrongs($t_return['answers'],$logid,$executor,'20');    
	    exam_paper::addQuestionLog($t_return['answers'],$logid,$executor);
	    
	    $data__exam_paper_log = array(
	    	 'mycent'=>$t_return['result']['mycent']
	    	,'mycent_objective'=>$t_return['result']['mycent_objective']
    		,'count_right'=>$t_return['result']['right']
    		,'count_wrong'=>$t_return['result']['wrong']
    		,'count_giveup'=>$t_return['result']['giveup']
	    	,'proportion'=> '0'//TODO
	    	,'time_lastupdated'=>date('Y-m-d H:i:s')
	    	,'status'=>'20'
	    );
	    $keys = array_keys($data__exam_paper_log);
	    $values = array_values($data__exam_paper_log);
	    $sql = "update exam_paper_log set ";
	    for($i=0;$i<count($keys);$i++){
	    	$sql .= " ".$keys[$i]." = '".$values[$i]."' ,";
	    }
	    $sql = substr($sql, 0,strlen($sql)-1);
	    $sql .= " where id = ".$logid;
	    mysql_query($sql,$conn);
	    
	    $msg = tools::readIl8n('exam_paper_multionline','submitted');
	    $msg = str_replace("__time_stop__", $data_m['time_stop'], $msg);
	    return array(
	        'status'=>'1'
	        ,'msg'=>$msg
	    );	   
	}
	
	public static function close($exam_paper__id){
		$conn = tools::getConn();
		$conn2 = tools::getConn(true);
		$t_return = array();
		mysql_query("START TRANSACTION;",$conn);
		$sql = "select passline from exam_paper_multionline where paper_id = ".$exam_paper__id;
		$res = mysql_query($sql,$conn2);
		$d = mysql_fetch_assoc($res);
		$passline = $d['passline'];
		$sql = "select * from exam_paper_log where paper_id = '".$exam_paper__id."' order by mycent desc ";
		$res = mysql_query($sql,$conn2);
		$rank = 0;
		$rank2 = 0;
		$mycent = 0;
		$count_passed = 0;
		$count_giveup = 0;
		$count_failed = 0;
		while ($temp=mysql_fetch_assoc($res)){
			$rank2 ++;
			$rank3 = $rank2;
			if($mycent==$temp['mycent']){
				$rank3 = $rank;
			}else{
				$rank = $rank2;
			}
			$mycent = $temp['mycent'];

			$status = "40";
			if($mycent==0){
				$status = '90';
				$count_giveup++;
			}
			else if($mycent>=$passline){
				$status = '91';
				$count_passed++;
			}
			else{
				$status = '92';
				$count_failed ++;
			}
			$sql = "update exam_paper_log set rank = '".$rank3."', status = '".$status."' where id = ".$temp['id'];
			mysql_query($sql,$conn);
			$sql = "update exam_question_log_wrongs set status = '40',  where paper_log_id = ".$temp['id'];
			mysql_query($sql,$conn);
			$sql = "update exam_subject_2_user_log set status = '40',  where paper_log_id = ".$temp['id'];
			mysql_query($sql,$conn);			
		}
		
		$sql = "update exam_paper_multionline set 
				status = '20'
				,count_giveup='".$count_giveup."'
				,count_passed='".$count_passed."'
				,count_failed='".$count_failed."'
				,proportion='".($count_passed/$rank2)."'
				 where paper_id =".$exam_paper__id;
		mysql_query($sql,$conn);
		mysql_query("COMMIT;",$conn);
		$t_return['status']=1;
		return $t_return;
	}
	
	public static function modify($data=NULL,$executor=NULL){
	    if (!basic_user::checkPermission("4123")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }		    
	    if($data==NULL)$data = $_REQUEST['data'];
	    if($executor==NULL)$executor = $_REQUEST['executor'];
	    
	    $conn = tools::getConn();
	    
	    $t_data = json_decode2($data,true);
	    $paper_id = $t_data['paper_id'];
	    unset($t_data['paper_id']);
		$str_keys = ",time_start,time_stop,passline,students,";		
		$sql = "";

		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    if(!strpos($str_keys, $keys[$i])){		        
		        return array(
		            'status'=>'2'
		            ,'msg'=>'data wrong'.$keys[$i]
		        );
		    }
		    $t_data[$keys[$i]] = "'".$t_data[$keys[$i]]."'";
		}
		
		$sql = "update exam_paper_multionline set ";
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    $sql .= $keys[$i]." = ".$t_data[$keys[$i]].",";
		}
		$sql = substr($sql, 0,strlen($sql)-1);
		$sql .= " where paper_id = '".$paper_id."' ";		

		if(mysql_query($sql,$conn)){		
    		return array(
                'status'=>'1'
                ,'msg'=>'ok'
            );
		}else{
		    return array(
                'status'=>'2'
                ,'msg'=>'sql wrong'
                ,'sql'=>$sql
            );
		}
	}	
	
	public static function upload(){	
	    if (!basic_user::checkPermission("4111")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }		        
	    $data = exam_paper::upload();
	    if(!isset($data['status']) || $data['status']!='1')return $data;
	    $conn = tools::getConn();
	    $phpexcel = exam_paper::$phpexcel;
	    $currentSheet = $phpexcel->getSheetByName('exam_paper_multionline');
	    if($currentSheet==null){
	        return array(
	             'msg'=>'Missing sheet exam_paper_multionline, check your excel'
	            ,'status'=>'2'
	        );
	    }
	    
        $data2 = array(
             'id'=>tools::getTableId("exam_paper_multionline")
            ,'time_start'=>$currentSheet->getCell('A2')->getValue()
            ,'time_stop'=>$currentSheet->getCell('B2')->getValue()
            ,'passline'=>$currentSheet->getCell('C2')->getValue()
            ,'students'=>$currentSheet->getCell('D2')->getValue()
            ,'paper_id'=>$data['paper_id']
            ,'count_total'=>count( explode(",", $currentSheet->getCell('D2')->getValue()) )		
        );
        
        $sql = "update exam_paper set type = '10' where id =".$data['paper_id'];
        mysql_query($sql,$conn);
        
        $keys = array_keys($data2);
        $keys = implode(",",$keys);
        $values = array_values($data2);
        $values = implode("','",$values);    
        $sql = "insert into exam_paper_multionline (".$keys.") values ('".$values."')";
        mysql_query($sql,$conn);
        
		return array(
		     'status'=>'1'
		    ,'msg'=>"ok"
		    ,'paper_id'=>$data['paper_id']
		    ,'paper_multionline_id'=>$data2['id']
		);        
	}
	
	public static function download($id=NULL){
	    if (!basic_user::checkPermission("4112")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }		    
	    if($id==NULL)$id = $_REQUEST['id'];
	    $data = exam_paper::download($id);
	    $phpexcel = exam_paper::$phpexcel;
	    
	    $data2 = exam_paper_multionline::view($id);
	    $data2 = $data2['data'];
		$phpexcel->createSheet();
		$phpexcel->setActiveSheetIndex($phpexcel->getSheetCount()-1);
		$phpexcel->getActiveSheet()->setTitle('exam_paper_multionline');	
		
		$phpexcel->getActiveSheet()->setCellValue('A1', tools::$LANG['exam_paper_multionline']['time_start']);
		$phpexcel->getActiveSheet()->setCellValue('B1', tools::$LANG['exam_paper_multionline']['time_stop']);
		$phpexcel->getActiveSheet()->setCellValue('C1', tools::$LANG['exam_paper_multionline']['passline']);
		$phpexcel->getActiveSheet()->setCellValue('D1', tools::$LANG['exam_paper_multionline']['students']);		
		
		$phpexcel->getActiveSheet()->setCellValue('A2', $data2['time_start']);
		$phpexcel->getActiveSheet()->setCellValue('B2', $data2['time_stop']);
		$phpexcel->getActiveSheet()->setCellValue('C2', $data2['passline']);
		$phpexcel->getActiveSheet()->setCellValue('D2', $data2['students']);
		
		$objWriter = new PHPExcel_Writer_Excel5($phpexcel);
		$file =  "../file/download/".date('YmdHis').".xls";
		$objWriter->save($file);

		return array(
		    'status'=>'1'
		    ,'file'=>$file
		);		
	}
	
	public static function order(){
	    if (!basic_user::checkPermission("4102")){
	        return array(
	             'msg'=>'access denied'
	            ,'status'=>'2'
	        );
	    }		    
	    $id = $_REQUEST['id'];
	    $conn = tools::getConn();
	    $sql = tools::getConfigItem("exam_paper_multionline__order");
        $sql = str_replace("__paper_id__", $id, $sql);
        
        $data = array();
        $res = mysql_query($sql,$conn);        
        while($temp = mysql_fetch_assoc($res)){
            $data[] = $temp;
        }
        
		return array(
		    'status'=>'1'
		    ,'Rows'=>$data
		);
	}
	
	public static function simulate__exam_paper_multionline($total,$a_times,$subject,$students,$delete=FALSE){
		$t_return = array("status"=>"1","msg"=>"");
		$conn = tools::getConn();
		$conn2 = tools::getConn(TRUE);
		$total_ = 0;
		
		if($delete){
			$sql = "delete from exam_paper_multionline";
			mysql_query($sql,$conn);
			$sql = "delete from exam_paper where type = '20'";
			mysql_query($sql,$conn);
			$sql = "delete from exam_question where remark = 'exam_paper_multionline'";
			mysql_query($sql,$conn);
			$sql = "delete from exam_paper_log where remark = 'exam_paper_multionline'";
			mysql_query($sql,$conn);
			tools::initMemory();
		}
		
		$sql_knowledge = "select * from exam_subject where type = '30' and code like '".$subject."-____'";
		//echo $sql_knowledge;
		$res_knowledge = mysql_query($sql_knowledge,$conn2);
		$a_knowledge = array();
		while($temp = mysql_fetch_assoc($res_knowledge)){
			$a_knowledge[] = $temp['code'];
		}
		
		$exam_paper__id = tools::getTableId("exam_paper",false);
		$exam_question__id = tools::getTableId("exam_question",false);
		$exam_paper_multionline__id = tools::getTableId("exam_paper_multionline",false);
		$exam_paper_log__id = tools::getTableId("exam_paper_log",false);
		$exam_question_log__id = tools::getTableId("exam_question_log",false);
		mysql_query("START TRANSACTION;",$conn);
		
		for($i=0;$i<count($a_times);$i++){
			$time = $a_times[$i];
			$exam_paper__id++;
			$exam_paper_multionline__id++;
			
			$data__exam_paper = array(
				'id'=>$exam_paper__id
				,'cost'=>'10'
				,'subject_code'=>$subject
				,'title'=>'多人考卷'.$a_times[$i]
				,'cent'=>'100'
				,'cent_subjective'=>'100'
				,'cent_objective'=>'0'
				,'count_question'=>'50'
				,'count_subjective'=>'0'
				,'count_objective'=>'50'
				,'directions'=>'这是一张多人考卷,来自模拟数据'

				,'creater_code'=>'330281-8432-04-X1--'.rand(1,9)
				,'creater_group_code'=>'330281-8432-04-X1'
				,'type'=>'20'
				,'status'=>'10'
				,'remark'=>'exam_paper_multionline'
				,'time_created'=>$a_times[$i]
			);			
			$keys = array_keys($data__exam_paper);
			$keys = implode(",",$keys);
			$values = array_values($data__exam_paper);
			$values = implode("','",$values);
			$sql = "insert into exam_paper (".$keys.") values ('".$values."')";
			mysql_query($sql,$conn);
			$total_++;
			
			$exam_question__id2 = $exam_question__id;
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
					 'subject_code'=>$subject
					,'cent'=>'2'
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
					,'layout'=>'2'
					,'paper_id'=>$exam_paper__id
					,'id'=>$exam_question__id
					,'creater_code'=>'330281-8432-04-X1--'.rand(1,9)
					,'creater_group_code'=>'330281-8432-04-X1'
					,'type'=>rand(1,3)
					,'status'=>'1'
					,'remark'=>'exam_paper_multionline'
				);
				$keys = array_keys($data__exam_question);
				$keys = implode(",",$keys);
				$values = array_values($data__exam_question);
				$values = implode("','",$values);
				$sql = "insert into exam_question (".$keys.") values ('".$values."')";
				mysql_query($sql,$conn);
				$total_++;
			}
			
			$time_stop = "";
			$time_start = "";
			$r = rand(1,100);
			if($r>90){
				//未开始
				$time_stop = '2015-01-01';
				$time_start = '2014-01-01';
			}
			else if($r>60){
				//开始,正常
				$time_stop = '2014-01-01';
				$time_start = $a_times[$i];
			}
			else{
				//已结束
				$time_start = $a_times[$i];
				$time_stop = date('Y-m-d',strtotime($time_start)+86400);				
			}
					
			$data__exam_paper_multionline = array(
					 'time_start'=>$time_start
					,'time_stop'=>$time_stop
					,'passline'=>'60'
					,'paper_id'=>$exam_paper__id
					,'count_total'=>count($students)
					,'count_giveup'=>'0'
					,'count_passed'=>''
					,'count_failed'=>'0'
					,'proportion'=>''
			
					,'id'=>$exam_paper_multionline__id
					,'creater_code'=>'330281-8432-04-X1--'.rand(1,9)
					,'creater_group_code'=>'330281-8432-04-X1'
					,'type'=>'20'
					,'status'=>'10'
					,'remark'=>'exam_paper_multionline'
					,'time_created'=>$a_times[$i]
			);
			$keys = array_keys($data__exam_paper_multionline);
			$keys = implode(",",$keys);
			$values = array_values($data__exam_paper_multionline);
			$values = implode("','",$values);
			$sql = "insert into exam_paper_multionline (".$keys.") values ('".$values."')";
			mysql_query($sql,$conn);
			$total_++;
			
			for($i2=0;$i2<count($students);$i2++){
				$status =  rand(1,100)>50?'20':'30';
				$student = $students[$i2];
				$rate = rand(20,99);
				$exam_paper_log__id++;
				$count_right = $rate/2;
				$count_wrong = (100-$rate)/2;
				$proportion = $rate;
				$mycent = $rate;
				$mycent_subjective = $rate;
				if($status=='30'){
					$count_right = 0;
					$count_wrong = 0;
					$proportion = 0;
					$mycent = 0;
					$mycent_subjective = 0;
				}
				$data__exam_paper_log = array(
					'mycent'=>$mycent
					,'mycent_subjective'=>$mycent_subjective
					,'mycent_objective'=>'0'
					,'count_right'=>$count_right
					,'count_wrong'=>$count_wrong
					,'count_giveup'=>'0'
					,'proportion'=>$rate
					,'paper_id'=>$exam_paper__id
					,'id'=>$exam_paper_log__id
					,'creater_code'=>$student
					,'creater_group_code'=>substr($student, 0,20)
					,'type'=>'20'
					,'status'=>$status
					,'remark'=>'exam_paper_multionline'
					,'time_created'=>$a_times[$i]	
					,'time_lastupdated'=>$a_times[$i]		
				);
				
				$keys = array_keys($data__exam_paper_log);
				$keys = implode(",",$keys);
				$values = array_values($data__exam_paper_log);
				$values = implode("','",$values);
				$sql = "insert into exam_paper_log (".$keys.") values ('".$values."')";
				mysql_query($sql,$conn);
				$total_++;				
				
				if($status=='30')continue;
				for($i3=0;$i3<50;$i3++){
					$exam_question__id2 ++;
					$exam_question_log__id++;
					$myanswer = 'A';
					$mycent = '2';
					if(rand(20,99)>$rate){
						$myanswer = 'B';
						$mycent = '0';
					}
					$data__exam_question_log = array(
						 'paper_log_id'=>$exam_paper_log__id
						,'question_id'=>$exam_question__id2
						,'myanswer'=>$myanswer
						,'mycent'=>$mycent
						,'id'=>$exam_question_log__id
					);
					$keys = array_keys($data__exam_question_log);
					$keys = implode(",",$keys);
					$values = array_values($data__exam_question_log);
					$values = implode("','",$values);
					$sql = "insert into exam_question_log (".$keys.") values ('".$values."')";
					mysql_query($sql,$conn);
					$total_++;
				}
			}

		}
		mysql_query("COMMIT;",$conn);
		tools::updateTableId("exam_paper");
		tools::updateTableId("exam_paper_multionline");
		tools::updateTableId("exam_question");
		tools::updateTableId("exam_paper_log");
		tools::updateTableId("exam_question_log");
		$t_return['msg']="SQL in total ".$total_.", exam_paper id: ".$exam_paper__id.", exam_paper_multionline id: ".$exam_paper_multionline__id.", exam_question id: ".$exam_question__id.", exam_paper_log id: ".$exam_paper_log__id.", exam_question_log id: ".$exam_question_log__id;
				
		return $t_return;
	}
	
	public static function simulate__get_ids(){
		$t_return = array();
		$conn = tools::getConn();
		$sql = "select paper_id from exam_paper_multionline where time_stop < now()";
		$res = mysql_query($sql,$conn);
		while ($temp=mysql_fetch_assoc($res)){
			$arr[] = $temp['paper_id'];
		}
		$t_return['data'] = $arr;
		return $t_return;;
	}
	
	public static function simulate__get_subjects(){
		$t_return = array();
		$conn = tools::getConn();
		$sql = "select code from exam_subject where type = '20' limit 12";
		$res = mysql_query($sql,$conn);
		while ($temp=mysql_fetch_assoc($res)){
			$arr[] = $temp['code'];
		}
		$t_return['data'] = $arr;
		return $t_return;;
	}
}