/*
Navicat MySQL Data Transfer

Source Server         : 本地数据库
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : yzjsshg

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-04-21 23:33:29
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for dlc_linkq
-- ----------------------------
DROP TABLE IF EXISTS `dlc_linkq`;
CREATE TABLE `dlc_linkq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) NOT NULL,
  `has_one` varchar(255) DEFAULT NULL COMMENT '字段对应的表 的数组 1对1',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否检查',
  `message` varchar(255) DEFAULT NULL COMMENT '错误提示',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1292 DEFAULT CHARSET=utf8;
