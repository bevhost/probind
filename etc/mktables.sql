#
# Create the data structures needed for the dnsdb system
#
# When     Who       What
# ======================================================================
# 20000622 FSJ       First version
# 20021215 alex      Version 2.0
# 20030110 alex      Version 2.1
# 20070511 youngmug  Fixed to support newer MySQL versions
# 20100614 youngmug  Added AAAA record type for IPv6
# 20140422 marado    Added columns needed for SRV records

DROP TABLE IF EXISTS zones, zoneattr, records, annotations, servers, deleted_domains, typesort, blackboard, active_sessions, auth_user, session_stats;

#
#
# For each domain served by our BIND servers, exactly one record
# must exist in the zones table. The update flag is supposed to be
# set by anyone updating a record in zones, or editing the set of
# associated records in the 'records' table.
#
CREATE TABLE zones (
# Unique zone ID
    id    INT(11) NOT NULL AUTO_INCREMENT,
# Origin of this zone
# NB: the PTR RRs for zones under in-addr.arpa. are auto-generated
    domain    CHAR(100) NOT NULL,
# Serial number for the SOA record
    serial    INT(12),
# Refresh rate for the SOA record
    refresh INT(12),
# Retry interval for the SOA record
    retry INT(12),
# Expire period for the SOA record
    expire INT(12),
# If set, an IP number for an auth. master for this zone i.e, if
# this column is non-null, then we are secondary for the zone
# Two IP delimited by ';' are allowed
    master    CHAR(32) NOT NULL,
# If set, the basename of the file containing zone records
# Either master or zonefile must be set, but not both
    zonefile    CHAR(80) NOT NULL,
# Zone options. $ACL will be replaced by the access list.
    options     VARCHAR(255),
# Zone record last modification time
    mtime    TIMESTAMP(14) NOT NULL,
# Zone record creation time
    ctime    TIMESTAMP(14),
# Zone has been updated => increment serial on next dump-to-file
    updated    INT(1) DEFAULT '0',
# disabled bit
    disabled INT(1) DEFAULT '0',
    PRIMARY KEY (id)
);

#
# This is where we store the actual Resource Records for the domains.
# For each domain served authoritatively, exactly one SOA record must
# exist here.
#
# NB: The NS records for our own DNS servers are automatically
# added when the zone files are generated, based on the contents of the
# 'servers' table. Thus NS records pointing to a known DNS server
# are redundant.
#
CREATE TABLE records (
# Unique Resource Record ID
    id    INT(11) NOT NULL AUTO_INCREMENT,
# foreign key to the zones table
    zone    INT(11) NOT NULL,
# Origin of this record
    domain    CHAR(100) DEFAULT '' NOT NULL,
# This application only deals with the IN class, so we dont
# bother representing the RR class in the database
# Time To Live, must be non-null for SOA records
    ttl    CHAR(15),
# RR type, e.g. A, MX, SOA, CNAME or NS
# NB: PTR records are _not_ stored explicitly, the reverse-lookup
# zone files are generated automatically.
    type    CHAR(10) DEFAULT '' NOT NULL,
# Preference value for this MX record
    pref    CHAR(5),
# RR Data
    data    CHAR(255) DEFAULT '' NOT NULL,
# weight
    weight CHAR(15),
# port
    port   CHAR(15),
# Comment
    comment  CHAR(32),
# Last modification time for this RR
    mtime    TIMESTAMP(14) NOT NULL,
# Creation time for this RR
    ctime    TIMESTAMP(14),
# Should PTR be generated for this record
    genptr  INT(1),
# disabled?
    disabled INT(1) DEFAULT '0',
    PRIMARY KEY (id)
);

#
# This table contains long annotations for zones or records. It is
# basically eyecandy for the web interface, and an aid for forgetful
# DNS admins, should any such exist (I don't remember meeting any').
#
CREATE TABLE annotations (
# Unique ID for a chunk of text
    zone    INT(11) NOT NULL,
# The actual text
    descr    TEXT NOT NULL,
    PRIMARY KEY (zone)
);

