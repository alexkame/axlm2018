<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
define('IN_ECS', true); 
require(dirname(__FILE__) . '/includes/init.php');

require_once(ROOT_PATH."/admin/zhifubao/alipay.config.php");
require_once(ROOT_PATH."/admin/zhifubao/lib/alipay_notify.class.php");
	
$fp = fopen("zhifubao.txt", "a+");
fwrite($fp, "\r\n\r\n\r\n");
fwrite($fp, "\r\n========".Date("Y-m-d H:i:s",time())."=========\r\n");
//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

fwrite($fp, json_encode($verify_result));
fwrite($fp, "\r\n=================\r\n");
fwrite($fp, json_encode($_POST));
fwrite($fp, "\r\n=================\r\n");

if($verify_result) {//验证成功
	fwrite($fp, "\r\n=======success===[".$_POST['batch_no']."]=======\r\n");
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代
	$batch_no = $_POST['batch_no'];
	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//批量付款数据中转账成功的详细信息
	
	$success_details = $_POST['success_details'];
	
	//批量付款数据中转账失败的详细信息
	$fail_details = $_POST['fail_details'];
	$fail_array = array();
	$fail_error_msg = array();
	if($fail_details){
		$fail_arr = explode("|",$fail_details);
		foreach($fail_arr as $k=>$v){
			$v_arr = explode("^",$v);
			$fail_array[] = intval($v_arr[0]);
			$fail_error_msg[intval($v_arr[0])] = $v_arr[5];
		}
	}
	$fail_array = array_filter($fail_array);
	fwrite($fp, "\r\n==[[[".json_encode($fail_array)."]]]");
	$sql = "update ".$GLOBALS['ecs']->table('alipy_pay')." set return_status = 'success' , return_success_detail = '".$success_details."', return_fail_detail = '".$fail_details."' where picihao = '".$batch_no."' limit 1";
	$GLOBALS['db']->query($sql);
	fwrite($fp, "\r\n==[[[$sql]]]\r\n");
	$list_sql = "select user_account_id, ua.amount, ua.user_id from ".$GLOBALS['ecs']->table('alipy_pay_tixian')." pt "
			." LEFT JOIN ".$GLOBALS['ecs']->table('user_account')." ua ON pt.user_account_id = ua.id "
			." where pt.picihao = '".$batch_no."' ";
	fwrite($fp, "\r\n==[[[$list_sql]]]\r\n");
	$list = $GLOBALS['db']->getAll($list_sql);
	if($list){
		foreach($list as $k=>$v){
			if(in_array($v['user_account_id'],$fail_array)){
				$sql = "update ".$GLOBALS['ecs']->table('user_account')." set is_paid = 0,alipay_exec_time= ".time().", alipay_return='fail',alipay_error_msg = '".$fail_error_msg[$v['user_account_id']]."'  where id = ".$v['user_account_id']." limit 1";
			}else{
				//更新会员余额数量
				log_account_change($v['user_id'], $v['amount'], 0, 0, 0, '批量提现,批次号'.$batch_no, 5);
				$sql = "update ".$GLOBALS['ecs']->table('user_account')." set is_paid = 1,alipay_exec_time= ".time().", alipay_return='success'  where id = ".$v['user_account_id']." limit 1";
			}
			fwrite($fp, "\r\n==[[[$sql]]]");
			$GLOBALS['db']->query($sql);
		}
	}
	
	//判断是否在商户网站中已经做过了这次通知返回的处理
		//如果没有做过处理，那么执行商户的业务程序
		//如果有做过处理，那么不执行商户的业务程序
        
	echo "success";		//请不要修改或删除

	//调试用，写文本函数记录程序运行情况是否正常
	//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
   fwrite($fp, "\r\n========fail=====".$_POST['batch_no']."====\r\n");
	$sql = "update ".$GLOBALS['ecs']->table('alipy_pay')." set return_status = 'fail' where picihao = '".$batch_no."' limit 1";
	$GLOBALS['db']->query($sql);
	
	$list_sql = "select user_account_id, ua.amount, ua.user_id from ".$GLOBALS['ecs']->table('alipy_pay_tixian')." pt "
			." LEFT JOIN ".$GLOBALS['ecs']->table('user_account')." ua ON pt.user_account_id = ua.id "
			." where pt.picihao = '".$batch_no."' ";
	fwrite($fp, "\r\n==[[[$list_sql]]]\r\n");
	$list = $GLOBALS['db']->getAll($list_sql);
	//$sql = "update ".$GLOBALS['ecs']->table('user_account')." set is_paid = 0,alipay_exec_time= ".time().", alipay_return='fail'  where id = ".$v['user_account_id']." limit 1";
	foreach($list as $k=>$v){
			
				$sql = "update ".$GLOBALS['ecs']->table('user_account')." set is_paid = 1,alipay_exec_time= ".time().", alipay_return='fail'  where id = ".$v['user_account_id']." limit 1";
			
			fwrite($fp, "\r\n==[[[$sql]]]");
			$GLOBALS['db']->query($sql);
		}
	fwrite($fp, "\r\n==[[[$sql]]]");
	$GLOBALS['db']->query($sql);
	
	echo "fail";
	
    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}
fclose($fp);
?>