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

//admin_priv('fenhong');
$timestamp = time();

$fanxian = unserialize($GLOBALS['_CFG']['fanxian']);

$separate_on = $fanxian['fanxian_open'];
//$rand_fenhong_bili = floatval(rand(5,10)/100);
$rand_fenhong_bili = 0.1;
$smarty->assign("rand_fenhong_bili",$rand_fenhong_bili);

if ($_REQUEST['act'] == 'dayfenhong')
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
        echo date("Y-m-d H:i:s")."---->分红金额不能小于0 \r\n";
		exit;
    }
    if($fenhongchi <= 0){
        //sys_msg('分红池金额小于0，不能执行分红', 0 ,$links);
        echo date("Y-m-d H:i:s")."---->分红池金额小于0，不能执行分红 \r\n";
		exit;
    }
    if($fenhong_total_dian <= 0){
        //sys_msg('分红总点不能为0，不能执行分红', 0 ,$links);
        echo date("Y-m-d H:i:s")."---->分红总点不能为0，不能执行分红 \r\n";
		exit;
    }
    if(($fenhong_total_dian*$fenhong_user_money)>$fenhongchi){
        //sys_msg('分红金额大于分红池金额', 0 ,$links);
        echo date("Y-m-d H:i:s")."---->分红金额大于分红池金额 \r\n";
		exit;
    }
	
	
    $cur_date = date("Y-m-d");
	//$cur_date = "2018-06-20";
    $sql = "select * from ".$ecs->table('pc_fenhong')." where fenhong_date='".$cur_date."'";
    $is_check = $db->getRow($sql);
	
	//echo $sql;
	//var_dump($is_check);

	
    if($is_check){
        //sys_msg('当日已分红，不可重复执行', 0 ,$links);
        echo date("Y-m-d H:i:s")."---->当日已分红，不可重复执行 \r\n";
    }else{
		
		//if(date("H") < 23){
		//	echo  date("Y-m-d H:i:s")."还未到执行时间\r\n";
		//}else{
			//fenhongjisuan($fenhong_user_money,"fenhong");
			fenhongjisuan_zhidingDate($fenhong_user_money,"fenhong");
			echo date("Y-m-d H:i:s")." 执行分红计算----> 1 \r\n ";
		//}
	}
	
	
	
	exit;
    //sys_msg('操作成功', 0 ,$links);
}

?>