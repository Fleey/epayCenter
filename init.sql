/*
 Navicat Premium Data Transfer

 Source Server         : 本地Mysql
 Source Server Type    : MySQL
 Source Server Version : 50553
 Source Host           : 127.0.0.1:3306
 Source Schema         : epay_center

 Target Server Type    : MySQL
 Target Server Version : 50553
 File Encoding         : 65001

 Date: 17/05/2019 17:04:11
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for epay_center_order
-- ----------------------------
DROP TABLE IF EXISTS `epay_center_order`;
CREATE TABLE `epay_center_order`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) UNSIGNED NOT NULL COMMENT 'uid',
  `tradeNoOut` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商户平台单号',
  `money` bigint(20) UNSIGNED NOT NULL COMMENT '订单金额 2位小数',
  `notify_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '异步回调地址',
  `return_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '同步回调地址',
  `payType` int(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付类型\r\n0 未知支付方式\r\n1 微信支付\r\n2 腾讯财付通支付\r\n3 支付宝支付',
  `payAisle` int(2) UNSIGNED NOT NULL COMMENT '支付通道类型\r\n0 未知 \r\n1 Yb支付\r\n2 Xa支付\r\n3 Ow支付\r\n4 Xd支付',
  `status` int(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态 0 未支付 1 已支付',
  `createTime` datetime NOT NULL COMMENT '订单创建时间',
  `endTime` datetime NULL DEFAULT NULL COMMENT '订单完成支付时间 如未支付则为null',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `search1`(`uid`, `tradeNoOut`) USING BTREE,
  INDEX `id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 120 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of epay_center_order
-- ----------------------------
INSERT INTO `epay_center_order` VALUES (41, 1, '2019040910492184051', 10000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 0, '2019-04-09 10:52:01', NULL);
INSERT INTO `epay_center_order` VALUES (42, 1, '2019040910523137817', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 1, '2019-04-09 10:52:32', '2019-04-09 10:53:10');
INSERT INTO `epay_center_order` VALUES (43, 1, '2019040911340816586', 1, 'http://pay.cn/Pay/CenterPay/Notify', '', 4, 2, 0, '2019-04-09 11:35:18', NULL);
INSERT INTO `epay_center_order` VALUES (46, 1, '2019040914064179898', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 0, '2019-04-09 14:07:04', NULL);
INSERT INTO `epay_center_order` VALUES (47, 1, '2019040914071535519', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 1, '2019-04-09 14:07:15', '2019-04-09 14:07:51');
INSERT INTO `epay_center_order` VALUES (48, 1, '2019040914073767098', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 0, '2019-04-09 14:07:37', NULL);
INSERT INTO `epay_center_order` VALUES (49, 1, '2019040914160014959', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', '', 4, 2, 1, '2019-04-09 14:16:00', '2019-04-09 14:16:30');
INSERT INTO `epay_center_order` VALUES (50, 1, '2019040915513216125', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 15:51:32', NULL);
INSERT INTO `epay_center_order` VALUES (51, 1, '2019040915521654427', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 15:52:16', NULL);
INSERT INTO `epay_center_order` VALUES (52, 1, '2019040915525050002', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 15:52:50', NULL);
INSERT INTO `epay_center_order` VALUES (53, 1, '2019040915540936150', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 15:54:09', NULL);
INSERT INTO `epay_center_order` VALUES (54, 1, '2019040915543392918', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 15:54:33', NULL);
INSERT INTO `epay_center_order` VALUES (55, 1, '2019040915590192715', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-09 15:59:01', '2019-04-09 16:00:40');
INSERT INTO `epay_center_order` VALUES (60, 1, '2019040916092748461', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 16:11:00', NULL);
INSERT INTO `epay_center_order` VALUES (61, 1, '2019040916090234030', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 16:11:38', NULL);
INSERT INTO `epay_center_order` VALUES (62, 1, '2019040916422382406', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-09 16:42:23', '2019-04-09 16:43:14');
INSERT INTO `epay_center_order` VALUES (63, 1, '2019040920213460522', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 20:21:34', NULL);
INSERT INTO `epay_center_order` VALUES (64, 1, '2019040920215947464', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-09 20:21:59', NULL);
INSERT INTO `epay_center_order` VALUES (65, 1, '2019041012495949225', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 12:49:59', NULL);
INSERT INTO `epay_center_order` VALUES (66, 1, '2019041012503595740', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 12:50:35', NULL);
INSERT INTO `epay_center_order` VALUES (67, 1, '2019041012525520664', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 12:52:55', NULL);
INSERT INTO `epay_center_order` VALUES (68, 1, '2019041013543466230', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 13:54:34', NULL);
INSERT INTO `epay_center_order` VALUES (74, 1, '2019041013562366562', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 13:56:24', NULL);
INSERT INTO `epay_center_order` VALUES (77, 1, '2019041013582274830', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-10 13:58:22', '2019-04-10 14:00:02');
INSERT INTO `epay_center_order` VALUES (80, 1, '2019041013553262943', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 14:05:18', NULL);
INSERT INTO `epay_center_order` VALUES (81, 1, '2019041014235218231', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 14:23:52', NULL);
INSERT INTO `epay_center_order` VALUES (82, 1, '2019041014270973796', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 14:27:10', NULL);
INSERT INTO `epay_center_order` VALUES (83, 1, '2019041014454932458', 1000, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-10 14:45:49', '2019-04-10 14:46:36');
INSERT INTO `epay_center_order` VALUES (84, 1, '2019041016422480792', 1, 'http://vip.zmz999.com/Pay/CenterPay/Notify', 'http://vip.cuwoc.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-10 16:42:28', NULL);
INSERT INTO `epay_center_order` VALUES (88, 1, '2019041109211089241', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 09:21:10', NULL);
INSERT INTO `epay_center_order` VALUES (89, 1, '2019041109221619864', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 09:22:17', NULL);
INSERT INTO `epay_center_order` VALUES (90, 1, '2019041109523122844', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 09:52:31', NULL);
INSERT INTO `epay_center_order` VALUES (91, 1, '2019041109533031449', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 09:53:30', NULL);
INSERT INTO `epay_center_order` VALUES (92, 1, '2019041110062364152', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:06:23', NULL);
INSERT INTO `epay_center_order` VALUES (93, 1, '2019041110064047374', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:06:40', NULL);
INSERT INTO `epay_center_order` VALUES (98, 1, '2019041110110335086', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 0, '2019-04-11 10:12:03', NULL);
INSERT INTO `epay_center_order` VALUES (100, 1, '2019041110110213001', 1000, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-11 10:13:29', '2019-04-11 10:20:03');
INSERT INTO `epay_center_order` VALUES (101, 1, '2019041110153565633', 10000, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:15:35', NULL);
INSERT INTO `epay_center_order` VALUES (102, 1, '2019041110161870754', 1000, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:16:18', NULL);
INSERT INTO `epay_center_order` VALUES (103, 1, '2019041110162617359', 500, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:16:26', NULL);
INSERT INTO `epay_center_order` VALUES (106, 1, '2019041110163923837', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:16:39', NULL);
INSERT INTO `epay_center_order` VALUES (107, 1, '2019041110181096784', 100, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:18:10', NULL);
INSERT INTO `epay_center_order` VALUES (111, 1, '2019041110395026699', 1, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:40:43', NULL);
INSERT INTO `epay_center_order` VALUES (112, 1, '2019041110405167499', 100, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:40:51', NULL);
INSERT INTO `epay_center_order` VALUES (113, 1, '2019041110512740924', 100, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 1, 0, '2019-04-11 10:51:27', NULL);
INSERT INTO `epay_center_order` VALUES (114, 1, '2019041110541439577', 1000, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 4, 2, 1, '2019-04-11 10:54:14', '2019-04-11 10:56:40');
INSERT INTO `epay_center_order` VALUES (115, 1, '2019041111111750885', 100, 'http://vip.cuwoc.com/Pay/CenterPay/Notify', 'http://vip.zmz999.com/Pay/CenterPay/Return', 3, 3, 0, '2019-04-11 11:11:17', NULL);
INSERT INTO `epay_center_order` VALUES (119, 1, '2019042416110497761', 1, 'http://pay.cn/Pay/CenterPay/Notify', 'http://pay.cn/Pay/CenterPay/Return', 1, 3, 0, '2019-04-24 16:28:05', NULL);

-- ----------------------------
-- Table structure for epay_center_settle
-- ----------------------------
DROP TABLE IF EXISTS `epay_center_settle`;
CREATE TABLE `epay_center_settle`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `uid` int(10) UNSIGNED NOT NULL COMMENT '申请人员',
  `settleNo` bigint(22) UNSIGNED NOT NULL COMMENT '结算单号',
  `money` bigint(20) UNSIGNED NOT NULL COMMENT '结算金额 分为单位',
  `bankCardName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '收款人名称',
  `bankCardNo` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '收款卡号',
  `bankType` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开户银行代号',
  `bankAddress` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开户分行',
  `bankBranchName` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开户支行',
  `bankProvince` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开户省份',
  `bankCity` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '开户城市',
  `settleAisle` int(2) NOT NULL COMMENT '结算通道',
  `status` int(1) UNSIGNED NOT NULL COMMENT '结算申请状态\r\n1 处理中\r\n2 结算成功\r\n3 结算失败',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `updateTime` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `settleID`(`settleNo`, `settleAisle`) USING BTREE,
  INDEX `createTime`(`createTime`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for epay_center_user
-- ----------------------------
DROP TABLE IF EXISTS `epay_center_user`;
CREATE TABLE `epay_center_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `username` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户名称',
  `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'sha256加密密码 算法sha256(sha256(密码)+散列)',
  `salt` varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '6为随机字符散列',
  `key` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '随机64位字符 推荐sha256',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of epay_center_user
-- ----------------------------
INSERT INTO `epay_center_user` VALUES (1, 'admin', 'b691b25f6473065741846994578cff649709c2f28bcf96add0f4785e0deeaeef', 'FbU4Mx', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', '0000-00-00 00:00:00');

-- ----------------------------
-- Table structure for epay_center_user_attr
-- ----------------------------
DROP TABLE IF EXISTS `epay_center_user_attr`;
CREATE TABLE `epay_center_user_attr`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `uid` int(10) NOT NULL COMMENT '用户uid',
  `attrKey` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '查询键',
  `attrValue` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '内容',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `查询内容`(`uid`, `attrKey`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
