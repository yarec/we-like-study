<?php 
include_once "tools.php";
include_once 'basic_group.php';
include_once 'basic_user.php';
include_once '../libs/phpexcel/Classes/PHPExcel.php';
include_once '../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
include_once '../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';

class install{
	
	public static function check_environment(){
		$t_return = array("status"=>"2","msg"=>"");
		
		$version=phpversion();
		$version_ = explode(".", $version);
		if($version_[0]<5 || $version_[1]<2){
			$t_return["msg"] .= "<br/>"." Php version unsupported. System need php 5.2 or heiger , while the environment's ".$version;
		}

		if(!function_exists('json_encode')){
			$t_return["msg"] .= "<br/>"." Php function json_encode unsupported. ";
		}
		
		if(function_exists('get_magic_quotes_gpc')){
			if(get_magic_quotes_gpc()==1){
				$t_return["msg"] .= "<br/>"." Turn off the magic please. Locate your php's php.ini , find the 'magic_quotes_gpc = On' and off it ;";
			}
		}
		
		$file = "../".tools::$configfilename;
		if(!is_writable($file)){
			$t_return["msg"] .= "<br/>". "File ".$file." is not writable, change it's mode to 777";
		}
		
		$file = "../file/upload/paper";
		if(!is_writable($file)){
			$t_return["msg"] .= "<br/>"."Folder ".$file." is not writable, change it's mode to 777";
		}
		
		$file = "../file/upload/photo";
		if(!is_writable($file)){
			$t_return["msg"] .= "<br/>". "Folder ".$file." is not writable, change it's mode to 777";
		}
		
		$file = "../file/upload/mp3";
		if(!is_writable($file)){
			$t_return["msg"] .= "<br/>". "Folder ".$file." is not writable, change it's mode to 777";
		}
		
		if($t_return['msg']==""){
			$t_return = array(
					"status"=>"1"
					,"msg"=>"OK");
		}
		
		return $t_return;
	}
	

	public static function check_db(){
		$t_return = array("status"=>"2","msg"=>"");
		
		$host = $_REQUEST['host'];
		$unm = $_REQUEST['unm'];
		$pwd = $_REQUEST['pwd'];
		$port = $_REQUEST['port'];
		$db = $_REQUEST['db'];
		$MODE = $_REQUEST['mode'];
		$il8n = $_REQUEST['il8n'];
		
		if($MODE=='DZX'){
			include_once '../../config/config_global.php';
		
			$db = $_config['db'][1]['dbname'];
			$unm = $_config['db'][1]['dbuser'] ;
			$pwd = $_config['db'][1]['dbpw'];
			$host = $_config['db'][1]['dbhost'];
			$port = "";
			unset($_config);
		}
		if($MODE=='JOOMLA'){
			include_once '../../configuration.php';
			$obj = new JConfig();
		
			$db = $obj->db;
			$unm = $obj->user;
			$pwd = $obj->password;
			$host = $obj->host;
			$port = "";
			unset($obj);
		}
		if($MODE=='DEDE'){
			include_once '../../data/common.inc.php';
		
			$db = $cfg_dbname;
			$unm = $cfg_dbuser;
			$pwd = $cfg_dbpwd;
			$host = $cfg_dbhost;
			$port = "";
		}
		if($MODE=='BAIDU'){
			$unm = getenv('HTTP_BAE_ENV_AK');
			$pwd = getenv('HTTP_BAE_ENV_SK');
			$host = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
			$port = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
		}		

		if($port!="")$host = $host.":".$port;
		$conn = mysql_connect($host,$unm,$pwd);
		if($conn){
			$db_ = mysql_select_db($db,$conn);
			if(!$db) $t_return["msg"] .= "<br/>". " Database name wrong";
		}else{
			$t_return["msg"] .= "<br/>". " Can not connect the database";
		}
		
		if($t_return['msg']==""){
			$t_return = array(
					"status"=>"1"
					,"msg"=>"OK");
		}
		
		$file = "../".tools::$configfilename;
		$fp = fopen($file, 'r');
		$arr = array();
		$num = 1;
		while(!feof($fp)){
			$line = trim(fgets($fp));
		
			if($num == 4){
				$line = "<item ID=\"IL8N\">".$il8n."</item>";
			}
			if($num == 5){
				$line = "<item ID=\"DB_NAME\">".$db."</item>";
			}
			if($num == 6){
				$line = "<item ID=\"DB_UNM\">".$unm."</item>";
			}
			if($num == 7){
				$line = "<item ID=\"DB_PWD\">".$pwd."</item>";
			}
			if($num == 8){
				$line = "<item ID=\"DB_HOST\">".$host.":".$port."</item>";
			}
			if($num == 9){
				$line = "<item ID=\"MODE\">".$MODE."</item>";
			}
			 
			$arr[] = $line;
			$num++;
		}
		fclose($fp);
		$s = implode("\r\n", $arr);
		file_put_contents("../".tools::$configfilename, $s);
		
		return $t_return;
	}
	
	public static function init_tables_readxls(){
		$t_return = array("status"=>"2","msg"=>"");
		$path_xls = "../sql/sql.xls";
		$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPReader->setReadDataOnly(true);
		$phpexcel = $PHPReader->load($path_xls);
		
		$sqls = array();
		$sqls2 = "";
		for($i0=0;$i0<$phpexcel->getSheetCount();$i0++){
			$currentSheet = $phpexcel->getSheet($i0);
			$row = $currentSheet->getHighestRow();
			for($i=1;$i<=$row;$i++){
				$cellvalue =  $currentSheet->getCell('E'.$i)->getValue();
				$cellvalue = trim($cellvalue);
				if($cellvalue!=NULL && $cellvalue[0]=='='){
					$cellvalue = $currentSheet->getCell('E'.$i)->getCalculatedValue();
				}
				$sqls[] = $cellvalue."\n";
				$sqls2 .= $cellvalue;
			}
		}
		
		$s = implode(" ", $sqls);
		file_put_contents("../sql/sql.sql", $s);
		$t_return = array("status"=>"1","msg"=>(count(explode(";", $sqls2))-1)." sql in total. Please waite for a while.","sql"=>explode(";", $sqls2));
		return $t_return;
	}
	
