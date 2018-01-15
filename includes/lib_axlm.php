<?php 
require(ROOT_PATH . 'includes/lib_axlm_core.php');

function get_pc_goods_info($gid){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $sql = "SELECT goods_id,goods_sn,goods_name,shop_price,goods_brief,goods_thumb,goods_img FROM " . $ecs->table('goods')."WHERE goods_id = '$gid'";
//    echo $sql;
    $data = $db->getRow($sql);
    return $data;
}
//{{{ axlm
//购物账户      
function axlmpc($user_id,$order_id,$order_amount,$good_amount,$paytype=''){
    
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $usql = "SELECT * FROM " . $ecs->table('pc_user')."WHERE uid = '$user_id'";
    
    $pcuserinfo = $db->getRow($usql);
    $tuijianren_user_id = $pcuserinfo['tuijianren_user_id'];
    $jiedianren_user_id = $pcuserinfo['jiedianren_user_id'];
    $fuwuzhongxin_user_id = $pcuserinfo['fuwuzhongxin_user_id'];

    if($order_id){
		//生成订单后，即激活账户
		$active_sql = "update ".$ecs->table("pc_user")." set status = 1 where uid = $user_id ";
        $db->query($active_sql);
		
        //获得该订单中女性专区的产品总金额
        $is_zhuanqu_product = check_zhuanqu_product($order_id);
//        echo $is_zhuanqu_product."]]]]";
        if($is_zhuanqu_product){ //专区产品走这个流程
//            echo "axlm pc jisuan <br>";
            $good_amount = $is_zhuanqu_product;
            
            //只有用户二次购物，且够6000的也返积分，这里需要判断是否是二次购物，也就是账户已经激活了
			if($pcuserinfo['status'] == 1){
				 if($good_amount > 6000){
					pc_set_guanli_butie($user_id,"goods");
					save_jifenbi_fanli($user_id,$good_amount/2,'购物赠积分币'); //二次进货打五折
					//step 8: 服务中心补贴，升级后， 查看是否给服务中心返利
					if($fuwuzhongxin_user_id){
						pc_set_fuwuzhongxin_butie($fuwuzhongxin_user_id,$good_amount/2);
					}
				}else{ //如果二次进货，但是不够6000，则不积分 ， changed 2018-01-15
					//step 8: 服务中心补贴，升级后， 查看是否给服务中心返利
					if($fuwuzhongxin_user_id){
						pc_set_fuwuzhongxin_butie($fuwuzhongxin_user_id,$good_amount/2);
					}
				}
			
			}else{//账户未激活
				save_jifenbi_fanli($user_id,$good_amount,'购物赠积分币');
                //step 8: 服务中心补贴，升级后， 查看是否给服务中心返利
                if($fuwuzhongxin_user_id){
                    pc_set_fuwuzhongxin_butie($fuwuzhongxin_user_id,$good_amount);
                }
			}
            
            
        }else{ //不是专区的产品才给推荐奖励
            //step 9: 购物给直推人返利,购买非专区的产品才给直推人返利
            
            $parent_id = $db->getOne("select parent_id from ".$ecs->table('users')." where user_id = ".$user_id);
            if($parent_id && $parent_id != $user_id){
                pc_set_zhitui_fanli($user_id,$parent_id,$order_id,$good_amount);
            }
            save_jifenbi_fanli($user_id,$good_amount,'购物赠积分币');
            
        }
      
        //step 2: 设置金融账户变更，见3,4,5,6,7,8   

        //step 7: 联盟商家补贴
        
            
        if($paytype == '现金币'){
            change_account_info($user_id, "xianjinbi", "-", $order_amount);
        }elseif($paytype == '消费币'){
            change_account_info($user_id, "xiaofeibi", "-", $order_amount);
        }
    }
 
}
//run it when user upgroup
//order_id: 符合双轨的订单金额（专区产品的）,
//return 0: 激活失败
//return 1: 激活成功
//return 2: 已经激活过了
function axlmpc_user_active($user_id,$order_id){
    pc_log($order_id,"符合的oid == 进入激活账户");
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];          
    
        if($order_id){
                //获得该订单中女性专区的产品总金额
                $is_zhuanqu_product = check_zhuanqu_product($order_id);
                pc_log($is_zhuanqu_product," 专区产品价格");
        //        echo $is_zhuanqu_product."]]]]";
                if($is_zhuanqu_product){ //专区产品走这个流程
                    $good_amount = $is_zhuanqu_product;
                    //step 1: 激活账户，设置pc_user.status = 1 ,  level=2,3,4
                    $level_sql = "SELECT * FROM " . $ecs->table('pc_user_level')." where level_limit_note <= $good_amount order by level_limit_note desc";
                    $level_list = $db->getRow($level_sql);
                    pc_log($level_list,"level_list");
                    $level = 0;
                    if($level_list){
                        $level = $level_list['id'];
                    }

                    $status = 1;
                    pc_set_user_status($user_id, $status, $level,'升级账户');
                    
                    //step 8: 服务中心补贴，升级后， 查看是否给服务中心返利
                    $usql = "SELECT fuwuzhongxin_user_id FROM " . $ecs->table('pc_user')."WHERE uid = '$user_id'";
                    $fuwuzhongxin_user_id = $db->getOne($usql);
                    pc_set_fuwuzhongxin_butie($fuwuzhongxin_user_id,$good_amount);
            
                }
                return 1;
        }
        
        return 0;
}
function set_user_tree($user_id){
//echo "<font style='color:red'>推广</font><br>";    
        //step 3: 推广
        pc_set_tuiguang_butie($user_id);
//echo "<font style='color:red'>服务</font><br>"; 
        //step 4: 服务
        pc_set_fuwu_butie($user_id);
//echo "<font style='color:red'>见点</font><br>";         
        //step 5: 见点
        pc_set_jiandian_butie($user_id);
//echo "<font style='color:red'>管理补贴</font><br>";              
        //step 6: 管理补贴       
        pc_set_guanli_butie($user_id,"expends");
        
        
}
function pc_set_user_status($user_id, $status,$level,$note=''){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    if($level != 0){
        $sql = "update ".$ecs->table("pc_user")." set status = 1 , level = $level where uid = $user_id ";
        $db->query($sql);
        pc_save_user_change_log($user_id,'status',$status,$note,0);
        pc_save_user_change_log($user_id,'level',$level,'',0);
    }else{
        $sql = "update ".$ecs->table("pc_user")." set status = 1 where uid = $user_id ";
        $db->query($sql);
        pc_save_user_change_log($user_id,'status',$status,$note,0);
    }
    pc_log($sql,"pc_set_user_status");
    //echo $sql;
    if($level>0){
        set_user_tree($user_id);
    }
}

//系统变化， adminid = 0
function pc_save_user_change_log($uid,$field_name,$new_value,$note,$adminid){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    
    $sql = "SELECT $field_name FROM " . $ecs->table('pc_user')."WHERE uid = '$uid'";
    $row = $db->getRow($sql);
    $original_value = 0;
    if($row){ $original_value = $row[$field_name];}
    
    $sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,type,name,original_value,new_value,note,adminid,ctime)values(".
            "'".$uid."',".
            "'baseinfo',".
            "'".$field_name."',".
            "'".$original_value."',".
            "'".$new_value."',".
            "'".$note."',".
            "'".$adminid."',".
            "'".time()."'".
            ")";
    //echo "<br>$sql<br>";
    $db->query($sql);
    
}

