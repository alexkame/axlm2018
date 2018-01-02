<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

/* 获得请求的分类 ID */
if (!empty($_REQUEST['id']))
{
    $id = intval($_REQUEST['id']);
}

if (empty($id))
{
    $smarty->assign('shops_list', get_list());
    $smarty->display('shops_list.dwt');
    exit();
}else{
	$info = get_info($id);
	//var_dump($info);
	$smarty->assign("info", $info);
	$smarty->display('shops_info.dwt');
	exit();
}

function get_info($id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('shops') . " WHERE id = '$id'";
	$data = $GLOBALS['db']->getRow($sql); 
	if($data){
		$data['imgs'] = $data['imgs']?unserialize($data['imgs']):array();
		
		$gsql = "select * from ".$GLOBALS['ecs']->table('shops_goods')." sg where sg.shops_id = $id and status = 1 ";
		$goods = $GLOBALS['db']->getAll($gsql);
		$data['goods_list'] = $goods?$goods:array();
	}
	return $data;
}

function get_list(){
	$sql = 'select * from '.$GLOBALS['ecs']->table('shops')." where `status` = 1 order by `sort` asc , id desc ";
	//echo $sql;
	return $GLOBALS['db']->getALl($sql);
}

?>