CREATE TABLE `exploits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(40) NOT NULL,
  `mac` varchar(20) NOT NULL,
  `nas_id` int(10) NOT NULL,
  `UserName` varchar(60) NOT NULL,
  `string` longtext NOT NULL,
  `violation` longtext NOT NULL,
  `target` longtext NOT NULL,
  `banned` int(1) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`,`mac`,`nas_id`,`UserName`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `badauth_counts` (
 `id` bigint(11) NOT NULL AUTO_INCREMENT,
 `username` int(11) NOT NULL,
 `address` varchar(40) NOT NULL,
 `ctime` datetime NOT NULL,
 `mtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `count` int(11) NOT NULL DEFAULT '0',
 `no_cookie` int(11) NOT NULL DEFAULT '0',
 `user_agent` varchar(50) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `username` (`username`),
 KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

