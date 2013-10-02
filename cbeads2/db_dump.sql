-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 06, 2012 at 05:19 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `cbeads`
--
CREATE DATABASE `cbeads` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `cbeads`;

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE IF NOT EXISTS `application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `namespace` (`namespace`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=26 ;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`id`, `name`, `description`, `namespace`, `version`) VALUES
(1, 'CBEADS', 'CBEADS Framework Core', 'cbeads', ''),
(15, 'SBOML', 'This application is for managing objects in the system using SBOML (Smart Business Objects Modeling Language)', 'sboml', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attribute_definition`
--

CREATE TABLE IF NOT EXISTS `attribute_definition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `db_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `render_type_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `additional` text COLLATE utf8_unicode_ci,
  `alias_for` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `render_type` (`render_type_id`),
  KEY `alias_for` (`alias_for`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=24 ;

--
-- Dumping data for table `attribute_definition`
--

INSERT INTO `attribute_definition` (`id`, `name`, `db_type`, `render_type_id`, `comment`, `additional`, `alias_for`) VALUES
(1, 'name', 'VARCHAR', 1, NULL, NULL, NULL),
(2, 'first_name', 'VARCHAR', 1, '', '', NULL),
(3, 'birthday', 'DATE', 5, 'abcdefghijk', '', NULL),
(5, 'address', 'TEXT', 6, '', '', NULL),
(6, 'email', 'VARCHAR', 7, '', '', NULL),
(7, 'photo', NULL, NULL, NULL, NULL, 13),
(8, 'password', 'VARCHAR', 14, 'For entering passwords.', '', NULL),
(9, 'description', 'TEXT', 6, '', '', NULL),
(10, 'notes', 'TEXT', 6, '', '', NULL),
(11, 'date', 'DATE', 5, 'For attributes that contain a date', '', NULL),
(12, 'colour', 'VARCHAR', 15, 'For attributes that store a colour value. These will use a colourpicker control.', '', NULL),
(13, 'file', 'FILE', 13, 'Attribute for file stored on server (column will store the file name)', NULL, NULL),
(14, 'pfile', 'PFILE', 13, 'Attribute for a private file (file is stored in database)', NULL, NULL),
(15, 'cv', NULL, NULL, NULL, NULL, 14),
(16, 'image', 'FILE', 16, NULL, NULL, NULL),
(17, 'pphoto', NULL, NULL, NULL, NULL, 14),
(18, 'html', 'LONGTEXT', 12, NULL, NULL, NULL),
(19, 'comment', NULL, NULL, NULL, NULL, 9),
(22, 'learning_guide', NULL, NULL, NULL, NULL, 13),
(23, 'unit_outline', NULL, NULL, NULL, NULL, 13);

-- --------------------------------------------------------

--
-- Table structure for table `attribute_render_def`
--

CREATE TABLE IF NOT EXISTS `attribute_render_def` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `validation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `input_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `output_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `additional` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=17 ;

--
-- Dumping data for table `attribute_render_def`
--

