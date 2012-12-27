﻿<?php
/**
 * 考试系统中的题目
 * 
 * @author wei1224hf
 * @version 201209
 * */
class education_question {
    
    
    public function loadConfig($return='json') {
        $CONN = tools::conn();
        $config = array();
        
        $sql = "select code,value from basic_parameter where reference = 'education_question__type' order by code";
        $res = mysql_query($sql,$CONN);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['type'] = $data;
		
        $sql = "select code,value from basic_parameter where reference = 'education_question__layout' order by code";
        $res = mysql_query($sql,$CONN);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['layout'] = $data;	
		
        $sql = "select code,name as value from education_subject order by code";
        $res = mysql_query($sql,$CONN);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['subject'] = $data;			

		if($return=='json'){
		    echo json_encode($config);
		}else{
		    return $config;
		}		
    }    
	
    /**
     * 得到JSON格式试卷列表
     * */
    public function select(){
        $CONN = tools::conn();
        
        if(!isset($_REQUEST['page']))$_REQUEST['page'] = 1;
        if(!isset($_REQUEST['pagesize']))$_REQUEST['pagesize'] = 15;
		
		$where = " where 1=1 ";
		$orderby = " ORDER BY education_question.time_created ASC ";
        //有查询条件
		if(isset($_REQUEST['searchJson'])){
			$search = json_decode($_REQUEST['searchJson'],true);
			//print_r($search);
			$where = " where 1=1 ";
			if(trim($search['subject'])!=''){
				$where .= " and education_question.subject = '".$search['subject']."' ";
			}
			if(trim($search['type'])!=''){
				$where .= " and education_question.type = '".$search['type']."' ";
			}
			if(trim($search['title'])!=''){
				$where .= " and education_question.title like '%".$search['title']."%' ";
			}	
		}
		//有排序条件
		if(isset($_REQUEST['sortname'])){
			$orderby = " order by education_question.".$_REQUEST['sortname']." ".$_REQUEST['sortorder']." ";
		}
		
        $sql = " 
		SELECT
		
		education_question.*,
		education_subject.name AS subjectname
		
		FROM
		education_question
		left Join education_subject ON education_question.subject = education_subject.code
		
		".$where."
		".$orderby."
		limit ".($_REQUEST['page']-1)*$_REQUEST['pagesize'].",".$_REQUEST['pagesize']."
		";
		
        $res = mysql_query($sql,$CONN);
        
        $data = array();
        while($temp = mysql_fetch_assoc($res)){
			$temp['time_created'] = substr($temp['time_created'],0,10);
			if(strlen($temp['title']) >= 16 ){
			    $temp['title'] = tools::cut_str($temp['title'], 8);
			}			
            $data[] = $temp;
        }
        
        $sql2 = "select count(*) as total from education_question ".$where;
        $res = mysql_query($sql2,$CONN);
        $total = 0;
        while($temp = mysql_fetch_assoc($res)){
            $total = $temp['total'];
        }
        $data = array("Rows"=>$data,"Total"=>$total,'sql'=>preg_replace("/\s(?=\s)/","",preg_replace('/[\n\r\t]/'," ",$sql)));
        echo json_encode($data);
    }    
    
     public function insert($data=NULL,$retur='json') {
        tools::checkPermission("120101");//TODO
        if ($data==NULL) {
            $data = json_decode($_REQUEST['json'],true);
        }
        $CONN = tools::conn();    
        //为了防止用户误点,导致重复提交,设置 2.5 秒的服务端暂停
        sleep(2.5);    

        $keys = array_keys($data);
        $keys = implode(",",$keys);
        $values = array_values($data);
        $values = implode("','",$values);    
        $sql = "insert into education_question (".$keys.") values ('".$values."')";
        mysql_query($sql,$CONN);
        
        echo json_encode(array('sql'=>$sql,'state'=>1));
    }    
	
    public function view(){
        $CONN = tools::conn();    
        $id = $_REQUEST['id'];
        $res = mysql_query( "select * from education_question where id = '".$id."' " , $CONN );

        $data= mysql_fetch_assoc($res);
        
        echo json_encode($data);
    } 	
    
