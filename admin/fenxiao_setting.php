<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: affiliate.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
admin_priv('fenxiao_setting');
$config = get_fenxiao();
//var_dump($config);
/*------------------------------------------------------ */
//-- 分成管理页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
 
    $smarty->assign('full_page', 1);
    
    $smarty->assign('ur_here', $_LANG['fenxiao_setting']);
    $smarty->assign('config', $config);
    $smarty->display('fenxiao.htm');
}
elseif ($_REQUEST['act'] == 'save')
{
//var_dump($_POST);
	//{{{
		$config2 = array();
		for($i=0;$i<=1; $i++){
                        $item = array();
			$ki = "sbs".$i;
			$item = array();
			//if($_POST[$ki.'_share_biaoshi']){
				//$item[$ki.'_share_people_num'] = intval($_POST[$ki.'_share_people_num']);
				$item[$ki.'_shop_amount'] = intval($_POST[$ki.'_shop_amount']);
				$item[$ki.'_leve1'] = intval($_POST[$ki.'_leve1']);
				$item[$ki.'_leve2'] = intval($_POST[$ki.'_leve2']);
				$item[$ki.'_leve3'] = intval($_POST[$ki.'_leve3']);
				$item['keys'] = $i;
			//}
                     
			$config2['dataitem']["zhitui_leve".$i] = $item;
                        unset($item);
		}
		
		$config2['tixian_limit'] = isset($_POST['tixian_limit'])?intval($_POST['tixian_limit']):1;
		$config2['tixian_shouxufei'] = isset($_POST['tixian_shouxufei'])?intval($_POST['tixian_shouxufei']):1;
		$config2['tixian_leve_basenums'] = isset($_POST['tixian_leve_basenums'])?intval($_POST['tixian_leve_basenums']):0;
		$config2['leve_fenhongnums'] = isset($_POST['leve_fenhongnums'])?intval($_POST['leve_fenhongnums']):0;
	
		$config2['on'] = 1;
		$config2['zhitui_level'] = 2;
		$config2['fenxiao_level'] = 2;
                
		//var_dump($config2);
		put_fenxiao($config2);
		
	//}}}
   
	ecs_header("Location: fenxiao_setting.php?act=list\n");
    exit;
}

/*------------------------------------------------------ */
//-- 推荐开关
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'on')
{

    $on = (intval($_POST['on']) == 1) ? 1 : 0;

    $config['on'] = $on;
    put_fenxiao($config);
    ecs_header("Location: fenxiao_setting.php?act=list\n");
}
elseif ($_REQUEST['act'] == 'del')
{
    $key = trim($_GET['id']) - 1;
    unset($config['item'][$key]);
    $temp = array();
    foreach ($config['item'] as $key => $val)
    {
        $temp[] = $val;
    }
    $config['item'] = $temp;
    $config['on'] = 1;
    $config['config']['separate_by'] = 0;
    put_fenxiao($config);
    ecs_header("Location: fenxiao.php?act=list\n");
    exit;
}

function get_fenxiao()
{
    $config = unserialize($GLOBALS['_CFG']['fenxiao']);
    empty($config) && $config = array();

    return $config;
}

function put_fenxiao($config2)
{
    $temp = serialize($config2);
    $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') .
           "SET  value = '$temp' " .
           "WHERE code = 'fenxiao'";
    
    //echo $sql;
    $GLOBALS['db']->query($sql);
    clear_all_files();
}
?>