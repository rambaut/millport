# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: tree.bio.ed.ac.uk (MySQL 5.5.5-10.3.39-MariaDB-0ubuntu0.20.04.2)
# Database: millport
# Generation Time: 2026-04-06 06:55:03 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table comedy
# ------------------------------------------------------------

CREATE TABLE `comedy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `species` varchar(128) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `image` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table identifications
# ------------------------------------------------------------

CREATE TABLE `identifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `species_id` int(11) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `site` int(11) unsigned DEFAULT NULL,
  `location` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `laboratory` int(1) unsigned DEFAULT NULL,
  `identified_by` varchar(64) DEFAULT NULL,
  `corroborated_by` varchar(64) DEFAULT NULL,
  `time` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table latest
# ------------------------------------------------------------

CREATE TABLE `latest` (
  `id` int(11) DEFAULT NULL,
  `species_id` int(11) unsigned DEFAULT NULL,
  `level` varchar(128) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `laboratory` int(1) unsigned DEFAULT NULL,
  `ident_id` int(11) unsigned DEFAULT NULL,
  `time` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table phyla
# ------------------------------------------------------------

CREATE TABLE `phyla` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_plant` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table sites
# ------------------------------------------------------------

CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `map` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table species
# ------------------------------------------------------------

CREATE TABLE `species` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `genus` varchar(1024) DEFAULT NULL,
  `species` varchar(1024) DEFAULT NULL,
  `class` varchar(128) DEFAULT NULL,
  `phylum_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(1024) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phylum_id` (`phylum_id`),
  FULLTEXT KEY `binomial` (`genus`,`species`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table synonyms
# ------------------------------------------------------------

CREATE TABLE `synonyms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `species_id` int(11) unsigned DEFAULT NULL,
  `synonym` varchar(256) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



# Dump of table years
# ------------------------------------------------------------

CREATE TABLE `years` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(4) DEFAULT NULL,
  `class_size` int(4) DEFAULT NULL,
  `tides` double DEFAULT NULL,
  `low_tide_time` time DEFAULT NULL,
  `tides_at_sampling` double DEFAULT NULL,
  `weather` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