    public function update($data=NULL,$retur='json') {
        tools::checkPermission("130111");//TODO
        if ($data==NULL) {
            $data = json_decode($_REQUEST['json'],true);
        }
        $id = $data['id'];
        unset($data['id']);

        $CONN = tools::conn();    
        //为了防止用户误点,导致重复提交,设置 2.5 秒的服务端暂停
        sleep(2.5);       
        
        $data['count_updated'] = "count_updated + 1";
        $data['time_lastupdated'] = date("Y-m-d");
        $keys = array_keys($data);        
        $sql = "update education_question set ";
        for($i=0;$i<count($keys);$i++){
            if($keys[$i]=='count_updated'){
                $sql.= $keys[$i]."= ".$data[$keys[$i]]." ,";
                continue;
            }
            $sql.= $keys[$i]."='".$data[$keys[$i]]."',";
        }
        $sql = substr($sql,0,strlen($sql)-1);
        $sql .= " where id =".$id;    
        mysql_query($sql,$CONN);
        
        echo json_encode(array('sql'=>$sql,'state'=>1));
    }     
	
	/**
	 * 得到一张试卷的所有题目,之前要判断用户的权限与金币剩余
	 * */
	public function getForPaper(){
        tools::checkPermission("1590");//TODO
		$CONN = tools::conn();//数据库连接
		
		$sql = "call education_paper__checkMoney(0,'".$_REQUEST['username']."',".$_REQUEST['id'].",@money_left,@state ,@msg )";
		//echo $sql;
		mysql_query($sql ,$CONN);
		$res = mysql_query("select @money_left as money_left,@state as state,@msg as msg",$CONN);
		$arr = mysql_fetch_array($res,MYSQL_ASSOC);
		if($arr['state']==0){
		    echo json_encode($arr);exit();
		}		

		$sql = "select * from education_question where id in( select id_question from education_paper_2_question where id_paper = '".$_REQUEST['id']."'  ) order by id";
		$res = mysql_query($sql,$CONN);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		echo json_encode(array('Rows'=>$data,'moneyLeft'=>$arr['money_left'],'sql'=>$sql,'state'=>1) );		
	}
	
	/**
	 * 从系统中读取题目数据,
	 * 
     * @param $objPHPExcel excel对象
     * @param $id_education_paper 试卷ID
     * @return $objPHPExcel excel对象
	 * */
	public function exportExcel($objPHPExcel,$id_education_paper){
	    $il8n = tools::getLanguage();
	    $CONN = tools::conn();

		$sql = "select * from education_question where id_paper = ".$id_education_paper;
		$res = mysql_query($sql,$CONN);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}

