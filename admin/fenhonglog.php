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

$user_id = isset($_REQUEST['id'])?$_REQUEST['id']:0;
if(!$user_id){ exit;}
$smarty->assign("user_id",$user_id);
/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
//用户列表
if ($_REQUEST['act'] == 'list')
{

	$logdb = get_list($user_id);//var_dump($logdb);
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '分红记录');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

   // assign_query_info();
    $smarty->display('fenhonglog.htm');
	
}

/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_list($user_id);
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('fenhonglog.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}

function get_list($user_id)
{

    $config_data = unserialize($GLOBALS['_CFG']['fenxiao']);
    empty($config_data) && $config_data = array();
  
    $sqladd = ' and ue.user_id = '.$user_id;
  
    if(!empty($config_data['on']))
    {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_bonus_log') . " ue ".
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
            $sql = "SELECT u.user_name, ue.id, ue.bonus_id, ue.user_id, ue.user_bonus_level, ue.bonus_money, ue.status, ue.ctime " .
                    " FROM " . $GLOBALS['ecs']->table('user_bonus_log') . " ue ".
					" left join ".$GLOBALS['ecs']->table('users')." u on ue.user_id = u.user_id ".
                    " WHERE ue.user_id > 0  $sqladd" .
                    " ORDER BY ue.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

          
    }
   
    //echo $sql;


    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
			$rt['user_bonus_level'] = $rt['user_bonus_level']?$rt['user_bonus_level']."%":"";
			$rt['bonus_money_format'] = price_format($rt['bonus_money']);
			$rt['ctime'] = date("Y-m-d", $rt['ctime']);
			
			$logdb[] = $rt;
    }
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>