INSERT INTO `attribute_render_def` (`id`, `name`, `label`, `validation`, `input_type`, `output_type`, `width`, `height`, `additional`) VALUES
(1, 'textbox', NULL, NULL, 'textbox', 'text', 30, 0, NULL),
(2, 'integer', 'a predefined label for integer field', 'NUMBER', 'textbox', 'text', 10, 0, ''),
(5, 'date', '', 'DATE', 'date', 'text', 0, 0, ''),
(6, 'textarea', NULL, NULL, 'textarea', 'text', 80, 10, NULL),
(7, 'email', 'Email', '/^[\\w\\-\\+\\._]+\\@[a-zA-Z0-9][-a-zA-Z0-9\\.]*\\.[a-zA-Z]+$/', 'textbox', 'email', 30, 0, NULL),
(8, 'map', '', '', 'map', 'map', 0, 0, ''),
(9, 'ordered list', '', '', 'sort_list', 'text', 0, 0, ''),
(10, 'html editor', '', '', 'html_editor', 'text', 0, 0, ''),
(11, 'TinyMCE', '', '', 'TinyMCE', 'text', 0, 0, ''),
(12, 'CKEditor', '', '', 'CKEditor', 'text', 0, 0, ''),
(13, 'file', NULL, NULL, 'file', 'file_download', 0, 0, NULL),
(14, 'password', 'password', '/^[\\w.!?@#$%&*]{5,}$/', 'password', 'none', 0, 0, ''),
(15, 'colour_picker', 'Colour', NULL, 'colourpicker', 'colour_display', 100, 100, NULL),
(16, 'image', 'image', NULL, 'file', 'image', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `session_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `ip_address` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `user_agent` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `function`
--

CREATE TABLE IF NOT EXISTS `function` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `controller_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `application_id` int(11) NOT NULL,
  `is_public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=62 ;

--
-- Dumping data for table `function`
--

INSERT INTO `function` (`id`, `name`, `description`, `controller_name`, `application_id`, `is_public`) VALUES
(10, 'Manage Users', 'Manage CBEADS Users, Roles, Teams, associations between teams, roles and users.', 'Manage_users', 1, 0),
(11, 'Manage Applications', 'Allows applications and their functions to be managed. Also allows functions to be assigned to team roles.', 'Manage_applications', 1, 0),
(12, 'Home', 'User''s home page.', 'Home', 1, 0),
(14, 'Manage Attributes', 'Manage attributes and how they are supposed to be displayed.', 'Manage_attributes', 1, 0),
(15, 'Session', 'See what users are logged onto the system and allows the removal of expired sessions.', 'Session', 1, 0),
(43, 'SBO Manager', 'Tool for manipulating databases using SBOML', 'sbo_manager', 15, 0),
(44, 'Editable Models', 'Displays a list of editable databases/namespaces', 'Editable_models', 15, 0),
(59, 'Manage Menu', 'Allows one to change the structure of the CBEADS menu on a global, team or team-role basis.', 'Manage_menu', 1, 0),
(60, 'Access Logs', 'Displays information stored in the access logs.', 'Access_logs', 1, 0),
(61, 'Controller Access and Profiles', 'Lets one view data collected on controller accesses and profiling information such as execution times and memory usage.', 'Controller_access_and_profiles', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `function_access_profile`
--

CREATE TABLE IF NOT EXISTS `function_access_profile` (
  `day` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  `ctrl_id` int(11) NOT NULL,
  `func_name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `hits` mediumint(8) unsigned NOT NULL,
  `first_hit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_hit` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `runtime_avg` float unsigned NOT NULL,
  `runtime_min` float unsigned NOT NULL,
  `runtime_max` float unsigned NOT NULL,
  `mem_avg` int(10) unsigned NOT NULL,
  `mem_min` int(10) unsigned NOT NULL,
  `mem_max` int(10) unsigned NOT NULL,
  PRIMARY KEY (`day`,`user_id`,`app_id`,`ctrl_id`,`func_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_group`
--

CREATE TABLE IF NOT EXISTS `menu_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `position` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `show_group_header` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `role_id` (`role_id`),
  KEY `app_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `menu_group`
--


-- --------------------------------------------------------

--
-- Table structure for table `menu_item`
--

CREATE TABLE IF NOT EXISTS `menu_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `function_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `position` int(11) NOT NULL,
  `custom_url` text COLLATE utf8_unicode_ci,
  `menu_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `function_id` (`function_id`,`menu_group_id`),
  KEY `menu_group_id` (`menu_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `menu_item`
--

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_nonce`
--

CREATE TABLE IF NOT EXISTS `password_reset_nonce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nonce` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `name_2` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `name`, `description`) VALUES
(1, 'Administrator', 'For people that have administrative duties in the team.'),
(5, 'All', 'A role that all users can have.');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE IF NOT EXISTS `team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`id`, `name`, `description`) VALUES
(1, 'CBEADS', 'For people that manage and work with CBEADS.');

-- --------------------------------------------------------

--
-- Table structure for table `team_application_map`
--

CREATE TABLE IF NOT EXISTS `team_application_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`,`application_id`),
  KEY `application_id` (`application_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=24 ;

--
-- Dumping data for table `team_application_map`
--

INSERT INTO `team_application_map` (`id`, `team_id`, `application_id`) VALUES
(1, 1, 1),
(20, 1, 15);

-- --------------------------------------------------------

--
-- Table structure for table `team_role_function_map`
--

CREATE TABLE IF NOT EXISTS `team_role_function_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `function_id` int(11) NOT NULL,
  `team_role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `function_id` (`function_id`,`team_role_id`),
  KEY `team_role_id` (`team_role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=165 ;

--
-- Dumping data for table `team_role_function_map`
--

INSERT INTO `team_role_function_map` (`id`, `function_id`, `team_role_id`) VALUES
(1, 10, 1),
(5, 11, 1),
(6, 12, 1),
(7, 14, 1),
(8, 15, 1),
(141, 43, 1),
(142, 44, 1),
(162, 59, 1),
(163, 60, 1),
(164, 61, 1);

-- --------------------------------------------------------

--
-- Table structure for table `team_role_map`
--

CREATE TABLE IF NOT EXISTS `team_role_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `team_role_map`
--

INSERT INTO `team_role_map` (`id`, `team_id`, `role_id`) VALUES
(1, 1, 1),
(2, 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `change_password` tinyint(1) NOT NULL,
  `can_login` tinyint(1) NOT NULL DEFAULT '1',
  `default_team` int(11) DEFAULT NULL,
  `status` text COLLATE utf8_unicode_ci,
  `preferences` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `firstname`, `lastname`, `uid`, `password`, `email`, `change_password`, `can_login`, `default_team`, `status`, `preferences`) VALUES
(1, 'Administrator', 'Administrator', 'admin', 'eb555c48ae2d8d2b96603caeb48bf58b', 'admin@localhost.com', 0, 1, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_team_role_map`
--

CREATE TABLE IF NOT EXISTS `user_team_role_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`team_role_id`),
  KEY `team_role_id` (`team_role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `user_team_role_map`
--

INSERT INTO `user_team_role_map` (`id`, `user_id`, `team_role_id`) VALUES
(1, 1, 1),
(2, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `validation_type`
--

CREATE TABLE IF NOT EXISTS `validation_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `regex` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `validation_type`
--

INSERT INTO `validation_type` (`id`, `name`, `regex`, `comment`) VALUES
(1, 'NUMBER', '/^-?\\s*[0-9]+\\.?[0-9]*$|^-?\\s*\\.[0-9]+$/', NULL),
(2, 'INTEGER', '/^-?\\s*[0-9]+$/', NULL),
(3, 'FLOAT', '/^-?\\s*[0-9]+\\.[0-9]+$/', NULL),
(4, 'DATE', '/^[0-9]{4}-?(0?[1-9]|1[0-2])-(0?[1-9]|[1-2][0-9]|3[0-1])$/', '/^(0?[1-9]|[1-2][0-9]|3[0-1])\\/?(0?[1-9]|1[0-2])\\/?[0-9]{4}$/'),
(5, 'EMAIL', '/^[\\w\\-\\+\\._]+\\@[a-zA-Z0-9][-a-zA-Z0-9\\.]*\\.[a-zA-Z]+$/', NULL),
(6, 'NAME', '/^[a-zA-Z]+$/', 'Matches a name (single word)');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attribute_definition`
--
ALTER TABLE `attribute_definition`
  ADD CONSTRAINT `attribute_definition_ibfk_1` FOREIGN KEY (`alias_for`) REFERENCES `attribute_definition` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cbeads_render_type_id_cbeads_id` FOREIGN KEY (`render_type_id`) REFERENCES `attribute_render_def` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `menu_group`
--
ALTER TABLE `menu_group`
  ADD CONSTRAINT `menu_group_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_group_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_group_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD CONSTRAINT `menu_item_ibfk_1` FOREIGN KEY (`menu_group_id`) REFERENCES `menu_group` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_item_ibfk_2` FOREIGN KEY (`function_id`) REFERENCES `function` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_application_map`
--
ALTER TABLE `team_application_map`
  ADD CONSTRAINT `team_application_map_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_application_map_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_role_function_map`
--
ALTER TABLE `team_role_function_map`
  ADD CONSTRAINT `team_role_function_map_ibfk_2` FOREIGN KEY (`team_role_id`) REFERENCES `team_role_map` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_role_function_map_ibfk_3` FOREIGN KEY (`function_id`) REFERENCES `function` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_role_map`
--
ALTER TABLE `team_role_map`
  ADD CONSTRAINT `team_role_map_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_role_map_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_team_role_map`
--
ALTER TABLE `user_team_role_map`
  ADD CONSTRAINT `user_team_role_map_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_team_role_map_ibfk_2` FOREIGN KEY (`team_role_id`) REFERENCES `team_role_map` (`id`) ON DELETE CASCADE;
--
-- Database: `sboml`
--
CREATE DATABASE `sboml` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `sboml`;

-- --------------------------------------------------------

--
-- Table structure for table `editable_model`
--

CREATE TABLE IF NOT EXISTS `editable_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(30) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_name` (`model_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=32 ;

SET FOREIGN_KEY_CHECKS=1;
