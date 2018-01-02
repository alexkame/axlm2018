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
	$user_id = $_SESSION['user_id'];
	
	/* 检查权限 */
	admin_priv('surplus_manage');
        
	$sql = "SELECT rank_id, rank_name, min_points FROM " . $ecs->table('user_rank') . " ORDER BY min_points ASC ";
	$rs = $db->query($sql);
	
	$ranks = array();
	while($row = $db->FetchRow($rs))
	{
		$ranks[$row['rank_id']] = $row['rank_name'];
	}
	
	$smarty->assign('user_ranks', $ranks);
	$smarty->assign('ur_here', $_LANG['03_users_list']);
	$smarty->assign('action_link', array(
		'text' => $_LANG['04_users_add'],'href' => 'users.php?act=add'
	));
	
	$user_list = user_list();
	//var_dump($user_list['user_list']);
	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');
	
	assign_query_info();
        $smarty->display('pc_users.htm');
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
	$user_id = $_SESSION['user_id'];
	
	$user_list = user_list();
	
	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	
	$sort_flag = sort_flag($user_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
	make_json_result($smarty->fetch('pc_users.htm'), '', array(
		'filter' => $user_list['filter'],'page_count' => $user_list['page_count']
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
function user_list ()
{
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		{
			$filter['keywords'] = json_str_iconv($filter['keywords']);
		}
		$filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
		$filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
		$filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);
		
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'user_id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
		
		//$ex_where = " WHERE 1 AND is_back_point = '1' ";
		$ex_where = " WHERE 1  ";
		if($filter['keywords'])
		{
			$ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' or email like  '%" . mysql_like_quote($filter['keywords']) . "%' or mobile_phone like  '%" . mysql_like_quote($filter['keywords']) . "%' ";
		}
		
		
		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . " u LEFT JOIN ".$GLOBALS['ecs']->table('pc_user')." pu ON pu.uid = u.user_id ".$ex_where);
		
		/* 分页大小 */
		$filter = page_and_size($filter);
		/* 代码增加2014-12-23 by www.cfweb2015.com _star */
	
		$sql = "SELECT u.user_id, u.user_name, u.email, u.mobile_phone, u.status, u.reg_time,pu.*  ".
                " FROM " . $GLOBALS['ecs']->table('users') . " u ". 
				" LEFT JOIN ".$GLOBALS['ecs']->table('pc_user')." pu ON pu.uid = u.user_id ".
				$ex_where . " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		//echo $sql;
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}
	//echo $sql;
	$user_list = $GLOBALS['db']->getAll($sql);
	//var_dump($user_list);
	$sql = "select * from " . $GLOBALS['ecs']->table('user_rank') . " ";
	$rank_list = $GLOBALS['db']->getAll($sql);
	
        //等级，初级志愿者，高级志愿者
        $level_sql = "select id,level_name from " . $GLOBALS['ecs']->table('pc_user_level') . " ";
	$level_list = $GLOBALS['db']->getAll($level_sql);
        $level_data = array();
        if($level_list){
            foreach($level_list as $k=>$v){
                $level_data[$v['id']] = $v['level_name'];
            }
        }
        //var_dump($level_data);
        //角色 主任，经理，总监
        $role_sql = "select id,role_name from " . $GLOBALS['ecs']->table('pc_user_role') . " ";
	$role_list = $GLOBALS['db']->getAll($role_sql);
        $role_data = array();
        if($role_list){
            $role_data[0] = "普通";
            foreach($role_list as $k=>$v){
                $role_data[$v['id']] = $v['role_name'];
            }
        }
        
        //身份，普通，会员，联盟商家，服务中心
        $identity_sql = "select id,identity_name from " . $GLOBALS['ecs']->table('pc_user_identity') . " ";
	$identity_list = $GLOBALS['db']->getAll($identity_sql);
        $identity_data = array();
        if($identity_list){
            $identity_data[0] = "无";
            foreach($identity_list as $k=>$v){
                $identity_data[$v['id']] = $v['identity_name'];
            }
        }
        
        $status_data = array(
            0=>'等待审核',
            1=>'审核通过',
            2=>'审核中',
            3=>'审核不通过',
        );
	$count = count($user_list);
	for($i = 0; $i < $count; $i ++)
	{
            $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['reg_time']);
            $user_list[$i]['level_title'] = $level_data[$user_list[$i]['level']];
            $user_list[$i]['role_title'] = $role_data[$user_list[$i]['role']];
            $user_list[$i]['identity_title'] = $identity_data[$user_list[$i]['identity']];
            if($user_list[$i]['leftright'] == 'left'){
                $user_list[$i]['leftright_title'] = "左区";
            }elseif($user_list[$i]['leftright'] == 'right'){
                $user_list[$i]['leftright_title'] = "右区";
            }else{
                $user_list[$i]['leftright_title'] = "";
            }
            $user_list[$i]['status_title'] = $status_data[$user_list[$i]['status']]; 
	}
	
	$arr = array(
		'user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);
	
	return $arr;
}

function action_abled(){
	$uid = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if($uid){
		$sql = "update ".$GLOBALS['ecs']->table('users')." set is_disabled_point = 0 where user_id = $uid limit 1";
		$GLOBALS['db']->query($sql);
	}

	$links[0]['text'] = "操作成功";
	$links[0]['href'] = 'fanxian_user.php?act=list';
	sys_msg($_LANG['update_success'], 0, $links);
}
function action_disabled(){
	$uid = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if($uid){
		$sql = "update ".$GLOBALS['ecs']->table('users')." set is_disabled_point = 1 where user_id = $uid limit 1";
		$GLOBALS['db']->query($sql);
	}
	$links[0]['text'] = "操作成功";
	$links[0]['href'] = 'fanxian_user.php?act=list';
	sys_msg($_LANG['update_success'], 0, $links);
}
?>