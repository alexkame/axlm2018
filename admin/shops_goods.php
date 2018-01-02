<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
$exc   = new exchange($ecs->table("ad"), $db, 'ad_id', 'ad_name');
$shoplist = get_shops_list();
$smarty->assign('shoplist',     $shoplist);
/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 商铺
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',     '商户商品列表');
    $smarty->assign('action_link', array('text' => '添加商户商品', 'href' => 'shops_goods.php?act=add'));
  
    $smarty->assign('full_page',  1);
    $list = get_shops_goods_lists();
	//var_dump($list);

    $smarty->assign('list',     $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('shops_goods_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $smarty->assign('ur_here',     '商户商品列表');
    $smarty->assign('action_link', array('text' => '添加商户商品', 'href' => 'shops_goods.php?act=add'));
  
    $list = get_shops_goods_lists();
	
    $smarty->assign('list',     $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);


    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('shops_goods_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}
elseif ($_REQUEST['act'] == 'insert')
{
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
    admin_priv('shops_manager');
	$data = array();
    /* 初始化变量 */
    $id      = !empty($_POST['id'])      ? intval($_POST['id'])    : 0;
    $shopid      = !empty($_POST['shops_id'])      ? intval($_POST['shops_id'])    : 0;
    $good_name = !empty($_POST['good_name']) ? trim($_POST['good_name']) : '';
	$goods_desc = !empty($_POST['goods_desc']) ? trim($_POST['goods_desc']) : '';
	$status = !empty($_POST['status']) ? trim($_POST['status']) : '1';
	
	if($good_name){ $data['good_name'] = $good_name; }
	else{
		$link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg('请填写商品名', 0, $link);
	}
	if($shopid){ $data['shops_id'] = $shopid;}
	if($good_name){ $data['good_name'] = $good_name;}
	if($goods_desc){ $data['goods_desc'] = $goods_desc;}
	if($status){ $data['status'] = $status;}


    /* 添加商户logo */
	$thumb = $thumb_t = '';
	if ((isset($_FILES['thumb']['error']) && $_FILES['thumb']['error'] == 0) || (!isset($_FILES['thumb']['error']) && isset($_FILES['thumb']['tmp_name'] ) &&$_FILES['thumb']['tmp_name'] != 'none'))
	{
		$shop_thumb = basename($image->upload_image($_FILES['thumb'], 'shops_goods_imgs'));
		$data['goods_thumb'] = $shop_thumb;
		$thumb_t = " goods_thumb,";
		$thumb = "'$data[goods_thumb]',";
	}

	$data = array_filter($data);
	
	if($data && $id){//修改数据 
		$u_sql = "";
		$i = 0;
		foreach($data as $k=>$v){
			if($i >= (count($data)-1)){
				$u_sql .= " `$k` = '$v' ";
			}else{
				$u_sql .= " `$k` = '$v', ";
			}
			$i++;
		}
		if($u_sql){ $sql = "update ".$ecs->table('shops_goods')." set $u_sql where id = $id";}
	}else{ //插入数据
		$sql = "INSERT INTO ".$ecs->table('shops_goods'). " (`shops_id`,`good_name`,`goods_desc`,$thumb_t `status`,ctime)
		VALUES (
			'$data[shops_id]',
            '$data[good_name]',
            '$data[goods_desc]',
			$thumb 
			'1',
			'".time()."')";
		printsqls($sql); 	
	}
	if($sql){
		printsqls($sql);
		
		$db->query($sql);
		
		/* 记录管理员操作 */
		admin_log('添加商户商品', 'add', 'ads');

		clear_cache_files(); // 清除缓存文件
	}
    /* 提示信息 */

    $link[0]['text'] = '操作完成';
    $link[0]['href'] = 'shops_goods.php?act=list';

    sys_msg('操作完成',0, $link);

}
elseif ($_REQUEST['act'] == 'add')
{
    admin_priv('shops_manager');

    $smarty->assign('ur_here',       $_LANG['ads_add']);
    $smarty->assign('action_link',   array('href' => 'shops_goods.php?act=list', 'text' => '商户商品列表'));

    $smarty->assign('form_act', 'insert');
    $smarty->assign('action',   'insert');
    $smarty->assign('cfg_lang', $_CFG['lang']);

    assign_query_info();
    $smarty->display('shops_goods_info.htm');
}
/*------------------------------------------------------ */
//-- 广告编辑页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    admin_priv('shops_manage');
	 $id = intval($_GET['id']);
	$info = get_shops_goods_info($id);
    

    $smarty->assign('ur_here',       '商品编辑');
    $smarty->assign('action_link',   array('href' => 'shops.php?act=list', 'text' => $_LANG['ad_list']));
    $smarty->assign('info',           $info);

    assign_query_info();
    $smarty->display('shops_goods_info.htm');
}
elseif($_REQUEST['act'] == 'execstatus'){
	$id = intval($_GET['id']);
	$status = intval($_GET['status']);
	if($id && $status){
		$sql = "update ".$GLOBALS['ecs']->table('shops')." set status = '$status' where id = $id limit 1 ";
		printsqls($sql);
		$GLOBALS['db']->query($sql);
		
		admin_log('设置商户商品状态['.$id.'] 为：['.$status.']', 'exec status', 'shops');
	}
    $url = 'shops.php?act=list';

    ecs_header("Location: $url\n");
    exit;

}
/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    $id = intval($_GET['id']);
	$info = get_shops_goods_info($id);

	if($info['thumb']){
		$img_name = basename($img);
		@unlink(ROOT_PATH. DATA_DIR . '/shops_goods_imgs/'.$img_name);
	}
	if($info['imgs']){
		foreach($info['imgs'] as $k=>$img_name){
			$img_name = basename($img);
			@unlink(ROOT_PATH. DATA_DIR . '/shops_goods_imgs/'.$img_name);
		}
	}
	$sql = "update ".$GLOBALS['ecs']->table('shops')." set status = '3' where id = $id limit 1 ";
	printsqls($sql);
	$GLOBALS['db']->query($sql);
    admin_log('删除商户['.$id.']', 'remove', 'shops');

    $url = 'shops.php?act=list';

    ecs_header("Location: $url\n");
    exit;
}
function get_shops_list(){
	$sql = "select id, name from ".$GLOBALS['ecs']->table('shops')." where status = '1' ";
	//printsqls($sql);
	$data = $GLOBALS['db']->getAll($sql);
	$data = array_filter($data);
	return $data;
}
function get_shops_goods_info($gid){
	
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('shops_goods'). " WHERE id='".$gid."'";
	printsqls($sql, 'get shop goods info');
    $info = $GLOBALS['db']->getRow($sql);

    $info['name'] = htmlspecialchars($info['name']);
    /* 格式化广告的有效日期 */
    $info['ctime'] = date("Y-m-d H:i", $info['ctime']);
	$info['content'] = nl2br($info['content']);
	
	return $info?$info:false;
}
/* 获取数据列表 */
function get_shops_goods_lists()
{
 
    $filter = array();
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['keyword']	  = isset($_REQUEST['keyword'])?$_REQUEST['keyword']:'';
    $filter['shopid']	  = isset($_REQUEST['shopid'])?intval($_REQUEST['shopid']):0;
    $where = 'WHERE 1 AND sg.status != "3" '; //查询未删除的记录
 
    if(!empty($filter['keyword']))
    {
	$where.= "AND sg.good_name like '%".$filter['keyword']."%'";
    }
    if($filter['shopid']){
		if($filter['shopid'] == 'all'){
			
		}else{
			$where .= "AND shops_id = ".$filter['shopid']." ";
		}
	}
	
    /* 获得总记录数据 */
    $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('shops_goods'). ' AS sg ' .
		' Left join '.$GLOBALS['ecs']->table('shops')." s ON sg.shops_id = s.id ".	$where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = page_and_size($filter);

    /* 获得商户数据 */
    $arr = array();
    $sql = 'SELECT sg.id,sg.shops_id, sg.good_name, sg.goods_thumb, sg.goods_desc, sg.`status`, sg.ctime, s.`name` shop_name '.
            'FROM ' .$GLOBALS['ecs']->table('shops_goods'). 'AS sg ' .
			' Left join '.$GLOBALS['ecs']->table('shops')." s ON sg.shops_id = s.id "
			.$where.
            'ORDER by sg.id desc ';
	//printsqls($sql);
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
         /* 格式化日期 */
        $rows['ctime']    = date("Y-m-d H:i",$rows['ctime']);
		$arr[] = $rows;
    }

    return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>