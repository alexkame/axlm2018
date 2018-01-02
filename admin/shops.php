<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
$exc   = new exchange($ecs->table("ad"), $db, 'ad_id', 'ad_name');

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
    $smarty->assign('ur_here',     '商户列表');
    $smarty->assign('action_link', array('text' => '添加商户', 'href' => 'shops.php?act=add'));
  
    $smarty->assign('full_page',  1);

    $list = get_shops_lists();
	//var_dump($list);
    $smarty->assign('list',     $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('shops_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $list = get_shops_lists();

    $smarty->assign('list',     $list['list']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);


    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('shops_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加新广告页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    admin_priv('shops_manager');

    $ad_link = empty($_GET['ad_link']) ? '' : trim($_GET['ad_link']);
    $ad_name = empty($_GET['ad_name']) ? '' : trim($_GET['ad_name']);

    $start_time = local_date('Y-m-d');
    $end_time   = local_date('Y-m-d', gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

    $smarty->assign('ads',
        array('ad_link' => $ad_link, 'ad_name' => $ad_name, 'start_time' => $start_time,
            'end_time' => $end_time, 'enabled' => 1));

    $smarty->assign('ur_here',       $_LANG['ads_add']);
    $smarty->assign('action_link',   array('href' => 'shops.php?act=list', 'text' => '商户列表'));
    $smarty->assign('position_list', get_position_list());

    $smarty->assign('form_act', 'insert');
    $smarty->assign('action',   'add');
    $smarty->assign('cfg_lang', $_CFG['lang']);

    assign_query_info();
    $smarty->display('shops_info.htm');
}

/*------------------------------------------------------ */
//-- 新广告的处理
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
    admin_priv('shops_manager');
	$data = array();
    /* 初始化变量 */
    $id      = !empty($_POST['id'])      ? intval($_POST['id'])    : 0;
    $name = !empty($_POST['name']) ? trim($_POST['name']) : '';
	$username = !empty($_POST['username']) ? trim($_POST['username']) : '';
	$phone = !empty($_POST['phone']) ? trim($_POST['phone']) : '';
	$hangye = !empty($_POST['hangye']) ? trim($_POST['hangye']) : '';
	$address = !empty($_POST['address']) ? trim($_POST['address']) : '';
	$desc = !empty($_POST['desc']) ? trim($_POST['desc']) : '';
	$content = !empty($_POST['content']) ? trim($_POST['content']) : '';
	$status = !empty($_POST['status']) ? trim($_POST['status']) : '1';
	$is_home = !empty($_POST['is_home']) ? trim($_POST['is_home']) : '0';
	$sort = !empty($_POST['sort']) ? trim($_POST['sort']) : '0';
	
	if($name){ $data['name'] = $name; }
	else{
		$link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg('请填写商户名', 0, $link);
	}
	if($username){ $data['username'] = $username;}
	if($phone){ $data['phone'] = $phone;}
	if($hangye){ $data['hangye'] = $hangye;}
	if($address){ $data['address'] = $address;}
	if($desc){ $data['desc'] = $desc;}
	if($content){ $data['content'] = $content;}
	if($status){ $data['status'] = $status;}
	if($is_home){ $data['is_home'] = $is_home;}
	if($sort){ $data['sort'] = $sort;}

    /* 查看商户名是否有重复 */
    //$sql = "SELECT COUNT(*) FROM " .$ecs->table('shops'). " WHERE name = '$name'";
    //if ($db->getOne($sql) > 0)
    //{
    //    $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
    //    sys_msg('商户名已存在', 0, $link);
    //}

    /* 添加商户logo */
	$thumb = $thumb_t = '';
	if ((isset($_FILES['thumb']['error']) && $_FILES['thumb']['error'] == 0) || (!isset($_FILES['thumb']['error']) && isset($_FILES['thumb']['tmp_name'] ) &&$_FILES['thumb']['tmp_name'] != 'none'))
	{
		$shop_thumb = basename($image->upload_image($_FILES['thumb'], 'shopsimgs'));
		$data['thumb'] = $shop_thumb;
		$thumb_t = " thumb,";
		$thumb = "'$data[thumb]',";
	}

	/* 商户店招 */
	$imgs_str = $imgs_t = '';
	$imgs = array();
	for($i=1; $i<=5; $i++){
		$img_num = "img_".$i;
		if ((isset($_FILES[$img_num]['error']) && $_FILES[$img_num]['error'] == 0) || (!isset($_FILES[$img_num]['error']) && isset($_FILES[$img_num]['tmp_name'] ) &&$_FILES[$img_num]['tmp_name'] != 'none'))
		{
			$img_1 = basename($image->upload_image($_FILES[$img_num], 'shopsimgs'));
			$imgs[$img_num] = $img_1;
		}
	}
	$imgs = array_filter($imgs);
	if(count($imgs)){
		$data['imgs'] = serialize($imgs);
		$imgs_t = " imgs,";
		$imgs_str = "'$data[imgs]',";
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
		if($u_sql){ $sql = "update ".$ecs->table('shops')." set $u_sql where id = $id";}
	}else{ //插入数据
		$sql = "INSERT INTO ".$ecs->table('shops'). " (`name`,`desc`,`content`,`username`,hangye,address,phone,$thumb_t $imgs_t `status`,ctime,is_home)
		VALUES ('$data[name]',
            '$data[desc]',
            '$data[content]',
            '$data[username]',
            '$data[hangye]',
            '$data[address]',
            '$data[phone]',
			$thumb $imgs_str
			'1',
			'".time()."',
			'".$is_home."')";
	}
	if($sql){
		printsqls($sql);
		
		$db->query($sql);
		
		/* 记录管理员操作 */
		admin_log('添加商户', 'add', 'ads');

		clear_cache_files(); // 清除缓存文件
	}
    /* 提示信息 */

    $link[0]['text'] = '操作完成';
    $link[0]['href'] = 'shops.php?act=list';

    sys_msg('操作完成',0, $link);

}

/*------------------------------------------------------ */
//-- 广告编辑页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    admin_priv('shops_manage');
	 $id = intval($_GET['id']);
	$info = get_shops_info($id);
    

    $smarty->assign('ur_here',       '商户编辑');
    $smarty->assign('action_link',   array('href' => 'shops.php?act=list', 'text' => $_LANG['ad_list']));
    $smarty->assign('info',           $info);

    assign_query_info();
    $smarty->display('shops_info.htm');
}
elseif($_REQUEST['act'] == 'execstatus'){
	$id = intval($_GET['id']);
	$status = intval($_GET['status']);
	if($id && $status){
		$sql = "update ".$GLOBALS['ecs']->table('shops')." set status = '$status' where id = $id limit 1 ";
		printsqls($sql);
		$GLOBALS['db']->query($sql);
		
		admin_log('设置商户状态['.$id.'] 为：['.$status.']', 'exec status', 'shops');
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
	$info = get_shops_info($id);

	if($info['thumb']){
		$img_name = basename($img);
		@unlink(ROOT_PATH. DATA_DIR . '/shopsimgs/'.$img_name);
	}
	if($info['imgs']){
		foreach($info['imgs'] as $k=>$img_name){
			$img_name = basename($img);
			@unlink(ROOT_PATH. DATA_DIR . '/shopsimgs/'.$img_name);
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
function get_shops_info($shop_id){
	
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('shops'). " WHERE id='".$shop_id."'";
	printsqls($sql, 'get shop info');
    $info = $GLOBALS['db']->getRow($sql);

    $info['name'] = htmlspecialchars($info['name']);
    /* 格式化广告的有效日期 */
    $info['ctime'] = date("Y-m-d H:i", $info['ctime']);
	$info['imgs'] = array_filter($info['imgs']?unserialize($info['imgs']):array());
	$info['content_format'] = nl2br($info['content']);
	
	return $info?$info:false;
}
/* 获取数据列表 */
function get_shops_lists()
{
 
    $filter = array();
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
    $filter['keyword']	  = $_REQUEST['keyword'];
    $where = 'WHERE 1 AND status != "3" '; //查询未删除的记录
 
    if(!empty($filter['keyword']))
    {
	$where.= "AND name like '%".$filter['keyword']."%'";
    }
    
	
    /* 获得总记录数据 */
    $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('shops'). ' AS s ' . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = page_and_size($filter);

    /* 获得商户数据 */
    $arr = array();
    $sql = 'SELECT s.id, s.name, s.desc, s.content, s.thumb, s.imgs, s.username,s.phone, s.hangye, s.address, s.ctime, s.status,s.is_home,s.`sort`  '.
            'FROM ' .$GLOBALS['ecs']->table('shops'). 'AS s ' .
			$where.
            'ORDER by s.id desc ';
	//printsqls($sql);
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
       

         /* 格式化日期 */
        $rows['ctime']    = date("Y-m-d H:i",$rows['ctime']);
		if($rows['imgs']){
			$imgs = unserialize($rows['imgs']);
		}
        $arr[] = $rows;
    }

    return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>