<?php
class basic_user {
	
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
		
		if($function == "grid"){
			$action = "120201";
			if(basic_user::checkPermission($executor, $action, $session)){
				$sortname = "id";
				$sortorder = "asc";
				if(isset($_REQUEST['sortname'])){
					$sortname = $_REQUEST['sortname'];
				}
				if(isset($_REQUEST['sortorder'])){
					$sortorder = $_REQUEST['sortorder'];
				}

				$t_return = basic_user::grid(
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
				$t_return = basic_user::add(
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
				$t_return = basic_user::modify(
					 $_REQUEST['data']
					,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="modify_myself"){
			$action = "1123";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = basic_user::modify_myself(
					$_REQUEST['data']
					,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="remove"){
			$action = "120223";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = basic_user::remove(
					$_REQUEST['usernames']
					,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="view"){
			$action = "120202";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = basic_user::view(
					$_REQUEST['id']
					,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="login"){
			$gis_lat = 0;
			$gis_lot = 0;
			if(isset($_REQUEST['gis_lat']))$gis_lat = $_REQUEST['gis_lat'];
			if(isset($_REQUEST['gis_lat']))$gis_lot = $_REQUEST['gis_lot'];
			$t_return = basic_user::login(
				 $_REQUEST['username']
				,$_REQUEST['password']
				,$_SERVER["REMOTE_ADDR"]
				,$_SERVER['HTTP_USER_AGENT']
				,$gis_lat
				,$gis_lot
			);	
		}
		else if($function =="logout"){
			$t_return = basic_user::logout(
				$_REQUEST['executor']
				,$_REQUEST['session']
			);
		}
		else if($function =="loadConfig"){
			$t_return = basic_user::loadConfig();
		}
		else if($function =="updateSession"){
			$t_return = basic_user::updateSession(
				 $executor
				,$session
			);
		}
		else if($function =="group_get"){
			$action = "120241";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = basic_user::group_get(
					 $_REQUEST['username']
					,$executor
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		else if($function =="group_set"){
			$action = "120241";
			if(basic_user::checkPermission($executor, $action, $session)){
				$t_return = basic_user::group_set(
					 $_REQUEST['username']
					,$_REQUEST['group_codes']
				);
			}else{
				$t_return['action'] = $action;
			}
		}
		return $t_return;
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

        $sql_where = basic_user::search($search, $executor);
		$sql_order = " order by basic_user.".$sortname." ".$sortorder." ";
		  
		$sql = tools::getSQL("basic_user__grid");
		$sql .= $sql_where." ".$sql_order." limit ".(($page-1)*$pagesize).", ".$pagesize;
		
		$res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		
		$sql_total = "select count(*) as total FROM basic_user ".$sql_where;		
		$res = mysql_query($sql_total,$conn);
		$total = mysql_fetch_assoc($res);
        
        $returnData = array(
            'Rows'=>$data,
            'Total'=>$total['total']
        );

        return $returnData;
    }
    
    private static function search($search,$executor){
    	$sql_where = " where 1=1 ";
    	
    	$search=json_decode2($search,true);
    	$search_keys = array_keys($search);
    	for($i=0;$i<count($search);$i++){
    		if($search_keys[$i]=='username' && trim($search[$search_keys[$i]])!='' ){
    			$sql_where .= " and basic_user.username like '%".$search[$search_keys[$i]]."%' ";
    		}
    		if($search_keys[$i]=='group_code' && trim($search[$search_keys[$i]])!='' ){
    			$sql_where .= " and basic_user.group_code = '".$search[$search_keys[$i]]."' ";
    		}
    		if($search_keys[$i]=='status' && trim($search[$search_keys[$i]])!='' ){
    			$sql_where .= " and basic_user.status = '".$search[$search_keys[$i]]."' ";
    		}
    		if($search_keys[$i]=='type' && trim($search[$search_keys[$i]])!='' ){
    			$sql_where .= " and basic_user.type = '".$search[$search_keys[$i]]."' ";
    		}
    	}
    	
    	return $sql_where;
    }
    
        
	public static function remove($usernames=NULL,$executor=NULL){
		$conn = tools::getConn();
		$usernames = explode(",", $usernames);
		for($i=0;$i<count($usernames);$i++){
		    $sql = "delete from basic_user where username = '".$usernames[$i]."' ;";
		    mysql_query($sql,$conn);
		    $sql = "delete from basic_group_2_user where user_code = '".$usernames[$i]."' ;";
		    mysql_query($sql,$conn);
		}
		
		return  array(
			'status'=>1
		    ,'msg'=>'OK'
		);
	}	
    
	public static function getPermission($username){
		$s_return = "";
		$sql = tools::getSQL("basic_user__getPermission");
		$sql = str_replace( "__username__", "'".$username."'",$sql);
		
		$conn = tools::getConn();
		$res = mysql_query($sql,$conn);
		
        while($temp = mysql_fetch_assoc($res)){
            $s_return .= $temp['code'].",";
        }		
        $s_return = substr($s_return,0,strlen($s_return)-1);
		
		return $s_return;
	}    
    
    public static function getPermissionTree($username){
		$a_return = array();
		
		$sql = tools::getSQL("basic_user__getPermission");
		$sql = str_replace( "__username__", "'".$username."'",$sql);
		
		$conn = tools::getConn();
		$res = mysql_query($sql,$conn);

        while($temp = mysql_fetch_assoc($res)){
            $a_return[] = $temp;
        }
		$a_return = tools::list2Tree($a_return);	

		return $a_return;
    }
    
	public static function checkUsernameUsed($username){
		$sql = "select id from basic_user where username = '".$username."' ";
		$res = mysql_query($sql,tools::getConn());
	    if($temp = mysql_fetch_assoc($res)){
            return true;
        }
		return false;
	}
    
	public static function getMySession($executor, $session){
        $conn = tools::getConn();
		$sql = tools::getSQL("basic_user__getMySession");
		$sql = str_replace("__user_code__", "'".$executor."'",$sql);
		$sql = str_replace("__session__", "'".$session."'",$sql);
		$sql = str_replace("\n", " ",$sql);

		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp){		    
			return array(
				 "status"=>"1"
				,"data"=>$temp
			);
		}else{            
		    //basic_user::$permissions = $sql;
		    exit("error");
		}
	}
	
	public static function getSession($executor){
		$conn = tools::getConn();
		$sql = tools::getSQL("basic_user__getSession");
		$sql = str_replace("__user_code__", "'".$executor."'",$sql);
		$sql = str_replace("\n", " ",$sql);
	
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp){
			return array(
					"status"=>"1"
					,"data"=>$temp
			);
		}else{
			//basic_user::$permissions = $sql;
			exit("error");
		}
	}	
	
	public static function checkPermission($executor, $action, $session){
		if($executor=='guest'){
			$s = basic_user::getSession($executor);
		}else{
			$s = basic_user::getMySession($executor, $session);
		}		
		$p = $s['data']['permissions'];
	    $arr = explode(",", $p );
	    for($i=0;$i<count($arr);$i++){
	        if($action == $arr[$i]){
	            return true; 
	        }
	    }
	    return false;
	}
	
	public static function login_joomla(){
		include_once '../../configuration.php';
		tools::$joomlaConfig = new JConfig();
		
	    $key = md5(md5( tools::$joomlaConfig->secret.'site'));
	    $pfx = tools::$joomlaConfig->dbprefix;
	    $conn = tools::getConn();
	    
	    if(!isset($_COOKIE[$key])){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the joomla first'
		    );
	    }
	    
		$sql = "select username,guest,userid,group_id from 
		".$pfx."session,
		".$pfx."user_usergroup_map
		
		where session_id = '".$_COOKIE[$key]."' 
			and ".$pfx."user_usergroup_map.user_id = ".$pfx."session.userid and client_id = 0
		";
		
        $res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp==false){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the joomla first'
		    );
		}
		
		$sql2 = "select * from basic_user where username = '".$temp['username']."' ";
		$res2 = mysql_query($sql2,$conn);
		$temp2 = mysql_fetch_assoc($res2);			
		
        if($temp2==false){      
        	$sutdent_default = tools::getConfigItem("dzx_defaultStudentGroup");
        	$teacher_default = tools::getConfigItem("dzx_defaultTeacherGroup");
            $type =  20; $group = $sutdent_default; 
            if($temp['group_id']=='5' || $temp['group_id']=='6' || $temp['group_id']=='7'){
                $type = '30'; $group = $teacher_default; 
            }
			$data = array(
			    'username'=>$temp['username']
			    ,'password'=>"JOOMLA"
			    ,'money'=>"0"
			    ,'group_code'=>$group
			    ,'group_all'=>$group
			    ,'type'=>$type
			    ,'id'=>tools::getTableId("basic_user")
			);
			
			$sql = "insert into basic_user (";
    		$sql_ = ") values (";
    		$keys = array_keys($data);
    		for($i=0;$i<count($keys);$i++){
        		$sql .= $keys[$i].",";
    		    $sql_ .= "'".$data[$keys[$i]]."',";
    		}
    		$sql = substr($sql, 0,strlen($sql)-1);
    		$sql_ = substr($sql_, 0,strlen($sql_)-1).")";
    		$sql = $sql.$sql_;		
 	  
    		mysql_query($sql,$conn);	

    		$sql = "insert into basic_group_2_user (user_code,group_code) values ('".$temp['username']."','".$group."');";
    		mysql_query($sql,$conn);	    		
        }		
		
		$t_return = basic_user::login_wls($temp['username'],'md5(concat(password, hour(now()) ))',$_SERVER["REMOTE_ADDR"],$_SERVER['HTTP_USER_AGENT'],"0","0");
		return $t_return;		
	}
	
	public static function login_dzx(){		
		include_once '../../config/config_global.php';
		tools::$dzxConfig = $_config;
	    if(!isset($_COOKIE[tools::$dzxConfig['cookie']['cookiepre'].'2132_sid'])){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the Discuzx first'
		    );
	    }
        $sessionid = $_COOKIE[tools::$dzxConfig['cookie']['cookiepre'].'2132_sid'];
		$conn = tools::getConn();
		$pfx = tools::$dzxConfig['db'][1]['tablepre'];
		
		$sql_dzx = "select 
			".$pfx."common_member.username
			,".$pfx."common_member.password
			,".$pfx."common_member.adminid
			,".$pfx."common_member.groupid
			,(select type from ".$pfx."common_usergroup where ".$pfx."common_usergroup.groupid = ".$pfx."common_member.groupid) as type
			,".$pfx."common_member_count.extcredits2 as money 
			,".$pfx."common_member.credits
			,".$pfx."common_session.sid
			 from 
			 ".$pfx."common_member
			,".$pfx."common_member_count
			,".$pfx."common_session
			 where 			
			".$pfx."common_member.uid = ".$pfx."common_member_count.uid and ".$pfx."common_session.uid = ".$pfx."common_member.uid
			 and ".$pfx."common_session.sid = '".$sessionid."';
		";

		$res = mysql_query($sql_dzx,$conn);
		$data_dzx = mysql_fetch_assoc($res);
		if($data_dzx==false){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the Discuzx first'
		    );
		}
		
		$sql2 = "select * from basic_user where username = '".$data_dzx['username']."' ";
		$res2 = mysql_query($sql2,$conn);
		$temp2 = mysql_fetch_assoc($res2);			
		
        if($temp2==false){      
        	$sutdent_default = tools::getConfigItem("dzx_defaultStudentGroup");
        	$teacher_default = tools::getConfigItem("dzx_defaultTeacherGroup");
            $type = "20"; $group = $sutdent_default; 
            if($data_dzx['groupid']=='2' || $data_dzx['groupid']=='3'){
            	
                $type = '30'; $group = $teacher_default;      
            }
            if($data_dzx['groupid']=='1'){
                $type = '10'; $group = "10";      
            }            
            if($data_dzx['type']=='special')$group = $data_dzx['groupid'];
			$data = array(
			    'username'=>$data_dzx['username']
			    ,'password'=>"DZX"
			    ,'money'=>"0"
			    ,'group_code'=>$group
			    ,'group_all'=>$group
			    ,'type'=>$type
			    ,'id'=>tools::getTableId("basic_user")
			    ,'status'=>'10'
			);
			
			$sql = "insert into basic_user (";
    		$sql_ = ") values (";
    		$keys = array_keys($data);
    		for($i=0;$i<count($keys);$i++){
        		$sql .= $keys[$i].",";
    		    $sql_ .= "'".$data[$keys[$i]]."',";
    		}
    		$sql = substr($sql, 0,strlen($sql)-1);
    		$sql_ = substr($sql_, 0,strlen($sql_)-1).")";
    		$sql = $sql.$sql_;		
 	  
    		mysql_query($sql,$conn);	

    		$sql2 = "insert into basic_group_2_user (user_code,group_code) values ('".$data_dzx['username']."','".$group."');";
    		mysql_query($sql2,$conn);	    		
        }		
		
		$t_return = basic_user::login_wls($data_dzx['username'],'md5(concat(password, hour(now()) ))',$_SERVER["REMOTE_ADDR"],$_SERVER['HTTP_USER_AGENT'],"0","0");
		$t_return['logindata']['money'] = $data_dzx['money'];
		$t_return['logindata']['credits'] = $data_dzx['credits'];
		return $t_return;
	}
	
    public static function login_dede(){
	    if(!isset($_COOKIE['DedeUserID'])){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the Dede CMS first'
		    );
	    }
        $sessionid = $_COOKIE['DedeUserID'];
        include_once '../../data/common.inc.php';
		$conn = tools::getConn();
		$pfx = $cfg_dbprefix;
		
		$sql = "SELECT
        dede_member.userid as username,
        dede_member.money,
        dede_member.matt as groupid,
        dede_member.`mid`
        FROM
        dede_member where mid = ".$sessionid;
		
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp==false){
		    return array(
		        'status'=>2
		        ,'msg'=>'Please login the Discuzx first'
		    );
		}
		
