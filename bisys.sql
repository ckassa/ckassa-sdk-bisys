-- phpMyAdmin SQL Dump
-- version 3.2.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 22, 2012 at 10:33 AM
-- Server version: 5.1.40
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `bisys`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE IF NOT EXISTS `accounts` (
 `account_id` int(11) NOT NULL AUTO_INCREMENT,
 `account` varchar(50) NOT NULL,
 `status` int(11) NOT NULL,
 `client_id` int(11) NOT NULL,
 `balance` int(11) NOT NULL,
 PRIMARY KEY (`account_id`),
 KEY `account` (`account`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
 `log_id` int(11) NOT NULL AUTO_INCREMENT,
 `date` datetime NOT NULL,
 `ip` varchar(20) NOT NULL,
 `in_data` varchar(1000) NOT NULL,
 `out_data` varchar(1000) NOT NULL,
 `err_code` int(11) NOT NULL,
 `err_text` varchar(100) NOT NULL,
 KEY `date` (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
 `payment_id` int(11) NOT NULL AUTO_INCREMENT,
 `agent_id` int(11) NOT NULL,
 `agent_date` datetime NOT NULL,
 `pay_date` datetime NOT NULL,
 `pay_num` varchar(50) NOT NULL,
 `account_id` int(11) NOT NULL,
 `amount` int(11) NOT NULL,
 `reg_date` datetime NOT NULL,
 PRIMARY KEY (`payment_id`),
 UNIQUE KEY `agent_id` (`agent_id`,`pay_num`)
) ENGINE=MyISAM  DEFAULT CHARSET=cp1251; 



