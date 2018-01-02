<?php
define('IN_ECS', true);

/* 代码 */
require(dirname(__FILE__) . '/includes/init.php');


/*------------------------------------------------------ */
//-- 列表编辑 ?act=list_edit
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'default')
{
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
	/* 检查权限 */
    admin_priv('pc_user_account');
	$pcconfig = array();
	
	$sql = "select * from ".$ecs->table('pc_config')." where display = 1";
	$pcconfig = $db->getAll($sql);
	
	$uid = isset($_REQUEST['uid'])?$_REQUEST['uid']:14;
	$sql = "select pu.uid , tuijianren_user_id, jiedianren_user_id, leftright, level,role,identity, account_xianjinbi, account_xiaofeibi, account_aixinbi,account_jifenbi,account_jifen, u.user_name,u.reg_time , u.mobile_phone,from_unixtime(u.reg_time,'%Y-%m-%d %H-%i-%s') as reg_time_format from ".$ecs->table('pc_user')." pu left join ".$ecs->table('users')." u on pu.uid = u.user_id where pu.uid = ".$uid;
	//echo $sql;
	//echo $uid;
	$userinfo = $db->getRow($sql);
	//var_dump($userinfo);
	if($userinfo['tuijianren_user_id']){
		$userinfo['tuijianren_user_name'] = $db->getOne("select user_name from ".$ecs->table('users')." where user_id = ".$userinfo['tuijianren_user_id']);
	}else{
		$userinfo['tuijianren_user_name'] = "";
	}
	if($userinfo['jiedianren_user_id']){
		$userinfo['jiedianren_user_name'] = $db->getOne("select user_name from ".$ecs->table('users')." where user_id = ".$userinfo['jiedianren_user_id']);
	}else{
		$userinfo['jiedianren_user_name'] = "";
	}
	//var_dump($userinfo);
	//$role = $db->getAll("select id,role_name as name from ".$ecs->table('pc_user_role'));
	//$level = $db->getAll("select id,level_name as name from ".$ecs->table('pc_user_level'));
	//$identity = $db->getAll("select id,identity_name as name from ".$ecs->table('pc_user_identity'));
	
	$smarty->assign('pcconfig',$pcconfig);
	$smarty->assign('userinfo',$userinfo);
	//$smarty->assign('role',$role);
	//$smarty->assign('level',$level);
	//$smarty->assign('identity',$identity);
    $smarty->display('pc_user_account_money.htm');
}


/*------------------------------------------------------ */
//-- 提交   ?act=post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'post')
{
    /* 检查权限 */
    admin_priv('pc_user_account');
	
	$uid = isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
	if(!$uid){
		sys_msg($_LANG['save_fail'], 0);
	}
	
	$sql = "select uid , tuijianren_user_id, jiedianren_user_id, leftright, level,role,identity, account_xianjinbi, account_xiaofeibi, account_jifenbi,account_jifen, u.user_name,u.reg_time , u.mobile_phone,from_unixtime(u.reg_time,'%Y-%m-%d %H-%i-%s') as reg_time_format from ".$ecs->table('pc_user')." pu left join ".$ecs->table('users')." u on pu.uid = u.user_id where pu.uid = ".$uid;
	$userinfo = $db->getRow($sql);
	//var_dump($userinfo);
	
	$username = isset($_REQUEST['username'])?$_REQUEST['username']:'';
	$username_change_note = isset($_REQUEST['username_change_note'])?$_REQUEST['username_change_note']:'';
	
	$mobile_phone = isset($_REQUEST['mobile_phone'])?$_REQUEST['mobile_phone']:'';
	$mobile_change_note = isset($_REQUEST['mobile_change_note'])?$_REQUEST['mobile_change_note']:'';
	
	$role = isset($_REQUEST['role'])?$_REQUEST['role']:'';
	$role_change_note = isset($_REQUEST['role_change_note'])?$_REQUEST['role_change_note']:'';
	
	$identity = isset($_REQUEST['identity'])?$_REQUEST['identity']:'';
	$identity_change_note = isset($_REQUEST['identity_change_note'])?$_REQUEST['identity_change_note']:'';
	
	$level = isset($_REQUEST['level'])?$_REQUEST['level']:'';
	$level_change_note = isset($_REQUEST['level_change_note'])?$_REQUEST['level_change_note']:'';
	
	$tuijianren_to_uid = isset($_REQUEST['tuijianren_to'])?$_REQUEST['tuijianren_to']:0;
	$tuijianren_change_note = isset($_REQUEST['tuijianren_change_note'])?$_REQUEST['tuijianren_change_note']:'';

	$jiedianren_to_uid = isset($_REQUEST['jiedianren_to'])?$_REQUEST['jiedianren_to']:0;
	$jiedianren_change_note = isset($_REQUEST['jiedianren_change_note'])?$_REQUEST['jiedianren_change_note']:'';
	
	//var_dump($userinfo['tuijianren_user_id']);
	//var_dump($tuijianren_to_uid);
	
	//var_dump($userinfo['jiedianren_user_id']);
	//var_dump($jiedianren_to_uid);
	if($userinfo['user_name'] != $username){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','user_name','".$username."','".$userinfo['user_name']."','".$username_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
	}
	
	if($userinfo['mobile_phone'] != $mobile_phone){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','mobile_phone','".$mobile_phone."','".$userinfo['mobile_phone']."','".$mobile_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
	}
	
	$pc_user_data = array();
	if($userinfo['role'] != $role){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','role','".$role."','".$userinfo['role']."','".$role_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
		$pc_user_data[] = " role = $role ";
	}
	
	if($userinfo['identity'] != $identity){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','identity','".$identity."','".$userinfo['identity']."','".$identity_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
		$pc_user_data[] = " identity = $identity ";
	}
	
	if($userinfo['level'] != $level){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','level','".$level."','".$userinfo['level']."','".$level_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
		$pc_user_data[] = " level = $level ";
	}
	
	if($userinfo['tuijianren_user_id'] != $tuijianren_to_uid){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','tuijianren_user_id','".$tuijianren_to_uid."','".$userinfo['tuijianren_user_id']."','".$tuijianren_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
		$pc_user_data[] = " tuijianren_user_id = $tuijianren_to_uid ";
	}
	
	if($userinfo['jiedianren_user_id'] != $jiedianren_to_uid){
		$sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,new_value,original_value,note,adminid,ctime)values('".$uid."','baseinfo','jiedianren_user_id','".$jiedianren_to_uid."','".$userinfo['jiedianren_user_id']."','".$jiedianren_change_note."','".$adminid."','".time()."')";
		$db->query($sql);
		$pc_user_data[] = " jiedianren_user_id = $jiedianren_to_uid ";
	}
	
	$pc_user_data = array_filter($pc_user_data);
	if($pc_user_data){
		$sql = "update ".$ecs->table('pc_user')." set ".implode(",",$pc_user_data)." where uid = $uid";
		//echo $sql;
		$db->query($sql);
	}
	sys_msg('保存成功', 0);
}



?>