-- phpMyAdmin SQL Dump
-- version 2.9.0-rc1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Sep 28, 2007 at 03:40 PM
-- Server version: 5.0.24
-- PHP Version: 5.1.2
-- 
-- 

-- --------------------------------------------------------
-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_dynamic`
-- 

DROP TABLE IF EXISTS `ssc_dynamic`;
CREATE TABLE `ssc_dynamic` (
  `id` smallint(6) NOT NULL auto_increment,
  `nav_id` int(11) NOT NULL,
  `title` varchar(50) character set latin1 NOT NULL,
  `comments` binary(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `nav_id` (`nav_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_dynamic_comments`
-- 

DROP TABLE IF EXISTS `ssc_dynamic_comments`;
CREATE TABLE `ssc_dynamic_comments` (
  `id` int(11) NOT NULL auto_increment,
  `post_id` int(11) NOT NULL,
  `name` varchar(30) collate utf8_bin NOT NULL,
  `email` varchar(50) collate utf8_bin NOT NULL,
  `site` varchar(80) collate utf8_bin NOT NULL,
  `comment` text collate utf8_bin NOT NULL,
  `date` datetime NOT NULL,
  `spam` binary(1) NOT NULL,
  `ip` varchar(20) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_dynamic_content`
-- 

DROP TABLE IF EXISTS `ssc_dynamic_content`;
CREATE TABLE `ssc_dynamic_content` (
  `id` int(11) NOT NULL auto_increment,
  `blog_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(50) character set latin1 NOT NULL,
  `uri` varchar(50) character set latin1 NOT NULL,
  `content` text character set latin1 NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `blog_id` (`blog_id`),
  KEY `date` (`date`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_dynamic_relation`
-- 

DROP TABLE IF EXISTS `ssc_dynamic_relation`;
CREATE TABLE `ssc_dynamic_relation` (
  `content_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY  (`content_id`,`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_dynamic_relation`
-- 
-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_dynamic_tags`
-- 

DROP TABLE IF EXISTS `ssc_dynamic_tags`;
CREATE TABLE `ssc_dynamic_tags` (
  `id` smallint(6) NOT NULL auto_increment,
  `tag` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_events`
-- 

DROP TABLE IF EXISTS `ssc_events`;
CREATE TABLE `ssc_events` (
  `id` smallint(6) NOT NULL auto_increment,
  `name` varchar(100) collate utf8_bin NOT NULL,
  `description` varchar(250) collate utf8_bin NOT NULL,
  `dt` date NOT NULL,
  `uri` varchar(200) collate utf8_bin NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_events`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_gallery`
-- 

DROP TABLE IF EXISTS `ssc_gallery`;
CREATE TABLE `ssc_gallery` (
  `id` smallint(6) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_gallery`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_gallery_content`
-- 

DROP TABLE IF EXISTS `ssc_gallery_content`;
CREATE TABLE `ssc_gallery_content` (
  `id` int(11) NOT NULL auto_increment,
  `owner` varchar(50) NOT NULL,
  `caption` varchar(50) NOT NULL,
  `med` tinyint(4) NOT NULL,
  `gallery_id` smallint(6) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `gallery_id` (`gallery_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_gallery_content`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_groups`
-- 

DROP TABLE IF EXISTS `ssc_groups`;
CREATE TABLE `ssc_groups` (
  `id` smallint(6) NOT NULL auto_increment,
  `name` varchar(20) NOT NULL,
  `description` varchar(150) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_groups`
-- 

INSERT INTO `ssc_groups` (`id`, `name`, `description`) VALUES 
(1, 'Super Administrator', 'Able to access all admin options including add / remove users');

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_log`
-- 

DROP TABLE IF EXISTS `ssc_log`;
CREATE TABLE `ssc_log` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL,
  `username` varchar(150) NOT NULL,
  `success` smallint(6) NOT NULL,
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_module_config`
-- 

DROP TABLE IF EXISTS `ssc_module_config`;
CREATE TABLE `ssc_module_config` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(20) collate utf8_bin NOT NULL,
  `value` varchar(50) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `key` (`key`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_module_config`
-- 

INSERT INTO `ssc_module_config` (`id`, `key`, `value`) VALUES 
(1, 0x6576656e74735f7469746c65, 0x4576656e7473),
(2, 0x6576656e74735f7469746c655f726563656e74, 0x526563656e74204576656e7473),
(3, 0x6576656e74735f7469746c655f63757272656e74, 0x43757272656e74204576656e7473),
(4, 0x6576656e74735f7469746c655f667574757265, 0x5570636f6d696e67204576656e7473),
(5, 0x6576656e74735f726563656e745f7374617274, 0x2d33207765656b),
(6, 0x6576656e74735f63757272656e745f7374617274, 0x746f646179),
(7, 0x6576656e74735f63757272656e745f656e64, 0x2b342064617973),
(8, 0x6576656e74735f6675747572655f656e64, 0x2b33207765656b73);

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_modules`
-- 

DROP TABLE IF EXISTS `ssc_modules`;
CREATE TABLE `ssc_modules` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `title` varchar(50) NOT NULL,
  `admin_text` varchar(50) NOT NULL,
  `admin_description` varchar(100) NOT NULL,
  `admin_about` varchar(100) NOT NULL,
  `version` varchar(10) NOT NULL,
  `filename` varchar(25) NOT NULL,
  `image` varchar(25) NOT NULL,
  `installed` smallint(1) NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `installed` (`installed`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_modules`
-- 

INSERT INTO `ssc_modules` (`id`, `name`, `title`, `admin_text`, `admin_description`, `admin_about`, `version`, `filename`, `image`, `installed`, `updated`) VALUES 
(1, 'Home', 'Welcome to LBYC', 'Home', 'Add or delete a post from the homepage', 'Main website homepage', '', 'home', 'home', 1, '2006-11-30 15:08:20'),
(9, 'users', 'User Administration', 'Users', 'Add, edit and remove user accounts and groups', 'Control administration access / accounts', '', 'users', 'user', 0, '2007-01-07 12:07:43'),
(10, 'modules', 'Modules', 'Modules', 'Install and remove modules and re-arrange the navigation bar', 'Control for additional module installation', '', 'modules', 'component', 0, '2007-01-14 19:48:20'),
(11, 'Navigation', 'Navigation', 'Navigation', 'Re-arrange the navigation bar, show/hide items.', 'Controls how the navigation bar is displayed', '', 'navigation', 'nav', 0, '2007-05-05 18:47:54'),
(12, 'static', 'Static', 'Static Text', 'Add, delete and modify pages with static content', 'Easily add and modify pages with static content', '', 'static', 'text', 0, '2007-05-20 16:07:37'),
(13, 'Photo Gallery name', 'Photo Gallery title', 'Photo Galleries', 'Set up individual galleries or add/remove photos', 'Set up and edit photo galleries', '0.5', 'gallery', 'gallery', 1, '2007-06-09 14:12:44'),
(14, 'Events', 'Events', 'Events', 'Add, remove or edit events or modify front-end display', 'Set up an events list showing upcoming, current and previous events', '', 'events', 'event', 0, '2007-07-12 11:32:45'),
(15, 'Race Results', 'Race Results', 'Race Results', 'Add, remove or edit race series'' as well as upload results', 'Allows the display of results from races throughout the years', 'ver', 'results', 'results', 1, '2007-07-16 15:38:02'),
(16, 'Dynamic Pages Name', 'Dynamic Pages Title', 'Dynamic Admin Text', 'Set up a dynamic page, eg for a news page', 'Adds the ability to set up a dynamic page which can be used for a news page, blog, etc', '0.5', 'dynamic', 'text', 1, '2007-09-09 18:48:53');

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_navigation`
-- 

DROP TABLE IF EXISTS `ssc_navigation`;
CREATE TABLE `ssc_navigation` (
  `id` smallint(6) NOT NULL auto_increment,
  `module_id` smallint(6) NOT NULL,
  `name` varchar(30) NOT NULL,
  `uri` text NOT NULL,
  `position` tinyint(4) NOT NULL,
  `hidden` binary(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `module_id` (`module_id`),
  KEY `position` (`position`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_navigation`
-- 

INSERT INTO `ssc_navigation` (`id`, `module_id`, `name`, `uri`, `position`, `hidden`) VALUES 
(1, 1, 'Home', '/', 1, 0x30),
(2, 14, 'Club Events', '/event', 60, 0x30),
(3, 15, 'Results', '/results', 70, 0x30),
(4, 13, 'Gallery', '/gallery', 50, 0x30);

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_permissions`
-- 

DROP TABLE IF EXISTS `ssc_permissions`;
CREATE TABLE `ssc_permissions` (
  `id` smallint(6) NOT NULL auto_increment,
  `module_id` smallint(6) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_results_entries`
-- 

DROP TABLE IF EXISTS `ssc_results_entries`;
CREATE TABLE `ssc_results_entries` (
  `no` varchar(20) NOT NULL,
  `skipper` varchar(50) NOT NULL,
  `class` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `crew` varchar(50) NOT NULL,
  `club` varchar(6) NOT NULL,
  PRIMARY KEY  (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_results_results`
-- 

DROP TABLE IF EXISTS `ssc_results_results`;
CREATE TABLE `ssc_results_results` (
  `id` int(11) NOT NULL auto_increment,
  `number` varchar(20) NOT NULL,
  `series_id` int(11) NOT NULL,
  `results` text NOT NULL,
  `points` int(11) NOT NULL,
  `division` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `number` (`number`,`series_id`,`division`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_results_series`
-- 

DROP TABLE IF EXISTS `ssc_results_series`;
CREATE TABLE `ssc_results_series` (
  `id` int(11) NOT NULL auto_increment,
  `nav_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `dt` date NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `nav_id` (`nav_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_results_series`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_static`
-- 

DROP TABLE IF EXISTS `ssc_static`;
CREATE TABLE `ssc_static` (
  `id` smallint(6) NOT NULL auto_increment,
  `nav_id` smallint(6) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- --------------------------------------------------------

-- 
-- Table structure for table `ssc_users`
-- 

DROP TABLE IF EXISTS `ssc_users`;
CREATE TABLE `ssc_users` (
  `id` smallint(6) NOT NULL auto_increment,
  `group_id` smallint(6) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `username` varchar(10) NOT NULL,
  `display` varchar(30) NOT NULL,
  `password` varchar(35) NOT NULL,
  `email` varchar(50) NOT NULL,
  `last_access` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  FULLTEXT KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `ssc_users`
-- 

INSERT INTO `ssc_users` (`id`, `group_id`, `fullname`, `username`, `display`, `password`, `email`, `last_access`) VALUES 
(1, 1, 'Admin User', 'admin', 'admin', '0a50e073fc5f28bfc585110f8018555a', '', '2007-01-01 01:00:00');
