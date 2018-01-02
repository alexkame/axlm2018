<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
$sql = 'UPDATE ' . $GLOBALS['ecs']->table('admin_user'). " SET user_name = 'admin', password = MD5('123456'), ec_salt =''  WHERE user_id =1";
$res = $GLOBALS['db']->query($sql);
if($res == true)
{
echo '<h2>密码重设成功！</h2><h5>现在请用用户名admin，密码123456，登入后台，然后重新设置下密码，删除reset.php文件（很重要）</h5>';
}
else
{
echo '<h2>密码重设失败!</h2>';
}
?>