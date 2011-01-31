<?php
/**
 * 用户操作,对应着一张数据库表
 * */
class m_user extends wls implements dbtable{

	public $phpexcel;
	public $id = null;

	/**
	 * 插入一条数据
	 *
	 * @param $data 一个数组,其键值与数据库表中的列一一对应
	 * @return bool
	 * */
	public function insert($data){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$keys = array_keys($data);
		$keys = implode(",",$keys);
		$values = array_values($data);
		$values = implode("','",$values);
		$sql = "insert into ".$pfx."wls_user (".$keys.") values ('".$values."')";
		mysql_query($sql,$conn);
		return mysql_insert_id($conn);
	}

	/**
	 * 只能根据编号来删除数据,一次性可以删除多条
	 *
	 * @param $ids 编号,每张表都id这个列,一般为自动递增
	 * @return bool
	 * */
	public function delete($ids){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$sql = "delete from ".$pfx."wls_user where id  in (".$ids.");";
		try{
			mysql_query($sql,$conn);
			return true;
		}catch (Exception $ex){
			return false;
		}
	}

	/**
	 * 更新一条数据
	 *
	 * @param $data 一个数组,其键值与数据库表中的列一一对应,肯定含有$id 
	 * @return bool
	 * */
	public function update($data){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$id = $data['id'];
		unset($data['id']);
		$keys = array_keys($data);

		$sql = "update ".$pfx."wls_user set ";
		for($i=0;$i<count($keys);$i++){
			$sql.= $keys[$i]."='".$data[$keys[$i]]."',";
		}
		$sql = substr($sql,0,strlen($sql)-1);
		$sql .= " where id =".$id;
		try{
			mysql_query($sql,$conn);
			return true;
		}catch (Exception $ex){
			return false;
		}
	}

	/**
	 * 创建这张数据库表
	 * 创建过程中,会先尝试删除这张表,然后重新建立.
	 * 因此在运行之前需要将数据备份
	 * 如果配置文件中的state不是debug,无法执行这类函数
	 *
	 * @return bool
	 * */
	public function create(){

		$conn = $this->conn();
		$pfx = $this->c->dbprefix;

		$sql = "drop table if exists ".$pfx."wls_user;";
		mysql_query($sql,$conn);
		$sql = "
			create table ".$pfx."wls_user(
				 id int primary key auto_increment	/*自动编号*/
				
				,username varchar(200) unique
				,password varchar(200) not null
				,money int default 0
				,credits int default 0
				
				,name varchar(200) /*姓名*/
				,sex varchar(200) default '男'
				,birthday datetime default '1999-09-09'
				,qq varchar(200) default '0'
				,photo varchar(200) 
			
			) DEFAULT CHARSET=utf8;
			";
		mysql_query($sql,$conn);
		return true;
	}

	/**
	 * 导入一张EXCEL,并将数据全部填充到表中去
	 * EXCEL已经成为数据存储标准,每个办公人员都会用
	 * 这是实现批导入最方便的形式
	 *
	 * @param $path EXCEL路径
	 * @return bool
	 * */
	public function importExcel($path){
		include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel.php';
		include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
		require_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
		$objPHPExcel = new PHPExcel();
		$PHPReader = PHPExcel_IOFactory::createReader('Excel5');
		$PHPReader->setReadDataOnly(true);
		$this->phpexcel = $PHPReader->load($path);

		$currentSheet = $this->phpexcel->getSheetByName('data');
		$allRow = array($currentSheet->getHighestRow());

		$data = array();
		$index = 0;
		for($i=2;$i<=$allRow[0];$i++){
			$data = array(
				'username'=>$currentSheet->getCell('A'.$i)->getValue(),
				'password'=>$currentSheet->getCell('B'.$i)->getValue(),				
				'money'=>$currentSheet->getCell('C'.$i)->getValue(),
				'credits'=>$currentSheet->getCell('D'.$i)->getValue(),
				'id_level_user_group'=>$currentSheet->getCell('E'.$i)->getValue(),
				'photo'=>$currentSheet->getCell('F'.$i)->getValue(),
			);
			$this->insert($data);
		}
	}