		$sql2 = "select * from basic_user where username = '".$temp['username']."' ";
		$res2 = mysql_query($sql2,$conn);
		$temp2 = mysql_fetch_assoc($res2);			
		
        if($temp2==false){      
        	$sutdent_default = tools::getConfigItem("dzx_defaultStudentGroup");
        	$teacher_default = tools::getConfigItem("dzx_defaultTeacherGroup");        	
            $type =  20; $group = $sutdent_default;
			$data = array(
			    'username'=>$data_dzx['username']
			    ,'password'=>"DEDE"
			    ,'money'=>"0"
			    ,'group_code'=>$group
			    ,'group_all'=>$group
			    ,'type'=>$type
			    ,'id'=>tools::getTableId("basic_user")
			    ,'status'=>'10'
			);
			
			$sql = "insert into basic_user (";
    		$sql_ = ") values (";
    		$keys = array_keys($data);
    		for($i=0;$i<count($keys);$i++){
        		$sql .= $keys[$i].",";
    		    $sql_ .= "'".$data[$keys[$i]]."',";
    		}
    		$sql = substr($sql, 0,strlen($sql)-1);
    		$sql_ = substr($sql_, 0,strlen($sql_)-1).")";
    		$sql = $sql.$sql_;		
 	  
    		mysql_query($sql,$conn);

    		$sql2 = "insert into basic_group_2_user (user_code,group_code) values ('".$temp['username']."',".$group.");";
    		mysql_query($sql2,$conn);	    		
        }		
		
