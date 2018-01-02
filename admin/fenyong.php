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
//佣金记录
if ($_REQUEST['act'] == 'list')
{
   $logdb = get_fenxiao_expend_user_list();
	
	
	$smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '佣金管理');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

	
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"fenxiao_list.php?act=fenxiao_expend_user_list"));
    }
	$smarty->display('fenyong.htm');
	
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_fenxiao_expend_user_list();
	
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('fenyong.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
else if($_REQUEST['act'] == "detail"){
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
}else if($_REQUEST['act'] == "push_expend_user"){//确定分佣
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){ 
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenxiao_list.php?act=fenxiao_expend_user_list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
	push_expend_user($id);
	$links[] = array('text' => "操作成功", 'href' => 'fenxiao_list.php?act=fenxiao_expend_user_list');
	sys_msg('操作成功', 0 ,$links);
}else if($_REQUEST['act'] == "cancel_expend_user"){//取消分佣
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){ 
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenxiao_list.php?act=fenxiao_expend_user_list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	cancel_expend_user($id);
	$links[] = array('text' => "操作成功", 'href' => 'fenxiao_list.php?act=fenxiao_expend_user_list');
	sys_msg('操作成功', 0 ,$links);
}

/*
    取消分成，不再能对该订单进行分成
*/
elseif ($_REQUEST['act'] == 'del')
{
    $oid = intval($_REQUEST['oid']);
    $stat = $db->getOne("SELECT is_separate FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$oid'");
    if (empty($stat))
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
               " SET is_separate = 2" .
               " WHERE order_id = '$oid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}

/** added by Ran **/
function get_fenxiao_list()
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

            /*
                SQL解释：

                列出同时满足以下条件的订单分成情况：
                1、有效订单o.user_id > 0
                2、满足以下情况之一：
                    a.有订单推荐上线的未分成订单 o.parent_id > 0 AND o.is_separate = 0
                    b.已分成订单 o.is_separate > 0

            */
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

function get_fenxiao_expend_user_list($status=1)
{

    $config_data = unserialize($GLOBALS['_CFG']['fenxiao']);
    empty($config_data) && $config_data = array();
	
    $sqladd = '';
	if($status == 0){
		$sqladd .= " AND status = 0 ";
	}
  
    if(!empty($config_data['on']))
    {
       
            //推荐订单分成
            $sql = "SELECT COUNT(ue.id) FROM " . $GLOBALS['ecs']->table('user_expend_log') . " ue ".
					" left join ".$GLOBALS['ecs']->table('order_info')." o on ue.order_id = o.order_id ".
                    " WHERE ue.user_id > 0  $sqladd";
        
    }

    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if(!empty($config_data['on']))
    {
      
            //推荐订单分成
            $sql = "SELECT ue.id,ue.user_id, ue.order_id, ue.expend_level, ue.expend_user_id, ue.expend_bili,ue.order_amount, ue.expend_amount,ue.status, ue.ctime,o.order_sn " .
                    " FROM " . $GLOBALS['ecs']->table('user_expend_log') . " ue".
					" left join ".$GLOBALS['ecs']->table('order_info')." o on ue.order_id = o.order_id ".
                    " WHERE ue.user_id > 0 $sqladd" .
                    " ORDER BY ue.status,ue.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

    }
   
    //echo $sql;


    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
			$rt['ctime'] = date("Y-m-d H:i", $rt['ctime']);
			$rt['expend_bili'] = $rt['expend_bili']?"￥".$rt['expend_bili']."":"";
			//$rt['order_amount'] = price_format($rt['order_amount']);
			$rt['expend_amount'] = price_format($rt['expend_amount']);
			if($rt['status'] == 1){
				$rt['status_format'] = '已分佣';
			}else if($rt['status'] == 4){
				$rt['status_format'] = '已取消';
			}else if($rt['status'] == 0){
				$rt['status_format'] = '未审核';
			}
			
        $logdb[] = $rt;
    }
	//var_dump($logdb);
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

function push_expend_user($id){
	$id = intval($id);
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
	$id = intval($id);
	$sql = "update ".$GLOBALS['ecs']->table('user_expend_log')." set status = 4 where id = $id ";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	return 1;
}
?>