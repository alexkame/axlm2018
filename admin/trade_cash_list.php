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

admin_priv('fenhong');
$timestamp = time();

$fanxian = unserialize($GLOBALS['_CFG']['fanxian']);

$separate_on = $fanxian['fanxian_open'];

if ($_REQUEST['act'] == 'list')
{
	
    $logdb = get_trade_cash_list(); 

    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['fenxiao_list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('list',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    
    assign_query_info();
    $smarty->display('trade_cash_list.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_trade_cash_list(); 
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('trade_cash_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}elseif($_REQUEST['act'] == 'del'){
	$id = isset($_REQUEST['id'])?$_REQUEST['id']:0;
	if($id){
		
		$sql = "delete from ".$GLOBALS['ecs']->table('cash_log')." where id =$id limit 1";
		$GLOBALS['db']->query($sql);
		
		
        $link[] = array('href' => 'trade_cash_list.php?act=list', 'text' => '删除成功');
		sys_msg('删除成功', 0, $link);
	}
	$link[] = array('href' => 'trade_cash_list.php?act=list', 'text' => '缺少参数');
	sys_msg('删除失败', 0, $link);
}

/** added by Ran **/
function get_trade_cash_list()
{
	$today_hours = $fanxian['day_trade_time_h']?$fanxian['day_trade_time_h']:'17';
	$today_minus = $fanxian['day_trade_time_m']?$fanxian['day_trade_time_m']:'00';

	$time = date("Y-m-d",strtotime("-1 day"));
	
	$time = strtotime($time." ".$today_hours.":".$today_minus);
	
    $sqladd = '';
    $sql = "SELECT COUNT(id) FROM " . $GLOBALS['ecs']->table('cash_log') . " cl ".
                    " WHERE cl.id > 0  $sqladd";
     
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    
	$sql = "SELECT * " .
                    " FROM " . $GLOBALS['ecs']->table('cash_log') . " cl".
                    " WHERE cl.id > 0  $sqladd" .
                    " ORDER BY cl.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";
	printsqls($sql,'trade cash list sql');
    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
		if($rt['ctime'] >= $time){
			$rt['exec'] = 1;
		}else{
			$rt['exec'] = 0;
		}
		
		$rt['ctime'] = date("Y-m-d H:i",$rt['ctime']);
		$rt['money'] = price_format($rt['money']);
		
        $logdb[] = $rt;
    }
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
?>