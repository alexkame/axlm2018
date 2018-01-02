# Host: localhost  (Version: 5.5.47)
# Date: 2016-12-27 00:15:55
# Generator: MySQL-Front 5.3  (Build 4.234)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "ecs_fxamount_log"
#

DROP TABLE IF EXISTS `ecs_fxamount_log`;
CREATE TABLE `ecs_fxamount_log` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `money` decimal(11,2) DEFAULT NULL,
  `status` char(3) DEFAULT '0' COMMENT '0:申请中，1：已批准提现 2:取消',
  `type` varchar(50) DEFAULT NULL COMMENT '提现账号类型',
  `account` varchar(255) DEFAULT NULL COMMENT '提现账号',
  `ctime` int(11) DEFAULT NULL COMMENT '申请时间',
  `confirmtime` int(11) DEFAULT NULL COMMENT '管理员确认时间',
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# Data for table "ecs_fxamount_log"
#

/*!40000 ALTER TABLE `ecs_fxamount_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ecs_fxamount_log` ENABLE KEYS */;
