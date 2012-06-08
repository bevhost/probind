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