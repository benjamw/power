DROP TABLE IF EXISTS `po_chat`;
CREATE TABLE IF NOT EXISTS `po_chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`chat_id`),
  KEY `game_id` (`game_id`),
  KEY `private` (`private`),
  KEY `from_id` (`from_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_game`;
CREATE TABLE IF NOT EXISTS `po_game` (
  `game_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `host_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(32) DEFAULT NULL,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `state` enum('Waiting','Playing','Finished') NOT NULL DEFAULT 'Waiting',
  `extra_info` text DEFAULT NULL,
  `paused` tinyint(1) NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_game_player`;
CREATE TABLE IF NOT EXISTS `po_game_player` (
  `game_id` int(11) unsigned NOT NULL DEFAULT '0',
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `color` char(1) NOT NULL DEFAULT '',
  `state` enum('Awarding','Moving','Waiting','Incapacitated','Dead') NOT NULL DEFAULT 'Waiting',
  `extra_info` text DEFAULT NULL,
  `move_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_game_history`;
CREATE TABLE `po_game_history` (
  game_id int(11) unsigned NOT NULL,
  player_id int(11) unsigned NOT NULL,
  commands text NOT NULL,
  move_date timestamp default CURRENT_TIMESTAMP,

  INDEX (game_id),
  INDEX (player_id),
  INDEX (move_date)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


DROP TABLE IF EXISTS `po_game_nudge`;
CREATE TABLE IF NOT EXISTS `po_game_nudge` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_message`;
CREATE TABLE IF NOT EXISTS `po_message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_message_glue`;
CREATE TABLE IF NOT EXISTS `po_message_glue` (
  `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_id` int(10) unsigned NOT NULL DEFAULT '0',
  `send_date` datetime DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `view_date` datetime DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (`message_glue_id`),
  KEY `outbox` (`from_id`,`message_id`),
  KEY `inbox` (`to_id`,`message_id`),
  KEY `created` (`create_date`),
  KEY `expire_date` (`expire_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


DROP TABLE IF EXISTS `po_settings`;
CREATE TABLE IF NOT EXISTS `po_settings` (
  `setting` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `notes` text,
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0',

  UNIQUE KEY `setting` (`setting`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


INSERT INTO `po_settings` (`setting`, `value`, `notes`, `sort`) VALUES
  ('site_name', 'Your Site Name', 'The name of your site', 10),
  ('default_color', 'c_blue_black.css', 'The default theme color for the script pages', 20),
  ('nav_links', '<a href="/">Home</a>', 'HTML code for your site''s navigation links to display on the script pages', 30),
  ('from_email', 'your.mail@yoursite.com', 'The email address used to send game emails', 40),
  ('to_email', 'you@yoursite.com', 'The email address to send admin notices to (comma separated)', 50),
  ('new_users', '1', '(1/0) Allow new users to register (0 = off)', 60),
  ('approve_users', '0', '(1/0) Require admin approval for new users (0 = off)', 70),
  ('confirm_email', '0', '(1/0) Require email confirmation for new users (0 = off)', 80),
  ('max_users', '0', 'Max users allowed to register (0 = off)', 90),
  ('default_pass', 'change!me', 'The password to use when resetting a user''s password', 100),
  ('expire_users', '45', 'Number of days until untouched games are deleted (0 = off)', 110),
  ('save_games', '1', '(1/0) Save games in the ''games'' directory on the server (0 = off)', 120),
  ('expire_games', '30', 'Number of days until untouched user accounts are deleted (0 = off)', 130),
  ('nudge_flood_control', '24', 'Number of hours between nudges. (-1 = no nudging, 0 = no flood control)', 135),
  ('timezone', 'UTC', 'The timezone to use for dates (<a href="http://www.php.net/manual/en/timezones.php">List of Timezones</a>)', 140),
  ('long_date', 'M j, Y g:i a', 'The long format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 150),
  ('short_date', 'Y.m.d H:i', 'The short format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 160),
  ('debug_pass', '', 'The DEBUG password to use to set temporary DEBUG status for the script', 170),
  ('DB_error_log', '1', '(1/0) Log database errors to the ''logs'' directory on the server (0 = off)', 180),
  ('DB_error_email', '1', '(1/0) Email database errors to the admin email addresses given (0 = off)', 190);


DROP TABLE IF EXISTS `po_po_player`;
CREATE TABLE IF NOT EXISTS `po_po_player` (
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_email` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `max_games` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `color` varchar(25) NULL DEFAULT NULL,
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_online` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


