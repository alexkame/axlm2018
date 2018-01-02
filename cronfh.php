<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

//将分红金额累计到分红池
$bili = 0.1;
fenhongjisuan($bili,"leiji");
echo '<h2>操作成功!</h2>';
?>
