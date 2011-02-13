<?php
class install extends wls {

	public function createTables(){
		include_once $this->c->license.'/model/subject.php';
		$obj = new m_subject();
		$obj->create();

		include_once $this->c->license.'/model/knowledge.php';
		$obj = new m_knowledge();
		$obj->create();

		include_once $this->c->license.'/model/knowledge/log.php';
		$obj = new m_knowledge_log();
		$obj->create();

		include_once $this->c->license.'/model/user/group.php';
		$obj = new m_user_group();
		$obj->create();

		include_once $this->c->license.'/model/user.php';
		$obj = new m_user();
		$obj->create();

		include_once $this->c->license.'/model/user/privilege.php';
		$obj = new m_user_privilege();
		$obj->create();

		include_once $this->c->license.'/model/question.php';
		$obj = new m_question();
		$obj->create();

		include_once $this->c->license.'/model/question/log.php';
		$obj = new m_question_log();
		$obj->create();

		include_once $this->c->license.'/model/quiz/paper.php';
		$obj = new m_quiz_paper();
		$obj->create();

		include_once $this->c->license.'/model/quiz/log.php';
		$obj = new m_quiz_log();
		$obj->create();

		include_once $this->c->license.'/model/quiz/wrong.php';
		$obj = new m_quiz_wrong();
		$obj->create();

		echo '
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script language="javascript">
		alert("'.$this->lang['createdTables'].','.$this->lang['importSysConfig'].'");
		self.location=("wls.php?controller=install&action=importSysConfig");
		</script>
		</head>
		<body>
		</body>
		</html>
		';			
	}

	public function importSysConfig(){
		$html = '
				<html xmlns="http://www.w3.org/1999/xhtml">		
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				</head>
				<body>
					'.$this->lang['importSysConfig'].',<a href="../file/test/nhzj.xls">'.$this->lang['seeExampleFile'].'</a>
					<br/>
					<br/>
					<form action="wls.php?controller=install&action=saveUpload" 
					method="post"
					enctype="multipart/form-data">
						<label for="file">'.$this->lang['ExcelFilePath'].':</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" name="submit" value="'.$this->lang['submit'].'" />
					</form>
				</body>
			</html>				
		';
		echo $html;
	}

	public function saveUpload(){
		$file = $this->c->filePath."upload/upload".date('Ymdims').".xls";
		move_uploaded_file($_FILES["file"]["tmp_name"],$file);
		
		include_once $this->c->license.'/model/user/group.php';
		$obj = new m_user_group();
		$obj->importExcelWithP($file);		
		$obj->importExcelWithS($file);
		$obj->importExcelWithU($file);
		
		include_once $this->c->license.'/model/knowledge.php';
		$obj = new m_knowledge();
		$obj->importExcel($file);
		
		echo '
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script language="javascript">
		alert("'.$this->lang['installDone'].'");
		self.location=("wls.php");
		</script>
		</head>
		<body>
		</body>
		</html>
		';	
	}
	
	public function setLanguage(){
		$html = "
			<html>
				<head>
					<meta http-equiv='content-type' content='text/html; charset=UTF-8'>
				</head>
			<body>
			<form method='post' action='wls.php?controller=install&action=saveLanguage'>
			<table width='100%'>			
				<tr>
					<td width='30%'>Language(语言,言語,taal,gjuhën,언어)</td>
					<td width='30%'>
						<select name='language' style='width:90%'>							
							<option value='zh-cn'>简体中文</option>
							<option value='en'>English</option>
						</select>
					</td>
					<td width='30%'><input type='submit' value='&nbsp;&nbsp;Next&nbsp;&nbsp;' /></td>
				</tr>																												
			</table>			
			</form>
			</body>
			</html>
		";
		echo $html;
	}
	
	public function saveLanguage(){
		$this->rewirteConfig($_POST);
		echo '
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script language="javascript">
		self.location=("wls.php?controller=install&action=setCMS&temp='.rand(1,100).'");
		</script>
		</head>
		<body>
		</body>
		</html>
		';	
	}
	
	public function setCMS(){
		$html = "
			<html>
				<head>
					<meta http-equiv='content-type' content='text/html; charset=UTF-8'>
				</head>
			<body>
			<form method='post' action='wls.php?controller=install&action=saveCMS'>
			<table width='90%'>			
				<tr>
					<td style='width:20%'  >".$this->lang['cmstype']."</td>
					<td style='width:60%' >
						<select name='cmstype' style='width:90%' >
							<option value=''>".$this->lang['NOCMS']."</option>
							<option value='DiscuzX'>DiscuzX</option>
							<option value='Joomla'>Joomla</option>
						</select>
					</td>
				</tr>	
				<tr>
					<td>".$this->lang['license']."</td>
					<td>
						<select name='license' style='width:90%' >
							<option value='free'>".$this->lang['freelicense']."</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>".$this->lang['siteName']."</td>
					<td><input name = 'siteName' value='We Like Study! www.welikestudy.com' style='width:90%' /></td>
				</tr>																														
			</table>
			<br/><br/>
			<input type='submit' value='".$this->lang['submit']."' />
			</form>
			</body>
			</html>
		";
		echo $html;
	}
	