function get_user_parent_tuijian_array($uid,&$parent_array){
    $info = get_pc_user_allinfo($uid);
    //var_dump($info);
    if(!$info['tuijianren_user_id']){
        
    }else{
        $parent_array[] = $info['tuijianren_user_id'];
        get_user_parent_tuijian_array($info['tuijianren_user_id'], $parent_array);
    }
    return $parent_array;
}



function get_top_user_same_parent($cengNum,$uid1,$uid2){
     $uid1_info = get_pc_user_allinfo($uid1);
     $uid2_info = get_pc_user_allinfo($uid2);
     
     $uid1_p_arr = array();
     $uid2_p_arr = array();
     $uid1_p_arr = get_user_parent_array($uid1,$uid1_p_arr);
     $uid2_p_arr = get_user_parent_array($uid2,$uid2_p_arr);
//     var_dump($uid1_p_arr);
//     var_dump($uid2_p_arr);
    if($cengNum){
        
    }
    $same_parent = 0;
    if(count($uid1_p_arr)>count($uid2_p_arr)){
        if($uid1_p_arr){
            foreach($uid1_p_arr as $k=>$v){
                if($v == $uid2_p_arr[$k]){
                    $same_parent = $v;
                    break;
                }
            }
        }
    }else{
        if($uid2_p_arr){
            foreach($uid2_p_arr as $k=>$v){
                if($v == $uid1_p_arr[$k]){
                    $same_parent = $v;
                    break;
                }
            }
        }
    }
    
    return $same_parent;
}


//value = yes,no,beyes
function save_user_status_log($uid,$field,$value){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    
    $sql = "select * from ".$ecs->table('pc_user_status_log')." where uid = $uid ";
    $check = $db->getRow($sql);
    if($check){
        if($field == 'fuwu'){
                $usql = "update ".$ecs->table('pc_user_status_log')." set is_fuwu = '".$value."', fuwu_used_time = ".time()." where uid = $uid ";
        }elseif($field == 'tuiguang'){
                $usql = "update ".$ecs->table('pc_user_status_log')." set is_tuiguang = '".$value."', tuiguang_used_time = ".time()." where uid = $uid ";
        }elseif($field == 'jiandian'){
                $usql = "update ".$ecs->table('pc_user_status_log')." set is_jiandian = '".$value."', jiandian_used_time = ".time()." where uid = $uid ";
        }elseif($field == 'guanli'){
                $usql = "update ".$ecs->table('pc_user_status_log')." set is_guanli = '".$value."', guanli_used_time = ".time()." where uid = $uid ";
        }
    }else{
        if($field == 'fuwu'){
                $usql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_fuwu,fuwu_used_time)values('".$uid."','".$value."','".time()."') ";
        }elseif($field == 'tuiguang'){
                $usql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_tuiguang,tuiguang_used_time)values('".$uid."','".$value."','".time()."') ";
        }elseif($field == 'jiandian'){
                $usql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_jiandian,jiandian_used_time)values('".$uid."','".$value."','".time()."') ";
        }elseif($field == 'guanli'){
                $usql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_guanli,guanli_used_time)values('".$uid."','".$value."','".time()."') ";
        }
    }
    pc_log($usql,"save_user_status_log");
    return $db->query($usql);
}

function get_top_user($uid){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    
    $sql = "select jiedianren_user_id from ".$ecs->table('pc_user')." where uid = $uid ";
    $jiedian = $db->getRow($sql);
//    pc_log($sql,'get_top_user');
    if(!$jiedian){ return 0;}
    if($jiedian['jiedianren_user_id'] == 0){
        return $uid;
    }else{
        return get_top_user($jiedian['jiedianren_user_id']);
    }
}


function getAllUserList($uid,&$data){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];

	//$sql = "select uid , tuijianren_user_id, jiedianren_user_id, leftright, u.user_name,u.reg_time , from_unixtime(u.reg_time,'%Y-%m-%d %H-%i-%s') as reg_time_format from ".$ecs->table('pc_user')." pu left join ".$ecs->table('users')." u on pu.uid = u.user_id where tuijianren_user_id = ".$uid;
	//echo "<br>".$sql;
         $sql = "SELECT u.*,log.is_tuiguang,log.is_fuwu,log.is_jiandian, log.is_guanli FROM " . $ecs->table('pc_user')." u left join ".$ecs->table('pc_user_status_log')." log on u.uid = log.uid WHERE u.tuijianren_user_id = '$uid' and u.status = 1 ";
        //echo $sql;
 
	$nextuser = $db->getAll($sql);
	
	if($nextuser){
		foreach($nextuser as $k=>$v){
                    $data[] = $v;
                    getAllUserList($v['uid'],$data);
		}
	}else{
		return $data;
	}
}


function pc_log($body,$title=''){
    $test = 0;
    if($test){
        echo "<br>===== {{{ $title ==========<br>";
        if(is_array($body)){
            var_dump($body);
        }else{
            echo "<br>$body<br>";
        }
        echo "<br>===== }}} ==========<br>";
    }
}


//}}}

//{{{ 服务补贴


//{{{服务补贴计算
function pc_set_fuwu_butie($uid){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $jiangxiang = "fuwubutie";
    $pcuserinfo = get_pc_user_allinfo($uid);
    if(intval($pcuserinfo['level'])<=2){ //只有当前用户是1.3的才会返
        return 0;
    }
//    $cenginfo = getUserCentNum($uid);
//    var_dump($cenginfo);
//    $cengNum = $cenginfo['cengNum'];
//    $cengList = $cenginfo['cengList'];
    //先获得所有的接点人直线数组，每一层循环判断
    $jiedianren_user_array = get_user_parent_array($uid);
    
    //var_dump($jiedianren_user_array);
   
    foreach($jiedianren_user_array as $k=>$v){
        //$v 即是接点人
        $ceng = $k+1;
        //echo "接点人 ： $v, 当前层 ： $ceng <br>";
        
        $user_ceng_stauts = pc_get_user_status_info($v,"fuwubutie",$ceng);
        
        if(!$user_ceng_stauts){ //这个层的这个状态没设置过，则进行
            $data = pc_get_user_ceng_leftright_user($v, $ceng);
            //var_dump($data);
            $leftnum = intval($data['left']);
            $rightnum = intval($data['right']);
            //echo "左边：$leftnum ; 右边 ：$rightnum<br>";
            if($leftnum/$rightnum>=1 || $rightnum/$leftnum>=1){ //服务是左右达成1:1则拿， 只要左右有人
                $userinfo = get_pc_user_allinfo($v);
                
               // var_dump($userinfo);
                if($userinfo['level'] > 2){
                    save_fuwu_fanli($v,$userinfo);
                    pc_save_user_status_info($v,$ceng, $jiangxiang,json_encode($data));
                }
            }
//            echo "<br>===============<br>";
        }
        
//        if($k == 0){
//            $userinfo = get_pc_user_allinfo($v);
//            if($userinfo['level'] > 2){
//                save_fuwu_fanli($v,$userinfo);
//            }
//        }else{
//           
//        }
    }
    
    
//    echo "层数:".$cengNum;
    /*
//    if($uid){
//        $pcuserinfo = get_pc_user_allinfo($uid);
//        //pc_log($pcuserinfo,"pc_set_fuwu_butie");
//        $tuijianren_user_id = $pcuserinfo['tuijianren_user_id'];
//        $jiedianren_user_id = $pcuserinfo['jiedianren_user_id'];
//        
//        $level = intval($pcuserinfo['level']);
//        $is_fuwu = $pcuserinfo['is_fuwu'];
//        $cengUser = $cengList[$cengNum];
//        
////        var_dump($cengUser);
//        if($cengUser){
//            $is_fuwu_pengdui = false;
//            foreach($cengUser as $k=>$v){
//                //判断当前层是否碰过对
//                if($v['is_fuwu'] == 'yes'){
//                    $is_fuwu_pengdui = true;
//                }
//            }
//            //已经碰对
//            if($is_fuwu_pengdui){
//                //save_user_status_log($uid,"fuwu",'beyes');
//                return 1;
//            }else{
//                //当前层没有碰过对,则说明，这层只有一个人或者还没有人
//                //只有高级会员即以上才能参与服务补贴, 且没有参与过的，也只能返利给高级会员及之上，每层一次， 最先达成的碰， 碰完这层结束
//                foreach($cengUser as $k=>$v){
////                    echo $v['uid']."(".$v['level'].")--VS--".$uid."<br>";
//                    //碰对的和自己不能是同一个人， 且两人必须同时达到高级及以上
//                    if($v['uid'] != $uid && $v['level']>2 && $level>2){
//                        //碰对
//                        //情况1， 同一个接点人下的左右区碰
//                        if($v['jiedianren_user_id'] == $jiedianren_user_id){
//                            $jiedianren_info = get_pc_user_allinfo($jiedianren_user_id);
//                            if($jiedianren_info && $jiedianren_info['level']>2){//返利用户必须是高级及以上
//                                save_fuwu_fanli($jiedianren_user_id,$jiedianren_info);
//                                save_user_status_log($v['uid'], "fuwu", 'yes');
//                                save_user_status_log($uid, "fuwu", 'yes');
//                            }
//                        }else{ //不是同一个接点人
//                         
//                            $p_uid = get_top_user_same_parent($cengNum,$v['uid'],$uid);
//                            $p_uid_info = get_pc_user_allinfo($p_uid);
////                            echo "<br>puid".$p_uid."<br>";
//                            if($p_uid_info && $p_uid_info['level']>2){ //返利用户必须是高级及以上
//                                save_fuwu_fanli($p_uid,$p_uid_info);
//                                save_user_status_log($v['uid'], "fuwu", 'yes');
//                                save_user_status_log($uid, "fuwu", 'yes');
//                            }
//                        }
//                    }
//                }
//
//            }
//            
//        }
//    }
//    
    */
}


