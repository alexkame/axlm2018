<?php
/**
 * ECSHOP 会员中心
 * ============================================================================
 * * 版权所有 和禹网络科技 藏锋科技有限公司。
 * 网站地址: http://www.cfweb2015.com/;
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: user.php 17217 2011-01-19 06:29:08Z derek $
 */
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');


/*end*/
$suser_code = isset($_SESSION['suser_code'])?$_SESSION['suser_code']:0;

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
if(!$suser_code){
	$action = 'login';
}

$smarty->assign("suser_code",$suser_code);

/* 路由 */

$function_name = 'action_' . $action;
//echo $function_name;

if($function_name == "action_default"){

	$smarty->display('shouyinyuan.dwt');
}else if($function_name == 'action_list'){
	$sql = "select * from ".$GLOBALS['ecs']->table('cash_log')." where shouyinyuan = '".$suser_code."' AND ctime > ".strtotime("-1 day");
	$list = $GLOBALS['db']->getAll($sql);
	if($list){
		foreach($list as $k=>&$v){
			$v['ctime'] = date("Y-m-d H:i",$v['ctime']);
			$v['money'] = price_format($v['money']);
		}
	}
	$smarty->assign("list", $list);
	$smarty->display('shouyinyuan_list.dwt');
	
}else if($function_name == 'action_push'){

	$money = isset($_REQUEST['money'])?$_REQUEST['money']:0;
	$huiyuanka = trim(isset($_REQUEST['huiyuanka'])?$_REQUEST['huiyuanka']:0);
	$shouyinyuan = trim(isset($_REQUEST['shouyinyuan'])?$_REQUEST['shouyinyuan']:0);
if(!is_numeric($money) || !floatval($money) || floatval($money) <0 ){
		show_message('请填如正确的消费金额', '返回继续收银', 'shouyinyuan.php?act=default');
	}
	$money = floatval($money);
	if($money && $huiyuanka && $shouyinyuan){
		$check_sql = "select ctime from ".$GLOBALS['ecs']->table('cash_log')." where shouyinyuan = '".$shouyinyuan."' order by id desc ";
		//echo $check_sql;
		$lasttime = $GLOBALS['db']->getOne($check_sql);
		//echo $lasttime."<br>";
		//exit;
		
		if($lasttime && time()-$lasttime < 5){
			show_message('操作间隔太短，请勿频繁操作', '返回', 'shouyinyuan.php?act=default');
		}else{
			
			$usql = "select user_id from ".$GLOBALS['ecs']->table('users')." where huiyuanka = '".$huiyuanka."'";
			
			$user_id = $GLOBALS['db']->getOne($usql);
			
			$sql = "insert into ".$GLOBALS['ecs']->table('cash_log')."(money, user_id, huiyuanka, shouyinyuan,ctime) values("
					."'".$money."',"
					."'".$user_id."',"
					."'".$huiyuanka."',"
					."'".$shouyinyuan."',"
					."'".time()."'"
					.")";
			//echo $sql;	
			$GLOBALS['db']->query($sql);
			
			show_message('提交成功', '返回继续收银', 'shouyinyuan.php?act=default');
		}
		
	}else{
		show_message('数据填写有误', '返回继续收银', 'shouyinyuan.php?act=default');
	}
}else if($function_name == 'action_login'){

		if(isset($_POST['shouyinyuan'])){
			$_POST['username'] = isset($_POST['shouyinyuan']) ? trim($_POST['shouyinyuan']) : '';
			$_POST['password'] = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
		
			if(isset($_POST['username']) && $_POST['username'] == ''){
				show_message('账号不能为空，非法登陆', '返回', 'shouyinyuan.php?act=login');
			}
			if(isset($_POST['password']) && $_POST['password'] == ''){
				show_message('密码不能为空，非法登陆', '返回', 'shouyinyuan.php?act=login');
			}
		
			$sql="SELECT `ec_salt` FROM ". $ecs->table('admin_user') ."WHERE user_name = '" . $_POST['username']."'";
			$ec_salt =$db->getOne($sql);
			if(!empty($ec_salt))
			{
				 /* 检查密码是否正确 */
				 $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt".
					" FROM " . $ecs->table('admin_user') .
					" WHERE user_name = '" . $_POST['username']. "' AND password = '" . md5(md5($_POST['password']).$ec_salt) . "'";
			}
			else
			{
				 /* 检查密码是否正确 */
				 $sql = "SELECT user_id, user_name, password, last_login, action_list, last_login,suppliers_id,ec_salt".
					" FROM " . $ecs->table('admin_user') .
					" WHERE user_name = '" . $_POST['username']. "' AND password = '" . md5($_POST['password']) . "'";
			}
			
			$row = $db->getRow($sql);
			if ($row)
			{
				//登陆成功
				$_SESSION['suser_code'] = $_POST['username'];
				show_message('登陆成功', '返回收银', 'shouyinyuan.php?act=default');
			}else{
				
				show_message('账号不正确，非法登陆', '返回', 'shouyinyuan.php?act=login');
			}
		}
		
		$smarty->display('shouyinyuan_login.dwt');
}
?>
