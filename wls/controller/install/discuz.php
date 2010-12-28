<?php
/**
 * 集成到Discuz系统中去
 * 
 * */
class install_discuz extends wls {
	public $rewrite = array();

	/**
	 * 扩展用户表
	 * */
	public function extendUser(){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$sql = "select fieldid from ".$pfx."profilefields where title = '参加的考试科目';";
		$res = mysql_query($sql,$conn);
		if($temp = mysql_fetch_assoc($res)){
			$sql = "delete from ".$pfx."profilefields where title = '参加的考试科目';";	
			mysql_query($sql,$conn);
			$sql = "alter table ".$pfx."memberfields drop column field_".$temp['fieldid']." ;";
			mysql_query($sql,$conn);
		}
		$sql = "
		insert into ".$pfx."profilefields(
			 available
			,size
			,title		
		) values (
			 '1'
			,200
			,'参加的考试科目'
		)";
		mysql_query($sql,$conn);
		$id = mysql_insert_id($conn);
		$sql = "alter table ".$pfx."memberfields add column field_".$id." varchar(200) default '无';";
		mysql_query($sql,$conn);

		$sql = "select fieldid from ".$pfx."profilefields where title = '错题数';";
		$res = mysql_query($sql,$conn);
		if($temp = mysql_fetch_assoc($res)){
			$sql = "delete from ".$pfx."profilefields where title = '错题数';";	
			mysql_query($sql,$conn);
			$sql = "alter table ".$pfx."memberfields drop column field_".$temp['fieldid']." ;";
			mysql_query($sql,$conn);
		}
		$sql = "
		insert into ".$pfx."profilefields(
			 available
			,size
			,title		
		) values (
			 '1'
			,2
			,'错题数'
		)";
		mysql_query($sql,$conn);
		$id2 = mysql_insert_id($conn);
		$sql = "alter table ".$pfx."memberfields add column field_".$id2." int default 0;";
		mysql_query($sql,$conn);
		
		$sql = "select fieldid from ".$pfx."profilefields where title = '已做试卷数';";
		$res = mysql_query($sql,$conn);
		if($temp = mysql_fetch_assoc($res)){
			$sql = "delete from ".$pfx."profilefields where title = '已做试卷数';";	
			mysql_query($sql,$conn);
			$sql = "alter table ".$pfx."memberfields drop column field_".$temp['fieldid']." ;";
			mysql_query($sql,$conn);
		}
		$sql = "
		insert into ".$pfx."profilefields(
			 available
			,size
			,title		
		) values (
			 '1'
			,2
			,'已做试卷数'
		)";
		mysql_query($sql,$conn);
		$id3 = mysql_insert_id($conn);
		$sql = "alter table ".$pfx."memberfields add column field_".$id3." int default 0;";
		mysql_query($sql,$conn);
		
		$this->rewrite['user_extend_myquiz']=$id;
		$this->rewrite['user_extend_wrongs']=$id2;
		$this->rewrite['user_extend_papers']=$id3;
	}

	/**
	 * 得到用户的基本信息
	 * */
	public function getUserInfo($id){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;
		if($id==null || $id=='mine'){
			if(isset($_COOKIE['qW3_sid'])){
				$sid = $_COOKIE['qW3_sid'];
				$sql = "select uid from ".$pfx."sessions where sid = '".$sid."'";
				$res = mysql_query($sql,$conn);
				$temp = mysql_fetch_assoc($res);
				$id = $temp['uid'];				
			}else{
				return array(
					'id_user'=>0,
					'id_group'=>0,
					'sex'=>0,
					'money'=>0,
					'cents'=>0,
					'count_wrongs'=>0,
					'count_papers'=>0,
					'myquiz'=>'免费试卷',
				);
			}
		}
		$sql = "select 
		  ".$pfx."members.uid,username,gender,groupid,extgroupids,credits,extcredits2
		 ,field_".$this->cfg->user_extend_myquiz."
		 ,field_".$this->cfg->user_extend_wrongs."
		 ,field_".$this->cfg->user_extend_papers."
		 from 
		".$pfx."members,".$pfx."memberfields where ".$pfx."members.uid = ".$id." and ".$pfx."members.uid = ".$pfx."memberfields.uid ;";
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		return array(
			'id_user'=>$id,
			'title_group'=>'',
			'name'=>$temp['username'],
			'id_group'=>$temp['extgroupids'],
			'sex'=>$temp['gender'],
			'money'=>$temp['extcredits2'],
			'cents'=>$temp['credits'],
			'count_wrongs'=>$temp["field_".$this->cfg->user_extend_wrongs],
			'count_papers'=>$temp["field_".$this->cfg->user_extend_papers],
			'myquiz'=>$temp["field_".$this->cfg->user_extend_myquiz],
		);		
	}
	
