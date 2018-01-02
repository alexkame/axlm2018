<?php
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');
/* 代码增加2014-12-23 by www.cfweb2015.com _star */
include_once (ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
/* 代码增加2014-12-23 by www.cfweb2015.com _end */

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_list";
}

call_user_func($function_name);

/* 路由 */

/* ------------------------------------------------------ */
// -- 用户帐号列表
/* ------------------------------------------------------ */
function action_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = isset($_REQUEST['user_id'])?$_REQUEST['user_id']:0;
	
	/* 检查权限 */
	admin_priv('users_manage');
	//echo $user_id;
	$list = lists($user_id);
	
	$smarty->assign('user_id', $user_id);
	$smarty->assign('lists', $list['lists']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');
	
	assign_query_info();
	$smarty->display('pc_user_account_log.htm');
}

/* ------------------------------------------------------ */
// -- ajax返回用户列表
/* ------------------------------------------------------ */
function action_query ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = isset($_REQUEST['user_id'])?$_REQUEST['user_id']:0;
	if(!$user_id){ return 0; }
	
	$list = lists($user_id);
	
	$smarty->assign('lists', $list['lists']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	
	$sort_flag = sort_flag($list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	$smarty->assign('user_id', $user_id);
	
	make_json_result($smarty->fetch('pc_user_account_log.htm'), '', array(
		'filter' => $list['filter'],'page_count' => $list['page_count']
	));
}


/**
 * 返回用户列表数据
 *
 * @access public
 * @param        	
 *
 * @return void
 */
function lists($uid)
{
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		//$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		//if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		//{
		//	$filter['keywords'] = json_str_iconv($filter['keywords']);
		//}
		//
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		//$ex_where = " WHERE 1 AND is_back_point = '1' ";
		$ex_where = " WHERE 1 AND uid = $uid ";
		//if($filter['keywords'])
		//{
		//	$ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or email like  '%" . mysql_like_quote($filter['keywords']) . "%' or mobile_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
		//}
		
		
		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('pc_user_change_log') . " ".$ex_where);
		
		/* 分页大小 */
		$filter = page_and_size($filter);
		/* 代码增加2014-12-23 by www.cfweb2015.com _star */
		// $sql = "SELECT user_id, user_name, email, is_validated,
		// validated,status,user_money, frozen_money, rank_points, pay_points,
		// reg_time ".
		$sql = "SELECT *  ".
                " FROM " . $GLOBALS['ecs']->table('pc_user_change_log') . " ". 
				$ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}
	//echo $sql;
	$lists = $GLOBALS['db']->getAll($sql);
	
	$count = count($lists);
	
	$role_array = array();
	$role = $db->getAll("select id,role_name as name from ".$ecs->table('pc_user_role'));
	if($role){
		foreach($role as $k=>$v){
			$role_array[$v['id']] = $v['name'];
		}
	}
	//var_dump($role_array);
	$level_array = array();
	$level = $db->getAll("select id,level_name as name from ".$ecs->table('pc_user_level'));
	if($level){
		foreach($level as $k=>$v){
			$level_array[$v['id']] = $v['name'];
		}
	}
	
	$identity_array = array();
	$identity = $db->getAll("select id,identity_name as name from ".$ecs->table('pc_user_identity'));
	if($identity){
		foreach($identity as $k=>$v){
			$identity_array[$v['id']] = $v['name'];
		}
	}
	
	for($i = 0; $i < $count; $i ++)
	{
		
		if($lists[$i]['name'] == 'role'){
			$lists[$i]['original_value'] = $role_array[$lists[$i]['original_value']];
			$lists[$i]['new_value'] = $role_array[$lists[$i]['new_value']];
		}elseif($lists[$i]['name'] == 'level'){
			$lists[$i]['original_value'] = $level_array[$lists[$i]['original_value']];
			$lists[$i]['new_value'] = $level_array[$lists[$i]['new_value']];
		}elseif($lists[$i]['name'] == 'identity'){
			$lists[$i]['original_value'] = $identity_array[$lists[$i]['original_value']];
			$lists[$i]['new_value'] = $identity_array[$lists[$i]['new_value']];
		}
		
		$lists[$i]['ctime'] = local_date("Y-m-d H:i:s", $lists[$i]['ctime']);
		
	}
	
	$arr = array(
		'lists' => $lists, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);
	
	return $arr;
}
?>