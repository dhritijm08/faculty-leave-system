--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `password`) VALUES
(1, '$2y$10$8xGcIbkSngy4xvDgfpkEkuaH/ZqI80XlsYNmH6tBbWpa0bclhtXlS');

-- --------------------------------------------------------

--
-- Table structure for table `leave_history`
--

CREATE TABLE `leave_history` (
  `id` int(11) NOT NULL,
  `leave_request_id` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `action_taken_by` enum('hod','dean','principal') NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `role` enum('Faculty','HOD','Dean','Principal') NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `hod_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `dean_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `principal_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `forwarded_to` varchar(255) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `faculty_id`, `role`, `leave_type`, `date_from`, `date_to`, `days`, `reason`, `status`, `hod_status`, `dean_status`, `principal_status`, `forwarded_to`, `department`, `created_at`, `rejection_reason`) VALUES
(1, 1, 'Faculty', 'Casual Leave', '2025-04-17', '2025-04-19', 3, 'sick', 'approved', 'approved', 'approved', 'pending', 'principal', 'M.Sc. Data Science', '2025-04-16 16:27:20', NULL),
(2, 3, 'Dean', 'emergency', '2025-04-18', '2025-04-19', 2, 'sick2.0', 'approved', 'pending', 'pending', 'pending', 'principal', 'M.Sc. Data Science', '2025-04-16 16:28:20', NULL),
(3, 2, 'HOD', 'emergency', '2025-04-17', '2025-04-25', 9, 'sick1.0', 'rejected', 'pending', 'pending', 'pending', 'dean,principal', 'M.Sc. Data Science', '2025-04-16 16:29:00', NULL),
(4, 2, 'HOD', 'emergency', '2025-04-16', '2025-04-24', 9, 'testing ', 'rejected', 'pending', 'pending', 'pending', 'dean,principal', 'M.Sc. Data Science', '2025-04-16 16:32:15', NULL),
(5, 2, 'HOD', 'emergency', '2025-04-17', '2025-04-19', 3, 'test01', 'rejected', 'pending', 'rejected', 'pending', 'dean,principal', 'M.Sc. Data Science', '2025-04-16 17:00:34', NULL),
(6, 1, 'Faculty', 'Casual Leave', '2025-04-17', '2025-04-18', 2, 'test03', 'rejected', 'rejected', 'rejected', 'pending', 'principal', 'M.Sc. Data Science', '2025-04-16 17:02:52', NULL),
(7, 2, 'HOD', 'emergency', '2025-04-24', '2025-04-26', 3, 'dkdk', 'approved', 'pending', 'approved', 'pending', 'principal', 'M.Sc. Data Science', '2025-04-23 14:45:59', NULL),
(8, 1, 'Faculty', 'Casual Leave', '2025-04-24', '2025-04-26', 3, 'mkmk', 'approved', 'approved', 'approved', 'pending', 'principal', 'M.Sc. Data Science', '2025-04-23 15:18:39', NULL),
(9, 1, 'Faculty', 'Casual Leave', '2025-04-18', '2025-04-30', 13, 'final test', 'rejected', 'rejected', 'rejected', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 00:35:22', NULL),
(10, 2, 'HOD', 'Medical Leave', '2025-04-25', '2025-04-28', 4, 'finaltsthod', 'rejected', 'pending', 'rejected', 'pending', 'dean,principal', 'M.Sc. Data Science', '2025-04-24 00:36:18', NULL),
(11, 1, 'Faculty', 'Casual Leave', '2025-04-24', '2025-04-24', 1, 'sick', 'approved', 'approved', 'pending', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 00:58:31', NULL),
(12, 5, 'Faculty', 'Medical Leave', '2025-04-24', '2025-04-26', 3, 'solla mudiyathu', 'rejected', 'rejected', 'rejected', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 05:21:28', NULL),
(13, 1, 'Faculty', 'Medical Leave', '2025-04-24', '2025-04-26', 3, 'sick', 'approved', 'approved', 'pending', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 05:35:02', NULL),
(14, 1, 'Faculty', 'Casual Leave', '2025-04-25', '2025-04-26', 2, 'hello', 'rejected', 'rejected', 'pending', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 06:01:04', 'sorry i am not intrested'),
(15, 5, 'Faculty', 'Casual Leave', '2025-04-25', '2025-04-26', 2, 'sick ', 'rejected', 'rejected', 'pending', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 06:08:26', 'inspection'),
(16, 5, 'Faculty', 'Medical Leave', '2025-04-19', '2025-04-29', 11, 'just for fun', 'rejected', 'rejected', 'pending', 'pending', 'hod,dean,principal', 'M.Sc. Data Science', '2025-04-24 06:08:49', 'we have exam');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `faculty_id` varchar(50) NOT NULL,
   `name` VARCHAR(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('faculty','hod','dean','principal') NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT 'uploads/default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` 
(`id`, `faculty_id`, `name`, `username`, `email`, `phone`, `password`, `role`, `department`, `photo`, `created_at`) 
VALUES
(1, '101', 'Padayappa', 'padayappa', 'padayappa@example.com', '9876543210', '$2y$10$mYACfc2uMNmNqTbu/0ND6ele70P0hCWU8q/qf/5hxrvixt.0lD/m', 'Faculty', 'M.Sc. Data Science', 'uploads/101_african.jpg', '2025-04-16 16:23:56'),
(2, '1001', 'Gopal', 'gopal', 'gopal@example.com', '9123456780', '$2y$10$EIkfVK6I1OPTcA1WYS83U.wfJnmC30fM.CMXLqt0yHCUxhv4reDKu', 'HOD', 'M.Sc. Data Science', 'uploads/1001_RDT_20240709_2351103344589701278250714.jpg', '2025-04-16 16:24:39'),
(3, '102', 'Meera Nair', 'meera', 'meera@example.com', '9988776655', '$2y$10$3l9jOpJXMiZVFxL8dkWY4ujkLDeT/Ewt4oTVrRLMi03vIk1Zivjxa', 'Dean', 'M.Sc. Computer Science', 'uploads/102_meera.jpg', '2025-05-30 10:00:00'),
(4, '1002', 'Rajiv Menon', 'rajiv', 'rajiv@example.com', '9112233445', '$2y$10$8LM0Jcn5BQQxXIMzqRLVaOho3fb2vg1dp9TxRjXf7qqKN3zS4ivhC', 'Principal', 'All', 'uploads/1002_rajiv.jpg', '2025-05-30 10:05:00');

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_request_id` (`leave_request_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_leave_request_status` (`status`),
  ADD KEY `idx_leave_request_dates` (`date_from`,`date_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`);

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_history`
--
ALTER TABLE `leave_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD CONSTRAINT `leave_history_ibfk_1` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */; 