//{{{见点补贴
function pc_set_jiandian_butie($uid){
    pc_log("见点奖");
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $userinfo = get_pc_user_allinfo($uid);
    
    if(intval($userinfo['level'])<=2){ //只有当前用户是1.3的才会上级返见点奖，5000的不给上级返见点奖
        return 0;
    }
  
    $jiandian_config = $db->getAll("select sname,svalue from ".$ecs->table('pc_config')." where sname in ('jiandian_limit_ceng','jiandian_left_danwei','jiandian_bili_left','jiandian_right_danwei','jiandian_bili_right')");
    $jiandain_config_array = array();
    if($jiandian_config){
       foreach($jiandian_config as $k=>$v){
           $jiandain_config_array[$v['sname']] = $v['svalue'];
       }
    }
  
    $jiandian_bili_left = $jiandain_config_array['jiandian_bili_left'];
    $jiandian_bili_right = $jiandain_config_array['jiandian_bili_right'];
    
    
    $jiedianren_user_array = get_user_parent_leftright_array($uid);
    if(!$jiedianren_user_array){return 0;}
    pc_log($jiedianren_user_array,"见点奖人");
//    var_dump($jiedianren_user_array);
    if($jiedianren_user_array){
        foreach($jiedianren_user_array as $k=>$v){
//            echo "<br>jiandian ===>".$v['uid']."===".$v['level']."<br>";
            if(intval($v['level'])>2){
                $checksql = "select id from ".$ecs->table('pc_user_jiandian_log')." where uid = ".$v['uid']." and from_uid = $uid";
//                echo "<br>".$checksql."<br>";
                $checkflag = $db->getOne($checksql);
                if($checkflag){
                    continue;
                }
                $return_bili = "";
                $type = $v['leftright'];
                if($type == 'left'){
                    $return_bili = $jiandian_bili_left;
                }elseif($type == 'right'){
                    $return_bili = $jiandian_bili_right;
                }
                save_jiandian_fanli($v['uid'],$type,$return_bili);
                $sql = "insert into ".$ecs->table("pc_user_jiandian_log")."(uid,from_uid,ctime)values(".
                        "'".$v['uid']."',".
                        "'".$uid."',".
                        "'".time()."'".
                        ")";
    //            echo "<br>".$sql;
                $db->query($sql);
            }
        }
    }
    return 1;
    /*
//    
//    $checksql = "select uid,is_jiandian from ".$ecs->table('pc_user_status_log')." where uid = $uid ";
//    //echo $checksql;
//    $checksql = $db->getRow($checksql);
//    if($checksql && $checksql['is_jiandian'] == 'yes'){
//        return 1;
//    }
//
//    $parent_array = array();
//    $parent_array = get_user_parent_array($uid,$parent_array);
//    $pcuserinfo = get_pc_user_allinfo($uid);
//     
//    $jiandian_config = $db->getAll("select sname,svalue from ".$ecs->table('pc_config')." where sname in ('jiandian_limit_ceng','jiandian_left_danwei','jiandian_bili_left','jiandian_right_danwei','jiandian_bili_right')");
//    $jiandain_config_array = array();
//    if($jiandian_config){
//       foreach($jiandian_config as $k=>$v){
//           $jiandain_config_array[$v['sname']] = $v['svalue'];
//       }
//    }
//    
//    $parent_array = array_reverse($parent_array);
//    $type = '';
//    if(count($parent_array) == 1){
//        return 1;
//    }elseif(count($parent_array)>=2){
//        $ceng2user = $parent_array[1];
//        $ceng2userinfo = get_pc_user_allinfo($ceng2user);
//        if($ceng2userinfo['leftright'] == 'left'){
//            $type = 'left';
//        }else{
//            $type = 'right';
//        }
//    }
//    
//    $jiandian_bili_left = $jiandain_config_array['jiandian_bili_left'];
//    $jiandian_bili_right = $jiandain_config_array['jiandian_bili_right'];
//    
//    $return_bili = "";
//    if($type == 'left'){
//        $return_bili = $jiandian_bili_left;
//    }elseif($type == 'right'){
//        $return_bili = $jiandian_bili_right;
//    }
//    
//    if($return_bili){
//        
//        if($checksql){
//            $sql = "update ".$ecs->table('pc_user_status_log')." set is_jiandian = 'yes', jiandian_used_time = '".time()."' where uid = $uid";
//        }else{
//            $sql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_jiandian, jiandian_used_time)values('".$uid."','yes','".time()."')";
//        }
//        $db->query($sql);
//        foreach($parent_array as $k=>$u){
//            $check_level_sql = "select level from ".$ecs->table('pc_user')." where uid = $u ";
//            $u_level = $db->getRow($check_level_sql);
//            if($u_level && $u_level['level']>2){
//                save_jiandian_fanli($u,$type,$return_bili);
//            }else{
//                continue;
//            }
//            $check_level_sql = "";
//        }
//    }
    */
}
function save_jiandian_fanli($uid,$type,$return_bili){
	$db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
       
        $userinfo = get_pc_user_allinfo($uid);
        
        $change_value = intval($return_bili);
        if($type == "left"){
            $original_value = intval($userinfo['account_xiaofeibi']);
        }else{
            //$original_value = intval($userinfo['account_xiaofeibi_zhuanqu']);
            $original_value = intval($userinfo['account_aixinbi']);
        }
        $new_value = $original_value + $change_value;
        if($type == "left"){
                $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$uid;
                $db->query($sql);
                 pc_log($sql,'save jiandain fanli');
                $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
                        "'".$uid."',".
                        "'account_xiaofeibi',".
                        "'".$original_value."',".
                        "'".$change_value."',".
                        "'".$new_value."',".
                        "'见点奖赠消费币',".
                        "'0',".
                        "'".time()."' ".
                ")";
                 pc_log($sql,'save jiandain fanli');
        }else{
            //右区见点，返专区消费币，即爱心币
                $sql = "update ".$ecs->table('pc_user')." set account_aixinbi = ".$new_value." where uid = ".$uid;
                $db->query($sql);
                 pc_log($sql,'save jiandain fanli');
                $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
                        "'".$uid."',".
                        "'account_aixinbi',".
                        "'".$original_value."',".
                        "'".$change_value."',".
                        "'".$new_value."',".
                        "'见点奖赠爱心币',".
                        "'0',".
                        "'".time()."' ".
                ")";
                 pc_log($sql,'save jiandain fanli');
        } 
	$db->query($sql);
}
//}}}见点补贴

