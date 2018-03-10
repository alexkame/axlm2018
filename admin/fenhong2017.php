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
//$rand_fenhong_bili = floatval(rand(5,10)/100);
$rand_fenhong_bili = 0.1;
$smarty->assign("rand_fenhong_bili",$rand_fenhong_bili);

if ($_REQUEST['act'] == 'list')
{
    $bili = 0.1;
    //计算已付款的所有订单，当天的付款，且付款时间是今日
    $cur_date = date("Y-m-d");
    //$cur_date = "2017-09-19";
    $start_time = strtotime($cur_date." 00:00:00");
    $end_time = strtotime($cur_date." 23:59:59");
    $sql = "select order_id, user_id,order_amount,pay_time from ".$ecs->table('order_info')." where pay_time > ".$start_time." and pay_time < ".$end_time." and pay_status = 2 ";
    //echo $sql;
    $order_list = $db->getAll($sql);
    
    $sumsql = "select sum(order_amount) from ".$ecs->table('order_info')." where pay_time > ".$start_time." and pay_time < ".$end_time." and pay_status = 2 ";
    $trade_today = floatval($db->getOne($sumsql));
    //$trade_fanli_today = $trade_today * $bili;
    
    $order_total = count($order_list);
    $order_total_amount = $trade_today;
    $order_total_fenhong_amount = $trade_fanli_today;
    
    $smarty->assign("order_total",$order_total);
    $smarty->assign("order_total_amount",$order_total_amount);
    $smarty->assign("order_total_fenhong_amount",$order_total_fenhong_amount);
    
    
    $fenhongchi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname = 'fenhongchi'");
    $fenhong_user_money = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname = 'fenhong_user_money'");
	//echo "select id,uid,account_jifenbi,account_fenhong_amount from ".$ecs->table('pc_user')." where account_jifenbi > 0 and status = 1";
    $pc_user_list = $db->getAll("select id,uid,account_jifenbi,account_fenhong_amount from ".$ecs->table('pc_user')." where account_jifenbi > 0 and status = 1");
    $pc_fenhongdian = array();
    $pc_total_fenhongdian = 0;
    foreach($pc_user_list as $k=>$v){
        $pc_fenhongdian[] = array(
            "uid"=>$v['uid'],
            "account_jifenbi"=>$v['account_jifenbi'],
            "account_fenhong_amount"=>$v['account_fenhong_amount'],
            "fenhongdain"=>intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360) //剩余的分红点，去除掉已分红的点
        );
		
        $pc_total_fenhongdian += intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360);
    }
	//var_dump($pc_fenhongdian);
    $smarty->assign("fenhongchi",$fenhongchi);
    $smarty->assign("fenhong_user_money",$fenhong_user_money);
    $smarty->assign("fenhong_total_user",count($pc_user_list));
    $smarty->assign("fenhong_total_dian",$pc_total_fenhongdian);
    
    
    $logdb = get_trade_list(); 
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['fenxiao_list']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('list',        $logdb['list']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    
    assign_query_info();
    $smarty->display('fenhong2017.htm');
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
	$t = isset($_REQUEST['t'])?$_REQUEST['t']:'default';

	if(!$id){
		$links[] = array('text' => "缺少参数，操作失败", 'href' => 'fenhong2017.php?act=list');
		sys_msg('缺少参数，操作失败', 0 ,$links);
	}
	
        $info = $db->getRow("select * from ".$ecs->table('pc_fenhong')." where id = ".$id);
        //var_dump($info);
	$smarty->assign("info",$info);
	
        $list = $db->getAll("select pf.*,u.user_name from ".$ecs->table('pc_fenhong_log')."pf left join ".$ecs->table('users')." u on pf.user_id = u.user_id where fenhong_date = '".$info['fenhong_date']."'");
        //echo "select * from ".$ecs->table('pc_fenhong_log')." where fenhong_date = '".$info['fenhong_date']."'";
        if($list){
            foreach($list as $k=>$v){
                $v['ctime_format'] = date("Y-m-d H:i:s",$v['ctime']);
                $list[$k] = $v;
            }
        }
        $smarty->assign('full_page',  1);
        $smarty->assign('list', $list);
        $smarty->assign('ur_here', '分红 ['.$info['fenhong_date'].'] 详情');
        $smarty->assign('on', $separate_on);
        $smarty->display("fenhong2017_trade_detail.htm");
	
}
//系统执行调用 将当日分红金额，累计到分红池
elseif ($_REQUEST['act'] == 'daytrade')
{
    //$bili = floatval(isset($_REQUEST['fenhong_bili'])?$_REQUEST['fenhong_bili']:0);
    $bili = 0.1;
    //echo $bili;
    if($bili > 0.1){
        sys_msg('分红比例最高不能超过10%', 0 ,$links);
        return 0;
    }
    if($bili <= 0){
        sys_msg('分红比例不能小于或等于0', 0 ,$links);
        return 0;
    }
    if($bili == ''){
        sys_msg('分红比例不能为空', 0 ,$links);
        return 0;
    }
    
    //将分红金额累计到分红池
    fenhongjisuan($bili,"leiji");
    sys_msg('操作成功', 0 ,$links);
    
}elseif ($_REQUEST['act'] == 'dayfenhong')
{
//run 自动执行
	$fenhongchi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname = 'fenhongchi'");
    $fenhong_user_money = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname = 'fenhong_user_money'");
	$pc_user_list = $db->getAll("select id,uid,account_jifenbi,account_fenhong_amount from ".$ecs->table('pc_user')." where account_jifenbi > 0 and status = 1");
    $pc_fenhongdian = array();
    $pc_total_fenhongdian = 0;
    foreach($pc_user_list as $k=>$v){
        $pc_fenhongdian[] = array(
            "uid"=>$v['uid'],
            "account_jifenbi"=>$v['account_jifenbi'],
            "account_fenhong_amount"=>$v['account_fenhong_amount'],
            "fenhongdain"=>intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360) //剩余的分红点，去除掉已分红的点
        );
		
        $pc_total_fenhongdian += intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360);
    }
	//var_dump($pc_fenhongdian);
    //$smarty->assign("fenhongchi",$fenhongchi);
    //$smarty->assign("fenhong_user_money",$fenhong_user_money);
    //$smarty->assign("fenhong_total_user",count($pc_user_list));
    //$smarty->assign("fenhong_total_dian",$pc_total_fenhongdian);
