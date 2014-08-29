-- ----------------------------
--  Table structure for `#__taxonomy_taxonomies`
-- ----------------------------
CREATE TABLE IF NOT EXISTS `#__taxonomy_taxonomies` (
  `taxonomy_taxonomy_id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `row` int(11) NOT NULL,
  `table` varchar(255) NOT NULL,
  `ancestors` text NOT NULL,
  `descendants` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `ordering` int(11) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` bigint(20) NOT NULL DEFAULT '0',
  `locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `locked_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`taxonomy_taxonomy_id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `composite_key` (`row`,`table`),
  KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;