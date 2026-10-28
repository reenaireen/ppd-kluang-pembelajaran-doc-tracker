-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql308.infinityfree.com
-- Generation Time: Oct 27, 2026 at 10:15 PM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43150604_document_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `doc_type` enum('Surat Rasmi','Sijil') NOT NULL DEFAULT 'Surat Rasmi',
  `target_date` date NOT NULL,
  `sample_file` varchar(255) DEFAULT NULL,
  `status` enum('In Progress','Pending Signature','Done') DEFAULT 'In Progress',
  `completed_at` datetime DEFAULT NULL,
  `handler_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `description` text NOT NULL,
  `final_link` varchar(255) DEFAULT NULL,
  `timbalan_status` varchar(50) DEFAULT 'Pending Review',
  `timbalan_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `title`, `doc_type`, `target_date`, `sample_file`, `status`, `completed_at`, `handler_id`, `created_at`, `description`, `final_link`, `timbalan_status`, `timbalan_comment`) VALUES
(21, 6, 'Pindaan Bengkel Penataran BI Ting1', 'Surat Rasmi', '2026-10-13', 'uploads/doc_6_1791862575.pdf', 'In Progress', NULL, 1, '2026-10-13 03:36:15', '', 'https://docs.google.com/document/d/1hEf4o1x8SpJCf8sAVnMBmjOgIkb8DnSbuGAESdYMa04/edit?usp=sharing', 'Requires Correction', ''),
(50, 2, 'SURAT TESTING 2', 'Surat Rasmi', '2026-10-21', 'uploads/doc_2_1792372074.docx', 'In Progress', NULL, 9, '2026-10-19 01:07:54', 'TESTING SURAT', 'https://docs.google.com/spreadsheets/d/1GKxDbUE6NXnLWdnqyjrW9QNvbXptrBVFcN7kYXMwRyo/edit?usp=drive_web&ouid=100663042105018607489', 'Requires Correction', 'testing correction'),
(51, 4, 'SURAT PENATARAN', 'Surat Rasmi', '0000-00-00', 'uploads/doc_4_1792568272.pdf', 'In Progress', NULL, 11, '2026-10-21 07:37:53', 'TEMPAT : \r\nTARIKH :\r\nTAJUK : \r\nMASA :', NULL, 'Pending Review', NULL),
(52, 4, 'surat penataran pendidikan islam', 'Surat Rasmi', '0000-00-00', 'uploads/doc_4_1792997941.docx', 'Done', '2026-10-26 00:24:50', 1, '2026-10-26 06:59:03', 'tajuk : surat penataran \r\ntempat : dewan sjkc\r\nmasa : 8:00 pagi - 1 petang\r\ntarikh : 30/10/2026', 'https://docs.google.com/document/d/181hwW91XkHXfivW1ZWuDfZZwbfz59GCnRHIXgegVpFU/edit?usp=sharing', 'Approved / Done', ''),
(53, 2, 'sijil agama islam', 'Sijil', '0000-00-00', 'uploads/doc_2_1792999893.docx', 'In Progress', NULL, NULL, '2026-10-26 07:31:33', '', NULL, 'Pending Review', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `full_name`, `department`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'AIREEN NUR AMELYN BINTI KHAIRUL FAUZI', 'PEMBELAJARAN', 'aireenamelyn17@gmail.com', '$2y$12$gw7MBcHiRX0fKid4ZTGBH.PqhtXgZfhIJCq5kBQX/zoo52P4vTw1W', 'staff', '2026-10-12 03:51:22'),
(2, 'adzura', '', 'adzura@gmail.com', '$2y$12$8VGw.Ww55EAj6QxK/nsBcOUujX4U8uotytHmffVTctvkI/6U2vM2e', 'requester', '2026-10-12 04:50:21'),
(3, 'Heri Fauzi', '', 'herifauzi20@gmail.com', '$2y$12$R19YLnoEh2aiQU0iBy1iI..H/q5kmN/qeDytExDiRshU4E9SWS2Oe', 'staff', '2026-10-12 07:10:15'),
(4, 'reen', '', 'acemoonie@gmail.com', '$2y$12$n8qleE6ULJkcInHV56QjV.rxi0sLDXKhe1uMFVatv5Jt/ZKnPbeRa', 'requester', '2026-10-12 08:28:51'),
(6, 'ISMAIL BIN ESA', '', 'ismail.esa65@gmail.com', '$2y$12$ecI/wAnM0SqEh8rqqC/uEu4HSd/8plTTE/GwDC/G7MEoQ25V1JPYW', 'requester', '2026-10-13 03:35:07'),
(7, 'Danish Ridzki', '', 'adriz@gmail.com', '$2y$12$rcphihUnuexfODIiHymQfusSsZxKoojABdXpDNNUd3u7cLmIeGlqW', 'staff', '2026-10-15 23:46:20'),
(8, 'Amelyn', '', 'amelyn17@gmail.com', '$2y$12$dUfgnxTGrlYhEZc0xZQ4oOjJRZgoRrrpviOdRlVJewDwrL.QJNdjy', 'requester', '2026-10-16 07:43:17'),
(9, 'AZARUDINAMRI BIN MAT ZAINON', '', 'azarudinamri@gmail.com', '$2y$12$2PCIhcce74/vvstcy.uAzeisosc3cunVUL0i8ADSg3gZ1chRk9WZ.', 'staff', '2026-10-16 08:15:40'),
(10, 'Ibrahim', '', 'ibrahim@gmail.com', '$2y$12$oFwnjiECaYmBJIhtdHyBzet2XYwu4XYQh1XPE0IFXhFHqoYeiPeR2', 'timbalan', '2026-10-21 03:33:26'),
(11, 'AZDURA MOHD NATAR', '', 'azdura.mnatar@moe.gov.my', '$2y$12$r4kjIzviUVj9uZQovKBhIO2s0LNtSkW6UTmJ1UwRgjYMM7A3RHIvW', 'staff', '2026-10-21 07:34:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
