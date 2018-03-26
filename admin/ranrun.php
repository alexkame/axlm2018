<?php

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

die('had run on 2018-03-26, 修复分红累计小数问题 ');
exit;



if ($_REQUEST['act'] == 'ranrun')
{
//run 自动执行
	//$fenhongchi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname = 'fenhongchi'");
	$user_fenhong = $db->getAll("select uid from ".$ecs->table('pc_user_account_log')." where (note = '分红' or note ='分红返利') and type = 'account_xiaofeibi' group by uid  order by uid asc ");
	
	foreach($user_fenhong as $u=>$user){
		$cur_user_id = $user['uid'];
		$account_fenhong_log = $db->getAll("select id,uid,type,original_value,change_value, new_value, note from ".$ecs->table('pc_user_account_log')." where (note = '分红' or note ='分红返利') and type = 'account_xiaofeibi' ".
		" and uid = ".$cur_user_id." order by uid asc , id asc  ");
		
		$total_chae = 0;
		$change_total = 0;
		foreach($account_fenhong_log as $k=>&$v){
			//echo "ID : ".$v['id']." --- ".$v['original_value']." --- ".$v['change_value']." --- ".$v['new_value']."<br>";
			if($k < count($account_fenhong_log)-1){
				$total_chae += floatval(number_format(floatval($v['new_value']) - intval($v['new_value']), 2));
			}
			
			$change_total += $v['change_value'];
			//echo "<br> change total : ". $change_total."<br>";
			
			//var_dump(floatval(number_format(floatval($v['new_value']) - intval($v['new_value']), 2)));
			//sprintf("%.2f",$pagedata['loaninfo']['loan_amount'])
			//echo "==>total ".$total_chae."----". number_format(floatval($v['new_value']) - intval($v['new_value']), 2)."<br>";
		
		}
		
		echo "<br>UID : ".$cur_user_id." --- ".$total_chae."---->".$user['new_value']."<br>";
		saveFenhongChange($cur_user_id, $total_chae, $ecs, $db);
	}

	exit;
    //sys_msg('操作成功', 0 ,$links);
}



function saveFenhongChange($uid, $change_value, $ecs, $db){
		//分红出的是消费币
		$sql = "select * from ".$ecs->table('pc_user')." where uid = $uid";
//    $sql = "SELECT u.*,log.is_tuiguang,log.is_fuwu,log.is_jiandian, log.is_guanli FROM " . $ecs->table('pc_user')." u left join ".$ecs->table('pc_user_status_log')." log on u.uid = log.uid WHERE u.uid = '$uid' and u.status = 1 ";
//    pc_log($sql,'get_pc_user_allinfo');
		$userinfo = $db->getRow($sql);
		if(!$userinfo){return false;}
		 $original_value = floatval($userinfo['account_xiaofeibi']);
		 $change_value = floatval($change_value);
		 $new_value = $original_value + $change_value;

		 $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$uid;
		 //$db->query($sql);
		 $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
				 "'".$uid."',".
				 "'account_xiaofeibi',".
				 "'".$original_value."',".
				 "'".$change_value."',".
				 "'".$new_value."',".
				 "'分红纠正',".
				 "'0',".
				 "'".time()."' ".
		 ")";
		 
		 echo $sql;
		 //$db->query($sql);
}

?>