#
# This table must contain one record for each DNS/BIND server managed
# by this database.
#
CREATE TABLE servers (
# Unique Resource Record ID
    id        INT(11) NOT NULL AUTO_INCREMENT,
# The hostname
    hostname    CHAR(200) NOT NULL,
# The IP number derived from the hostname
    ipno        CHAR(15) NOT NULL,
# Either 'M' or 'S'
    type        CHAR(1) NOT NULL,
# If non-zero and non-null, do push updates from the database to the server
# This field was added to enable us to handle server aliases
    pushupdates    INT(1) NOT NULL,
# If non-zero, include this server when generating NS records for a domain
    mknsrec        INT(1) NOT NULL,
# Path to directory on the DNS server containing the zone files
    zonedir        VARCHAR(255) NOT NULL,
# Path to the chroot base of the server (if chroot is used)
    chrootbase     VARCHAR(255) NULL,
# Template directory; must contain named.conf anc can contain other files as well...
    template    VARCHAR(255) NOT NULL,
# Path to script that will push updates to this server
    script        VARCHAR(255) NOT NULL,
# Server options (additional)
    options         TEXT,
# Descriptive text
    descr        TEXT,
# Current status; can be 'OK',
#                        'OUT' (out of date),
#                        'CHG' (changed but not pushed yet)
#                        'CFG' (pushed but not reconfigured yet)
#                        'ERR' (error during last update)
#
    state           CHAR(5) DEFAULT 'OK',
    PRIMARY KEY (id)
);

# This table tracks deleted domains until they have been cleaned up
# on the BIND servers.
CREATE TABLE deleted_domains(
# The domain name of the defunct domain
    domain        CHAR(100) NOT NULL,
# The zonefile associated with the defunct domain
    zonefile    CHAR(80) NOT NULL
);

# This table controls the record sorting order in the domain browser
CREATE TABLE typesort (
    type    CHAR(10) NOT NULL,
    ord    INT(2) NOT NULL
);
INSERT INTO typesort (type, ord) values ('SOA', 1);
INSERT INTO typesort (type, ord) values ('NS', 2);
INSERT INTO typesort (type, ord) values ('TXT', 3);
INSERT INTO typesort (type, ord) values ('HINFO', 4);
INSERT INTO typesort (type, ord) values ('MX', 5);
INSERT INTO typesort (type, ord) values ('A', 6);
INSERT INTO typesort (type, ord) values ('AAAA', 7);
INSERT INTO typesort (type, ord) values ('CNAME', 8);
INSERT INTO typesort (type, ord) values ('PTR', 9);
INSERT INTO typesort (type, ord) values ('SRV', 10);


# This table stores various management info. First (and so far only)
# use is to help limit the push functionality to one single user at a time.
CREATE TABLE blackboard (
    name  VARCHAR(32) NOT NULL,
    value VARCHAR(255) NOT NULL,
    ctime TIMESTAMP(14)
);

# Initialize the zones table with a TEMPLATE record
# By definition, this zone gets ID = 1
# The default refresh, retry, expire and minimum TTL are taken from
# the RIPE recommendations found at http://www.ripe.net/ripe/docs/ripe-203.html
INSERT INTO zones (domain, serial, refresh, retry, expire, options)
    VALUES ('TEMPLATE', 1, 86400, 7200, 3628800, 'allow-transfer{ $ACL };' );
UPDATE zones SET ctime = mtime;
INSERT INTO records (zone, domain, ttl, type)
    VALUES (1, '@', 172800, 'SOA');
UPDATE records SET ctime = mtime;
INSERT INTO annotations (zone, descr)
    VALUES (1, "This is the template from which new master domains
are initialized. It is not a 'REAL' domain, it is
not pushed to the BIND servers, and you cannot
delete it.");

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
