<?php

/**
 * ECSHOP 管理中心商店设置
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: shop_config.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

/* 代码 */
require(dirname(__FILE__) . '/includes/init.php');
//var_dump($GLOBALS['_CFG']);
$fanxian = unserialize($GLOBALS['_CFG']['fanxian']);
empty($fanxian) && $fanxian = array();
$separate_on = $fanxian['fanxian_open'];

/*------------------------------------------------------ */
//-- 列表编辑 ?act=list_edit
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'default')
{
    /* 检查权限 */
    admin_priv('fanxian_config');
	//var_dump($fanxian);
    $smarty->assign('cfg', $GLOBALS['_CFG']);
	$smarty->assign('fanxian',$fanxian);
    $smarty->display('fanxian_config.htm');
}


/*------------------------------------------------------ */
//-- 提交   ?act=post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'post')
{
    /* 检查权限 */
    admin_priv('fanxian_config');
	
	$fanxian_open = empty($_POST['fanxian_open']) ? 0 : $_POST['fanxian_open'];
	$fanxian_type = empty($_POST['fanxian_type']) ? 'jiecengfanxian' : $_POST['fanxian_type'];
	$day_trade_time_h = empty($_POST['day_trade_time_h'])?'17':$_POST['day_trade_time_h'];
	$day_trade_time_m = empty($_POST['day_trade_time_m'])?'00':$_POST['day_trade_time_m'];
	$fanxian_bili = intval(empty($_POST['fanxian_bili'])?'10':$_POST['fanxian_bili']);
	$fanxian_money_limit = intval(empty($_POST['fanxian_money_limit'])?'365':$_POST['fanxian_money_limit']);
	$renminbi = intval(empty($_POST['renminbi'])?'1':$_POST['renminbi']);
	$point = intval(empty($_POST['point'])?'1':$_POST['point']);
	
	$config = array(
		"fanxian_open"=>$fanxian_open,
		"fanxian_type"=>$fanxian_type,
		"day_trade_time_h"=>$day_trade_time_h,
		"day_trade_time_m"=>$day_trade_time_m,
		"fanxian_bili"=>intval($fanxian_bili),
		"fanxian_money_limit"=>intval($fanxian_money_limit),
		"renminbi"=>$renminbi,
		"point"=>$point,
	);
	put_fanxian($config);
	
	//$smarty->display('fanxian_config.htm');
	sys_msg($_LANG['save_success'], 0);
}


function put_fanxian($config)
{
    $temp = serialize($config);
    $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') .
           "SET  value = '$temp' " .
           "WHERE code = 'fanxian'";
    
    //echo $sql;
    $GLOBALS['db']->query($sql);
    clear_all_files();
}

?>