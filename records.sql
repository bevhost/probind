CREATE TABLE `LinkedTables` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `FormName` varchar(64) NOT NULL DEFAULT '',
 `FieldName` varchar(64) NOT NULL DEFAULT '',
 `LinkTable` varchar(64) NOT NULL DEFAULT '',
 `LinkField` varchar(64) NOT NULL DEFAULT '',
 `LinkDesc` varchar(40) NOT NULL DEFAULT '',
 `LinkInfo` varchar(128) DEFAULT '',
 `NullValue` varchar(255) DEFAULT '',
 `NullDesc` varchar(255) DEFAULT '',
 `LinkCondition` varchar(255) DEFAULT '',
 `LinkErrorMsg` varchar(255) DEFAULT '',
 `DefaultValue` varchar(255) DEFAULT '',
 PRIMARY KEY (`id`),
 KEY `FormName` (`FormName`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `LinkedTables` VALUES 
(1,'recordsform','zone','zones','id','domain','','','','','Please select zone',''),
(2,'recordsform','type','typesort','type','type','','','','','','A');


CREATE TABLE `z_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserName` varchar(20) NOT NULL,
  `Table` varchar(20) NOT NULL,
  `At` datetime NOT NULL,
  `SQL` text NOT NULL,
  `Was` text NOT NULL,
  `IP` varchar(40) NOT NULL,
  `Key` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