// end run 自动执行
	
    $fenhong_user_money = floatval(isset($fenhong_user_money)?$fenhong_user_money:0);
    $fenhong_total_dian = intval(isset($pc_total_fenhongdian)?$pc_total_fenhongdian:0);
    $fenhongchi = floatval(isset($fenhongchi)?$fenhongchi:0);
    //echo $fenhong_user_money;

	
	//echo $fenhong_user_money."<br>";
	//echo $fenhong_total_dian."<br>";
	//echo $fenhongchi;
	//exit;
	
    if($fenhong_user_money <= 0){
        //sys_msg('分红金额不能小于0', 0 ,$links);
        return 0;
		exit;
    }
    if($fenhongchi <= 0){
        //sys_msg('分红池金额小于0，不能执行分红', 0 ,$links);
        return 0;
		exit;
    }
    if($fenhong_total_dian <= 0){
        //sys_msg('分红总点不能为0，不能执行分红', 0 ,$links);
        return 0;
		exit;
    }
    if(($fenhong_total_dian*$fenhong_user_money)>$fenhongchi){
        //sys_msg('分红金额大于分红池金额', 0 ,$links);
        return 0;
		exit;
    }
	
	
    $cur_date = date("Y-m-d");
    $sql = "select * from ".$ecs->table('pc_fenhong')." where fenhong_date='".$cur_date."'";
    $is_check = $db->getRow($sql);
	
	//echo $sql;
	//var_dump($is_check);

	
    if($is_check){
        //sys_msg('当日已分红，不可重复执行', 0 ,$links);
        echo 0;
    }else{
        fenhongjisuan($fenhong_user_money,"fenhong");
		echo 1;
    }
	
	exit;
    //sys_msg('操作成功', 0 ,$links);
}
elseif ($_REQUEST['act'] == 'dayfenhong_save_fenhong_user_money')
{
    $fenhong_user_money = floatval(isset($_REQUEST['fenhong_user_money'])?$_REQUEST['fenhong_user_money']:0);
   

    if($fenhong_user_money <= 0){
        sys_msg('分红金额不能小于0', 0 ,$links);
        return 0;
    }
   
    if(($fenhong_total_dian*$fenhong_user_money)>$fenhongchi){
        sys_msg('分红金额大于分红池金额', 0 ,$links);
        return 0;
    }
	
	$osql = "update ".$GLOBALS['ecs']->table('pc_config')." set svalue = '".$fenhong_user_money."' where sname = 'fenhong_user_money' limit 1 ";
	//echo $osql;
	
	$GLOBALS['db']->query($osql);
   
           
    sys_msg('操作成功', 0 ,$links);
}

/** added by Ran **/
function get_trade_list()
{

    $sqladd = '';
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('pc_fenhong') . " dt ".
                    " WHERE dt.id > 0  $sqladd";
     
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
	
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    
	$sql = "SELECT * " .
                    " FROM " . $GLOBALS['ecs']->table('pc_fenhong') . " dt".
                    " WHERE dt.id > 0  $sqladd" .
                    " ORDER BY dt.id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";
	//printsqls($sql,'trade list sql');
    $query = $GLOBALS['db']->query($sql);
    
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
	
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