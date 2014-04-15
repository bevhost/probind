-- Convert from ProBIND to ProBIND 2 Table Format

-- 20070511 (youngmug)
--          - Initial Updates


ALTER TABLE `records`
  ADD COLUMN `comment` char(32) default NULL AFTER `data`,
  ADD COLUMN `genptr` int(1) default NULL AFTER `ctime`,
  ADD COLUMN `disabled` int(1) default '0' AFTER `genptr`;

ALTER TABLE `servers`
  ADD COLUMN `options` text AFTER `script`,
  ADD COLUMN `state` char(5) default 'OK' AFTER `descr`;

ALTER TABLE `zones`
  MODIFY `master` char(32) NOT NULL,
  ADD COLUMN `options` varchar(255) default NULL AFTER `zonefile`,
  ADD COLUMN `disabled` int(1) default 0 AFTER `updated`;

INSERT INTO `typesort` (`type`, `ord`) VALUES
  ('SRV', 9);




-- 20120609 (bevhost)
-- update to allow permissions / zone ownership

ALTER TABLE `zones`
  ADD COLUMN `owner` varchar(32);

ALTER TABLE `deleted_domains`
  ADD COLUMN `owner` varchar(32);

-- new tables for session/auth/perm 

CREATE TABLE `active_sessions` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `val` text,
  `changed` varchar(14) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL,
  PRIMARY KEY (`name`,`sid`),
  KEY `changed` (`changed`)
);

CREATE TABLE `auth_user` (
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `perms` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `k_username` (`username`)
);

CREATE TABLE `session_stats` (
  `sid` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `start_time` varchar(14) NOT NULL DEFAULT '',
  `referer` varchar(250) NOT NULL DEFAULT '',
  `addr` varchar(15) NOT NULL DEFAULT '',
  `user_agent` varchar(250) NOT NULL DEFAULT '',
  KEY `session_identifier` (`name`,`sid`),
  KEY `start_time` (`start_time`)
);

-- new tables for Event Logging

CREATE TABLE IF NOT EXISTS `EventLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `EventTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Program` varchar(64) NOT NULL DEFAULT '',
  `IPAddress` varchar(20) DEFAULT '',
  `UserName` varchar(100) NOT NULL DEFAULT '',
  `Description` varchar(255) NOT NULL,
  `ExtraInfo` text,
  `Level` enum('Info','Warning','Error','Debug') CHARACTER SET latin1 NOT NULL DEFAULT 'Info',
  PRIMARY KEY (`id`),
  KEY `Program` (`Program`),
  KEY `IPAddress` (`IPAddress`),
  KEY `UserName` (`UserName`),
  KEY `Level` (`Level`),
  KEY `EventTime` (`EventTime`),
  FULLTEXT KEY `Description` (`Description`,`ExtraInfo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;



