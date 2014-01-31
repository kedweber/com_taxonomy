-- ----------------------------
--  Table structure for `keoxh_taxonomy_taxonomies`
-- ----------------------------
CREATE TABLE IF NOT EXISTS `#__taxonomy_taxonomies` (
  `taxonomy_taxonomy_id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `row` int(11) NOT NULL,
  `table` varchar(255) NOT NULL,
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
  UNIQUE KEY `composite_key` (`row`,`table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `keoxh_taxonomy_taxonomy_relations`
-- ----------------------------
CREATE TABLE IF NOT EXISTS `#__taxonomy_taxonomy_relations` (
  `ancestor_id` int(11) unsigned NOT NULL DEFAULT '0',
  `descendant_id` int(11) unsigned NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL DEFAULT '0',
  `draft` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ancestor_id`,`descendant_id`,`level`),
  KEY `path_index` (`descendant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;