//{{{ 管理补贴
//消费满6000，折合3000pv
//发展下线，1.3w,折合3000pv
function pc_set_guanli_butie($uid,$type){
    pc_log("管理奖".$type);
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    pc_log('','pc_set_guanli_butie');
    
    $userinfo = get_pc_user_allinfo($uid);
    if(intval($userinfo['level'])<=2){ //只有当前用户是1.3的才会返
        return 0;
    }
    $tuijian_parent_array = array();
    $tuijian_parent_array = get_user_parent_tuijian_array($uid);
    
    pc_log($tuijian_parent_array,'tuijian_paretn_array');
    
    $pv = 3000;
     //echo "9999";
    if($type == "goods"){
        //购物只给自己返积分,并且已经是高级会员及以上了
        if($userinfo['level']>2){
            save_pv_fanli($uid, $pv, '购物返积分');
        }
    }elseif($type == 'expends'){
        //给直推线上的人返积分
       
        if($tuijian_parent_array){
            //{{{ 返现金币
                save_pv_fanli_xianjinbi($tuijian_parent_array,$pv);
            //}}} 返现金币
                
            $sql = "select uid, role,level from ".$ecs->table('pc_user')." where uid in (".implode(",",$tuijian_parent_array).") order by uid desc ";
                    //echo $sql;
            $ulist = $db->getAll($sql);
            if($ulist){
                
            //{{{给直推人返积分    
                foreach($ulist as $k=>$v){
                  //echo "<br>".$v['uid']."-----".$v['role']."------".$v['level']."<br>";
                    $level = $v['level'];
                    if($level > 2){ //只给高级会员及以上返
                        
                            $pv = 3000;
                            save_pv_fanli($v['uid'], $pv, '推广会员赠积分');
                        
                    }
                }
            //}}} end 直推人返积分
                  
            }
        }
    }
}

function save_pv_fanli($uid,$pv,$note){
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
       
        $userinfo = get_pc_user_allinfo($uid);
        
        $change_value = intval($pv);
        $original_value = intval($userinfo['account_jifen']);
        
        $new_value = $original_value + $change_value;
        
        $sql = "update ".$ecs->table('pc_user')." set account_jifen = ".$new_value." where uid = ".$uid;
        $db->query($sql);
        pc_log($sql,'save pv fanli');
        $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
                "'".$uid."',".
                "'account_jifen',".
                "'".$original_value."',".
                "'".$change_value."',".
                "'".$new_value."',".
                "'".$note."',".
                "'0',".
                "'".time()."' ".
        ")";

	$db->query($sql);
        
        //每次保存完积分后，更新下积分用户状态
        shengji_pv_account($uid);
}

function shengji_pv_account($uid){
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
       
        $userinfo = get_pc_user_allinfo($uid);
        $user_pv = $userinfo['account_jifen'];
        
        $role_sql = "select id,role_name,role_pv, role_limit from ".$ecs->table('pc_user_role')." where role_pv <= ".intval($user_pv)." order by id desc";
        pc_log($role_sql,"shengji_pv_account");
        $nextrole = $db->getRow($role_sql);
        
        $update_flag = 0;
        
//        var_dump($nextrole);
        if($nextrole && $nextrole['id'] != $userinfo['role']){
            if($nextrole['id'] >= 3){ //见习经理开始，除了积分够之外，还要判断是否满足ab市场各有一个下级
                $xiaji_role = $nextrole['id']-1;
                
                            $left_sql = "select uid from ".$ecs->table('pc_user')." where jiedianren_user_id = $uid and leftright = 'left'";
                            $right_sql = "select uid from ".$ecs->table('pc_user')." where jiedianren_user_id = $uid and leftright = 'right'";
//                            echo $left_sql."<br>";
//                            echo $right_sql."<br>";;
                            $leftuser = $db->getOne($left_sql);
                            $rightuser = $db->getOne($right_sql);
                            $leftuserid = $leftuser?$leftuser:0;
                            $rightuserid = $rightuser?$rightuser:0;
                            $left_array = array();
                            $right_array = array();
//                            echo "<br>left user id : $leftuserid <br>right user id : $rightuserid<br>";
                            if($leftuserid){
                                $left_arr = getCengDataByUid($leftuserid);
                                foreach($left_arr as $kk=>$vv){
                                    foreach($vv as $kkk=>$vvv){
                                        $left_array[] = $vvv;
                                    }
                                }    
                            }
                            
                            if($rightuserid){
                                $right_arr = getCengDataByUid($rightuserid);
                                foreach($right_arr as $kk=>$vv){
                                    foreach($vv as $kkk=>$vvv){
                                        $right_array[] = $vvv;
                                    }
                                }   
                            }
//                            var_dump($left_array);echo "<br><br>";
//                            var_dump($right_array);echo "<br><br>";
                            if($left_array && $right_array){
                                $left_arr_str = implode(",",$left_array);
                                $right_arr_str = implode(",",$right_array);
                                $sql_left = "select uid from ".$ecs->table('pc_user')." where role = ".$xiaji_role." and uid in (".$left_arr_str.")";
                                $sql_right = "select uid from ".$ecs->table('pc_user')." where role = ".$xiaji_role." and uid in (".$right_arr_str.")";
//                                echo "<br>===".$sql_left;
//                                echo "<br>===".$sql_right;
                                $is_left = $db->getRow($sql_left);
                                $is_right = $db->getRow($sql_right);
                                if($is_left && $is_right){
                                    $update_flag = 1;
                                }
                            }
                            
                            
            }else{
                $update_flag = 1;
            }
//            echo "====".$update_flag;
            if($update_flag){
                    $sql = "update ".$ecs->table('pc_user')." set role = ".$nextrole['id']." where uid = $uid ";
                    $db->query($sql);

                    $sql = "insert into ".$ecs->table('pc_user_change_log')."(uid,name,type,original_value,new_value,note,ctime)values(".
                                "'".$uid."',".
                                "'baseinfo',".
                                "'role',".
                                "'".$userinfo['role']."',".
                                "'".$nextrole['id']."',".
                                "'pv积分升级',".
                                "'".time()."'"
                            .")";
                    $db->query($sql);
            }
        }
    
}

