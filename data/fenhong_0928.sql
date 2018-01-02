/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50540
Source Host           : localhost:3306
Source Database       : axlm365

Target Server Type    : MYSQL
Target Server Version : 50540
File Encoding         : 65001

Date: 2017-09-28 18:06:01
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `ecs_pc_day_amount_fenhong_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pc_day_amount_fenhong_log`;
CREATE TABLE `ecs_pc_day_amount_fenhong_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fenhong_date` varchar(20) DEFAULT '',
  `day_amount` decimal(11,2) DEFAULT '0.00' COMMENT '当日新增业绩金额',
  `day_fenhong_amount` decimal(11,2) DEFAULT '0.00' COMMENT '当日新增业绩，划拨到分红池中金额',
  `ctime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pc_day_amount_fenhong_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pc_fenhong`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pc_fenhong`;
CREATE TABLE `ecs_pc_fenhong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fenhong_date` varchar(50) DEFAULT NULL,
  `fenhong_total` int(11) DEFAULT '0' COMMENT '总分红点个数',
  `fenhong_money` decimal(11,2) DEFAULT '0.00' COMMENT '分红总金额',
  `fenhong_user_total` int(11) DEFAULT '0' COMMENT '分红用户总数',
  `fenhong_dian_money` decimal(11,2) DEFAULT '0.00' COMMENT '每个分红点金额',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pc_fenhong
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pc_fenhong_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pc_fenhong_log`;
CREATE TABLE `ecs_pc_fenhong_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `fenhong` decimal(11,2) DEFAULT NULL,
  `user_fenhongdian` int(11) DEFAULT NULL,
  `fenhong_date` varchar(50) DEFAULT NULL,
  `ctime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=162 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pc_fenhong_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pc_fenhongchi_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pc_fenhongchi_log`;
CREATE TABLE `ecs_pc_fenhongchi_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `change_date` varchar(20) DEFAULT '',
  `old_value` decimal(11,2) DEFAULT '0.00',
  `change_value` decimal(11,2) DEFAULT '0.00',
  `new_value` decimal(11,2) DEFAULT '0.00',
  `ctime` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pc_fenhongchi_log
-- ----------------------------
