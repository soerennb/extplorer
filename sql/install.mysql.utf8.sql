CREATE TABLE IF NOT EXISTS `#__extwebdav_locks` (
				`token` varchar(255) NOT NULL default '',
				`path` varchar(200) NOT NULL default '',
				`expires` int(11) NOT NULL default '0',
				`owner` varchar(200) default NULL,
				`recursive` int(11) default '0',
				`writelock` int(11) default '0',
				`exclusivelock` int(11) NOT NULL default 0,
						  PRIMARY KEY  (`token`),
						  KEY `path` (`path`),
						  KEY `expires` (`expires`)
						) ENGINE=MyISAM;
CREATE TABLE IF NOT EXISTS `#__extwebdav_properties` (
				`path` varchar(255) NOT NULL default '',
				`name` varchar(120) NOT NULL default '',
				`ns` varchar(120) NOT NULL default 'DAV:',
				`value` text,
						  PRIMARY KEY ( `ns` ( 100 ) , `path` ( 100 ) , `name` ( 50 ) ),
						  KEY `path` (`path`)
						) ENGINE=MyISAM;
