-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hostiteľ: 127.0.0.1:3306
-- Čas generovania: Po 14.Júl 2025, 16:28
-- Verzia serveru: 8.3.0
-- Verzia PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáza: `studomat`
--

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `student_id` int NOT NULL,
  `content` text NOT NULL,
  `reply_to` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `score` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Sťahujem dáta pre tabuľku `comments`
--

INSERT INTO `comments` (`id`, `question_id`, `student_id`, `content`, `reply_to`, `created_at`, `score`) VALUES
(13, 3, 2, 'akože wtf..', 4, '2025-07-13 23:12:13', 1),
(4, 3, 2, 'Určite nie.', NULL, '2025-07-13 20:58:43', 1),
(16, 3, 2, 'aha', NULL, '2025-07-13 23:15:05', -1),
(15, 3, 2, 'AHA', 13, '2025-07-13 23:12:26', 0),
(17, 3, 2, 'Tak to je haluz :D', NULL, '2025-07-13 23:17:09', -1),
(19, 3, 2, 'haluz', 4, '2025-07-13 23:18:54', -1),
(23, 3, 3, 'Aha ok', 4, '2025-07-13 23:33:13', 1),
(26, 5, 3, 'ddd', NULL, '2025-07-14 15:27:07', 1),
(28, 3, 2, 'Dobre', NULL, '2025-07-14 15:42:37', 1),
(29, 3, 2, 'dobre.. KEKWWWW', 4, '2025-07-14 15:58:15', 0),
(30, 3, 2, 'dddd', 16, '2025-07-14 17:53:54', 0),
(31, 3, 3, 'dd', 4, '2025-07-14 17:58:06', 0);

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_closed` tinyint(1) DEFAULT '0',
  `helpful_comment_id` int DEFAULT NULL,
  `score` int DEFAULT '0',
  `school` varchar(255) NOT NULL,
  `faculty` varchar(255) DEFAULT NULL,
  `program` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `category` enum('pomoc','internát','škola','projekt','predmety','učitelia','rozvrh','ais') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Sťahujem dáta pre tabuľku `questions`
--

INSERT INTO `questions` (`id`, `student_id`, `title`, `content`, `created_at`, `is_closed`, `helpful_comment_id`, `score`, `school`, `faculty`, `program`, `category`) VALUES
(3, 3, 'Richard Boor', 'Je Richard boor god?', '2025-07-13 20:56:55', 0, 4, 1, '', NULL, NULL, 'pomoc'),
(5, 2, 'KEKW', 'ok', '2025-07-14 15:18:36', 0, NULL, 1, '', NULL, NULL, 'pomoc'),
(6, 2, 'ddd', 'gksdgdggaaggaag', '2025-07-14 15:55:01', 0, NULL, 0, '', NULL, NULL, 'pomoc'),
(8, 2, 'TESTTTT', 'ddddd', '2025-07-14 17:53:17', 0, NULL, 0, 'Univerzita Komenského v Bratislave', NULL, NULL, 'pomoc'),
(9, 2, 'marcel', 'MARCEL', '2025-07-14 18:21:10', 0, NULL, 0, 'Trnavská univerzita v Trnave', 'Filozofická fakulta', NULL, 'projekt'),
(10, 2, 'marcel dubec', 'marcelino', '2025-07-14 18:23:00', 0, NULL, 0, 'Katolícka univerzita v Ružomberku', 'Filozofická fakulta', NULL, 'projekt'),
(11, 2, 'kksafsfafasfs', 'fFFFFFFF', '2025-07-14 18:24:25', 0, NULL, 0, 'Katolícka univerzita v Ružomberku', 'Pedagogická fakulta', NULL, 'škola'),
(12, 2, 'gagsgsagas', 'gasgasgasgagasgsa', '2025-07-14 18:28:13', 0, NULL, 0, 'Akadémia umení v Banskej Bystrici', 'Fakulta dramatických umení', 'Herectvo (Bc.)', 'učitelia');

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `question_views`
--

DROP TABLE IF EXISTS `question_views`;
CREATE TABLE IF NOT EXISTS `question_views` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `student_id` int NOT NULL,
  `viewed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_question_user` (`question_id`,`student_id`),
  KEY `idx_qv_question_user` (`question_id`,`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Sťahujem dáta pre tabuľku `question_views`
--

INSERT INTO `question_views` (`id`, `question_id`, `student_id`, `viewed_at`) VALUES
(1, 3, 2, '2025-07-14 15:13:01'),
(2, 5, 2, '2025-07-14 15:18:40'),
(3, 5, 3, '2025-07-14 15:22:21'),
(4, 3, 3, '2025-07-14 15:26:02'),
(5, 6, 2, '2025-07-14 15:55:02'),
(7, 8, 2, '2025-07-14 17:54:03'),
(8, 8, 3, '2025-07-14 17:55:07'),
(9, 9, 2, '2025-07-14 18:21:52');

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(100) NOT NULL,
  `registrated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `logged_in` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Sťahujem dáta pre tabuľku `student`
--

INSERT INTO `student` (`id`, `name`, `surname`, `email`, `registrated`, `password`, `logged_in`) VALUES
(2, 'Marcel', 'Dubec', 'majko.dc@gmail.com', '2025-07-13 19:45:19', '$2y$10$SnUYwUYvzJ.3iWbpO/as0.ve7uzrlCIrsV.t91HNuZifpamaBFwSu', '2025-07-14 18:02:08'),
(3, 'Test', 'Test', 'test@test.sk', '2025-07-13 20:21:01', '$2y$10$p2XHM914UNqCXyjnOsqAVetDYl.LMqA2S1RVTwCMBH0K0nIPMpRJi', '2025-07-14 17:55:05');

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `target_type` enum('question','comment') NOT NULL,
  `target_id` int NOT NULL,
  `value` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`student_id`,`target_type`,`target_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Sťahujem dáta pre tabuľku `votes`
--

INSERT INTO `votes` (`id`, `student_id`, `target_type`, `target_id`, `value`) VALUES
(2, 2, 'question', 3, 1),
(3, 3, 'comment', 3, -1),
(4, 3, 'comment', 4, 1),
(5, 3, 'comment', 19, -1),
(6, 3, 'comment', 13, 1),
(7, 3, 'comment', 16, -1),
(8, 2, 'comment', 21, 1),
(9, 3, 'comment', 17, -1),
(10, 3, 'question', 5, 1),
(11, 2, 'comment', 23, 1),
(12, 3, 'comment', 28, 1),
(13, 2, 'comment', 26, 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
