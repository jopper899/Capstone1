-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 28, 2026 at 09:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elmsdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `posted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `author_id`, `course_id`, `title`, `body`, `posted_at`) VALUES
(1, 1, NULL, 'Welcome to Arandia College eLMS — SHS & HS Portal!', 'The Electronic Learning Management System is now live for School Year 2025-2026. SHS and HS students and teachers may now log in using their assigned credentials to access subjects, modules, and activities.', '2026-03-12 02:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `instructions` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `instructions`, `description`, `file_path`, `due_date`, `max_score`, `created_at`) VALUES
(1, 38, 'asdsad', '', '', 'uploads/assignments/assign_69beb3d541fcb.pdf', NULL, 100.00, '2026-03-21 23:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL DEFAULT '1st',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `teacher_id`, `school_year`, `semester`, `status`, `created_at`) VALUES
(1, 'SHS-ORALCOM', 'Oral Communication in Context', 'Develops listening and speaking skills in various communicative contexts.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(2, 'SHS-RECOM', 'Reading and Writing Skills', 'Strengthens reading comprehension and academic writing.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(3, 'SHS-21CLIT', '21st Century Literature', 'Survey of Philippine and world literature from the 21st century.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(4, 'SHS-GENMATH', 'General Mathematics', 'Functions, rational expressions, exponential and logarithmic functions, and finance.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(5, 'SHS-STATS', 'Statistics and Probability', 'Data collection, analysis, and interpretation; probability concepts.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(6, 'SHS-EARTH', 'Earth and Life Science', 'Geological, biological, and environmental processes on Earth.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(7, 'SHS-PHYSSCI', 'Physical Science', 'Principles of chemistry and physics and their real-world applications.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(8, 'SHS-PERDEV', 'Personal Development', 'Self-awareness, coping skills, and healthy interpersonal relationships.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(9, 'SHS-UCSP', 'Understanding Culture, Society & Politics', 'Concepts of culture, society, politics, and governance.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(10, 'SHS-CONTEMPH', 'Contemporary Philippine Arts', 'Art forms, their historical context, and creative expression in the Philippines.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(11, 'SHS-MIL', 'Media and Information Literacy', 'Critical analysis and responsible use of media and digital information.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(12, 'SHS-PE1', 'Physical Education and Health 1', 'Fitness, sports, and health promotion for senior high learners.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(13, 'ABM-FABM1', 'Fundamentals of ABM 1', 'Introduction to accounting and bookkeeping concepts.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(14, 'ABM-FABM2', 'Fundamentals of ABM 2', 'Advanced bookkeeping, financial statements, and business math.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(15, 'ABM-BUSMATH', 'Business Mathematics', 'Mathematical concepts applied in business settings.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(16, 'ABM-ENTREP', 'Entrepreneurship', 'Business planning, market analysis, and enterprise development.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(17, 'ABM-ORGMGT', 'Organization and Management', 'Principles and functions of management in organizations.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(18, 'GAS-SOCSCI', 'Applied Social Sciences', 'Introduction to counseling, social work, and communication.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(19, 'GAS-ENTREP', 'Entrepreneurship (GAS)', 'Business planning, market analysis, and enterprise development.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(20, 'GAS-RESEARCH', 'Practical Research 1', 'Introduction to qualitative research methods and writing.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(21, 'HUMSS-CPAR', 'Creative Writing', 'Creative nonfiction, fiction, poetry, and Philippine popular culture.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(22, 'HUMSS-DIWA', 'Komunikasyon at Pananaliksik', 'Academic writing and research in Filipino.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(23, 'HUMSS-INTRO', 'Introduction to World Religions', 'Survey of major world religions and their cultural impact.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(24, 'HUMSS-DISAS', 'Disciplines and Ideas in Social Sciences', 'Key concepts from psychology, sociology, and anthropology.', NULL, '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(25, 'HS7-ENGLISH', 'English 7', 'Reading comprehension, vocabulary, grammar, and composition.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(26, 'HS7-MATH', 'Mathematics 7', 'Sets, integers, rational numbers, algebraic expressions.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(27, 'HS7-SCI', 'Science 7', 'Matter, living things, ecosystems, and Earth science basics.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(28, 'HS7-FIL', 'Filipino 7', 'Komunikasyon at pag-unawa sa wikang Filipino.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(29, 'HS7-AP', 'Araling Panlipunan 7', 'Kasaysayan ng Asya at Pilipinas.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(30, 'HS8-ENGLISH', 'English 8', 'Literary genres, research skills, and advanced grammar.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(31, 'HS8-MATH', 'Mathematics 8', 'Patterns, functions, linear equations, and geometry.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(32, 'HS8-SCI', 'Science 8', 'Force, motion, waves, light, sound, and heredity.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(33, 'HS8-FIL', 'Filipino 8', 'Pagbasa at pagsulat sa ibat ibang disiplina.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(34, 'HS9-ENGLISH', 'English 9', 'World literature, critical thinking, and writing.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(35, 'HS9-MATH', 'Mathematics 9', 'Quadratic equations, functions, and trigonometry.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(36, 'HS9-SCI', 'Science 9', 'Biodiversity, evolution, and plate tectonics.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(37, 'HS9-FIL', 'Filipino 9', 'Panitikang Filipino at mga anyo ng komunikasyon.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(38, 'HS10-ENGLISH', 'English 10', 'Research-based writing, grammar review, and literary criticism.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(39, 'HS10-MATH', 'Mathematics 10', 'Sequences, polynomial functions, and combinatorics.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(40, 'HS10-SCI', 'Science 10', 'Universe, stars, climate, ecosystems, and genetic engineering.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(41, 'HS10-FIL', 'Filipino 10', 'Pagsulat ng pananaliksik at pagbasa ng ibat ibang teksto.', NULL, '2025-2026', '1st', 'Active', '2026-03-12 22:47:44');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Enrolled','Dropped','Completed') NOT NULL DEFAULT 'Enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrolled_at`, `status`) VALUES
(1, 5, 1, '2026-03-12 22:47:44', 'Enrolled'),
(2, 5, 2, '2026-03-12 22:47:44', 'Enrolled'),
(3, 5, 4, '2026-03-12 22:47:44', 'Enrolled'),
(4, 5, 8, '2026-03-12 22:47:44', 'Enrolled'),
(5, 5, 11, '2026-03-12 22:47:44', 'Enrolled'),
(6, 5, 13, '2026-03-12 22:47:44', 'Enrolled'),
(7, 5, 15, '2026-03-12 22:47:44', 'Enrolled'),
(8, 6, 1, '2026-03-12 22:47:44', 'Enrolled'),
(9, 6, 2, '2026-03-12 22:47:44', 'Enrolled'),
(10, 6, 3, '2026-03-12 22:47:44', 'Enrolled'),
(11, 6, 8, '2026-03-12 22:47:44', 'Enrolled'),
(12, 6, 9, '2026-03-12 22:47:44', 'Enrolled'),
(13, 6, 21, '2026-03-12 22:47:44', 'Enrolled'),
(14, 6, 24, '2026-03-12 22:47:44', 'Enrolled'),
(15, 10, 30, '2026-03-12 22:47:44', 'Enrolled'),
(16, 10, 31, '2026-03-12 22:47:44', 'Enrolled'),
(17, 10, 32, '2026-03-12 22:47:44', 'Enrolled'),
(18, 10, 33, '2026-03-12 22:47:44', 'Enrolled'),
(19, 12, 38, '2026-03-15 14:04:03', 'Enrolled'),
(20, 12, 41, '2026-03-15 14:04:03', 'Enrolled'),
(21, 12, 39, '2026-03-15 14:04:03', 'Enrolled'),
(22, 12, 40, '2026-03-15 14:04:03', 'Enrolled'),
(23, 13, 3, '2026-03-23 22:28:33', 'Enrolled'),
(24, 13, 10, '2026-03-23 22:28:33', 'Enrolled'),
(25, 13, 6, '2026-03-23 22:28:33', 'Enrolled'),
(26, 13, 4, '2026-03-23 22:28:33', 'Enrolled'),
(27, 13, 11, '2026-03-23 22:28:33', 'Enrolled'),
(28, 13, 1, '2026-03-23 22:28:33', 'Enrolled'),
(29, 13, 12, '2026-03-23 22:28:33', 'Enrolled'),
(30, 13, 8, '2026-03-23 22:28:33', 'Enrolled'),
(31, 13, 7, '2026-03-23 22:28:33', 'Enrolled'),
(32, 13, 2, '2026-03-23 22:28:33', 'Enrolled'),
(33, 13, 5, '2026-03-23 22:28:33', 'Enrolled'),
(34, 13, 9, '2026-03-23 22:28:33', 'Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `midterm_grade` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `week_number` tinyint(3) UNSIGNED DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `course_id`, `title`, `description`, `file_path`, `week_number`, `published`, `created_at`) VALUES
(1, 38, 'wqeqweqweqw', '', 'uploads/modules/mod_69b64c7e951c3.pdf', NULL, 1, '2026-03-15 14:06:54'),
(2, 38, 'week2', 'sdasdasd', 'uploads/modules/mod_69be92c6855e1.pdf', 2, 1, '2026-03-21 20:44:54'),
(3, 39, 'week1', 'hotdog ni rebo', 'uploads/modules/mod_69be939922922.pdf', NULL, 1, '2026-03-21 20:48:25'),
(4, 40, 'week1', 'asdasdas', 'uploads/modules/mod_69be93b37e9b2.pdf', NULL, 1, '2026-03-21 20:48:51'),
(5, 38, 'hotdog', '', 'uploads/modules/mod_69c14eb535714.pdf', 3, 1, '2026-03-23 22:31:17');

-- --------------------------------------------------------

--
-- Table structure for table `module_progress`
--

CREATE TABLE `module_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `module_progress`
--

INSERT INTO `module_progress` (`id`, `student_id`, `module_id`, `course_id`, `completed_at`) VALUES
(3, 12, 1, 38, '2026-03-21 20:29:38'),
(6, 12, 2, 38, '2026-03-21 20:45:16');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` smallint(5) UNSIGNED DEFAULT NULL,
  `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
  `open_at` datetime DEFAULT NULL,
  `close_at` datetime DEFAULT NULL,
  `quiz_type` enum('questions','file') NOT NULL DEFAULT 'questions',
  `file_path` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `title`, `description`, `time_limit`, `max_score`, `open_at`, `close_at`, `quiz_type`, `file_path`, `created_at`) VALUES
(1, 38, 'quiz1', '', NULL, 100.00, '2026-03-15 15:54:00', NULL, 'questions', NULL, '2026-03-15 13:52:39'),
(2, 38, 'quiz2', '', 1, 100.00, '2026-03-15 20:04:00', NULL, 'questions', NULL, '2026-03-15 20:01:57'),
(5, 38, 'asdsad', '', NULL, 100.00, NULL, NULL, 'file', 'uploads/quizzes/quiz_69beb32a0d2cc.pdf', '2026-03-21 23:03:06'),
(6, 38, 'bawal lumabas', '', 4, 100.00, '2026-03-23 22:32:00', '2026-03-24 22:32:00', 'file', 'uploads/quizzes/quiz_69c14f26b0c71.pdf', '2026-03-23 22:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `status` enum('In Progress','Submitted','Graded') NOT NULL DEFAULT 'In Progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `quiz_id`, `student_id`, `score`, `file_path`, `remarks`, `finished_at`, `started_at`, `submitted_at`, `status`) VALUES
(1, 1, 12, 100.00, NULL, NULL, NULL, '2026-03-15 14:05:30', '2026-03-15 14:05:33', 'Submitted'),
(2, 2, 12, 100.00, NULL, NULL, NULL, '2026-03-21 21:21:46', '2026-03-21 21:21:53', 'Submitted'),
(4, 5, 12, 50.00, 'uploads/quiz_submissions/qsub_12_5_69c1502c79dcc.pdf', 'mali yung pinasa mo', '2026-03-23 22:37:32', '2026-03-21 23:03:13', '2026-03-23 22:37:32', 'Submitted'),
(5, 6, 12, 15.00, 'uploads/quiz_submissions/qsub_12_6_69c150152f335.pdf', 'mali yung pinasa mo', '2026-03-23 22:37:09', '2026-03-23 22:37:09', '2026-03-23 22:37:09', 'Graded');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_choices`
--

CREATE TABLE `quiz_choices` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_choices`
--

INSERT INTO `quiz_choices` (`id`, `question_id`, `choice_text`, `is_correct`) VALUES
(1, 1, '1sdasdasd', 1),
(2, 1, 'asdasd', 0),
(3, 2, 'asdsaas', 1),
(4, 2, 'asdsad', 0),
(5, 2, 'asdasd', 0);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quiz_id` int(10) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','essay') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `order` smallint(5) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `order`) VALUES
(1, 1, 'wdasdasdasd', 'multiple_choice', 1.00, 1),
(2, 2, 'asdasdasd', 'multiple_choice', 1.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_responses`
--

CREATE TABLE `quiz_responses` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `choice_id` int(10) UNSIGNED DEFAULT NULL,
  `essay_answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `answered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(300) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `graded_at` datetime DEFAULT NULL,
  `status` enum('Submitted','Graded','Late') NOT NULL DEFAULT 'Submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `section` varchar(100) NOT NULL,
  `school_year` varchar(20) NOT NULL DEFAULT '2025-2026',
  `semester` enum('1st','2nd','Summer') NOT NULL DEFAULT '1st',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `course_id`, `section`, `school_year`, `semester`, `assigned_at`) VALUES
(1, 3, 3, 'Grade 11 - HUMSS-A', '2025-2026', '1st', '2026-03-12 22:52:39'),
(2, 3, 8, 'Grade 12 - GAS-A', '2025-2026', '1st', '2026-03-12 22:56:39'),
(3, 11, 38, 'Grade 10', '2025-2026', '1st', '2026-03-15 12:04:48'),
(4, 4, 41, 'Grade 10', '2025-2026', '1st', '2026-03-15 14:08:51'),
(5, 2, 39, 'Grade 10', '2025-2026', '1st', '2026-03-15 14:09:06'),
(6, 2, 40, 'Grade 10', '2025-2026', '1st', '2026-03-21 20:47:07'),
(7, 2, 38, 'Grade 10', '2025-2026', '1st', '2026-03-23 22:27:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` varchar(30) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Teacher','Student') NOT NULL DEFAULT 'Student',
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(300) DEFAULT NULL,
  `section_dept` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `username`, `password`, `role`, `first_name`, `last_name`, `middle_name`, `email`, `contact`, `profile_picture`, `section_dept`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN-001', 'admin', '$2y$10$kbIAMRFV7QnWCE49mg8IS.FmSZO0hTNPB0I3nuDQzbWuynd8NoDSC', 'Admin', 'System', 'Administrator', NULL, 'admin@gmail.com', NULL, NULL, NULL, 'Active', '2026-03-12 02:59:06', '2026-03-12 22:51:26'),
(2, 'EMP-2024-001', 'jreyes', '$2y$10$1FzymHhgV8KaMcB4b40vKOkGhdmT99.CkFvPz5lJkbE88x90V9tQS', 'Teacher', 'Jose', 'Reyes', 'M', 'jreyes@arandia.edu.ph', NULL, NULL, 'Science Department', 'Active', '2026-03-12 02:59:06', '2026-03-21 20:47:55'),
(3, 'EMP-2024-002', 'mcruz', '$2y$10$Wu6LwmJd9piWTizjJuj7cuZLrdreV08h/VYADtRGOqVYI7CqzD7PK', 'Teacher', 'Maria', 'Cruz', 'L', 'mcruz@arandia.edu.ph', NULL, NULL, 'Mathematics Department', 'Active', '2026-03-12 02:59:06', '2026-03-12 22:49:29'),
(4, 'EMP-2024-003', 'asantos', 'teacher123', 'Teacher', 'Ana', 'Santos', 'B', 'asantos@arandia.edu.ph', NULL, NULL, 'English Department', 'Active', '2026-03-12 02:59:06', '2026-03-12 02:59:06'),
(5, '2024-0001', 'jdelacruz', 'student123', 'Student', 'Juan', 'Dela Cruz', 'S', 'jdelacruz@student.arandia.edu.ph', NULL, NULL, 'Grade 11 - ABM-A', 'Active', '2026-03-12 02:59:06', '2026-03-12 22:47:44'),
(6, '2024-0002', 'alopez', 'student123', 'Student', 'Ana', 'Lopez', 'G', 'alopez@student.arandia.edu.ph', NULL, NULL, 'Grade 11 - HUMSS-A', 'Active', '2026-03-12 02:59:06', '2026-03-12 22:47:44'),
(10, '12345', 'marc', '$2y$10$.qqFxmpe4STKDva6MH4ad.87nFTZO.AzF/Iuiuw1rSpiFiTtHlTUS', 'Student', 'Marc', 'Macarubbo', 'Vincent', 'marc.macarubbo@gmail.com', '', NULL, 'Grade 8', 'Active', '2026-03-12 08:58:19', '2026-03-12 22:48:27'),
(11, '00001', 'vincent', '$2y$10$Acotvf55JVeVGePaGvCS0.o8IZsFvbvt3NJxXD8AAOLfaeg773enu', 'Teacher', 'Marc', 'Macarubbo', '', 'marc.macarubbo@gmail.com', '09491702182', 'uploads/profiles/user_11_1773574740.png', '', 'Active', '2026-03-15 12:03:58', '2026-03-15 19:39:00'),
(12, '00002', 'vince', '$2y$10$0yyb9CelkKZ1R8cYoTrfAu6Md0qNfbsxZUnteI5KbZs7jRPobcXua', 'Student', 'vince', 'ables', 'Vincent', '', '', 'uploads/profiles/user_12_1773575960.jpg', 'Grade 10', 'Active', '2026-03-15 12:05:52', '2026-03-21 17:41:07'),
(13, '123456789', 'denver', '$2y$10$MgfdZHtmYo73J5A//KWX8Ok6jmKVBLpXFpm0In/zswJ2VBtx0i3pu', 'Student', 'Denver', 'Magdaong', 'M', 'denver.magdaong@gmailcom', '', NULL, 'Grade 11 - ABM-A', 'Active', '2026-03-23 22:26:56', '2026-03-23 22:26:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);n

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enroll` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grade` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_progress` (`student_id`,`module_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `mp_ibfk_2` (`module_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `quiz_choices`
--
ALTER TABLE `quiz_choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_responses`
--
ALTER TABLE `quiz_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `choice_id` (`choice_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_submit` (`assignment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assignment` (`teacher_id`,`course_id`,`section`,`school_year`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `module_progress`
--
ALTER TABLE `module_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_choices`
--
ALTER TABLE `quiz_choices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD CONSTRAINT `mp_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mp_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mp_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_choices`
--
ALTER TABLE `quiz_choices`
  ADD CONSTRAINT `quiz_choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `ta_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ta_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