	public function saveCMS(){	
		if($_POST['cmstype']=='DiscuzX'){
			include_once dirname(__FILE__).'/../../../../../../config/config_global.php';
			$config = array(
				'dbname'=>$_config['db'][1]['dbname'],
				'dbhost'=>$_config['db'][1]['dbhost'],
				'dbuser'=>$_config['db'][1]['dbuser'],
				'dbpwd'=>$_config['db'][1]['dbpw'],
				'dbprefix'=>$_config['db'][1]['tablepre'],
				'cmstype'=>'DiscuzX',
				'state'=>'running',
			);
			$this->rewirteConfig($config);					
		}else if($_POST['cmstype']=='Joomla'){
			include_once dirname(__FILE__).'/../../../../../configuration.php';
			$jconfig = new JConfig();
			
			$config = array(
				'dbname'=>$jconfig->db,
				'dbhost'=>$jconfig->host,
				'dbuser'=>$jconfig->user,
				'dbpwd'=>$jconfig->password,
				'dbprefix'=>$jconfig->dbprefix,
				'cmstype'=>'Joomla',
				'state'=>'running',
			);
			$this->rewirteConfig($config);
		}else{
			$this->rewirteConfig($_POST);
			$this->setConfig();
			return;
		}
		echo '
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script language="javascript">
		alert("'.$this->lang['settedConfig'].','.$this->lang['createTables'].'");
		self.location=("wls.php?controller=install&action=createTables");
		</script>
		</head>
		<body>
		</body>
		</html>
		';	
	}

	public function setConfig(){
		$html = "
			<html>
				<head>
					<meta http-equiv='content-type' content='text/html; charset=UTF-8'>
				</head>
			<body>
			<form method='post' action='wls.php?controller=install&action=saveconfig'>
			<table>
				<tr>
					<td colspan='2'><b>".$this->lang['setConfig']."</b></td>
				</tr>
				<tr>
					<td colspan='2'>&nbsp;</td>
				</tr>								
				<tr>
					<td>".$this->lang['dbhost']."</td>
					<td><input name = 'dbhost' value='localhost' /></td>
				</tr>				
				<tr>
					<td>".$this->lang['dbname']."</td>
					<td><input name = 'dbname' value='wls' /></td>
				</tr>
				<tr>
					<td>".$this->lang['dbuser']."</td>
					<td><input name = 'dbuser' value='admin' /></td>
				</tr>
				<tr>
					<td>".$this->lang['dbpwd']."</td>
					<td><input name = 'dbpwd' value='admin' /></td>
				</tr>
				<tr>
					<td>".$this->lang['dbprefix']."</td>
					<td><input name = 'dbprefix' value='w_' /></td>
				</tr>	
				<tr>
					<td colspan='2'>&nbsp;</td>
				</tr>																															
			</table>
			<input type='submit' value='".$this->lang['submit']."' />
			</form>
			</body>
			</html>
		";
		echo $html;
	}

	public function saveConfig(){
		$_POST['state']= 'running';
		$this->rewirteConfig($_POST);

		echo '
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script language="javascript">
		alert("'.$this->lang['settedConfig'].','.$this->lang['createTables'].'");
		self.location=("wls.php?controller=install&action=createTables");
		</script>
		</head>
		<body>
		</body>
		</html>
		';		
	}

	public function rewirteConfig($foo=null){
		$file_name = "config.php";
		if(!$file_handle = fopen($file_name,"w")){
			die($this->lang['configFileError']);
		}
		$arr = array();
		$cfg = (array)$this->c;
		$keys = array_keys($cfg);
		for($i=0;$i<count($keys);$i++){
			eval('$arr["'.$keys[$i].'"] = $this->c->'.$keys[$i].';');
		}

		if($foo!=null){
			$keys = array_keys($foo);
			for($i=0;$i<count($foo);$i++){
				$arr[$keys[$i]] = $foo[$keys[$i]];
			}
		}

		$content = "<?php
class wlsconfig{
";
		$keys = array_keys($arr);
		for($i=0;$i<count($arr);$i++){
			$content .= "
			public \$".$keys[$i]." = '".$arr[$keys[$i]]."';";
		}
		$content.=
"
}
?>";
		fwrite($file_handle,$content);
		fclose($file_handle);
	}	
}
?>