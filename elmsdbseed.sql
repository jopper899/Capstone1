-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 03:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `author_id`, `course_id`, `title`, `body`, `posted_at`) VALUES
(1, 1, NULL, 'Welcome to Arandia College eLMS — SHS & HS Portal!', 'The Electronic Learning Management System is now live for School Year 2025-2026. SHS and HS students and teachers may now log in using their assigned credentials to access subjects, modules, and activities.', '2026-03-12 02:59:06'),
(2, 1, NULL, 'asdasd', 'adsasdasd', '2026-05-15 18:59:58'),
(3, 3, 38, 'asdasd', 'asdasds', '2026-05-15 19:08:07'),
(4, 3, 38, 'sdfdsf', 'sdfsdfs', '2026-05-16 18:00:27'),
(5, 3, 41, 'sdfdsf', 'sdfsdfs', '2026-05-16 18:00:27'),
(6, 3, 39, 'sdfdsf', 'sdfsdfs', '2026-05-16 18:00:27'),
(7, 3, 40, 'sdfdsf', 'sdfsdfs', '2026-05-16 18:00:27');

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `instructions`, `description`, `file_path`, `due_date`, `max_score`, `created_at`) VALUES
(1, 38, 'asdsad', '', '', 'uploads/assignments/assign_69beb3d541fcb.pdf', NULL, 100.00, '2026-03-21 23:05:57');

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `school_year`, `semester`, `status`, `created_at`) VALUES
(1, 'SHS-ORALCOM', 'Oral Communication in Context', 'Develops listening and speaking skills in various communicative contexts.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(2, 'SHS-RECOM', 'Reading and Writing Skills', 'Strengthens reading comprehension and academic writing.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(3, 'SHS-21CLIT', '21st Century Literature', 'Survey of Philippine and world literature from the 21st century.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(4, 'SHS-GENMATH', 'General Mathematics', 'Functions, rational expressions, exponential and logarithmic functions, and finance.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(5, 'SHS-STATS', 'Statistics and Probability', 'Data collection, analysis, and interpretation; probability concepts.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(6, 'SHS-EARTH', 'Earth and Life Science', 'Geological, biological, and environmental processes on Earth.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(7, 'SHS-PHYSSCI', 'Physical Science', 'Principles of chemistry and physics and their real-world applications.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(8, 'SHS-PERDEV', 'Personal Development', 'Self-awareness, coping skills, and healthy interpersonal relationships.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(9, 'SHS-UCSP', 'Understanding Culture, Society & Politics', 'Concepts of culture, society, politics, and governance.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(10, 'SHS-CONTEMPH', 'Contemporary Philippine Arts', 'Art forms, their historical context, and creative expression in the Philippines.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(11, 'SHS-MIL', 'Media and Information Literacy', 'Critical analysis and responsible use of media and digital information.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(12, 'SHS-PE1', 'Physical Education and Health 1', 'Fitness, sports, and health promotion for senior high learners.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(13, 'ABM-FABM1', 'Fundamentals of ABM 1', 'Introduction to accounting and bookkeeping concepts.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(14, 'ABM-FABM2', 'Fundamentals of ABM 2', 'Advanced bookkeeping, financial statements, and business math.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(15, 'ABM-BUSMATH', 'Business Mathematics', 'Mathematical concepts applied in business settings.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(16, 'ABM-ENTREP', 'Entrepreneurship', 'Business planning, market analysis, and enterprise development.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(17, 'ABM-ORGMGT', 'Organization and Management', 'Principles and functions of management in organizations.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(18, 'GAS-SOCSCI', 'Applied Social Sciences', 'Introduction to counseling, social work, and communication.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(19, 'GAS-ENTREP', 'Entrepreneurship (GAS)', 'Business planning, market analysis, and enterprise development.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(20, 'GAS-RESEARCH', 'Practical Research 1', 'Introduction to qualitative research methods and writing.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(21, 'HUMSS-CPAR', 'Creative Writing', 'Creative nonfiction, fiction, poetry, and Philippine popular culture.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(22, 'HUMSS-DIWA', 'Komunikasyon at Pananaliksik', 'Academic writing and research in Filipino.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(23, 'HUMSS-INTRO', 'Introduction to World Religions', 'Survey of major world religions and their cultural impact.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(24, 'HUMSS-DISAS', 'Disciplines and Ideas in Social Sciences', 'Key concepts from psychology, sociology, and anthropology.', '2025-2026', '2nd', 'Active', '2026-03-12 22:47:44'),
(25, 'HS7-ENGLISH', 'English 7', 'Reading comprehension, vocabulary, grammar, and composition.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(26, 'HS7-MATH', 'Mathematics 7', 'Sets, integers, rational numbers, algebraic expressions.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(27, 'HS7-SCI', 'Science 7', 'Matter, living things, ecosystems, and Earth science basics.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(28, 'HS7-FIL', 'Filipino 7', 'Komunikasyon at pag-unawa sa wikang Filipino.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(29, 'HS7-AP', 'Araling Panlipunan 7', 'Kasaysayan ng Asya at Pilipinas.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(30, 'HS8-ENGLISH', 'English 8', 'Literary genres, research skills, and advanced grammar.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(31, 'HS8-MATH', 'Mathematics 8', 'Patterns, functions, linear equations, and geometry.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(32, 'HS8-SCI', 'Science 8', 'Force, motion, waves, light, sound, and heredity.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(33, 'HS8-FIL', 'Filipino 8', 'Pagbasa at pagsulat sa ibat ibang disiplina.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(34, 'HS9-ENGLISH', 'English 9', 'World literature, critical thinking, and writing.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(35, 'HS9-MATH', 'Mathematics 9', 'Quadratic equations, functions, and trigonometry.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(36, 'HS9-SCI', 'Science 9', 'Biodiversity, evolution, and plate tectonics.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(37, 'HS9-FIL', 'Filipino 9', 'Panitikang Filipino at mga anyo ng komunikasyon.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(38, 'HS10-ENGLISH', 'English 10', 'Research-based writing, grammar review, and literary criticism.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(39, 'HS10-MATH', 'Mathematics 10', 'Sequences, polynomial functions, and combinatorics.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(40, 'HS10-SCI', 'Science 10', 'Universe, stars, climate, ecosystems, and genetic engineering.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44'),
(41, 'HS10-FIL', 'Filipino 10', 'Pagsulat ng pananaliksik at pagbasa ng ibat ibang teksto.', '2025-2026', '1st', 'Active', '2026-03-12 22:47:44');

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
(34, 13, 9, '2026-03-23 22:28:33', 'Enrolled'),
(35, 6, 38, '2026-05-13 20:07:13', 'Enrolled'),
(36, 6, 41, '2026-05-13 20:07:13', 'Enrolled'),
(37, 6, 39, '2026-05-13 20:07:13', 'Enrolled'),
(38, 6, 40, '2026-05-13 20:07:13', 'Enrolled');

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `course_id`, `item_id`, `item_name`, `max_score`, `raw_score`, `weight`, `final_score`, `midterm_grade`, `final_grade`, `remarks`, `recorded_at`) VALUES
(1, 12, 38, 1, 'quiz1', 100.00, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-12 09:35:51');

--
-- Dumping data for table `login_otps`
--

INSERT INTO `login_otps` (`user_id`, `otp_hash`, `expires_at`, `used`) VALUES
(1, '$2y$10$LPgLfRYxcUy/92gcz2PiXuWL9dnpnq86xyd5yabfiAQGzeq.OUotu', '2026-04-30 21:20:21', 0),
(3, '$2y$10$bGi5uv4RBgsEeWpSC06KNuAzXdDskcqXH1MwtZvyEWQjW1FIqRdUq', '2026-05-16 18:05:03', 1),
(6, '$2y$10$ih0s92qHF5aFD541Qto36uz/m4Ne462Pv3JCmC53L7KwlYlnDFNbm', '2026-05-15 19:18:41', 1),
(7, '$2y$10$GcqcCUNlMf4I7kSQdNcWZeqvGJM0DkOgKfSZf9Nk/KmNUQLz6aHxK', '2026-05-16 18:04:04', 1);

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `course_id`, `title`, `description`, `file_path`, `week_number`, `published`, `created_at`) VALUES
(1, 38, 'wqeqweqweqw', '', 'uploads/modules/mod_69b64c7e951c3.pdf', NULL, 1, '2026-03-15 14:06:54'),
(2, 38, 'week2', 'sdasdasd', 'uploads/modules/mod_69be92c6855e1.pdf', 2, 1, '2026-03-21 20:44:54'),
(3, 39, 'week1', 'hotdog ni rebo', 'uploads/modules/mod_69be939922922.pdf', NULL, 1, '2026-03-21 20:48:25'),
(4, 40, 'week1', 'asdasdas', 'uploads/modules/mod_69be93b37e9b2.pdf', NULL, 1, '2026-03-21 20:48:51'),
(5, 38, 'hotdog', '', 'uploads/modules/mod_69c14eb535714.pdf', 3, 1, '2026-03-23 22:31:17');

--
-- Dumping data for table `module_progress`
--

INSERT INTO `module_progress` (`id`, `student_id`, `module_id`, `course_id`, `completed_at`) VALUES
(3, 12, 1, 38, '2026-03-21 20:29:38'),
(6, 12, 2, 38, '2026-03-21 20:45:16'),
(8, 12, 4, 40, '2026-03-28 17:57:52'),
(9, 12, 5, 38, '2026-03-28 18:15:37'),
(10, 12, 3, 39, '2026-03-28 18:38:35');

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`user_id`, `token`, `expires_at`, `used`) VALUES
(10, '0433efd3425cb5eee6a8bba6d0402224324248474b38d8cdb15f4f21bd05ac42', '2026-04-17 19:14:34', 0);

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `title`, `description`, `time_limit`, `max_score`, `open_at`, `close_at`, `quiz_type`, `file_path`, `created_at`) VALUES
(1, 38, 'quiz1', '', NULL, 100.00, '2026-03-15 15:54:00', NULL, 'questions', NULL, '2026-03-15 13:52:39'),
(2, 38, 'quiz2', '', 1, 100.00, '2026-03-15 20:04:00', NULL, 'questions', NULL, '2026-03-15 20:01:57'),
(5, 38, 'asdsad', '', NULL, 100.00, NULL, NULL, 'file', 'uploads/quizzes/quiz_69beb32a0d2cc.pdf', '2026-03-21 23:03:06'),
(6, 38, 'bawal lumabas', '', 4, 100.00, '2026-03-23 22:32:00', '2026-03-24 22:32:00', 'file', 'uploads/quizzes/quiz_69c14f26b0c71.pdf', '2026-03-23 22:33:10'),
(7, 41, 'asdasd', 'asdasd', 122, 100.00, '2026-05-13 00:18:00', '2026-06-18 00:18:00', 'questions', NULL, '2026-05-16 00:18:46');

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `quiz_id`, `student_id`, `score`, `file_path`, `remarks`, `finished_at`, `started_at`, `submitted_at`, `status`) VALUES
(1, 1, 12, 100.00, NULL, NULL, NULL, '2026-03-15 14:05:30', '2026-03-15 14:05:33', 'Submitted'),
(2, 2, 12, 100.00, NULL, NULL, NULL, '2026-03-21 21:21:46', '2026-03-21 21:21:53', 'Submitted'),
(4, 5, 12, 50.00, 'uploads/quiz_submissions/qsub_12_5_69c1502c79dcc.pdf', 'mali yung pinasa mo', '2026-03-23 22:37:32', '2026-03-21 23:03:13', '2026-03-23 22:37:32', 'Submitted'),
(5, 6, 12, 98.50, 'uploads/quiz_submissions/qsub_12_6_69c7a33a68a69.pdf', 'mali yung pinasa mo', '2026-03-28 17:45:30', '2026-03-23 22:37:09', '2026-03-28 17:45:30', 'Graded'),
(6, 7, 6, 0.00, NULL, NULL, '2026-05-16 00:19:28', '2026-05-16 00:19:25', '2026-05-16 00:19:28', 'Submitted');

--
-- Dumping data for table `quiz_choices`
--

INSERT INTO `quiz_choices` (`id`, `question_id`, `choice_text`, `is_correct`) VALUES
(1, 1, '1sdasdasd', 1),
(2, 1, 'asdasd', 0),
(3, 2, 'asdsaas', 1),
(4, 2, 'asdsad', 0),
(5, 2, 'asdasd', 0);

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`) VALUES
(3, 7, 'asdasdasd', 'multiple_choice', 1.00);

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `course_id`, `section`, `school_year`, `semester`, `assigned_at`) VALUES
(1, 3, 38, 'Grade 10', '2025-2026', '1st', '2026-05-13 20:06:23'),
(2, 3, 41, 'Grade 10', '2025-2026', '1st', '2026-05-13 20:06:31'),
(3, 3, 39, 'Grade 10', '2025-2026', '1st', '2026-05-13 20:06:44'),
(4, 3, 40, 'Grade 10', '2025-2026', '1st', '2026-05-13 20:06:53');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `username`, `password`, `role`, `first_name`, `last_name`, `middle_name`, `email`, `contact`, `profile_picture`, `section_dept`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN-001', 'admin', '$2y$10$duTSxxADOs/qjqum80q41.UZh8PeDdDDs6UMaomV6rB7ONbp4DJW.', 'Admin', 'System', 'Administrator', '', 'admin@gmail.com', '', NULL, '', 'Active', '2026-03-12 02:59:06', '2026-05-13 19:43:16'),
(3, '011', 'marc', '$2y$10$rnN4z1YisaI37TBpo41tXOnqOXkAeqyni.GvpuO5t/n.iv9fjwKri', 'Teacher', 'Marc', 'Macarubbo', 'Vincent', 'marc.macarubbo@gmail.com', '', NULL, '', 'Active', '2026-05-13 19:23:29', '2026-05-13 19:23:29'),
(6, '02', 'denver', '$2y$10$vhcbcKQ6N2S8iPIofB6.8.9lR4VePUQ1qD8izSH0oxfXtIr37sTSi', 'Student', 'Denver', 'Magdaong', '', 'macarubbo.marc13@gmail.com', '', NULL, 'Grade 10', 'Active', '2026-05-13 19:49:40', '2026-05-13 19:49:40'),
(7, '123456789', 'Jerbs', '$2y$10$opJKQwaurwYD2s8LscbAPu2AYBC.VROp6AcoRWeTVIaWjh/d4LAE2', 'Student', 'Ven Jerby', 'Ardiente', 'Lelis', 'ardientejerby26@gmail.com', '09094077216', NULL, 'Grade 10', 'Active', '2026-05-16 17:53:21', '2026-05-16 17:53:21');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
