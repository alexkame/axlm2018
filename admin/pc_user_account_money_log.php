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
	$sql = "select * from ".$ecs->table('pc_config')." ";
	$pcconfig = $db->getAll($sql);
	$pc_config_array = array();
	if($pcconfig){
		foreach($pcconfig as $k=>$v){
			$pc_config_array[$v['sname']] = $v['svalue'];
		}
	}
	//var_dump($pc_config_array);
	$uid = isset($_REQUEST['uid'])?$_REQUEST['uid']:14;
	$type = isset($_REQUEST['type'])?$_REQUEST['type']:'';
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
	
	$where = '';
	if($type){
		$where .= " And type = '".$bizhong."' ";
	}
	$sql = "select * from ".$ecs->table('pc_user_account_log')." where uid = $uid $where order by id desc ";
	$lists = $db->getAll($sql);
	if($lists){
		foreach($lists as $k=>&$v){
			$v['type_title'] = $pc_config_array[$v['type']];
			$v['ctime'] = date("Y-m-d H:i:s", $v['ctime']);
		}
	}
	//var_dump($lists);
	
	$smarty->assign("type",$type);
	$smarty->assign("lists",$lists);
	$smarty->assign("uid",$uid);
	$smarty->assign('full_page', 1);
    $smarty->display('pc_user_account_money_log.htm');
}



?>