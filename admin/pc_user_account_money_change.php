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
	$type = isset($_REQUEST['type'])?$_REQUEST['type']:'';
	
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
	$smarty->assign('type',$type);
	$smarty->assign('uid',$uid);
	//$smarty->assign('role',$role);
	//$smarty->assign('level',$level);
	//$smarty->assign('identity',$identity);
    $smarty->display('pc_user_account_money_change.htm');
}


/*------------------------------------------------------ */
//-- 提交   ?act=post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'post')
{
    /* 检查权限 */
    admin_priv('pc_user_account');
	$db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
	
	$uid = isset($_REQUEST['uid'])?$_REQUEST['uid']:0;
	$oldvalue = intval(isset($_REQUEST['oldvalue'])?$_REQUEST['oldvalue']:0);
	$value = intval(isset($_REQUEST['value'])?$_REQUEST['value']:0);
	$changetype = isset($_REQUEST['changetype'])?$_REQUEST['changetype']:0;
	$note = isset($_REQUEST['note'])?$_REQUEST['note']:'购物见点奖';
	$type = isset($_REQUEST['type'])?$_REQUEST['type']:'';
	
	if(!$uid){
		sys_msg($_LANG['save_fail'], 0);
	}
        if($value == 0){
            sys_msg('金额不能为0', 1);
            exit;
        }
	if($changetype == 'add'){
		$new_value = $oldvalue + $value; 
		$change_value = "+".$value;
	}elseif($changetype == 'minus'){
            if($oldvalue<$value){
                sys_msg('减少的金额不能大于原有的金额', 1);
                exit;
            }
		$new_value = $oldvalue - $value; 
		$change_value = "-".$value;
	}
		
	if($type == 'xianjinbi'){
		$bizhong = "account_xianjinbi";
	}elseif($type == 'xiaofeibi'){
		$bizhong = "account_xiaofeibi";
	}elseif($type == 'aixinbi'){
		$bizhong = "account_aixinbi";
	}elseif($type == 'jifenbi'){
		$bizhong = "account_jifenbi";
	}elseif($type == 'jifen'){
		$bizhong = "account_jifen";
	}
	
	
		
	$sql = "update ".$ecs->table('pc_user')." set $bizhong = ".$new_value." where uid = ".$uid;
	$db->query($sql);
		
	save_user_account_log($uid,$bizhong, $oldvalue,$change_value,$new_value,$note,$_SESSION['admin_id']);
        
        if($type == 'jifenbi'){
            $pc_user = $db->getRow("select * from ".$ecs->table('pc_user')." where uid = $uid");
//            var_dump($pc_user);
            if($pc_user && $pc_user['identity'] == 3 && $changetype == 'add'){ //如果调整的是联盟商家的积分币，则给联盟商家的推荐人1%提成
                
                $tuijianren_uid = $pc_user['tuijianren_user_id'];
//                var_dump($tuijianren_uid);
                pc_set_lianmengshangjia_butie($tuijianren_uid, $value);
            }
        }
	sys_msg('保存成功', 1);
}

function save_user_account_log($uid,$type, $original_value,$change_value,$new_value,$note,$adminid){
	$db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
	
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
		"'".$uid."',".
		"'".$type."',".
		"'".$original_value."',".
		"'".$change_value."',".
		"'".$new_value."',".
		"'".$note."',".
		"'".$adminid."',".
		"'".time()."' ".
	")";
	return $db->query($sql);
	
}



?>