function save_pv_fanli_xianjinbi($tuijian_parent_array, $pv){
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    //查询有身份的pv用户,倒序排放， 从下级向上,高级用户才返现金币
    $sql = "select uid, role,level from ".$ecs->table('pc_user')." where uid in (".implode(",",$tuijian_parent_array).") and role>0 and level>2 order by uid desc";
    pc_log($sql,"== fanli array ==");
    $ulist = $db->getAll($sql);
    $data = array();
    foreach($ulist as $k=>$v){
        $data[] = $v;
    }

    //如果上级只有一个人
    $fanli_array = array();
    foreach($data as $k=>$v){
        if($fanli_array){
            //如果上级和上上级身份一样
            $flag = true;
            foreach($fanli_array as $kk=>$vv){
                //echo "<br>$kk ==>role ==> ".$v['uid']."==".$vv['role']."====".$v['role']."<br>";
                if($vv['role']>=1 && $vv['role'] == $v['role']){
                    $flag = false;
                   break;
                }
            }
            if($flag){
                $fanli_array[] = $v;
            }
        }else{
            $fanli_array[] = $v;
        }
    }
  
    pc_log($fanli_array,"== fanli array ==");
    $role_sql = "select * from ".$ecs->table('pc_user_role')." ";
    $role = $db->getAll($role_sql);
    $role_array = array();
    if($role){
        foreach($role as $k=>$v){
            $role_array[$v['id']] = $v['fanli'];
        }
    }
    
    foreach($fanli_array as $k=>$v){
        if($k == 0){
            $v['fanli'] = $role_array[$v['role']];
        }else{
            $v['fanli'] = $role_array[$v['role']] - $role_array[$fanli_array[$k-1]['role']];
        }
        $fanli_array[$k] = $v;
    }
    
 
    //返现金币
    if($fanli_array){
        pc_log($fanli_array,"save_pv_fanli_xianjinbi");
        foreach($fanli_array as $k=>$v){
            //echo "<br>".$v['uid']."===".(float)$v['fanli']."===".(int)$pv;
            $fanli = (float)$v['fanli']* (int)$pv;
            save_pv_xianjianbi_fanli($v['uid'], $fanli);
        }
    }
}


function save_pv_xianjianbi_fanli($uid,$fanli){
	$db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
        pc_log($fanli,'save_pv_xianjianbi_fanli');
        
        $userinfo = get_pc_user_allinfo($uid);
        if(intval($userinfo['level'])<=2){ //只有当前用户是1.3的才会返
            return 0;
        }
        if(!$fanli){ return 0;}
	$original_value = intval($userinfo['account_xianjinbi']);
        $change_value = floatval($fanli);
        
        //保存现金币，扣除费用
        $kouchu_aixinbi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_aixinbi'");
        $kouchu_shuishou = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_shuishou'");
        $kouchu_xiaofeibi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_xiaofeibi'");
        
        $kouchu_aixinbi = $kouchu_aixinbi?$change_value*floatval($kouchu_aixinbi):0;
        $kouchu_shuishou = $kouchu_shuishou?$change_value*floatval($kouchu_shuishou):0;
        $kouchu_xiaofeibi = $kouchu_xiaofeibi?$change_value*floatval($kouchu_xiaofeibi):0;
        
        $xianjinbi_systemnote = "扣除之前的现金币".$change_value;
        $change_value = $change_value - $kouchu_aixinbi - $kouchu_shuishou - $kouchu_xiaofeibi;
        
        //现金币
        $new_value = $original_value + $change_value;
        $sql = "update ".$ecs->table('pc_user')." set account_xianjinbi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
        pc_log($sql,'save_pv_xianjianbi_fanli');
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
		"'".$uid."',".
		"'account_xianjinbi',".
		"'".$original_value."',".
		"'".$change_value."',".
		"'".$new_value."',".
		"'管理补贴',".
		"'0',".
		"'".time()."', ".
		"'".$xianjinbi_systemnote."' ".
	")";
	$db->query($sql);
        
        //保存爱心币
        $original_value = 0;
        $new_value = 0;
        $original_value = intval($userinfo['account_aixinbi']);
        $new_value = $original_value + $kouchu_aixinbi;
        $sql = "update ".$ecs->table('pc_user')." set account_aixinbi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
		"'".$uid."',".
		"'account_aixinbi',".
		"'".$original_value."',".
		"'".$kouchu_aixinbi."',".
		"'".$new_value."',".
		"'管理补贴赠爱心币',".
		"'0',".
		"'".time()."', ".
		"'管理补贴赠爱心币-扣除兑换成爱心币'".
	")";
	$db->query($sql);
        
        //保存消费币
        $original_value = 0;
        $new_value = 0;
        $original_value = intval($userinfo['account_xiaofeibi']);
        $new_value = $original_value + $kouchu_xiaofeibi;
        $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
		"'".$uid."',".
		"'account_xiaofeibi',".
		"'".$original_value."',".
		"'".$kouchu_xiaofeibi."',".
		"'".$new_value."',".
		"'管理补贴赠消费币',".
		"'0',".
		"'".time()."', ".
		"'管理补贴赠消费币-扣除兑换成消费币'".
	")";
	$db->query($sql);
        return 1;
}
//}}}