	/**
	 * 导出一张EXCEL文件,
	 * 提供下载,实现数据的多处同步,并让这个EXCEL文件形成标准
	 *
	 * @return $path
	 * */
	public function exportExcel(){
		include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel.php';
		include_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/IOFactory.php';
		require_once dirname(__FILE__).'/../../../libs/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
		$objPHPExcel = new PHPExcel();
		$data = $this->getList(1,1000);
		$data = $data['data'];

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('data');

		$objPHPExcel->getActiveSheet()->setCellValue('A1', '用户名');
		$objPHPExcel->getActiveSheet()->setCellValue('B1', '密码');
		$objPHPExcel->getActiveSheet()->setCellValue('C1', '金币');
		$objPHPExcel->getActiveSheet()->setCellValue('D1', '积分');
		$objPHPExcel->getActiveSheet()->setCellValue('E1', '用户组');
		$objPHPExcel->getActiveSheet()->setCellValue('F1', '照片');

		$index = 1;
		for($i=0;$i<count($data);$i++){
			$index ++;
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$index, $data[$i]['username']);
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$index, $data[$i]['password']);
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$index, $data[$i]['money']);
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$index, $data[$i]['credits']);
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$index, $data[$i]['id_level_user_group']);
			$objPHPExcel->getActiveSheet()->setCellValue('F'.$index, $data[$i]['photo']);
		}
		$objStyle = $objPHPExcel->getActiveSheet()->getStyle('E2');
		$objStyle->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objPHPExcel->getActiveSheet()->duplicateStyle($objStyle, 'E2:E'.(count($data)+1));
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$file =  "file/download/".date('YmdHis').".xls";
		$objWriter->save(dirname(__FILE__)."/../../../".$file);
		return $file;
	}

	/**
	 * 累加某个值
	 *
	 * @param $column 列名称
	 * @return bool
	 * */
	public function cumulative($column){}

	/**
	 * 得到列表,
	 * 也充当了读取单行数据的角色
	 *
	 * @param $page 页码,为整数
	 * @param $pagesize 页大小
	 * @param $search 查询条件
	 * @param $orderby 排序条件
	 * @return $array
	 * */
	public function getList($page=null,$pagesize=null,$search=null,$orderby=null,$columns="*"){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$where = " where 1 =1  ";

		if($search!=null){
			$keys = array_keys($search);
			for($i=0;$i<count($keys);$i++){
				if($keys[$i]=='type'){
					$where .= " and type in (".$search[$keys[$i]].") ";
				}
				if($keys[$i]=='id'){
					$where .= " and id in (".$search[$keys[$i]].") ";
				}
				if($keys[$i]=='username'){
					$where .= " and username = '".$search[$keys[$i]]."' ";
				}
			}
		}
		if($orderby==null)$orderby = " order by id";
		$sql = "select ".$columns." from ".$pfx."wls_user ".$where." ".$orderby;
		$sql .= " limit ".($pagesize*($page-1)).",".$pagesize." ";

		$res = mysql_query($sql,$conn);
		if($res==false){

			return;
		}

		$arr = array();
		while($temp = mysql_fetch_assoc($res)){
			$arr[] = $temp;
		}

		$sql2 = "select count(*) as total from ".$pfx."wls_user ".$where;
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

	/**
	 * 得到用户信息
	 * 会引用session
	 *
	 * @param $id 如果id为空,则返回我的个人信息
	 * @param $mine 是否提取我自己的个人信息,这就需要session
	 * */
	public function getUser($id=null,$mine=null){
		if($mine==true){
				
			if( (isset($_SESSION)) && (isset($_SESSION['wls_user'])) && $_SESSION['wls_user']!=''){

			}else{
				//
				session_start();
			}
				
			if(!isset($_SESSION['wls_user']) || $_SESSION['wls_user']['privilege']==''){//TODO
			if($id==null){//访客
				$data = $this->getList(1,1,array('username'=>'guest'));
				$data = $data['data'][0];
			}else{
				$data = $this->getList(1,1,array('id'=>$id));
				$data = $data['data'][0];
					
			}
			include_once dirname(__FILE__).'/user/privilege.php';
			$o = new m_user_privilege();
			$d = $o->getListForUser($data['username']);
			$ids = '';
			$privileges = array();
			for($i=0;$i<count($d);$i++){
				if($d[$i]['checked']=='1'){
					$ids .= $d[$i]['id_level'].",";
					$privileges[$d[$i]['id_level']] = $d[$i]['money'];
				}
			}
			$ids = substr($ids,0,strlen($ids)-1);
			$data['privilege'] = $ids;
			$data['privileges'] = $privileges;

			include_once dirname(__FILE__).'/user/group.php';
			$o = new m_user_group();
			$d = $o->getListForUser($data['username']);
			$ids = '';
			for($i=0;$i<count($d);$i++){
				if($d[$i]['checked']=='1')$ids .= $d[$i]['id_level'].",";
			}
			$ids = substr($ids,0,strlen($ids)-1);
			$data['group'] = $ids;

			include_once dirname(__FILE__).'/subject.php';
			$o = new m_subject();
			$d = $o->getListForUser($data['username']);
			$ids = '';
			for($i=0;$i<count($d);$i++){
				if($d[$i]['checked']=='1')$ids .= $d[$i]['id_level'].",";
			}
			$ids = substr($ids,0,strlen($ids)-1);
			$data['subject'] = $ids;

			$_SESSION['wls_user'] = $data;
			}
			return $_SESSION['wls_user'];
		}else{

			$data = $this->getList(1,1,array('id'=>$id));
			return $data['data'][0];

		}
	}

	public function login($username,$password){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$sql = "select * from ".$pfx."wls_user where username = '".$username."';";

		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp==false){
			return false;
		}else{
			if($temp['password']!=$password){
				return false;
			}
		}
		$this->getUser($temp['id'],true);
		return $temp;
	}

	/**
	 * 导出某用户的成绩单
	 *
	 * @param $id 如果id为空,则返回当前用户
	 * */
	public function exportTranscripts($id){}

	/**
	 * 导入某用户的成绩单
	 *
	 * @param $id 如果id为空,则返回当前用户
	 * */
	public function importTranscripts($id){}

	public function updateGroup($username,$ids_group){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$sql = "delete from ".$pfx."wls_user_group2user where username = '".$username."' ;";
		mysql_query($sql,$conn);
		$arr = explode(",",$ids_group);

		include_once dirname(__FILE__).'/user/group.php';
		$g = new m_user_group();

		for($i=0;$i<count($arr);$i++){
			$data = array(
				'id_level_group'=>$arr[$i],
				'username'=>$username
			);
			$g->linkUser($data);
		}
	}

	public function checkMyPrivilege($privilege){
		$pfx = $this->c->dbprefix;
		$conn = $this->conn();

		$user = $this->getUser($this->id,true);
		$privileges = $user['privilege'];
		$privileges = explode(",",$privileges);
		if(in_array($privilege,$privileges)){
			if($user['money']>$user['privileges'][$privilege]){
				$sql = "update ".$pfx."wls_user set money = money - ".$user['privileges'][$privilege]." where id = ".$user['id'];
				mysql_query($sql,$conn);
				$_SESSION['wls_user']['money'] -= $user['privileges'][$privilege];

				if($this->c->cmstype!=''){//同步金钱
					$obj = null;
					eval("include_once dirname(__FILE__).'/integration/".$this->c->cmstype.".php';");
					eval('$obj = new m_integration_'.$this->c->cmstype.'();');
					$obj->synchroMoney($user['username']);
				}
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function getMyMenu(){
		$me = $this->getUser(null,true);

		$username = $me['username'];
		include_once dirname(__FILE__).'/user/privilege.php';
		$obj = new m_user_privilege();
		$data = $obj->getListForUser($username);

		$data2 = array();
		for($i=0;$i<count($data);$i++){
			if($data[$i]['ismenu']==1){
				if($data[$i]['checked']==true || $data[$i]['checked']=='true' || $data[$i]['checked']==1){
					$data[$i]['type'] = 'menu';
					$data2[] = $data[$i];
				}
			}
		}

		$data = $this->t->getTreeData(null,$data2);
		for($i=0;$i<count($data);$i++){
			if($data[$i]['id_level']=='11'){
				include_once dirname(__FILE__).'/subject.php';
				$obj = new m_subject();
				$data_ = $obj->getListForUser($username);

				if(count($data_)>0){
					$data__ = array();
					for($ii=0;$ii<count($data_);$ii++){
						$data_[$ii]['type'] = 'subject';
						$data_[$ii]['id_level_s'] = $data_[$ii]['id_level'];
						$data_[$ii]['id_level'] = '11'.$data_[$ii]['id_level'];
						if($data_[$ii]['checked']==1){
							$data_[$ii]['ismenu'] = 1;
							$data__[] = $data_[$ii];
						}
					}
					$subject = $this->t->getTreeData('11',$data__);
						
					$arr = array();
					if(isset($data[$i]['children']) && count($data[$i]['children'])>0){
						$arr = $data[$i]['children'];
					}
					$data[$i]['children'] = $subject;

					if(count($arr)>0){
						$data[$i]['children'][] = array('text'=>'slide');
						for($ii=0;$ii<count($arr);$ii++){
							$data[$i]['children'][] = $arr[$ii];
						}
					}
				}
			}
			if($data[$i]['id_level']=='13'){
				include_once dirname(__FILE__).'/user/group.php';
				$obj = new m_user_group();
				$data_ = $obj->getListForUser($username);
				if(count($data_)>0){
					$data__ = array();
					for($ii=0;$ii<count($data_);$ii++){
						$data_[$ii]['type'] = 'group';
						$data_[$ii]['icon'] = 'groups';
						$data_[$ii]['isshortcut'] = '0';
						$data_[$ii]['isquickstart'] = '0';
						$data_[$ii]['description'] = '0';

						$data_[$ii]['id_level_g'] = $data_[$ii]['id_level'];
						$data_[$ii]['id_level'] = '13'.$data_[$ii]['id_level'];
						if($data_[$ii]['checked']==1){
							$data_[$ii]['ismenu'] = 1;
							$data__[] = $data_[$ii];
						}
					}
					$subject = $this->t->getTreeData('13',$data__);
						
					$arr = array();
					if(isset($data[$i]['children']) && count($data[$i]['children'])>0){
						$arr = $data[$i]['children'];
					}
					$data[$i]['children'] = $subject;

					if(count($arr)>0){
						$data[$i]['children'][] = array('text'=>'slide');
						for($ii=0;$ii<count($arr);$ii++){
							$data[$i]['children'][] = $arr[$ii];
						}
					}
				}
			}
		}
		return $data;
	}

	public function getMyMenuForDesktop(){
		$data = $this->getMyMenu();

		$this->t->treeMenuToDesktopMenu(null,$data);
		$data = $this->t->desktopMenu;
		return $data;
	}
}
?>