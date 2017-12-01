-- phpMyAdmin SQL Dump
-- version 2.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 06, 2006 at 10:39 PM
-- Server version: 5.0.18
-- PHP Version: 5.1.4
--
-- Database: `power`
--

-- --------------------------------------------------------

--
-- Table structure for table `cmd_chat_1`
--

CREATE TABLE `chat_1` (
  `c_player_id` int(11) NOT NULL,
  `c_message` text NOT NULL,
  `c_timestamp` datetime NOT NULL,
  KEY `c_player_id` (`c_player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cmd_game_1`
--

CREATE TABLE `game_1` (
  `g_player_id` int(11) NOT NULL,
  `g_color_code` enum('Y','K','A','R','Z') NOT NULL,
  `g_cur_command` varchar(128) NOT NULL,
  `g_history` text NOT NULL,
  `g_state` enum('Awarding','Moving','Waiting','Incapacitated','Finished') NOT NULL,
  `g_last_move` timestamp NOT NULL default CURRENT_TIMESTAMP,
  UNIQUE KEY `g_color_code` (`g_color_code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cmd_player`
--

CREATE TABLE `player` (
  `p_id` int(10) unsigned NOT NULL auto_increment,
  `p_username` varchar(32) NOT NULL,
  `p_password` char(32) NOT NULL,
  `p_level` smallint(5) unsigned NOT NULL default '1',
  `p_email` varchar(64) NOT NULL,
  `p_wins` smallint(5) unsigned NOT NULL default '0',
  `p_losses` smallint(5) unsigned NOT NULL default '0',
  `p_last_seen` datetime NOT NULL,
  `p_ident` char(32) default NULL,
  `p_token` char(32) default NULL,
  PRIMARY KEY  (`p_id`),
  UNIQUE KEY `p_username` (`p_username`,`p_email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;