//{{{补贴奖计算
function pc_set_tuiguang_butie($uid){
    pc_log("推广补贴");
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $jiangxiang = "fuwubutie";
    //$userinfo = get_pc_user_allinfo($uid);
    //var_dump($userinfo);
    //$jiedianren_user_id = $userinfo['jiedianren_user_id'];
    
    //先获得所有的接点人直线数组，每一层循环判断
    $jiedianren_user_array = get_user_parent_array($uid);
    if(!$jiedianren_user_array){return 0;}
    
//    var_dump($jiedianren_user_array);
//    echo "<br>";
    foreach($jiedianren_user_array as $k=>$v){
        //$v 即是接点人
//        echo "<hr><br>接点人 ： $v <br>";
        $left_sql = "select uid from ".$ecs->table('pc_user')." where jiedianren_user_id = $v and leftright = 'left'";
        $right_sql = "select uid from ".$ecs->table('pc_user')." where jiedianren_user_id = $v and leftright = 'right'";
//        echo $left_sql."<br>";
//        echo $right_sql."<br>";
        $leftuser = $db->getOne($left_sql);
        $rightuser = $db->getOne($right_sql);
      
        $leftuserid = $leftuser?$leftuser:0;
        $rightuserid = $rightuser?$rightuser:0;
//        echo "接点人下的left: $leftuserid;<br>";
//        echo "接点人下的right: $rightuserid;<br>";
        $left_array = array();
        $right_array = array();
        if($leftuserid){
            $left_arr = getCengDataByUid($leftuserid);
//            var_dump($left_arr);
            foreach($left_arr as $kk=>$vv){
                foreach($vv as $kkk=>$vvv){
                    $check_sql = "select id from ".$ecs->table('pc_user_tuiguang_log')." where uid = $v and tuiguang_uid_used = $vvv ";
//                    echo "left : ".$check_sql."<br>";
                    $is_used_tuiguang = $db->getOne($check_sql);
//                    echo $is_used_tuiguang."----<br>";
                    if($is_used_tuiguang){
                        
                    }else{//没用过才能用
                        $left_array[] = $vvv;
                    }
                }
            }
        }
        if($rightuserid){
            $right_arr = getCengDataByUid($rightuserid);
            foreach($right_arr as $kk=>$vv){
                foreach($vv as $kkk=>$vvv){
                    $check_sql = "select id from ".$ecs->table('pc_user_tuiguang_log')." where uid = $v and tuiguang_uid_used = $vvv ";
//                    echo "right : ".$check_sql."<br>";
                    $is_used_tuiguang = $db->getOne($check_sql);
//                    echo $is_used_tuiguang."----<br>";
                    if($is_used_tuiguang){ 
                        continue;
                    }else{//没用过才能用
                        $right_array[] = $vvv;
                    }
                    
                }
            }
        }
//                echo "<br>左边：".count($left_array)."<br>";
//                var_dump($left_array);
                $left_total = count($left_array);
//                echo "<br>右边：".count($right_array)."<br>";
//                var_dump($right_array);
                $right_total = count($right_array);
                
        if(($left_total>=2 && $right_total) || ($right_total>=2 && $left_total)){
            $peng = array();
            if($left_total>$right_total){ //哪边人数多从那一边多碰
                $peng[] = $left_array[0];
                $peng[] = $left_array[1];
                $peng[] = $right_array[0];
                
            }else{
                $peng[] = $left_array[0];
                $peng[] = $right_array[0];
                $peng[] = $right_array[1];
            }
            if($peng){
            $log_id = save_tuiguang_fanli($v);
                foreach($peng as $pk=>$pv){
                    $peng_sql = "insert into ".$ecs->table('pc_user_tuiguang_log')."(uid,tuiguang_uid_used,tuiguang_account_log_id,ctime)values(".
                            "'".$v."',".
                            "'".$pv."',".
                            "'".$log_id."',".
                            "'".time()."'".
                            ")";
//                    echo $peng_sql."<br>";
                    $db->query($peng_sql);
                }
            }
        }else{
            //没有碰对
            continue;
        }

        
        
    }
    
//    var_dump($jiedianren_user_array);
//    $where = "";
//    if($userinfo['leftright'] == 'left'){
//        $where .=" And leftright = 'right'";
//    }else{
//        $where .= " And leftright = 'left'";
//    }
   
    /*
//    foreach($jiedianren_user_array as $jiedian_k=>$jiedian_uid){
//       
//        $jiedian_info = getLeftRightUserList($jiedian_uid);
//        if($jiedian_info['status']){ //左右区必须都有才有可能碰对，如果没有，则status = 0
//            $pengdui_success = false;
//            
//            $leftuid = $jiedian_info['leftuid'];
//            $rightuid = $jiedian_info['rightuid'];
//            
//            $leftqu_array = array();
//            getAllUserListByUidNotUsedTuiguang($leftuid,$leftqu_array);
////            pc_log($leftqu_array," left qu array {".$leftuid."}");
//            
//            $rightqu_array = array();
//            getAllUserListByUidNotUsedTuiguang($rightuid,$rightqu_array);
////            pc_log($rightqu_array," right qu array {".$rightuid."}");
//            
//            $bili = intval(count($leftqu_array))/intval(count($rightqu_array));
//            pc_log($bili," 对碰比例 [left: ".count($leftqu_array)."] [right: ".count($rightqu_array)."]");
//            if($bili == 0.5 || $bili == 2){
//                save_tuiguang_fanli($jiedian_uid,$leftqu_array,$rightqu_array);
//                $pengdui_success = true;
//            }else{
//                $pengdui_success = false;
//            }
//            if($pengdui_success){ //碰对成功即跳出
//                break;
//            }
//        }else{
//            continue;
//        }
//        
//    }
    */
    
    
    $cur_cent = count($jiedianren_user_array)+1;
    
    
}

function getLeftRightUserList($uid){
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
        
    $sql = "select uid,leftright from ".$ecs->table('pc_user')." where jiedianren_user_id = ".$uid;
    $data = $db->getAll($sql);
    $leftuid = 0;
    $rightuid = 0;
    if($data){
        foreach($data as $k=>$v){
            if($v['leftright'] == 'left'){ $leftuid = $v['uid'];}
            if($v['leftright'] == 'right'){ $rightuid = $v['uid'];}
        }
    }
//    左右区必须都有才有可能碰对，如果没有，则status = 0
    if($leftuid && $rightuid){
        return array("status"=>1,"leftuid"=>$leftuid,"rightuid"=>$rightuid);
    }else{
        return array("status"=>0);
    }
    
}
//获得推广用户所以下线列表,且是未碰过对的
function getAllUserListByUidNotUsedTuiguang($uid,&$data){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
        $sql = "select pu.uid , tuijianren_user_id, jiedianren_user_id, leftright,log.is_tuiguang,log.is_fuwu,log.is_jiandian, log.is_guanli  from ".
                $ecs->table('pc_user')." pu left join ".$ecs->table('pc_user_status_log')." log on pu.uid = log.uid ".
                " where jiedianren_user_id = ".$uid." and is_tuiguang <> 'yes'";
        
//        echo "<br>".$sql;
	$nextuser = $db->getAll($sql);
	
	if($nextuser){
		foreach($nextuser as $k=>$v){
                    $data[] = $v;
                    getAllUserListByUidNotUsedTuiguang($v['uid'],$data);
		}
	}else{
		return $data;
	}
}

function save_tuiguang_fanli($uid,$leftqu_array=array(),$rightqu_array=array()){
    $bili = 0.26;
    $basenum = 5000;
    //不区分高级，初级会员，都返利
    $fanli = $basenum * $bili;
    
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
   // pc_log($fanli,'save_tuiguang_fanli');
    $userinfo = get_pc_user_allinfo($uid);
    if(!$fanli){ return 0;}

    $original_value = intval($userinfo['account_xianjinbi']);
    $change_value = floatval($fanli);
    
     //保存现金币，扣除费用
        $kouchu_aixinbi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_aixinbi'");
        $kouchu_shuishou = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_shuishou'");
        $kouchu_xiaofeibi = $db->getOne("select svalue from ".$ecs->table('pc_config')." where sname='kouchu_xiaofeibi'");
        
        $kouchu_aixinbi = $kouchu_aixinbi?$change_value*floatval($kouchu_aixinbi):0;
        $kouchu_shuishou = $kouchu_shuishou?$change_value*floatval($kouchu_shuishou):0;
        $kouchu_xiaofeibi = $kouchu_xiaofeibi?$change_value*floatval($kouchu_xiaofeibi):0;
        
        $xianjinbi_systemnote = "扣除之前的现金币".$change_value;
        $change_value = $change_value - $kouchu_aixinbi - $kouchu_shuishou - $kouchu_xiaofeibi;
        
    $new_value = $original_value + $change_value;
    //保存现金币
    $sql = "update ".$ecs->table('pc_user')." set account_xianjinbi = ".$new_value." where uid = ".$uid;
    $db->query($sql);
    pc_log($sql,'save_pv_xianjianbi_fanli');
    $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
            "'".$uid."',".
            "'account_xianjinbi',".
            "'".$original_value."',".
            "'".$change_value."',".
            "'".$new_value."',".
            "'推广补贴',".
            "'0',".
            "'".time()."', ".
            "'".$xianjinbi_systemnote."' ".
    ")";
    $db->query($sql);
     //保存爱心币
        $original_value = 0;
        $new_value = 0;
        $original_value = intval($userinfo['account_aixinbi']);
        $new_value = $original_value + $kouchu_aixinbi;
        $sql = "update ".$ecs->table('pc_user')." set account_aixinbi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
		"'".$uid."',".
		"'account_aixinbi',".
		"'".$original_value."',".
		"'".$kouchu_aixinbi."',".
		"'".$new_value."',".
		"'推广补贴赠爱心币',".
		"'0',".
		"'".time()."', ".
		"'推广补贴赠爱心币-扣除兑换成爱心币'".
	")";
	$db->query($sql);
        
        //保存消费币
        $original_value = 0;
        $new_value = 0;
        $original_value = intval($userinfo['account_xiaofeibi']);
        $new_value = $original_value + $kouchu_xiaofeibi;
        $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime,systemnote) values(".
		"'".$uid."',".
		"'account_xiaofeibi',".
		"'".$original_value."',".
		"'".$kouchu_xiaofeibi."',".
		"'".$new_value."',".
		"'推广补贴赠消费币',".
		"'0',".
		"'".time()."', ".
		"'推广补贴赠消费币-扣除兑换成消费币'".
	")";
	$db->query($sql);
        return 1;
//    foreach($leftqu_array as $k=>$v){
//        $sql = "select * from ".$ecs->table('pc_user_status_log')." where uid =  ".$v['uid'];
//        $check = $db->getRow($sql);
//        if($check){
//            $sql = "update ".$ecs->table('pc_user_status_log')." set is_tuiguang = 'yes' where uid = ".$v['uid'];
//        }else{
//            $sql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_tuiguang,tuiguang_used_time)values(".
//                    "'".$v['uid']."',".
//                    "'yes',".
//                    "'".time()."'".
//                    ")";
//        }
//        $db->query($sql);
//    }
//    
//    foreach($rightqu_array as $k=>$v){
//        $sql = "select * from ".$ecs->table('pc_user_status_log')." where uid =  ".$v['uid'];
//        $check = $db->getRow($sql);
//        if($check){
//            $sql = "update ".$ecs->table('pc_user_status_log')." set is_tuiguang = 'yes' where uid = ".$v['uid'];
//        }else{
//            $sql = "insert into ".$ecs->table('pc_user_status_log')."(uid,is_tuiguang,tuiguang_used_time)values(".
//                    "'".$v['uid']."',".
//                    "'yes',".
//                    "'".time()."'".
//                    ")";
//        }
//        $db->query($sql);
//    }
}
function save_jifenbi_fanli($uid,$jifenbi,$note){
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
       
        $userinfo = get_pc_user_allinfo($uid);
        
        $change_value = intval($jifenbi);
        $original_value = intval($userinfo['account_jifenbi']);
        
        $new_value = $original_value + $change_value;
        
        $sql = "update ".$ecs->table('pc_user')." set account_jifenbi = ".$new_value." where uid = ".$uid;
        $db->query($sql);
        pc_log($sql,'save jifenbi fanli');
        $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
                "'".$uid."',".
                "'account_jifenbi',".
                "'".$original_value."',".
                "'".$change_value."',".
                "'".$new_value."',".
                "'".$note."',".
                "'0',".
                "'".time()."' ".
        ")";

	$db->query($sql);
        
}        
//}}}

