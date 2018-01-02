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
    admin_priv('pc_config');
	$pcconfig = array();
	
	$sql = "select * from ".$ecs->table('pc_config')." where display = 1";
	$pcconfig = $db->getAll($sql);
	
	$smarty->assign('pcconfig',$pcconfig);
    $smarty->display('pc_config.htm');
}


/*------------------------------------------------------ */
//-- 提交   ?act=post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'post')
{
    /* 检查权限 */
    admin_priv('pc_config');
	
	$fanxian_open = empty($_POST['fanxian_open']) ? 0 : $_POST['fanxian_open'];
	

	sys_msg($_LANG['save_success'], 0);
}



?>