	/**
	 * 减少某个用户的学习币
	 * Discuz中,用extcredits2字段
	 * */
	public function reduceMyMoney($id,$money){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;
		if($id==null || $id=='mine'){
			if(isset($_COOKIE['qW3_sid'])){
				$sid = $_COOKIE['qW3_sid'];
				$sql = "select uid from ".$pfx."sessions where sid = '".$sid."'";
				$res = mysql_query($sql,$conn);
				$temp = mysql_fetch_assoc($res);
				$id = $temp['uid'];				
			}else{
				
			}
		}	
		$sql = "select extcredits2 from ".$pfx."members where uid =".$id;
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp['extcredits2']<$money){
			return false;
		}else{
			$sql = "update ".$pfx."members set extcredits2 = ".($temp['extcredits2']-$money)." where uid =".$id;
			mysql_query($sql,$conn);
			return true;
		}
	}

	/**
	 * 往Discuz中的用户组表中插入用户组信息
	 * */
	public function initGroup(){
		if($this->cfg->debug!=1){
			$this->hackAttack();
			return;
		}
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;

		$sql = "
		delete from ".$pfx."usergroups where radminid = 0 and grouptitle in ('管理员','教师','学员'); ";
		mysql_query($sql,$conn);

		$sql = "
		insert into ".$pfx."usergroups(
			 radminid
			,type
			,grouptitle
		)values(
			 '0'
			,'special'
			,'管理员'
		) ";
		mysql_query($sql,$conn);
		$id1 = mysql_insert_id($conn);
		$sql = "
		insert into ".$pfx."usergroups(
			 radminid
			,type
			,grouptitle
		)values(
			 '0'
			,'special'
			,'教师'
		) ";
		mysql_query($sql,$conn);
		$id2 = mysql_insert_id($conn);
		$sql = "
		insert into ".$pfx."usergroups(
			 radminid
			,type
			,grouptitle
		)values(
			 '0'
			,'special'
			,'学员'
		) ";
		mysql_query($sql,$conn);
		$id3 = mysql_insert_id($conn);

		$this->rewrite['group_admin']=$id1;
		$this->rewrite['group_teacher']=$id2;
		$this->rewrite['group_student']=$id3;
	}
	
	/**
	 * 在系统的主导航栏上添加一个项目
	 * 但是没有保存,需要用户在后台 更新保存一下 才可以
	 * */
	public function initNav(){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;
		
		$sql = "delete from ".$pfx."navs where title = 'wls' ";
		mysql_query($sql,$conn);		
		
		$sql = "insert into ".$pfx."navs (
			 name
			,title
			,url
			,available		
			,displayorder	
			,target
		) values(
			 '在线考试学习'
			,'wls'
			,'".$_SERVER['SCRIPT_NAME']."?controller=user&action=viewProfile'
			,'1'
			,'9'	
			,'1'	
		)  ";		
		mysql_query($sql,$conn);
	}
	
	/**
	 * 累加已做试卷的数目
	 * 
	 * @param $id 用户编号
	 * */
	public function addUpQuiz($id){
		$conn = $this->conn();
		$pfx = $this->cfg->dbprefix;		
		if($id==null || $id=='mine'){
			if(isset($_COOKIE['SE7P_2132_sid'])){
				$sid = $_COOKIE['SE7P_2132_sid'];
				$sql = "select uid from ".$pfx."session where sid = '".$sid."'";

				$res = mysql_query($sql,$conn);
				$temp = mysql_fetch_assoc($res);
				$id = $temp['uid'];	
			}
		}
		$sql = "update ".$pfx."memberfields set papers = papers+1 where uid = ".$id;
		mysql_query($sql,$conn);
	}
}
?>