function change_account_info($uid,$bizhong,$type, $change_value){
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
        $userinfo = get_pc_user_allinfo($uid);
        $original_value = intval($userinfo['account_'.$bizhong]);
        $change_value = intval($change_value);
        if($type == '+'){
            $new_value = $original_value + $change_value;
        }else{
            $new_value = $original_value - $change_value;
        }
        $sql = "update ".$ecs->table('pc_user')." set account_".$bizhong." = ".$new_value." where uid = ".$uid;
  
	$db->query($sql);
        
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
		"'".$uid."',".
		"'account_".$bizhong."',".
		"'".$original_value."',".
		"'".$type.$change_value."',".
		"'".$new_value."',".
		"'系统自动',".
		"'0',".
		"'".time()."' ".
	")";
	$db->query($sql);
}

//给服务中心返利
function pc_set_fuwuzhongxin_butie($uid, $good_amount){
    if(!$uid){return 1;}
        $db = $GLOBALS['db'];
        $ecs = $GLOBALS['ecs'];
        $fuwuzhongxin_butie = 0.03;
        $fanli = floatval($good_amount) * $fuwuzhongxin_butie;
        
        pc_log($fanli,'pc_set_fuwuzhongxin_butie');
        $userinfo = get_pc_user_allinfo($uid);
        if(!$fanli){ return 0;}
	$original_value = intval($userinfo['account_xianjinbi']);
        $change_value = floatval($fanli);
        $new_value = $original_value + $change_value;
        
        $sql = "update ".$ecs->table('pc_user')." set account_xianjinbi = ".$new_value." where uid = ".$uid;
	$db->query($sql);
        pc_log($sql,'save_pv_xianjianbi_fanli');
	$sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
		"'".$uid."',".
		"'account_xianjinbi',".
		"'".$original_value."',".
		"'".$change_value."',".
		"'".$new_value."',".
		"'服务中心补贴',".
		"'0',".
		"'".time()."' ".
	")";
	$db->query($sql);
}

//直推1人,直找上一级，进行返利,直推消费返利是消费币
function pc_set_zhitui_fanli($fuid,$puid,$oid,$good_amount){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $zhitui_bili = 0.02;
    $fanli = floatval($good_amount) * $zhitui_bili;
         
     $sql = "insert into ".$ecs->table('pc_zhitui_fanli')."(oid,from_uid,uid,amount,ctime)values(".
            "'".$oid."',".
            "'".$fuid."',".
            "'".$puid."',".
            "'".$fanli."',".
            "'".time()."'".
            ")";
    //echo "<br>$sql<br>";
    $db->query($sql);
    
    
     $userinfo = get_pc_user_allinfo($puid);

    if(!$fanli){ return 0;}

    $original_value = intval($userinfo['account_xiaofeibi']);
    $change_value = floatval($fanli);
    $new_value = $original_value + $change_value;

    $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$puid;
    $db->query($sql);
    $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
            "'".$puid."',".
            "'account_xiaofeibi',".
            "'".$original_value."',".
            "'".$change_value."',".
            "'".$new_value."',".
            "'直推消费返利',".
            "'0',".
            "'".time()."' ".
    ")";
    $db->query($sql);
    
}

