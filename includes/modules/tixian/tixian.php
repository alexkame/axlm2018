<?php

/**
 * ECSHOP 支付宝插件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: douqinghua $
 * $Id: alipay.php 17217 2011-01-19 06:29:08Z douqinghua $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$tixian = ROOT_PATH . 'includes/modules/tixian/alipayapi.php';

if (file_exists($tixian))
{
    global $_LANG;

    include_once($tixian);
}


 $payment = payment_info($order['pay_id']);

        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

        $pay_obj    = new $payment['pay_code'];

        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
		
		/* 代码修改_start  By www.ecshop68.com */
		$payment_www_com=unserialize_config($payment['pay_config']);
		if ($payment['pay_code']=='alipay_bank')
		{
			$payment_www_com['www_ecshop68_com_alipay_bank'] = $_POST['www_68ecshop_com_bank'] ? trim($_POST['www_68ecshop_com_bank']) : "www_ecshop68_com";
			
			$pay_online = $pay_obj->get_code($order, $payment_www_com);			
		}
        
		/* 代码修改_end  By www.ecshop68.com */

        $order['pay_desc'] = $payment['pay_desc'];

        $smarty->assign('pay_online', $pay_online);