		$t_return = basic_user::login_wls($temp['username'],'md5(concat(password, hour(now()) ))',$_SERVER["REMOTE_ADDR"],$_SERVER['HTTP_USER_AGENT'],"0","0");
		$t_return['sql1'] = $sql;
		$t_return['sql2'] = $sql2;
		return $t_return;
	}	
	
	public static function login_phpwind(){
	    //TODO
	}
	
	public static function login(
			$username=NULL
			,$md5PasswordTime=NULL
			,$ip=NULL
			,$client=NULL
			,$gis_lat=NULL
			,$gis_lot=NULL
			){
		$t_return = array(
			'status'=>'2'
			,'msg'=>'wrong config '
		);
		$mode = tools::getConfigItem("MODE");
		if($mode=='DZX'){
			$t_return = basic_user::login_dzx();
		}
		else if($mode=='WLS'){
			$t_return = basic_user::login_wls(
					$username
					,$md5PasswordTime
					,$ip
					,$client
					,$gis_lat
					,$gis_lot
			);
		}
		else if($mode=='DEDE'){
			$t_return = basic_user::login_dede();
		}	
		else if($mode=='JOOMLA'){
			$t_return = basic_user::login_joomla();
		}
		return $t_return;
	}

	public static function login_wls(
			 $username=NULL
			,$md5PasswordTime=NULL
			,$ip=NULL
			,$client=NULL
			,$gis_lat=NULL
			,$gis_lot=NULL){
		
		$t_return = array("status"=>"2");
		$conn = tools::getConn();
		$sql = "";
		
		$sql = "select count(*) as total from basic_memory ";
		$res = mysql_query($sql,$conn);
		$temp = mysql_fetch_assoc($res);
		if($temp['total']=='0'){
		    tools::initMemory();
		}	
		
		if($username=="guest"){
		    $md5PasswordTime = "md5(concat(password, hour(now()) ))";
		}
		if(substr($md5PasswordTime, 0,3)!='md5'){
			$md5PasswordTime = "'".$md5PasswordTime."'";
		}
		
		$sql = tools::getSQL("basic_user__login_check");
		$sql = str_replace("__username__", "'".$username."'",$sql);
		$sql = str_replace("__password__",$md5PasswordTime,$sql);
		$sql = str_replace("\n"," ",$sql);
		$sql = str_replace("\t"," ",$sql);

		$res = mysql_query($sql,$conn);
		if(!$temp = mysql_fetch_assoc($res)){
		    return array(
		        'status'=>'2'
		        ,'msg'=>'Wrong password or username or group was closed'
		        ,'sql'=>$sql
		    );
		}else{
		    $session = md5(rand(10000, 99999));
		    $temp['session'] = md5($session.date("G"));
		    
		    $t_return = array(
				 'foo'=>'bar'
		        ,'logindata'=>$temp
		        ,'status'=>"1"
		        ,'msg'=>'OK'
		        ,'permissions'=>basic_user::getPermissionTree($username)
		        ,'il8n'=>tools::readIl8n()	
		        ,'H'=>date("G")	       
		    );
		    
            $sql_logout = tools::getSQL("basic_user__login_logout");
            $sql_logout = str_replace( '__user_code__', "'".$username."'",$sql_logout);
			mysql_query($sql_logout,$conn);
						
			//更新SESSION表
			$permissions = basic_user::getPermission($username);
			$sql = tools::getSQL("basic_user__login_session");
			$sql = str_replace( '__username__', "'".$username."'" ,$sql);
			$sql = str_replace( '__permissions__', "'".$permissions."'",$sql);
			$sql = str_replace( '__session__', "'".$session."'",$sql);
			$sql = str_replace( '__ip__', "'".$ip."'",$sql);
			$sql = str_replace( '__client__', "'".$client."'",$sql);
			$sql = str_replace( '__gis_lat__', "'".$gis_lat."'",$sql);
			$sql = str_replace( '__gis_lot__', "'".$gis_lot."'",$sql);
			mysql_query($sql,$conn);
		}
		
		return $t_return;
	}
    
	public static function logout($username=NULL,$session=NULL){
	    if($username==NULL)$username = $_REQUEST['executor'];
	    if($session==NULL)$session = $_REQUEST['session'];
	    
		$conn = tools::getConn();
		$sql = tools::getSQL("basic_user__logout");
		$sql = str_replace("__user_code__", "'".$username."'", $sql) ;
		$sql = str_replace("__session__", "'".$session."'", $sql) ;
		mysql_query($sql,$conn);
		
		return array(
		    'status'=>'1'
		    ,'msg'=>'ok'
		);
	}
	
	public static function modify($data=NULL,$executor=NULL){   
	    $conn = tools::getConn();
	    
	    $t_data = json_decode2($data,true);
	    $username = $t_data['username'];
	    unset($t_data['username']);
		$str_keys = ",username,group_code,status,type,password,money";		
		$sql = "";

		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    if(!strpos($str_keys, $keys[$i])){		        
		        return array(
		            'status'=>'2'
		            ,'msg'=>'data wrong'.$keys[$i]
		        );
		    }
		    if ($keys[$i]=='group_code') {
		        mysql_query("delete from basic_group_2_user where user_code = '".$username."' ;",tools::getConn());
		        mysql_query("insert into basic_group_2_user (user_code,group_code) values ('".$username."','".$t_data[$keys[$i]]."'); ;",tools::getConn());
		    }
		    $t_data[$keys[$i]] = "'".$t_data[$keys[$i]]."'";
		}
		$t_data['time_lastupdated'] = "now()";
		$t_data['count_updated'] = "count_updated+1";
		
		$sql = "update basic_user set ";
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    $sql .= $keys[$i]." = ".$t_data[$keys[$i]].",";
		}
		$sql = substr($sql, 0,strlen($sql)-1);
		$sql .= " where username = '".$username."' ";
		
		mysql_query($sql,$conn);		
		
		return array(
            'status'=>'1'
            ,'msg'=>'ok'
        );
	}		
	
	public static function modify_myself($data=NULL,$executor=NULL){	    
	    $conn = tools::getConn();
	    
	    $t_data = json_decode2($data,true);
		$str_keys = ",password,email,photo,";		
		$sql = "";
		$password_old = $t_data["password_old"];
		unset($t_data["password_old"]);
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    if(!strpos($str_keys, $keys[$i])){
		        echo strpos($str_keys, $keys[$i]);
		        return array(
		            'status'=>'2'
		            ,'msg'=>'data wrong'.$keys[$i]
		        );
		    }
		    $t_data[$keys[$i]] = "'".$t_data[$keys[$i]]."'";
		}
		$t_data['time_lastupdated'] = "now()";
		$t_data['count_updated'] = "count_updated+1";
		
		$sql = "update basic_user set ";
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    $sql .= $keys[$i]." = ".$t_data[$keys[$i]].",";
		}
		$sql = substr($sql, 0,strlen($sql)-1);
		$sql .= " where username = '".$executor."' and password = '".$password_old."'";
		
		mysql_query($sql,$conn);		
		
		return array(
            'status'=>'1'
            ,'msg'=>'ok'
        );
	}	
	
    public static function loadConfig() {
        $conn = tools::getConn();
        $config = array();
        
        $sql = "select code,value from basic_parameter where reference = 'basic_user__type'  order by code";
        $res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['basic_user__type'] = $data;
		
		$sql = "select code,value from basic_parameter where reference = 'basic_user__status' order by code";
        $res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){
			$data[] = $temp;
		}
		$config['basic_user__status'] = $data;
		
		$sql = "select code,name as value from basic_group order by code";
        $res = mysql_query($sql,$conn);
		$data = array();
		while($temp = mysql_fetch_assoc($res)){			
			$len = strlen($temp['code']);
			for($i=2;$i<$len;$i+=2){
				$temp['value'] = "-".$temp['value'];
			}
			$data[] = $temp;
		}
		$config['group'] = $data;

	    return $config;		
	}  
    
	public static function add($data=NULL,$executor=NULL){
	    
	    $t_data = json_decode2($data,true);
		$conn = tools::getConn();
		$username = $t_data['username'];
		if(basic_user::checkUsernameUsed($username)){
			return array(
                'status'=>"2"
                ,'msg'=>'username wrong'
            );
		}				
		
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
		    $t_data[$keys[$i]] = "'".$t_data[$keys[$i]]."'";
		}
		
		$id = tools::getTableId("basic_user");
		$t_data['id'] = "'".$id."'";
		$t_data['creater_code'] = "'".$executor."'";
		$t_data['creater_group_code'] = "(select group_code from basic_group_2_user where user_code = '".$executor."' order by group_code limit 1 )";
		
		$sql = "insert into basic_user (";
		$sql_ = ") values (";
		$keys = array_keys($t_data);
		for($i=0;$i<count($keys);$i++){
    		$sql .= $keys[$i].",";
		    $sql_ .= $t_data[$keys[$i]].",";
		}
		$sql = substr($sql, 0,strlen($sql)-1);
		$sql_ = substr($sql_, 0,strlen($sql_)-1).")";
		$sql = $sql.$sql_;			
		mysql_query($sql,$conn);
		
		$user_code = $t_data["username"];
		$group_code = $t_data["group_code"];
		
		$sql = "insert into basic_group_2_user (user_code,group_code) values (".$user_code.",".$group_code.");";
		mysql_query($sql,$conn);
		
		$sql = "update basic_group set count_users = (select count(*) from basic_user where basic_user.group_code = basic_group.code )";
		mysql_query($sql,$conn);
		
        return array(
            'status'=>"1"
            ,'msg'=>'ok'
        );
	}
	
	public static function add_register(){
	    $conn = tools::getConn();   	    
	    $json = json_decode2($_REQUEST['data'],true);

	    $data = array(
	        'username'=>"'".$json['executor']."'"
	        ,'password'=>"md5('".$json['password']."')"
	        ,'money'=>'100'
	        ,'group_code'=>"'20'"
	        ,'group_all'=>"'20'"
	        ,'id'=>tools::getTableId("basic_user")
	        ,'type'=>'20'
	    );
	    
	    $sql = "select * from basic_user where username = ".$data['username']."";	
	    $res = mysql_query($sql,$conn);
	    $temp = mysql_fetch_assoc($res);
	    if($temp!=false){
	        return array(
                'status'=>"2"
                ,'msg'=>'username already used'
            );
	    }    
	    
		$sql = "insert into basic_user (";
		$sql_ = ") values (";
		$keys = array_keys($data);
		for($i=0;$i<count($keys);$i++){
    		$sql .= $keys[$i].",";
		    $sql_ .= $data[$keys[$i]].",";
		}
		$sql = substr($sql, 0,strlen($sql)-1);
		$sql_ = substr($sql_, 0,strlen($sql_)-1).")";
		$sql = $sql.$sql_;		    		
		mysql_query($sql,$conn);	

		$sql = "insert into basic_group_2_user (user_code,group_code) values (".$data['username'].",".$data['group_code'].");";
		mysql_query($sql,$conn);
		
        return array(
            'status'=>"1"
            ,'msg'=>'ok'
            ,'data'=>$data
        );
	}
    
    public static function view($id){  
        $conn = tools::getConn();    
        
        $sql = tools::getSQL("basic_user__view");
        $sql = str_replace("__id__", $id, $sql);
        $res = mysql_query($sql, $conn );
        $data= mysql_fetch_assoc($res);
        
        return array(
            'status'=>"1"
            ,'msg'=>'ok'
            ,'data'=>$data
        );
    }  

	public static function updateSession($user_code=NULL,$session=NULL){
	    if($user_code==NULL)$user_code = $_REQUEST['executor'];
	    if($session==NULL)$session = $_REQUEST['session'];
	    
		$r_session = md5(rand(1000, 9999));
		$sql = tools::getSQL("basic_user__session_update");
		$sql = str_replace("__user_code__", "'".$user_code."'", $sql);
		$sql = str_replace("__r_session__", "'".$r_session."'", $sql);
		$sql = str_replace("__session__", "'".$session."'", $sql);
        $conn = tools::getConn();
        mysql_query($sql, $conn );
        		
		$r_session = md5($r_session.date("G"));
		return array(
		    'status'=>'1'
		    ,'session'=>$r_session
		);		
	}
	
	public static function group_get($username=NULL){
		$conn = tools::getConn();
		
		$sql = tools::getSQL("basic_user__group_get");
		$sql = str_replace("__username__", "'".$username."'", $sql);
		
        $res = mysql_query($sql,$conn);
        $data = array();
        while($temp = mysql_fetch_assoc($res)){
            if ($temp['user_code']!=NULL) {
                $temp['ischecked'] = 1;
            }
            $temp['code_'] =  $temp['code'];
            $temp['code'] =  str_replace("-", "", $temp['code']);
            $data[] = $temp;
        }
        $data = tools::list2Tree($data);

        return array("status"=>"1","groups"=>$data);
	}	
	
	public static function group_set($username=NULL,$group_codes=NULL){
		$conn = tools::getConn();
		
		$sql = "delete from basic_group_2_user where user_code = '".$username."' ";
		mysql_query($sql,$conn);
		
		$group_codes = explode(",", $group_codes);
		for($i=0;$i<count($group_codes);$i++){
		    $sql = "insert into basic_group_2_user (user_code,group_code) values ( '".$username."','".$group_codes[$i]."' ); ";
		    mysql_query($sql,$conn);
		}
		
		$sql = "update basic_user set group_all = '".implode($group_codes,",")."' where username = '".$username."'";
		$res = mysql_query($sql,$conn);
		if($res){
			return array(
					'status'=>"1"
					,'msg'=>'ok'
			);
		}else{
			return array(
					'status'=>"2"
					,'msg'=>mysql_error($conn)
			);
		}
	}	

	public static function data4test($total){
		$t_return = array("status"=>"1","msg"=>"");
		$conn = tools::getConn();
		$conn2 = tools::getConn(true);
		
		$sql = "delete from basic_user where type in ('20','30')";
		$sql = "delete from basic_group_2_user where user_code not in ('admin','guest')";
		mysql_query($sql,$conn);
		$sql = "select code from basic_group where type = '40' ";
		mysql_query("START TRANSACTION;",$conn2);
		$res = mysql_query($sql,$conn);
		$total_ = 0;
		while($temp = mysql_fetch_assoc($res)){
			$r = rand(10, 20);
			for($i=0;$i<$r;$i++){
				$code = $temp['code']."--".$i;
				$sql = "insert into basic_user(username,password,group_code,id,type,status,money) values ('".$code."',md5('".$code."'),'". $temp['code']."','".(1000+$total_)."','20','10','1000');";
				mysql_query($sql,$conn);
				$sql = "insert into basic_group_2_user(user_code,group_code) values ('".$code."','". $temp['code']."');";
				mysql_query($sql,$conn);
				$total_++;
				/*
				if($total_>=$total){
					mysql_query("COMMIT;",$conn2);
					return $t_return;
				}
				*/
			}
		}
		$code = $temp['code'];
		mysql_query("COMMIT;",$conn2);
		
		$sql = "update basic_user set type = '30' where group_code like '%X%'";
		mysql_query($sql,$conn2);
		$t_return['msg']="Table basic_user added row in total : ".$total_;
		return $t_return;
	}
}