function fenhongjisuan($bili='0.1',$type=""){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
	
    //累计分红到分红池
    if($type == "leiji"){
         //计算已付款的所有订单，当天的付款，且付款时间是今日
        $cur_date = date("Y-m-d");
        //$cur_date = "2017-09-19";
        $start_time = strtotime($cur_date." 00:00:00");
        $end_time = strtotime($cur_date." 23:59:59");
		
		//$start_time = strtotime("2017-08-01 00:00:00");
        //$end_time = strtotime("2017-10-05 23:59:59");
		
        $bili = $bili;
        
        if($db->getOne("select id from ".$ecs->table('pc_day_amount_fenhong_log')." where fenhong_date = '".$cur_date."'")){
            //sys_msg('已存在【'.date('Y-m-d').'】分红记录', 0 ,$links);
			$fenhong_cron_log = fopen("data/fenhong_cron_log.log","a+");
            fwrite($fenhong_cron_log,"已存在【'.date('Y-m-d').'】分红记录\r\n");
			fclose($fenhong_cron_log);
			return 0;
        }
        
        $sql = "select order_id, user_id,order_amount,pay_time from ".$ecs->table('order_info')." where pay_time > ".$start_time." and pay_time < ".$end_time." and pay_status = 2 ";
        $order_list = $db->getAll($sql);

        $sumsql = "select sum(order_amount) from ".$ecs->table('order_info')." where pay_time > ".$start_time." and pay_time < ".$end_time." and pay_status = 2 ";
        //echo $sumsql;
		$trade_today = floatval($db->getOne($sumsql));
        if(!$trade_today){
			$fenhong_cron_log = fopen("data/fenhong_cron_log.log","a+");
            fwrite($fenhong_cron_log,"【".date('Y-m-d')."】交易额为0，不可分红\r\n");
			fclose($fenhong_cron_log);
            //sys_msg('交易额为0，不可分红', 0 ,$links);
            return 0;
        }
        $trade_fanli_today = $trade_today * $bili;
        
        $sql = "insert into ".$ecs->table('pc_day_amount_fenhong_log')."(fenhong_date,day_amount, day_fenhong_amount, ctime)values('".$cur_date."','".$trade_today."','".$trade_fanli_today."','".time()."')";
//        echo $sql."<br>";
        $db->query($sql);
        
        $sql ="select svalue from ".$ecs->table('pc_config')." where sname = 'fenhongchi'";
//        echo $sql."<br>";
        $fenhongchi = $db->getOne($sql);
        $old_value = floatval($fenhongchi);
        $change_value = floatval($trade_fanli_today);
        $new_value = $old_value+$change_value;
        
        $sql = "update ".$ecs->table('pc_config')." set svalue = '".$new_value."' where sname = 'fenhongchi'";
        $db->query($sql);
        
        $sql = "insert into ".$ecs->table('pc_fenhongchi_log')."(change_date,old_value,change_value,new_value,ctime)values(".
                "'".$cur_date."',".
                "'".$old_value."',".
                "'".$change_value."',".
                "'".$new_value."',".
                "'".time()."'".
                ")";
//        echo $sql."<br>";
        $db->query($sql);
        $fenhong_cron_log = fopen("data/fenhong_cron_log.log","a+");
		fwrite($fenhong_cron_log,"【".date('Y-m-d')."】执行完成\r\n");
		fclose($fenhong_cron_log);
        
    }elseif($type == 'fenhong'){ //给每个人符合条件的人分红
                $cur_date = date("Y-m-d");
                //$cur_date = "2017-09-19";
        
                $fenhong_user_money = $bili; //每个分红点的金额
                $sql = "select * from ".$ecs->table('pc_fenhong')." where fenhong_date='".$cur_date."'";
                $is_check = $db->getRow($sql);
                if($is_check){
                    return 0;
                }
                
                $pc_user_list = $db->getAll("select id,uid,account_jifenbi,account_fenhong_amount from ".$ecs->table('pc_user')." where account_jifenbi > 0 and status = 1");
                $pc_fenhongdian = array();
                $pc_total_fenhongdian = 0;
                foreach($pc_user_list as $k=>$v){
                    $pc_fenhongdian[] = array(
                        "user_id"=>$v['uid'],
                        "account_jifenbi"=>$v['account_jifenbi'],
						"account_fenhong_amount"=>$v['account_fenhong_amount'],
                        "fenhong_user_money"=>$fenhong_user_money*(intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360)),
                        //"fenhongdain"=>intval($v['account_jifenbi']/360),
						"fenhongdain"=>intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360), //剩余的分红点，去除掉已分红的点
                    );
                    //$pc_total_fenhongdian += intval($v['account_jifenbi']/360);
					$pc_total_fenhongdian += intval($v['account_jifenbi']/360)-intval($v['account_fenhong_amount']/360);
                }
               
                $trade_today = $pc_total_fenhongdian * $fenhong_user_money; //今日总分红点金额
                
            if($pc_fenhongdian){
                
                //保存分红信息， 总分红点，总分红金额，总用户个数，用户总分红金额，每个分红点金额
                $save_sql = "insert into ".$ecs->table('pc_fenhong')."(fenhong_date,fenhong_total,fenhong_money,fenhong_user_total,fenhong_dian_money)values(".
                        "'".date("Y-m-d")."',".
                        "'".$pc_total_fenhongdian."',".
                        "'".$trade_today."',".
                        "'".count($pc_user_list)."',".
                        "'".$fenhong_user_money."'".
                        ")";
                $db->query($save_sql);

                foreach($pc_fenhongdian as $k=>$v){
                    //记录分红返利log
                     $save_sql = "insert into ".$ecs->table('pc_fenhong_log')."(user_id,fenhong,fenhong_date,user_fenhongdian,ctime)values(".
                            "'".$v['user_id']."',".
                            "'".$v['fenhong_user_money']."',".
                            "'".date("Y-m-d")."',".
                            "'".$v['fenhongdain']."',".
                            "'".time()."'".
                            ")";
                     $db->query($save_sql);

                     //分红出的是消费币
                     $userinfo = get_pc_user_allinfo($v['user_id']);
                     $original_value = intval($userinfo['account_xiaofeibi']);
                     $change_value = floatval($v['fenhong_user_money']);
                     $new_value = $original_value + $change_value;

                     $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$v['user_id'];
                     $db->query($sql);
                     $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
                             "'".$v['user_id']."',".
                             "'account_xiaofeibi',".
                             "'".$original_value."',".
                             "'".$change_value."',".
                             "'".$new_value."',".
                             "'分红',".
                             "'0',".
                             "'".time()."' ".
                     ")";
                     $db->query($sql);

                }
                
                //更新分红池金额
                $sql ="select svalue from ".$ecs->table('pc_config')." where sname = 'fenhongchi'";
        //        echo $sql."<br>";
                $fenhongchi = $db->getOne($sql);
                $old_value = floatval($fenhongchi);
                $change_value = floatval($trade_today);
                $new_value = $old_value-$change_value;

                $sql = "update ".$ecs->table('pc_config')." set svalue = '".$new_value."' where sname = 'fenhongchi'";
                $db->query($sql);

                $sql = "insert into ".$ecs->table('pc_fenhongchi_log')."(change_date,old_value,change_value,new_value,ctime)values(".
                        "'".$cur_date."',".
                        "'".$old_value."',".
                        "'-".$change_value."',".
                        "'".$new_value."',".
                        "'".time()."'".
                        ")";
        //        echo $sql."<br>";
                $db->query($sql);
                //}}}更新分红池金额
            }
    }
   
}
    
//    $level_1 = array();
//    $level_2 = array();
//    $level_3 = array();
//    $level_4 = array();
//    $user_sql = "select uid,level from ".$ecs->table('pc_users')." where status = 1";
//    $user_list = $db->getAll($user_sql);
//    if($user_list){
//        foreach($user_list as $k=>$v){
//            if($v['level'] == 0 || $v['level'] == 1){
//                $level_1[] = $v;
//            }else if($v['level'] == 2){
//                $level_2[] = $v;
//            }else if($v['level'] == 3){
//                $level_3[] = $v;
//            }else if($v['level'] == 4){
//                $level_4[] = $v;
//            }
//        }
//    }

//当后台给用户充积分币时，如果该用户是联盟商家，则给其推荐者1%的提成，消费币
function pc_set_lianmengshangjia_butie($uid,$amount){
    $ecs = $GLOBALS['ecs'];
    $db = $GLOBALS['db'];
    $bili = 0.01;
    $fanli = floatval($amount)*$bili;
    $userinfo = get_pc_user_allinfo($uid);

    $original_value = floatval($userinfo['account_xiaofeibi']);
    $change_value = floatval($fanli);
    $new_value = $original_value + $change_value;

    $sql = "update ".$ecs->table('pc_user')." set account_xiaofeibi = ".$new_value." where uid = ".$uid;
//    echo $sql;
    $db->query($sql);
    $sql = "insert into ".$ecs->table('pc_user_account_log')."(uid,type,original_value,change_value,new_value,note,adminid,ctime) values(".
            "'".$uid."',".
            "'account_xiaofeibi',".
            "'".$original_value."',".
            "'".$change_value."',".
            "'".$new_value."',".
            "'推荐联盟商家返利',".
            "'".$_SESSION['admin_id']."',".
            "'".time()."' ".
    ")";
//    echo $sql;
    $db->query($sql);
}