	public static function init_tables_dosql(){
		$t_return = array("status"=>"2","msg"=>"");
		$sqls = json_decode($_REQUEST['sqls']);
		$conn = tools::getConn();
		for($i=0;$i<count($sqls);$i++){
			mysql_query($sqls[$i],$conn);
		}
		$t_return = array("status"=>"1","msg"=>count($sqls)." sql executed ");
		return $t_return;
	}
	
	public static function basic_data(){
		$t_return = array("status"=>"2","msg"=>"");
		$path_xls = "../sql/data.xls";
		$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPReader->setReadDataOnly(true);
		$phpexcel = $PHPReader->load($path_xls);
		$conn = tools::getConn();
		
		mysql_query("delete from basic_user;");
		mysql_query("insert into basic_user(username,password,group_code,group_all,id,type,status) values ('admin',md5('admin'),'10','10',1,'10','10');");
		mysql_query("insert into basic_user(username,password,group_code,group_all,id,type,status) values ('guest',md5('guest'),'99','99',2,'10','10');");
		mysql_query("delete from basic_group_2_user;");
		mysql_query("insert into basic_group_2_user(user_code,group_code) values ('admin','10');");
		mysql_query("insert into basic_group_2_user(user_code,group_code) values ('guest','99');");
		mysql_query("delete from basic_group_2_permission;");
		mysql_query("delete from basic_permission;");
		mysql_query("delete from basic_group;");
		
		mysql_query("START TRANSACTION;",$conn);
		$sqls = array();
		
		$currentSheet = $phpexcel->getSheetByName("data_basic_group");		
		$row = $currentSheet->getHighestRow();
		for($i=2;$i<=$row;$i++){
			$cellvalue = "insert into basic_group(name,code,type,status) values ('".trim($currentSheet->getCell('A'.$i)->getValue())."','".$currentSheet->getCell('B'.$i)->getValue()."','".$currentSheet->getCell('C'.$i)->getValue()."','".$currentSheet->getCell('D'.$i)->getValue()."');";
			$sqls[] = $cellvalue."\n";
			mysql_query($cellvalue,$conn);
		}
		
		$currentSheet = $phpexcel->getSheetByName("data_basic_permission");
		$row = $currentSheet->getHighestRow();
		for($i=2;$i<=$row;$i++){
			$cellvalue = "insert into basic_permission(name,code,type,icon,path) values ('".trim($currentSheet->getCell('A'.$i)->getValue())."','".$currentSheet->getCell('C'.$i)->getValue()."','".$currentSheet->getCell('B'.$i)->getValue()."','".$currentSheet->getCell('D'.$i)->getCalculatedValue()."','".$currentSheet->getCell('E'.$i)->getValue()."');";
			$sqls[] = $cellvalue."\n";
			mysql_query($cellvalue,$conn);
		}
		
		$currentSheet = $phpexcel->getSheetByName("data_basic_group_2_permission");
		$rowindex = 0;
		foreach ($currentSheet->getRowIterator() as $row) {
			$rowindex ++;
			if($rowindex<=2)continue;
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells( false);
			$columnindex = 0;
			foreach ($cellIterator as $cell) {
				$columnindex++;
				if($columnindex<=2)continue;
				if ((!is_null($cell)) && ($cell->getValue()=="1") ) {
					$permission = $currentSheet->getCellByColumnAndRow(1,$rowindex)->getValue();
					$group = $currentSheet->getCellByColumnAndRow($columnindex-1,2)->getValue();
					$cellvalue = "insert into basic_group_2_permission (permission_code,group_code) values('".$permission."','".$group."');";
					$sqls[] = $cellvalue."\n";
					mysql_query($cellvalue,$conn);
				}
			}
		}
		
		mysql_query("COMMIT;",$conn);
		$s = implode(" ", $sqls);
		file_put_contents("../sql/data.sql", $s);
		$t_return = array("status"=>"1","msg"=>count($sqls)." sql executed ","sqls"=>$sqls);
		return $t_return;
	}	
}

$functionName = $_REQUEST['function'];
$data = array();
if($functionName=="check_environment"){
	$data = install::check_environment();
}
if($functionName=="check_db"){
	$data = install::check_db();
}
if($functionName=="init_tables_readxls"){
	$data = install::init_tables_readxls();
}	
if($functionName=="init_tables_dosql"){
	$data = install::init_tables_dosql();
}
if($functionName=="basic_data"){
	$data = install::basic_data();
}
if($functionName=="data4test__basic_group"){
	$data = basic_group::data4test(100);
}
if($functionName=="data4test__basic_user"){
	$data = basic_user::data4test(2000);
}
if($functionName=="data4test__exam_subject"){
	include_once 'exam_subject.php';
	$data = exam_subject::data4test(2000);
}
if($functionName=="data4test__exam_paper"){
	include_once 'exam_paper.php';
	$data = exam_paper::data4test(2000,array('2011-01-01','2011-01-02'));
}

echo json_encode($data);
if(tools::$conn!=null)mysql_close(tools::$conn);
