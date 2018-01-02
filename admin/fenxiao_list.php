<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * * 版权所有 和禹网络科技 藏锋科技有限公司。
 * 网站地址: http://www.cfweb2015.com/；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: affiliate_ck.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

admin_priv('fenxiao_list');
$timestamp = time();

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
empty($affiliate) && $affiliate = array();
$separate_on = $fenxiao['on'];

/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
//用户列表
if ($_REQUEST['act'] == 'list')
{
	$logdb = get_list();
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '会员统计');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

   // assign_query_info();
    $smarty->display('fenxiao_list.htm');
	
}

/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_list();
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('fenxiao_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
else if($_REQUEST['act'] == "detail"){
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
	$detail = get_detail($id);
	$userlist = array();
	if($detail['expend_user_amount']){
		$userlist = get_user_list($detail['user_id']);
	}	
	$smarty->assign("info",$detail);
	$smarty->assign("list",$userlist);
	$smarty->assign("listtotal"," 【总共：".count($userlist)."人】");
	$smarty->display('fenxiao_detail.htm');
}else if($_REQUEST['act'] == "push_expend_user"){//确定分佣
	$id = isset($_REQUEST['id'])?$_REQUEST['id']:0;
	if(!$id){ 
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenyong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
	push_expend_user($id);
	$links[] = array('text' => "操作成功", 'href' => 'fenyong.php?act=list');
	sys_msg('操作成功', 0 ,$links);
}else if($_REQUEST['act'] == "cancel_expend_user"){//取消分佣
	$id = isset($_REQUEST['id'])?$_REQUEST['id']:0;
	if(!$id){ 
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenyong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	cancel_expend_user($id);
	$links[] = array('text' => "操作成功", 'href' => 'fenyong.php?act=list');
	sys_msg('操作成功', 0 ,$links);
}
/** added by Ran **/
function get_user_list($uid){
	$sql = "SELECT ue.id, u.user_name, ue.user_id, ue.expend_pid, ue.expend_code, ue.is_valid_user, ue.expend_ctime, ue.buyctime, ue.is_bonus, ".
                    "ue.bonus_proportional, ue.expend_user_amount,ue.bonus_amount, ue.shop_amount, ue.expend_amount, ue.expend_amount_cash, ue.expend_amount_rest, ue.expend_amount_process, ue.is_expend_valid ,u.user_money" .
                    " FROM " . $GLOBALS['ecs']->table('user_expend') . " ue ".
					" left join ".$GLOBALS['ecs']->table('users')." u on ue.user_id = u.user_id ".
                    " WHERE ue.user_id > 0  and ue.expend_pid = $uid";
	$list = $GLOBALS['db']->getAll($sql);
	return $list;
}
function get_detail($id){
	//推荐订单分成
    $sql = "SELECT ue.id, u.user_name, ue.user_id, ue.expend_pid, ue.expend_code, ue.is_valid_user, ue.expend_ctime, ue.buyctime, ue.is_bonus, ".
                    "ue.bonus_proportional, ue.expend_user_amount,ue.bonus_amount, ue.shop_amount, ue.expend_amount, ue.expend_amount_cash, ue.expend_amount_rest, ue.expend_amount_process, ue.is_expend_valid ,u.user_money" .
                    " FROM " . $GLOBALS['ecs']->table('user_expend') . " ue ".
					" left join ".$GLOBALS['ecs']->table('users')." u on ue.user_id = u.user_id ".
                    " WHERE ue.user_id > 0  and ue.id = $id";
	$rt = $GLOBALS['db']->getRow($sql);
	if($rt){
		$rt['bonus_proportional'] = $rt['bonus_proportional']?$rt['bonus_proportional']."%":"";
		$rt['expend_amount'] = price_format($rt['expend_amount']);
		$rt['bonus_amount'] = price_format($rt['bonus_amount']);
		$rt['expend_amount_cash'] = price_format($rt['expend_amount_cash']);
		$rt['user_money'] = price_format($rt['user_money']);
	}
	
	return is_array($rt)?$rt:array();
}
function get_list()
{

    $config_data = unserialize($GLOBALS['_CFG']['fenxiao']);
    empty($config_data) && $config_data = array();
  
    $sqladd = '';
  
    if(!empty($config_data['on']))
    {
       
            //推荐订单分成
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_expend') . " ue ".
				" left join ".$GLOBALS['ecs']->table('users')." u on ue.user_id = u.user_id ".
                    " WHERE ue.user_id > 0  $sqladd";
        
    }

    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if(!empty($config_data['on']))
    {
      
            //推荐订单分成
            $sql = "SELECT ue.id, u.user_name, ue.user_id, ue.expend_pid, ue.expend_code, ue.is_valid_user, ue.expend_ctime, ue.buyctime, ue.is_bonus, ".
                    "ue.bonus_proportional, ue.expend_user_amount,ue.bonus_amount, ue.shop_amount, ue.expend_amount, ue.expend_amount_cash, ue.expend_amount_rest, ue.expend_amount_process, ue.is_expend_valid ,u.user_money" .
                    " FROM " . $GLOBALS['ecs']->table('user_expend') . " ue ".
					" left join ".$GLOBALS['ecs']->table('users')." u on ue.user_id = u.user_id ".
                    " WHERE ue.user_id > 0  $sqladd" .
                    " ORDER BY ue.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

          
    }
   
    //echo $sql;


    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
			$rt['bonus_proportional'] = $rt['bonus_proportional']?$rt['bonus_proportional']."%":"";
			$rt['expend_amount'] = price_format($rt['expend_amount']);
			$rt['bonus_amount'] = price_format($rt['bonus_amount']);
			$rt['expend_amount_cash'] = price_format($rt['expend_amount_cash']);
			$rt['user_money'] = price_format($rt['user_money']);
        $logdb[] = $rt;
    }
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

function push_expend_user($id){
	
	$sql = "select id, user_id, order_id, expend_user_id, order_amount, expend_amount, status ,ctime from ".$GLOBALS['ecs']->table('user_expend_log')." where id = $id ";
	//echo $sql."<br>";
	$info = $GLOBALS['db']->getRow($sql);
	if(!$info){ return 0;}
	$userid = $info['expend_user_id'];
	$expend_amount = $info['expend_amount'];
	if($info['status'] == 1){ return 0;  }
	
	$sql = "update ".$GLOBALS['ecs']->table('user_expend_log')." set status = 1 where id = $id ";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	
	//到账
	//累计到用户资金， 用户分佣金额
	set_fx_cash($userid,"+","expend_amount",$expend_amount);//累计用户分佣金额
			
	//佣金到账后，增加用户资金
	log_account_change($userid, $expend_amount, 0, 0, 0, '佣金到账，佣金id :'.$id, 3);
	return 1;
}

function cancel_expend_user($id){
	$sql = "update ".$GLOBALS['ecs']->table('user_expend_log')." set status = 4 where id = $id ";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	return 1;
}


?>