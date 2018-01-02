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
	$info = get_today_trade('select'); 
	$is_finished = $GLOBALS['db']->getOne("select id from ".$GLOBALS['ecs']->table('day_trade')." where trade_sn = ".$info['trade_sn']);
	if($is_finished != null && $is_finished){
		$smarty->assign("is_finished", 1);
	}else{
		$smarty->assign("is_finished", 0);
	}
	
    $logdb = get_trade_list(); 
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['fenxiao_list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('list',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    
    assign_query_info();
    $smarty->display('trade_list.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_trade_list(); 
    $smarty->assign('logdb',        $logdb['list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    make_json_result($smarty->fetch('trade_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
elseif ($_REQUEST['act'] == 'day')
{
    //查看今日交易额
	$info = get_today_trade('select'); 
	$is_finished = $GLOBALS['db']->getOne("select id from ".$GLOBALS['ecs']->table('day_trade')." where trade_sn = ".$info['trade_sn']);
	if($is_finished != null && $is_finished){
		$smarty->assign("is_finished", 1);
	}else{
		$smarty->assign("is_finished", 0);
	}
	if($info){
		$info['stime'] = date("Y-m-d H:i", $info['stime']);
		$info['etime'] = date("Y-m-d H:i", $info['etime']);
		$info['trade_amount'] = price_format($info['trade_amount']);
		$info['trade_fanxian_amount'] = price_format($info['trade_fanxian_amount']);
		$info['fanxian_bili'] = ($info['fanxian_bili']*100)."%";
		
		$info['order_amount'] = price_format($info['order_amount']);
		$info['cash_amount'] = price_format($info['cash_amount']);
		$info['user_money'] = price_format($info['user_money']);
	}
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '今日交易');
    $smarty->assign('on', $separate_on);
    $smarty->assign('info',        $info);
  
    $smarty->display('trade_day.htm');
}else if($_REQUEST['act'] == "detail"){
	$id = isset($_REQUEST['id'])?intval($_REQUEST['id']):0;
	$trade_sn = isset($_REQUEST['trade_sn'])?intval($_REQUEST['trade_sn']):0;
	$t = isset($_REQUEST['t'])?$_REQUEST['t']:'default';

	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	if(!$trade_sn){
		$trade_sn = $GLOBALS['db']->getOne("select trade_sn from ".$GLOBALS['ecs']->table('day_trade')." where id = $id");
	}
	
	$info = get_trade_info($id);
	$smarty->assign("info",$info);
		
	if($t == 'default'){
		 $smarty->assign('full_page',  1);
		$smarty->assign('ur_here', '交易号'.$trade_sn.'详情');
		$smarty->assign('on', $separate_on);
		$smarty->display("trade_detail.htm");
	}else if($t == 'order'){
		
		$sql = "SELECT  o.order_sn id,pl.order_amount money FROM " 
			.$GLOBALS['ecs']->table('day_trade_order')." dt " 
			." LEFT JOIN ".$GLOBALS['ecs']->table('order_info')." o ON dt.order_id = o.order_id  " 
			." LEFT JOIN ".$GLOBALS['ecs']->table('pay_log')." pl ON dt.order_id = pl.order_id  " 
			." WHERE 1 AND dt.trade_sn = '".$trade_sn."' and dt.type = 'order' ";
			
		printsqls($sql,"order ");
			
		$list = $GLOBALS['db']->getALL($sql);
		if($list){
			foreach($list as $k=>&$v){
				$v['money'] = price_format($v['money']);
			}
		}
		
		$smarty->assign('tongji', "合计：".$info['order_amount']."<br> 记录数：".$info['order_nums']."");
		$smarty->assign("title_id", "订单号");
		$smarty->assign("title_money", "事件金额");
		$smarty->assign("list",$list);
		$smarty->assign('full_page',  1);
		$smarty->assign('ur_here', '交易号'.$trade_sn.'');
		$smarty->assign('on', $separate_on);
		$smarty->display("trade_detail_record.htm");
	}else if($t == 'cash'){
		$sql = "SELECT  c.id ,c.money FROM " 
			.$GLOBALS['ecs']->table('day_trade_order')." dt " 
			." LEFT JOIN ".$GLOBALS['ecs']->table('cash_log')." c ON c.id = dt.cash_id  " 
			." WHERE 1 AND dt.trade_sn = '".$trade_sn."' and dt.type = 'cash' ";
			
		printsqls($sql,"detail cash SQL ");
			
		$list = $GLOBALS['db']->getALL($sql);
		if($list){
			foreach($list as $k=>&$v){
				$v['money'] = price_format($v['money']);
			}
		}
		$smarty->assign('tongji', "合计：".$info['cash_amount']."<br> 记录数：".$info['cash_nums']."");
		$smarty->assign("title_id", "交易号");
		$smarty->assign("title_money", "交易金额");
		$smarty->assign("list",$list);
		$smarty->assign('full_page',  1);
		$smarty->assign('ur_here', '交易号'.$trade_sn.'');
		$smarty->assign('on', $separate_on);
		$smarty->display("trade_detail_record.htm");
	}else if($t == 'users'){
		$sql = "select u.user_name id, ub.point money FROM "
			.$GLOBALS['ecs']->table('user_backpoint_log')." ub "
			." LEFT JOIN ".$GLOBALS['ecs']->table('users')." u ON u.user_id = ub.user_id "
			." WHERE 1 AND ub.trade_sn = ".$trade_sn;
		printsqls($sql,"user ");
		$list = $GLOBALS['db']->getALL($sql);
		if($list){
			foreach($list as $k=>&$v){
				$v['money'] = price_format($v['money']);
			}
		}
		
		$user_trade_fanxian_amount = $GLOBALS['db']->getOne("select sum(point) from ".$GLOBALS['ecs']->table("user_backpoint_log")." where trade_sn = '".$trade_sn."'");
		$smarty->assign('tongji', "合计:".$user_trade_fanxian_amount."<br> 总人数：".$info['user_nums']."");
		$smarty->assign("title_id", "用户名");
		$smarty->assign("title_money", "返现金额");
		$smarty->assign("list",$list);
		$smarty->assign('full_page',  1);
		$smarty->assign('ur_here', '交易号'.$trade_sn.'');
		$smarty->assign('on', $separate_on);
		$smarty->display("trade_detail_record.htm");
	}
	
}
//系统执行调用
elseif ($_REQUEST['act'] == 'daytrade')
{
	if($separate_on){	
	   get_today_trade('exec');
	}else{
		die('closed');
	}
}

/** added by Ran **/
function get_trade_list()
{

    $sqladd = '';
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('day_trade') . " dt ".
                    " WHERE dt.id > 0  $sqladd";
     
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    
	$sql = "SELECT id, trade_sn,trade_amount, trade_fanxian_amount,trade_fanxian_shiji_amount,trade_fanxian_bili, user_money,user_nums, order_amount, order_nums, cash_amount, cash_nums, trade_stime, trade_etime " .
                    " FROM " . $GLOBALS['ecs']->table('day_trade') . " dt".
                    " WHERE dt.id > 0  $sqladd" .
                    " ORDER BY dt.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";
	printsqls($sql,'trade list sql');
    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
		$rt['stime'] = date("Y-m-d H:i",$rt['trade_stime']);
		$rt['etime'] = date("Y-m-d H:i",$rt['trade_etime']);
		$rt['trade_amount'] = price_format($rt['trade_amount']);
		$rt['trade_fanxian_amount'] = price_format($rt['trade_fanxian_amount']);
		$rt['trade_fanxian_shiji_amount'] = price_format($rt['trade_fanxian_shiji_amount']);
		$rt['user_money'] = price_format($rt['user_money']);
		$rt['order_amount'] = price_format($rt['order_amount']);
		$rt['cash_amount'] = price_format($rt['cash_amount']);
		$rt['trade_fanxian_bili'] = $rt['trade_fanxian_bili']."%";
        $logdb[] = $rt;
    }
    $arr = array('list' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
//#type = select, 只查询，不执行 
//#type = exec, 执行
function get_today_trade($type='select'){
	$fanxian = unserialize($GLOBALS['_CFG']['fanxian']);
	$fanxian_type = $fanxian['fanxian_type']?$fanxian['fanxian_type']:'jiecengfanxian';
	$today_hours = $fanxian['day_trade_time_h']?$fanxian['day_trade_time_h']:'17';
	$today_minus = $fanxian['day_trade_time_m']?$fanxian['day_trade_time_m']:'00';
	$fanxian_bili = $fanxian['fanxian_bili']?$fanxian['fanxian_bili']:10; //默认10%
	
	$fanxian_limit = $fanxian['fanxian_money_limit']?$fanxian['fanxian_money_limit']:365;
	$renminbi = $fanxian['renminbi']?$fanxian['renminbi']:1;
	$point = $fanxian['point']?$fanxian['point']:365;
	
	$stime = date("Y-m-d",strtotime("-1 day"));
	$etime = date("Y-m-d",time());
	$trade_sn = date("Ymd",time());
	
	//昨天测试
	//$stime = date("Y-m-d",strtotime("-2 day"));
	//$etime = date("Y-m-d",strtotime("-1 day"));
	//$trade_sn = date("Ymd",strtotime("-1 day"));
	
	$stime = strtotime($stime." ".$today_hours.":".$today_minus);
	$etime = strtotime($etime." ".$today_hours.":".$today_minus);
	
	$data = array();
	//{{{ 根据付款日期计算交易额
	$pay_amount_sql = "select sum(pl.order_amount) FROM " 
			.$GLOBALS['ecs']->table('order_info')." o " 
			." LEFT JOIN ".$GLOBALS['ecs']->table('pay_log')." pl ON o.order_id = pl.order_id  " 
			." WHERE 1 AND pl.order_amount>0 and o.pay_time > ".$stime." AND o.pay_time <= ".$etime ;
	$pay_amount = $GLOBALS['db']->getOne($pay_amount_sql);
	if($pay_amount == null || !$pay_amount){ $pay_amount = 0;}
	$pay_amount = floatvalx($pay_amount, 2);
	//}}}
	
	//{{{ 获得今天交易订单
	$order_sql = "SELECT o.order_id, o.order_sn, o.user_id, o.order_status,o.goods_amount, o.money_paid, o.surplus, o.inv_money,o.pay_time,pl.order_amount FROM " 
			.$GLOBALS['ecs']->table('order_info')." o " 
			." LEFT JOIN ".$GLOBALS['ecs']->table('pay_log')." pl ON o.order_id = pl.order_id  " 
			." WHERE 1 AND pl.order_amount>0 and o.pay_time > ".$stime." AND o.pay_time <= ".$etime ;
	//echo $sql;
	$order_list = $GLOBALS['db']->getALL($order_sql);
	$order_num = intval(count($order_list));
	//}}}
	
	//{{{ 统计现金交易 - 超市 
	$cash_amount_sql = "select sum(money) from ".$GLOBALS['ecs']->table('cash_log')." where 1 AND money >0 and ctime > ".$stime." AND ctime <= ".$etime ;
	$cash_amount = $GLOBALS['db']->getOne($cash_amount_sql);
	if($cash_amount == null || !$cash_amount){ $cash_amount = 0;}
	$cash_amount = floatvalx($cash_amount, 2);
	//echo $cash_amount_sql;
	$cash_sql = "select id from ".$GLOBALS['ecs']->table('cash_log')." where 1 AND money >0 and ctime > ".$stime." AND ctime <= ".$etime ;
	$cash_list = $GLOBALS['db']->getAll($cash_sql);
	$cash_num = intval(count($cash_list));
	//}}}
	
	$pay_total_amount = $pay_amount + $cash_amount;
	$fanxian_amount = $pay_total_amount*floatval($fanxian_bili/100);
	
	//{{{获得可返现客户,并且管理员未禁用
	$user_sql = "select ub.user_id,ub.consume, ub.back_point from ".$GLOBALS['ecs']->table('users')." u "
			." left join ".$GLOBALS['ecs']->table('user_backpoint')." ub ON u.user_id = ub.user_id "
			."where u.is_back_point = '1' and is_disabled_point = '0' and ub.back_point < ub.consume ";
	$user_list = $GLOBALS['db']->getAll($user_sql);
	printsqls($user_sql);
	
	$user_num = count($user_list);
	if($user_num){
		$user_money = floatvalx($fanxian_amount/$user_num,2);
	}else{
		$user_money = 0;
	}
	//}}}
	
	
	
	$trade_data = array(
		"trade_sn"=>$trade_sn,
		"trade_amount"=>$pay_total_amount,
		"stime"=>$stime,
		"etime"=>$etime,
		"trade_fanxian_amount"=>$fanxian_amount,
		"fanxian_bili"=>floatval($fanxian_bili/100),
		"user_num"=>$user_num,
		"user_money"=>$user_money,
		"order_amount"=>$pay_amount,
		"order_nums"=>$order_num,
		"cash_amount"=>$cash_amount,
		"cash_nums"=>$cash_num
	);
	//var_dump($trade_data);
	if($type == 'select'){
		return $trade_data;
	}
	
	if($type == 'exec'){
			//获得今天的交易额，并生成记录
			//step 1 插入 day_trade 表，生成返现记录
			$trade_sql = "insert into ".$GLOBALS['ecs']->table('day_trade')."(trade_sn, trade_amount,trade_stime, trade_etime, trade_fanxian_amount,  trade_fanxian_bili,user_nums, user_money, order_nums,order_amount,cash_nums, cash_amount,ctime)values("
				."'".$trade_sn."',"
				."'".$pay_total_amount."',"
				."'".$stime."',"
				."'".$etime."',"
				."'".$fanxian_amount."',"
				."'".$fanxian_bili."',"
				."'".$user_num."',"
				."'".$user_money."',"
				."'".$order_num."',"
				."'".$pay_amount."',"
				."'".$cash_num."',"
				."'".$cash_amount."',"
				."'".time()."'"
			.")";
			
			printsqls($trade_sql,'trade sql');
			
			$GLOBALS['db']->query($trade_sql);
			
			//step 2 插入 day_trade_order 记录表， 将order list, cash list 插入 
			if($order_list){
				$olist = array();
				foreach($order_list as $k=>$v){
					$olist[] = "(".$trade_sn.",".$v['order_id'].",'order')";
				}
				$ostr = implode(",",$olist);
				
				$osql = "insert into ".$GLOBALS['ecs']->table('day_trade_order')." (trade_sn, order_id,type) values ".$ostr;
				printsqls($osql,'order list sql');
				$GLOBALS['db']->query($osql);
				
			}
			if($cash_list){
				$clist = array();
				foreach($cash_list as $k=>$v){
					$clist[] = "(".$trade_sn.",".$v['id'].",'cash')";
				}
				$cstr = implode(",",$clist);
				
				$csql = "insert into ".$GLOBALS['ecs']->table('day_trade_order')." (trade_sn, cash_id,type) values ".$cstr;
				printsqls($csql,'cash list sql');
				$GLOBALS['db']->query($csql);
			}
			if($user_list){
			
				foreach($user_list as $k=>$v){
					//step 3 返现给用户 user list , 累计返现金额
					
					//$user_money //实际返现金额
					//$user_real_money //实际返现金额
					$user_shiji_money = 0;
					if($fanxian_type == 'jiecengfanxian'){
						if(intval(floor($v['consume']/$fanxian_limit) - floor($v['back_point']/$fanxian_limit)>0)){ //判断是否返现完成
							$user_zuiduo_money = floatval(floor($v['consume']/$fanxian_limit)*$fanxian_limit) - $v['back_point'];
							if($user_money > $user_zuiduo_money){
								$user_shiji_money = $user_zuiduo_money;
							}else{
								$user_shiji_money = $user_money;
							}
						}else{
							$user_shiji_money = 0;
						}
					}else if($fanxian_type == 'jinefanxian'){
						$user_shiji_money = $user_money;
						if($user_money > ($v['consume'] - $v['back_point'])){
							$user_shiji_money =  floatval($v['consume'] - $v['back_point']);
						}
					}
					
					
					if($user_shiji_money){
						$usql = "update ".$GLOBALS['ecs']->table('user_backpoint')." set back_point = back_point + ".$user_shiji_money." where user_id = ".$v['user_id']." limit 1";
						$GLOBALS['db']->query($usql);
						printsqls($usql,'user 返现sql');
						//step 4 插入返现用户 记录  user_backpoint_log
						$usql2 = "insert into ".$GLOBALS['ecs']->table('user_backpoint_log')." (user_id, point, renjun_point, point_bili, ctime, trade_sn) values("
							."'".$v['user_id']."',"
							."'".$user_shiji_money."',"
							."'".$user_money."',"
							."'".$fanxian_bili."',"
							."'".time()."',"
							."'".$trade_sn."' "
						.")";
						printsqls($usql2,'user_backpoint_log');
						$GLOBALS['db']->query($usql2);
						
						//累计到用户总资金账户中
						$user_duihuan = floatval($user_shiji_money * floatval($renminbi / $point));
						log_account_change($v['user_id'], $user_duihuan, 0, 0, 0, '返现到账，返现ID :'.$trade_sn, 6);
					}
					//step5 返现完成后，查看该用户是否还满足返现要求
					if(check_user_is_fanxian($v['user_id'])){
						$user_sql = "update ".$GLOBALS['ecs']->table('users')." set is_back_point = 1 where user_id = ".$v['user_id']." limit 1 ";
						printsqls($user_sql,'check_user_is_fanxian');
						$GLOBALS['db']->query($user_sql);
					}else{
						$user_sql = "update ".$GLOBALS['ecs']->table('users')." set is_back_point = 0 where user_id = ".$v['user_id']." limit 1 ";
						printsqls($user_sql,'check_user_is_fanxian');
						$GLOBALS['db']->query($user_sql);
					}
				}
			}
			
			//step6 统计实际返现金额
			$trade_shiji_sql = "select sum(point) from ".$GLOBALS['ecs']->table('user_backpoint_log')." where trade_sn = '".$trade_sn."' ";
			printsqls($trade_shiji_sql,'trade 实际返现金额');
			$trade_shiji = $GLOBALS['db']->getOne($trade_shiji_sql);
			$GLOBALS['db']->query("update ".$GLOBALS['ecs']->table('day_trade')." set trade_fanxian_shiji_amount = ".floatval($trade_shiji)." where trade_sn = '".$trade_sn."' limit 1 ");
			
	}
	
	
	$links[0]['text'] = "操作成功";
	$links[0]['href'] = 'trade_list.php?act=list';
	sys_msg($_LANG['update_success'], 0, $links);
}

function get_trade_info($id){
	if(!$id){
		return false;
	}
	$id = intval($id);
	$sqladd = " AND dt.id = $id";
	
	$sql = "SELECT id, trade_sn,trade_amount, trade_fanxian_amount,trade_fanxian_shiji_amount, trade_fanxian_bili, user_money,user_nums, order_amount, order_nums, cash_amount, cash_nums, trade_stime, trade_etime " .
                    " FROM " . $GLOBALS['ecs']->table('day_trade') . " dt".
                    " WHERE dt.id > 0  $sqladd";
	printsqls($sql,'trade info sql [trade ID : '.$id.']');
    $info = $GLOBALS['db']->getRow($sql);
    
    if($info){
		$info['stime'] = date("Y-m-d H:i",$info['trade_stime']);
		$info['etime'] = date("Y-m-d H:i",$info['trade_etime']);
		$info['trade_amount'] = price_format($info['trade_amount']);
		$info['trade_fanxian_amount'] = price_format($info['trade_fanxian_amount']);
		$info['trade_fanxian_shiji_amount'] = price_format($info['trade_fanxian_shiji_amount']);
		$info['user_money'] = price_format($info['user_money']);
		$info['order_amount'] = price_format($info['order_amount']);
		$info['cash_amount'] = price_format($info['cash_amount']);
		$info['trade_fanxian_bili'] = $info['trade_fanxian_bili']."%";
    }
	
	return $info?$info:array();
	
}
?>