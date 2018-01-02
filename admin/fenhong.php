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

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
empty($affiliate) && $affiliate = array();
$separate_on = $fenxiao['on'];

/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    	
	$lasttotal = get_last_fenhong(); //统计上次分红之后到现在的订单
    $logdb = get_fenhong_list(); //已分红列表
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['fenxiao_list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('lasttotal',        $lasttotal);
    $smarty->assign('list',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    
    assign_query_info();
    $smarty->display('fenhong_list.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $lasttotal = get_last_fenhong(); //统计上次分红之后到现在的订单
    $logdb = get_fenhong_list(); //已分红列表
	$smarty->assign('lasttotal',        $lasttotal);
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('fenhong_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
else if($_REQUEST['act'] == 'create'){
	$lasttotal = get_last_fenhong();
	
	create_fenhong($lasttotal);
	
	$links[] = array('text' => "创建成功", 'href' => 'fenhong.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}else if($_REQUEST['act'] == "detail"){
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	$info = get_fenhong_info($id);
	//var_dump($info);
	$smarty->assign("info",$info);
	 $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['fenxiao_list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('lasttotal',        $lasttotal);
    $smarty->assign('list',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
	$smarty->display("fenhong_detail.htm");
	
}else if($_REQUEST['act'] == 'push'){
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
	push_fenxiao($id);
	$links[] = array('text' => "操作成功", 'href' => 'fenhong.php?act=list');
	sys_msg('操作成功', 0 ,$links);
}

/*
    取消分成，不再能对该订单进行分成
*/
elseif ($_REQUEST['act'] == 'del')
{
    $oid = (int)$_REQUEST['oid'];
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
/*
    撤销某次分成，将已分成的收回来
*/
elseif ($_REQUEST['act'] == 'rollback')
{
    $logid = (int)$_REQUEST['logid'];
    $stat = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') . " WHERE log_id = '$logid'");
    if (!empty($stat))
    {
        if($stat['separate_type'] == 1)
        {
            //推荐订单分成
            $flag = -2;
        }
        else
        {
            //推荐注册分成
            $flag = -1;
        }
        log_account_change($stat['user_id'], -$stat['money'], 0, -$stat['point'], 0, $_LANG['loginfo']['cancel']);
        $sql = "UPDATE " . $GLOBALS['ecs']->table('affiliate_log') .
               " SET separate_type = '$flag'" .
               " WHERE log_id = '$logid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
    分成
*/
elseif ($_REQUEST['act'] == 'separate')
{
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    empty($affiliate) && $affiliate = array();

    $separate_by = $affiliate['config']['separate_by'];			

    $oid = (int)$_REQUEST['oid'];
//代码增加--cb--增加u.parent_id
    $row = $db->getRow("SELECT o.order_sn,u.parent_id, o.is_separate, (o.goods_amount - o.discount) AS goods_amount, o.user_id FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id".
            " WHERE order_id = '$oid'");
			
//代码增加--cb--推荐分成
	if($separate_by==0)
	{
		$pid = $row['parent_id'];
	}
	else
	{
		$pid = $db->getOne("SELECT parent_id FROM " . $GLOBALS['ecs']->table('order_info')." WHERE order_id = '$oid'");
	}
	
	$row1=$db->getAll("SELECT order_id,goods_number,goods_price,cost_price,promote_price FROM " . $GLOBALS['ecs']->table('order_goods')." WHERE order_id = '$oid'");
			
	$f_rank = $db->getOne("SELECT is_recomm FROM " . $GLOBALS['ecs']->table('user_rank') . " r" .
                          " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON r.rank_id = u.user_rank".
                        " WHERE u.user_id = '$pid'");
	$user_rank_point = $db->getOne("SELECT rank_points FROM " . $ecs->table('users') . " WHERE user_id = '$pid'");

    $sql = "SELECT is_recomm FROM " . $ecs->table('user_rank') . " WHERE min_points < '$user_rank_point' AND max_points > '$user_rank_point' ORDER BY min_points ASC LIMIT 1";

	$rt  = $db->getOne($sql);

	if( $rt > 0 )
	{
		$f_rank =1;
	}
	else
	{
		$f_rank =0;
	}
				
    $order_sn = $row['order_sn'];

    if (empty($row['is_separate']))
    {  
        $affiliate['config']['level_point_all'] = (float)$affiliate['config']['level_point_all'];
        $affiliate['config']['level_money_all'] = (float)$affiliate['config']['level_money_all'];
        if ($affiliate['config']['level_point_all'])
        {
            $affiliate['config']['level_point_all'] /= 100;
        }
        if ($affiliate['config']['level_money_all'])
        {
            $affiliate['config']['level_money_all'] /= 100;
        }
		//代码增加--cb--推荐分成-start
		if($affiliate['config']['level_money_all']==1)
		{
			for($i=0;$i<count($row1);$i++)
        	  {		  
        	  	if($row1[$i]['promote_price']==$row1[$i]['cost_price'] || $row1[$i]['cost_price']==0 || $f_rank == 0)
        	  	{
        	  		$all_cost_price  = 0;
        	  	}
        	  	else
        	  	{
					$all_cost_price   = $row1[$i]['cost_price']  * $row1[$i]['goods_number'];
        	    }
				$money +=round($all_cost_price,2);
        	  }
		}
		else
		{
			$money = round($affiliate['config']['level_money_all'] * $row['goods_amount'],2);
		}
		//代码增加--cb--推荐分成-end
        $integral = integral_to_give(array('order_id' => $oid, 'extension_code' => ''));
        $point = round($affiliate['config']['level_point_all'] * intval($integral['rank_points']), 0);

        if(empty($separate_by))
        {
            //推荐注册分成
            $num = count($affiliate['item']);
            for ($i=0; $i < $num; $i++)
            {
                $affiliate['item'][$i]['level_point'] = (float)$affiliate['item'][$i]['level_point'];
                $affiliate['item'][$i]['level_money'] = (float)$affiliate['item'][$i]['level_money'];
                if ($affiliate['item'][$i]['level_point'])
                {
                    $affiliate['item'][$i]['level_point'] /= 100;
                }
                if ($affiliate['item'][$i]['level_money'])
                {
                    $affiliate['item'][$i]['level_money'] /= 100;
                }
                $setmoney = round($money * $affiliate['item'][$i]['level_money'], 2);
                $setpoint = round($point * $affiliate['item'][$i]['level_point'], 0);
                $row = $db->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                        " WHERE o.user_id = '$row[user_id]'"
                    );
				//代码增加--cb--推荐分成-start
                $up_uid = $row['user_id'];

                $user_rank_point = $db->getOne("SELECT rank_points FROM " . $ecs->table('users') . " WHERE user_id = '$up_uid'");


                $sql = "SELECT is_recomm FROM " . $ecs->table('user_rank') . " WHERE min_points < '$user_rank_point' AND max_points > '$user_rank_point' ORDER BY min_points ASC LIMIT 1";

                $f_rank  = $db->getOne($sql);



				if(!empty($f_rank))
				{
					if (empty($up_uid) || empty($row['user_name']))
					{
						break;
					}
					else
					{
						$info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, $setpoint);
						log_account_change($up_uid, $setmoney, 0, $setpoint, 0, $info);
						write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $setpoint, $separate_by);
					}
				}
					//代码增加--cb--推荐分成-end
			}
        }
        else
        {
            //推荐订单分成
            $row = $db->getRow("SELECT o.parent_id, u.user_name FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                    " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                    " WHERE o.order_id = '$oid'"
                );
            $up_uid = $row['parent_id'];
            if(!empty($up_uid) && $up_uid > 0)
            {
                $info = sprintf($_LANG['separate_info'], $order_sn, $money, $point);
                log_account_change($up_uid, $money, 0, $point, 0, $info);
                write_affiliate_log($oid, $up_uid, $row['user_name'], $money, $point, $separate_by);
            }
            else
            {
                $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
                sys_msg($_LANG['edit_fail'], 1 ,$links);
            }
        }
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
               " SET is_separate = 1" .
               " WHERE order_id = '$oid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
function get_affiliate_ck()
{

    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    empty($affiliate) && $affiliate = array();
    $separate_by = $affiliate['config']['separate_by'];

    $sqladd = '';
    if (isset($_REQUEST['status']))
    {
        $sqladd = ' AND o.is_separate = ' . (int)$_REQUEST['status'];
        $filter['status'] = (int)$_REQUEST['status'];
    }
    if (isset($_REQUEST['order_sn']))
    {
        $sqladd = ' AND o.order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }
    if (isset($_GET['auid']))
    {
        $sqladd = ' AND a.user_id=' . $_GET['auid'];
    }

    if(!empty($affiliate['on']))
    {
        if(empty($separate_by))
        {
            //推荐注册分成
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd";
        }
        else
        {
            //推荐订单分成
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd";
        }
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND o.is_separate > 0 $sqladd";
    }


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if(!empty($affiliate['on']))
    {
        if(empty($separate_by))
        {
            //推荐注册分成
            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd".
                    " ORDER BY order_id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

            /*
                SQL解释：

                列出同时满足以下条件的订单分成情况：
                1、有效订单o.user_id > 0
                2、满足以下情况之一：
                    a.有用户注册上线的未分成订单 u.parent_id > 0 AND o.is_separate = 0
                    b.已分成订单 o.is_separate > 0

            */
        }
        else
        {
            //推荐订单分成
            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd" .
                    " ORDER BY order_id DESC" .
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
    }
    else
    {
        //关闭
        $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND o.is_separate > 0 $sqladd" .
                " ORDER BY order_id DESC" .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";
    }


    $query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        if(empty($separate_by) && $rt['up'] > 0)
        {
            //按推荐注册分成
            $rt['separate_able'] = 1;
        }
        elseif(!empty($separate_by) && $rt['parent_id'] > 0)
        {
            //按推荐订单分成
            $rt['separate_able'] = 1;
        }
        if(!empty($rt['suid']))
        {
            //在affiliate_log有记录
            $rt['info'] = sprintf($GLOBALS['_LANG']['separate_info2'], $rt['suid'], $rt['auser'], $rt['money'], $rt['point']);
            if($rt['separate_type'] == -1 || $rt['separate_type'] == -2)
            {
                //已被撤销
                $rt['is_separate'] = 3;
                $rt['info'] = "<s>" . $rt['info'] . "</s>";
            }
        }
        $logdb[] = $rt;
    }
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
function write_affiliate_log($oid, $uid, $username, $money, $point, $separate_by)
{
    $time = gmtime();
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_log') . "( order_id, user_id, user_name, time, money, point, separate_type)".
                                                              " VALUES ( '$oid', '$uid', '$username', '$time', '$money', '$point', $separate_by)";
    if ($oid)
    {
        $GLOBALS['db']->query($sql);
    }
}

/** added by Ran **/
function get_fenhong_list()
{
	$is_fenhong = true;

    $sqladd = '';
  
    if($is_fenhong)
    {
		
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bonus_total') . " b ".
                    " WHERE b.id > 0  $sqladd";
        
    }
	
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if($is_fenhong)
    {
      
            //推荐订单分成
            $sql = "SELECT id, stime, etime, order_total, money_amount, status,confirmtime" .
                    " FROM " . $GLOBALS['ecs']->table('bonus_total') . " b".
                    " WHERE b.id > 0  $sqladd" .
                    " ORDER BY b.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

            
    }
   
    //echo $sql;


    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
		$rt['stime'] = date("Y-m-d H:i",$rt['stime']);
		$rt['etime'] = date("Y-m-d H:i",$rt['etime']);
		$rt['confirmtime'] = date("Y-m-d H:i",$rt['confirmtime']);
		$rt['status_format'] = $rt['status'] == 1?'已分红':'未分红';
        $logdb[] = $rt;
    }
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

function get_last_fenhong(){
	//$sql = "select id, stime, etime, order_total, money_amount, status from ".$GLOBALS['ecs']->table('bonus_total')." where status = 1 order by id desc";
	$sql = "select id, stime, etime, order_total, money_amount, status from ".$GLOBALS['ecs']->table('bonus_total')." order by id desc";
	
	$record = $GLOBALS['db']->getRow($sql);
	
	if($record){
		$stime = $record['etime'];
	}else{
		$stime = strtotime("2010-01-01");
	}
	return get_total_amount_bytime($stime, time());
}
function get_fenhong_info($bid){
	if(!$bid){
		return false;
	}
	$bid = intval($bid);
	$data = array();
	
	//获得基本信息
	$sql = "select id, stime, etime, order_total, money_amount,status from ".$GLOBALS['ecs']->table('bonus_total')." where id = $bid";
	//echo $sql;
	$baseinfo = $GLOBALS['db']->getRow($sql);
	if(!$baseinfo){ return false;}
	$baseinfo['stime'] = date("Y-m-d H:i",$baseinfo['stime']);
	$baseinfo['etime'] = date("Y-m-d H:i",$baseinfo['etime']);
	$baseinfo['money_amount_format'] = price_format($baseinfo['money_amount']);
	$baseinfo['status_format'] = $baseinfo['status'] == 1?'已分佣':'未分佣';
	$data['baseinfo'] = $baseinfo;
	
	//获得分红单的等级详情
	$sql = "select id, bonus_money_amount, time, level, useramount, status from ".$GLOBALS['ecs']->table('bonus')." where bid = $bid";
	//echo $sql;
	$levelinfo = $GLOBALS['db']->getAll($sql);
	if($levelinfo){
		foreach($levelinfo as $k=>&$v){
			$v['bonus_money_amount'] = price_format($v['bonus_money_amount']);
			$v['time'] = date("Y-m-d H:i",$v['time']);
		}
	}
	$data['leveinfo'] = $levelinfo;
	
	//获得分红单，用户等级，分红金额列表
	$sql = "select id, bonus_id, user_id, user_bonus_level, admin, bonus_money, ctime from ".$GLOBALS['ecs']->table('user_bonus_log')." where bonus_id = $bid";
	$userlist = $GLOBALS['db']->getAll($sql);
	$data['userlist'] = $userlist;
	
	//获得分红单的订单列表
	$sql = "select id, bid, oid from ".$GLOBALS['ecs']->table('bonus_order_total')." where bid = $bid";
	//echo $sql;
	$orderlist = $GLOBALS['db']->getAll($sql);
	$data['orderlist'] = $orderlist;
	
	return $data;
	//echo "<br>================<br>";
	//var_dump($data);
	
}
function get_total_amount_bytime($stime,$etime){
	//已确认收货来判断订单的完成
	if($stime && $etime){
		//按照商品交易额来计算
		$sql = "select sum(goods_amount) total_money,count(order_id) total_order from ".$GLOBALS['ecs']->table('order_info')." where shipping_status = 2 and shipping_time_end >= $stime and shipping_time_end < $etime";
		$info = $GLOBALS['db']->getRow($sql);
		if(!$info){
			$info = array("total_money"=>0, "total_order"=>0);
		}
		
		$sql = "select order_id from ".$GLOBALS['ecs']->table('order_info')." where shipping_status = 2 and shipping_time_end >= $stime and shipping_time_end < $etime";
		$orderlist = $GLOBALS['db']->getAll($sql);
		if($orderlist){
			foreach($orderlist as $k=>$v){
				$info['orderlist'][] = $v['order_id'];
			}
		}
		
		$info['stime_my'] = $stime;
		$info['stime'] = date("Y-m-d H:i", $stime);
		$info['etime_my'] = $etime;
		$info['etime'] = date("Y-m-d H:i", $etime);
		$sql = "select count(id) from ".$GLOBALS['ecs']->table('user_expend')." where is_bonus = 1 and bonus_proportional = ";
		$info['leve1'] = $GLOBALS['db']->getOne($sql."1");
		$info['leve2'] = $GLOBALS['db']->getOne($sql."2");
		$info['leve3'] = $GLOBALS['db']->getOne($sql."5");
		//$info['leve1_money'] = price_format($info['total_money'] * 0.01 * $info['leve1']);
		$info['leve1_money'] = price_format($info['total_money'] * 0.01);
		$info['leve1_my'] = floatval($info['total_money'] * 0.01);
		//$info['leve2_money'] = price_format($info['total_money'] * 0.02 * $info['leve2']);
		$info['leve2_money'] = price_format($info['total_money'] * 0.02);
		$info['leve2_my'] = floatval($info['total_money'] * 0.02);
		//$info['leve3_money'] = price_format($info['total_money'] * 0.05 * $info['leve3']);
		$info['leve3_money'] = price_format($info['total_money'] * 0.05);
		$info['leve3_my'] = floatval($info['total_money'] * 0.05);
		return $info;
	}else{
		return false;
	}
}

function create_fenhong($info){
	
	$orderlist = $info['orderlist'];
	//var_dump($info);
	//exit;
	$sql = "insert into ".$GLOBALS['ecs']->table('bonus_total')." (stime,etime,order_total, money_amount, status,ctime) values(".
		"'".$info['stime_my']."',".
		"'".$info['etime_my']."',".
		"'".$info['total_order']."',".
		"'".$info['total_money']."',".
		"'0',".
		time().")";
	//echo $sql;

	$GLOBALS['db']->query($sql);
	$bid = $GLOBALS['db']->insert_id();
	if(!$bid){ return false;}
	
	//插入分红等级关系表
	//{{{	
		if(!$info['leve1']){
			$info['leve1'] = 0;$info['leve1_my'] = 0;
		}	
		$sql1 = "insert into ".$GLOBALS['ecs']->table('bonus')."(bid,bonus_money_amount, time,level,useramount,status) values($bid, ".$info['leve1_my'].",".time().",1,".$info['leve1'].", 0)";
		//echo $sql;
		$GLOBALS['db']->query($sql1);
		
		if(!$info['leve2']){
			$info['leve2'] = 0;$info['leve2_my'] = 0;
		}	
		$sql2 = "insert into ".$GLOBALS['ecs']->table('bonus')."(bid,bonus_money_amount, time,level,useramount,status) values($bid, ".$info['leve2_my'].",".time().",2,".$info['leve2'].", 0)";
		//echo $sql;
		$GLOBALS['db']->query($sql2);
		
		if(!$info['leve3']){
			$info['leve3'] = 0;$info['leve3_my'] = 0;
		}	
		$sql3 = "insert into ".$GLOBALS['ecs']->table('bonus')."(bid,bonus_money_amount, time,level,useramount,status) values($bid, ".$info['leve3_my'].",".time().",5,".$info['leve3'].", 0)";
		//echo $sql;
		$GLOBALS['db']->query($sql3);
	//}}}
	
	//插入分红订单记录
	//{{{
	$orderstr = "(".implode(",".$bid.",".time()."),(",$orderlist).",".$bid.",".time().")";
	$sql = "insert into ".$GLOBALS['ecs']->table('bonus_order_total')."(oid,bid,ctime) values ".$orderstr;
	//echo $sql;
	$GLOBALS['db']->query($sql);
	//}}}
	

	$admin_id = 1;
	//{{{ 插入用户分红表 
	$sql = "select user_id,user_name, bonus_proportional from ".$GLOBALS['ecs']->table('user_expend')." where is_bonus = 1";
	$userlist = $GLOBALS['db']->getAll($sql);
	if($userlist){
		foreach($userlist as $k=>$v){
			
			$bonus_proportional = floatval($v['bonus_proportional']/100);
			if($bonus_proportional == 0.01){
				$user_post_money = floatval(($info['total_money']*$bonus_proportional)/$info['leve1']);
			}else if($bonus_proportional == 0.02){
				$user_post_money = floatval(($info['total_money']*$bonus_proportional)/$info['leve2']);
			}else if($bonus_proportional == 0.05){
				$user_post_money = floatval(($info['total_money']*$bonus_proportional)/$info['leve3']);
			}
			
			//$bonus_money = moneys_format($info['total_money']*$bonus_proportional,2);
			$bonus_money = moneys_format($user_post_money,2);
			//echo $bonus_money;
			$sql = "insert into ".$GLOBALS['ecs']->table('user_bonus_log')." (bonus_id, user_id, user_bonus_level, admin, bonus_money, ctime) values(".
				"'".$bid."',".
				"'".$v['user_id']."',".
				"'".$v['bonus_proportional']."',".
				"'".$admin_id."',".
				"'".$bonus_money."',".
				time().
			") ";
			//echo $sql."<br>";
			$GLOBALS['db']->query($sql);
		}
	}
	

	
	//}}}
	return $bid;
}

function push_fenxiao($bid){
	$bid = intval($bid);
	//分红记录状态
	$sql = "update ".$GLOBALS['ecs']->table('bonus_total')." set status = 1, confirmtime = ".time()." where id = $bid";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	//分红等级统计记录状态
	$sql = "update ".$GLOBALS['ecs']->table('bonus')." set status = 1 where bid = $bid";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);

	//用户到账记录
	$usersql = "select user_id,bonus_money from ".$GLOBALS['ecs']->table('user_bonus_log')." where status = 0 and bonus_id = $bid ";
	//echo $usersql."<br>";
	$userlist = $GLOBALS['db']->getAll($usersql);
	if($userlist){
		foreach($userlist as $k=>$v){
			//累计到用户资金， 用户分红金额
			set_fx_cash($v['user_id'],"+","bonus_amount",$v['bonus_money']);//累计用户分红金额
			
			//分红后，增加用户资金
			log_account_change($v['user_id'], $v['bonus_money'], 0, 0, 0, '分红到账，分红id :'.$bid, 4);
		}
	}
		
	$sql = "update ".$GLOBALS['ecs']->table('user_bonus_log')." set status = 1 where bonus_id = $bid";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);


}
?>