		$objPHPExcel->createSheet();
		$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setTitle($il8n['education_question']['question']);

		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'WLS PAPER EXPORT');

		$objPHPExcel->getActiveSheet()->setCellValue('A2', $il8n['education_question']['index'] );
		$objPHPExcel->getActiveSheet()->setCellValue('B2', $il8n['education_question']['belongto'] );
		$objPHPExcel->getActiveSheet()->setCellValue('C2', $il8n['education_question']['type'] );
		$objPHPExcel->getActiveSheet()->setCellValue('D2', $il8n['title'] );
		$objPHPExcel->getActiveSheet()->setCellValue('E2', $il8n['education_question']['answer'] );
		$objPHPExcel->getActiveSheet()->setCellValue('F2', $il8n['education_question']['score'] );
		$objPHPExcel->getActiveSheet()->setCellValue('G2', $il8n['education_question']['option'].'A' );
		$objPHPExcel->getActiveSheet()->setCellValue('H2', $il8n['education_question']['option'].'B' );
		$objPHPExcel->getActiveSheet()->setCellValue('I2', $il8n['education_question']['option'].'C' );
		$objPHPExcel->getActiveSheet()->setCellValue('J2', $il8n['education_question']['option'].'D' );
		$objPHPExcel->getActiveSheet()->setCellValue('K2', $il8n['education_question']['option'].'E' );
		$objPHPExcel->getActiveSheet()->setCellValue('L2', $il8n['education_question']['option'].'F' );
		$objPHPExcel->getActiveSheet()->setCellValue('M2', $il8n['education_question']['option'].'G' );
		$objPHPExcel->getActiveSheet()->setCellValue('N2', $il8n['education_question']['optionlength'] );
		$objPHPExcel->getActiveSheet()->setCellValue('O2', $il8n['education_question']['description'] );
		$objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('T')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('U')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('V')->setWidth(5);
		$objPHPExcel->getActiveSheet()->getColumnDimension('W')->setWidth(15);
		$objPHPExcel->getActiveSheet()->setCellValue('P2', $il8n['education_question']['path_listen']);
		$objPHPExcel->getActiveSheet()->setCellValue('Q2', $il8n['education_question']['difficulty']);
		$objPHPExcel->getActiveSheet()->setCellValue('R2', $il8n['education_question']['markingmethod']);
		$objPHPExcel->getActiveSheet()->setCellValue('S2', $il8n['education_question']['type2']);
		$objPHPExcel->getActiveSheet()->setCellValue('T2', $il8n['education_question']['layout']);
		
		$types = self::getTypes();
		
		$quesTypes = $types[0];
		$layTypes = $types[1];
		$quesTypes = array_flip($quesTypes);
		$layTypes = array_flip($layTypes);

		$index = 3;
		for($i=0;$i<count($data);$i++){
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$index, $data[$i]['id']);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$index, $data[$i]['id_parent']);
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$index, $quesTypes[$data[$i]['type']]);			
				
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$index, $data[$i]['title'] );
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$index, $data[$i]['answer']);
			$objPHPExcel->getActiveSheet()->setCellValue('F'.$index, $data[$i]['cent']);
			$objPHPExcel->getActiveSheet()->setCellValue('G'.$index, $data[$i]['option1'] );
			$objPHPExcel->getActiveSheet()->setCellValue('H'.$index, $data[$i]['option2'] );
			$objPHPExcel->getActiveSheet()->setCellValue('I'.$index, $data[$i]['option3'] );
			$objPHPExcel->getActiveSheet()->setCellValue('J'.$index, $data[$i]['option4'] );
			$objPHPExcel->getActiveSheet()->setCellValue('K'.$index, $data[$i]['option5']);
			$objPHPExcel->getActiveSheet()->setCellValue('L'.$index, $data[$i]['option6']);
			$objPHPExcel->getActiveSheet()->setCellValue('M'.$index, $data[$i]['option7']);
			$objPHPExcel->getActiveSheet()->setCellValue('N'.$index, $data[$i]['optionlength']);
			
			$objPHPExcel->getActiveSheet()->setCellValue('O'.$index, $data[$i]['description'] );
			$objPHPExcel->getActiveSheet()->setCellValue('P'.$index, $data[$i]['path_listen']);

			$objPHPExcel->getActiveSheet()->setCellValue('Q'.$index, $data[$i]['difficulty']);
			//$objPHPExcel->getActiveSheet()->setCellValue('R'.$index, $data[$i]['markingmethod']);
			$objPHPExcel->getActiveSheet()->setCellValue('S'.$index, $data[$i]['type2']);
			$objPHPExcel->getActiveSheet()->setCellValue('T'.$index, $layTypes[$data[$i]['layout']]);

			$index ++;
		}
		$objStyle = $objPHPExcel->getActiveSheet()->getStyle('E2');
		$objStyle->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->duplicateStyle($objStyle, 'E2:E'.(count($data)+1));
		
		return $objPHPExcel;
	}
	
    public function delete(){
        $CONN = tools::conn();
        $ids = $_REQUEST['ids'];
		$arr = explode(",", $ids);
		for($i=0;$i<count($arr);$i++){
		    mysql_query("call education_question__delete(".$arr[$i].")",$CONN);
		}

        sleep(1);
        echo json_encode(array('state'=>1));
    }	
}