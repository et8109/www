-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 08, 2013 at 03:51 AM
-- Server version: 5.5.24-log
-- PHP Version: 5.4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `game`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `ID` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `Description` tinytext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`ID`, `Description`) VALUES
(1, 'Your new item has been added to your description. You should edit it soon.'),
(2, 'An item of yours has been hidden, you can change your description so it it no longer highlighted.');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `ID` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `Name` char(20) NOT NULL,
  `Description` tinytext NOT NULL,
  `size` int(1) unsigned NOT NULL,
  `room` int(3) unsigned NOT NULL COMMENT 'space to store things in',
  `insideOf` int(3) unsigned NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=181 ;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`ID`, `Name`, `Description`, `size`, `room`, `insideOf`) VALUES
(100, 'Sword', 'normal sword', 0, 0, 0),
(101, 'shield', 'A simple shield.', 0, 0, 0),
(111, 'testsql', 'ignore this', 0, 0, 0),
(112, 'asdf', 'a metal sword', 0, 0, 0),
(113, 'sdfds', 'frw wood', 0, 0, 0),
(114, 'ytyt', 'metal wood', 0, 0, 0),
(115, 'Giant blob!', 'hmmm, wooden, i guess', 0, 0, 0),
(116, 'xgf', 'wood', 0, 0, 0),
(117, 'ef', 'wood', 0, 0, 0),
(118, 'sdf', 'metal', 0, 0, 0),
(119, 'sfd', 'sdf wood', 0, 0, 0),
(120, 'fifthThing', 'wooden metal stuff', 0, 0, 0),
(121, 'sadf', 'metalcrafted', 0, 0, 0),
(122, 'sdfs', 'sadf wood', 0, 0, 0),
(123, 'df', 'metal', 0, 0, 0),
(124, 'dsg', 'metal', 0, 0, 0),
(125, 'efdgfd', 'n thing', 0, 0, 0),
(126, 'null', 'null', 0, 0, 0),
(127, 'null', 'null', 0, 0, 0),
(128, 'null', 'null', 0, 0, 0),
(129, 'sdfge', 'werewetal', 0, 0, 0),
(130, 'a sword', 'made on friday, out o', 0, 0, 0),
(131, 'stick', '<span class=''material''>metal</span>en and pointy.', 0, 0, 0),
(132, 'dfgds', '<span class=''keyword'' onclick=''addDesc(undefined, 0)''>plain</span> and <span class=''keyword'' onclick=''addDesc(3, 0)''>wood</span>en.', 0, 0, 0),
(133, 'new1', 'null', 0, 0, 0),
(134, 'itemNumTwo', 'null', 0, 0, 0),
(135, 'things', '<span class=''keyword'' onclick=''addDesc([object Object], null)''>wood</span>en, <span class=''keyword'' onclick=''addDesc([object Object], null)''>plain</span>.', 0, 0, 0),
(136, 'sdfds', '<span class=''keyword'' onclick=''addDesc(3, wood)''>wood</span>en <span class=''keyword'' onclick=''addDesc(4, plain)''>plain</span> tihngs.', 0, 0, 0),
(137, 'three', '<span class=''keyword'' onclick=''addDesc(4, ''plain'')''>plain</span> <span class=''keyword'' onclick=''addDesc(3, ''wood'')''>wood</span>.', 0, 0, 0),
(138, 'four', '<span class=''keyword'' onclick=''addDescKeyword(4,0,0)''>plain</span> <span class=''keyword'' onclick=''addDescKeyword(3,0,0)''>wood</span>.', 0, 0, 0),
(139, 'stinds', '<span class=''keyword'' onclick=''addDesc(4, plain)''>simple</span> <span class=''keyword'' onclick=''addDesc(3, metal)'' >metal</span>lic.', 0, 0, 0),
(140, 'sevener', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, simple)''>simple</span> and <span class=''keyword'' onclick=''addDescKeyword(3, 1, metal)'' >metal</span>lic sword.', 0, 0, 0),
(141, 'tester', '<span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en 1 things.', 0, 0, 0),
(142, 'testerDos', '1 and <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en.', 0, 0, 0),
(143, 'testertres', '<span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en and 1.', 0, 0, 0),
(144, 'goodTester', '1 <span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metal</span> object.', 0, 0, 0),
(145, 'pudding', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>. has a <span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metal</span>lic taste.', 0, 0, 0),
(146, 'barks', '<span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(147, 'board', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>, <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>.', 0, 0, 0),
(148, 'bath', '<span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metal</span>, <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(149, 'things', '<span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en and <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(150, 'wefre', 'not enough room, probs. <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en, <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(151, 'thingds', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>, <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en.', 0, 0, 0),
(152, 'wefew', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>, <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en.', 0, 0, 0),
(153, 'ewfew', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>, <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en.', 0, 0, 0),
(154, 'anItem', '<span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wood</span>en and <span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metal</span> and <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(155, 'tester5', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 0)''>plain</span> <span class=''keyword'' onclick=''addDescKeyword(3, 1, 1)'' >metal</span> thing.', 0, 0, 0),
(156, 'testet6', '<span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span> <span class=''keyword'' onclick=''addDescKeyword(3, 0, 0)''>wooden</span> thing.', 0, 0, 0),
(157, 'tester 7..', 'a nice <span class=''keyword'' onclick=''addDescKeyword(3, 1, 1)'' >metal</span> blob. very <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(158, 'tester8', '<span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metallic</span>, <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(159, 'tester9', '<span class=''keyword'' onclick=''addDescKeyword(3, 1, 0)'' >metallic</span> <span class=''keyword'' onclick=''addDescKeyword(4, 0, 1)''>simple</span>.', 0, 0, 0),
(160, 'tester1', '<span class=''material'' onclick=''addDesc(3,simple)''>''tester1''</span> matallic.', 0, 0, 0),
(161, 'stuff', 'simple, yo.', 0, 0, 0),
(162, 'stuff', 'simple, yo.', 0, 0, 0),
(163, 'stuff', 'simple, yo.', 0, 0, 0),
(164, 'stuff', 'simple, yo.', 0, 0, 0),
(165, 'stuff', 'simple, yo.', 0, 0, 0),
(166, 'stuff', 'simple, yo.', 0, 0, 0),
(167, 'stuff', 'simple, yo.', 0, 0, 0),
(168, 'stuff', 'simple, yo.', 0, 0, 0),
(169, 'stuff', 'aime.', 0, 0, 0),
(170, 'stuff', 'simple.', 0, 0, 0),
(171, 'stuff', 'yea, yo.', 0, 0, 0),
(172, 'stuff2', 'failure. <span class=''material'' onclick=''addDesc(3,wood)''>''stuff2''</span> .', 0, 0, 0),
(173, 'yoyos', 'fail. <span class=''material'' onclick=''addDesc(3,wood)''>''yoyos''</span> . <span class=''material'' onclick=''addDesc(3,simple)''>''yoyos''</span> .', 0, 0, 0),
(174, 'yayers', '<span class=''keyword'' onclick=''addDesc(3,''simple'')''>''simple''</span> <span class=''keyword'' onclick=''addDesc(3,''metal'')''>''metal''</span> ball.', 0, 0, 0),
(175, 'grrrguh', '<span class=''keyword'' onclick=''addDesc(3,simple)''>''simple''</span> <span class=''keyword'' onclick=''addDesc(3,wooden)''>''wooden''</span> thinlg.', 0, 0, 0),
(176, 'yoohoo', '<span class=''keyword'' onclick=''addDesc(3,''simple'')''>''simple''</span> <span class=''keyword'' onclick=''addDesc(3,''metallic'')''>''metallic''</span> .', 0, 0, 0),
(177, 'blarg', '<span class=''keyword'' onclick=''addDesc(3,&apos;simple&apos;)''>''simple''</span> <span class=''keyword'' onclick=''addDesc(3,&apos;metallic&apos;)''>''metallic''</span> .', 0, 0, 0),
(178, 'things', '<span class=''keyword'' onclick=''addDesc(3,&apos;simple&apos;)''>simple</span> <span class=''keyword'' onclick=''addDesc(3,&apos;wooden&apos;)''>wooden</span> .', 0, 0, 0),
(179, 'yo', '<span class=''keyword'' onclick=''addDesc(3,&apos;simple&apos;)''>simple</span> <span class=''keyword'' onclick=''addDesc(3,&apos;wooden&apos;)''>wooden</span> .', 0, 0, 0),
(180, 'things', '<span class=''keyword'' onclick=''addDesc(3,&apos;simple&apos;)''>simple</span> <span class=''keyword'' onclick=''addDesc(3,&apos;metal&apos;)''>metal</span> things.', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `keywords`
--

CREATE TABLE IF NOT EXISTS `keywords` (
  `ID` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `Description` tinytext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `keywords`
--

INSERT INTO `keywords` (`ID`, `Description`) VALUES
(1, 'Not very strong, it is usually used for the handles of things. The useful ends of object should have a stronger material.'),
(2, 'A strong material, but it must be mined and then smelted into a practical shape.'),
(3, 'It does what it''s supposed to.'),
(4, 'Very fancy. Whoever made this is a skilled craftsman.'),
(5, 'This item can hold other items.');

-- --------------------------------------------------------

--
-- Table structure for table `keywordwords`
--

CREATE TABLE IF NOT EXISTS `keywordwords` (
  `Word` varchar(20) NOT NULL,
  `ID` int(3) unsigned NOT NULL COMMENT 'Same ID means synonyms for a keyword',
  `Type` int(3) unsigned NOT NULL COMMENT '0:container, 1:material, 2:quality',
  PRIMARY KEY (`Word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `keywordwords`
--

INSERT INTO `keywordwords` (`Word`, `ID`, `Type`) VALUES
('bag', 5, 0),
('beautiful', 4, 2),
('excellent', 4, 2),
('exquisite', 4, 2),
('metal', 2, 1),
('metallic', 2, 1),
('plain', 3, 2),
('simple', 3, 2),
('wood', 1, 1),
('wooden', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `playeralerts`
--

CREATE TABLE IF NOT EXISTS `playeralerts` (
  `playerID` int(3) unsigned NOT NULL,
  `alertID` int(3) unsigned NOT NULL,
  PRIMARY KEY (`playerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `playeralerts`
--

INSERT INTO `playeralerts` (`playerID`, `alertID`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `playerinfo`
--

CREATE TABLE IF NOT EXISTS `playerinfo` (
  `ID` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `Name` char(20) NOT NULL,
  `Password` char(20) NOT NULL,
  `Description` tinytext NOT NULL,
  `Scene` int(3) unsigned NOT NULL,
  `craftSkill` int(1) unsigned NOT NULL,
  `adminLevel` int(2) unsigned NOT NULL,
  `frontLoadAlerts` tinyint(1) NOT NULL,
  `frontLoadScenes` tinyint(1) NOT NULL,
  `frontLoadKeywords` tinyint(1) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ID` (`ID`),
  KEY `Scene` (`Scene`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `playerinfo`
--

INSERT INTO `playerinfo` (`ID`, `Name`, `Password`, `Description`, `Scene`, `craftSkill`, `adminLevel`, `frontLoadAlerts`, `frontLoadScenes`, `frontLoadKeywords`) VALUES
(0, 'tester', 'password', 'tests things.', 101, 0, 1, 0, 0, 0),
(1, 'Ensetym', 'password', 'Crystal Ball, <span class=''item'' onclick=''addDesc(0,177)''>blarg</span> <span class=''item'' onclick=''addDesc(0,176)''>yoohoo</span> <span class=''item'' onclick=''addDesc(0,172)''>stuff2</span> <span class=''item'' onclick=''addDesc(0,173)''>yoyos</span> <span class', 102, 0, 1, 0, 0, 0),
(2, 'HeWhoIsSec', 'pass', 'none', 100, 0, 0, 0, 0, 0),
(3, 'wefrew', 'sec', 'none', 100, 0, 0, 0, 0, 0),
(4, 'Tester', 'Tester', 'Testers description.', 100, 0, 0, 0, 0, 0),
(5, 'Tester', 'Tester', 'Testers description.', 100, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `playeritems`
--

CREATE TABLE IF NOT EXISTS `playeritems` (
  `playerID` int(3) unsigned NOT NULL,
  `itemID` int(3) unsigned NOT NULL,
  PRIMARY KEY (`itemID`),
  KEY `playerID` (`playerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `playeritems`
--

INSERT INTO `playeritems` (`playerID`, `itemID`) VALUES
(1, 172),
(1, 173),
(1, 174),
(1, 175),
(1, 176),
(1, 177),
(1, 178),
(1, 179),
(1, 180);

-- --------------------------------------------------------

--
-- Table structure for table `scenes`
--

CREATE TABLE IF NOT EXISTS `scenes` (
  `ID` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `Name` char(20) NOT NULL,
  `Description` tinytext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103 ;

--
-- Dumping data for table `scenes`
--

INSERT INTO `scenes` (`ID`, `Name`, `Description`) VALUES
(100, 'Pub', 'A pub, you know. To the south is the <span class="active path" onclick="walk(101)">Town Square</span>'),
(101, 'Town Square', 'There are people walking around, you can go north to the <span class="active path" onclick="walk(100)">Pub</span>, or south to the  <span class="active path" onclick="walk(102)">Blacksmith</span> yo.'),
(102, 'Blacksmith', 'crafting place. North is the <span class="active path" onclick="walk(101)">Town Square</span>. There is an <span class="active action" onclick="startCraft()">anvil</span> here. test.');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
