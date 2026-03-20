-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 20, 2026 lúc 10:56 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `it_service_request`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Hardware', 'Hardware-related issues and requests', '2026-03-20 09:50:27'),
(2, 'Software', 'Software installation, updates, and troubleshooting', '2026-03-20 09:50:27'),
(3, 'Network', 'Network connectivity and access issues', '2026-03-20 09:50:27'),
(4, 'Security', 'Security-related concerns and access requests', '2026-03-20 09:50:27'),
(5, 'Account', 'User account management and permissions', '2026-03-20 09:50:27'),
(6, 'Other', 'Miscellaneous IT requests', '2026-03-20 09:50:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `complete_request_attachments`
--

CREATE TABLE `complete_request_attachments` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(17, 'Ban Giám đốc', 'Ban lãnh đạo cao nhất của công ty', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(18, 'Phòng Kế hoạch', 'Phòng lập kế hoạch chiến lược và hoạt động', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(19, 'Phòng Tài chính - Kế toán', 'Phòng quản lý tài chính và kế toán', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(20, 'Phòng Nhân sự', 'Phòng quản lý nhân sự và tuyển dụng', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(21, 'Phòng Kinh doanh', 'Phòng phát triển kinh doanh và bán hàng', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(22, 'Phòng Marketing', 'Phòng marketing và quảng bá', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(23, 'Phòng Kỹ thuật', 'Phòng kỹ thuật và phát triển sản phẩm', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(24, 'Phòng Nghiên cứu và Phát triển', 'Phòng R&D và đổi mới', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(25, 'Phòng Mua hàng', 'Phòng mua hàng và chuỗi cung ứng', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(26, 'Phòng Chất lượng', 'Phòng kiểm soát chất lượng', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(27, 'Phòng Pháp chế', 'Phòng pháp chế và tuân thủ', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(28, 'Phòng Hành chính', 'Phòng hành chính và văn phòng', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(29, 'Phòng An ninh', 'Phòng an ninh và bảo vệ', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(30, 'Kho', 'Quản lý kho và tồn kho', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(31, 'Bảo trì', 'Phòng bảo trì và sửa chữa', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41'),
(32, 'Khác', 'Các phòng ban khác', 1, '2026-03-20 09:54:41', '2026-03-20 09:54:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_type` enum('request','comment','assignment','resolution','software_update') DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reject_requests`
--

CREATE TABLE `reject_requests` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `rejected_by` int(11) NOT NULL,
  `reject_reason` text NOT NULL,
  `reject_details` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reject_request_attachments`
--

CREATE TABLE `reject_request_attachments` (
  `id` int(11) NOT NULL,
  `reject_request_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `request_feedback`
--

CREATE TABLE `request_feedback` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `feedback` text DEFAULT NULL,
  `would_recommend` varchar(10) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `software_feedback` text DEFAULT NULL COMMENT 'Feedback about IT SRM software',
  `ease_of_use` int(11) DEFAULT NULL COMMENT 'Ease of use rating 1-5',
  `speed_stability` int(11) DEFAULT NULL COMMENT 'Speed and stability rating 1-5',
  `requirement_meeting` int(11) DEFAULT NULL COMMENT 'Requirement meeting rating 1-5'
) ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `resolutions`
--

CREATE TABLE `resolutions` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `error_description` text NOT NULL,
  `error_type` varchar(100) NOT NULL,
  `replacement_materials` text DEFAULT NULL,
  `solution_method` text NOT NULL,
  `resolved_by` int(11) NOT NULL,
  `resolved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed','cancelled','request_support','rejected') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `error_description` text DEFAULT NULL,
  `error_type` varchar(100) DEFAULT NULL,
  `replacement_materials` text DEFAULT NULL,
  `solution_method` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`id`, `data`, `timestamp`) VALUES
('8ol8kouvta8anu3ih1jdphhph0', 'user_id|i:1;username|s:5:\"admin\";full_name|s:20:\"System Administrator\";role|s:5:\"admin\";', 1774000551),
('nvodufjdp4kemghh0p0ivq4fn3', '', 1774000559),
('qoe11t6mtheoqt5hnmk2i12af5', '', 1774000558);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_requests`
--

CREATE TABLE `support_requests` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `support_type` enum('equipment','person','department') NOT NULL,
  `support_details` text NOT NULL,
  `support_reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_request_attachments`
--

CREATE TABLE `support_request_attachments` (
  `id` int(11) NOT NULL,
  `support_request_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','staff','user') DEFAULT 'user',
  `department` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `department`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@company.com', '$2y$10$SEhfdD8EiF8Ay9gFQZANQeNstaAuzqyXBhjkC4Em5olLfVc0l0p32', 'System Administrator', 'admin', 'IT', NULL, '2026-03-20 09:50:27', '2026-03-20 09:50:27'),
(2, 'staff1', 'staff1@company.com', '$2y$10$XJNvvS8344Fi5Blrg4vZL.9OXXJ6MsUyPRBEHL/Nb3YwrV2vnQxPK', 'John Smith', 'staff', 'IT', NULL, '2026-03-20 09:50:27', '2026-03-20 09:50:27'),
(3, 'staff2', 'staff2@company.com', '$2y$10$XJNvvS8344Fi5Blrg4vZL.9OXXJ6MsUyPRBEHL/Nb3YwrV2vnQxPK', 'Jane Doe', 'staff', 'IT', NULL, '2026-03-20 09:50:27', '2026-03-20 09:50:27'),
(4, 'user1', 'user1@company.com', '$2y$10$gPhkITe1Oi101enIdp6BvOsS5IkzHE/GSTNlp1WnmmkDlRocuKXK6', 'Mike Johnson', 'user', 'Sales', NULL, '2026-03-20 09:50:27', '2026-03-20 09:50:27');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `complete_request_attachments`
--
ALTER TABLE `complete_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_request_id` (`service_request_id`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Chỉ mục cho bảng `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `reject_requests`
--
ALTER TABLE `reject_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_service_request_id` (`service_request_id`),
  ADD KEY `idx_rejected_by` (`rejected_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `reject_request_attachments`
--
ALTER TABLE `reject_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reject_request_id` (`reject_request_id`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Chỉ mục cho bảng `request_feedback`
--
ALTER TABLE `request_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_request_id` (`service_request_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Chỉ mục cho bảng `resolutions`
--
ALTER TABLE `resolutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_request_id` (`service_request_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Chỉ mục cho bảng `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Chỉ mục cho bảng `support_requests`
--
ALTER TABLE `support_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_service_request_id` (`service_request_id`),
  ADD KEY `idx_requester_id` (`requester_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `support_request_attachments`
--
ALTER TABLE `support_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_request_id` (`support_request_id`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `complete_request_attachments`
--
ALTER TABLE `complete_request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reject_requests`
--
ALTER TABLE `reject_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reject_request_attachments`
--
ALTER TABLE `reject_request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `request_feedback`
--
ALTER TABLE `request_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `resolutions`
--
ALTER TABLE `resolutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `support_requests`
--
ALTER TABLE `support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `support_request_attachments`
--
ALTER TABLE `support_request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `complete_request_attachments`
--
ALTER TABLE `complete_request_attachments`
  ADD CONSTRAINT `complete_request_attachments_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reject_requests`
--
ALTER TABLE `reject_requests`
  ADD CONSTRAINT `reject_requests_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reject_requests_ibfk_2` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reject_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `reject_request_attachments`
--
ALTER TABLE `reject_request_attachments`
  ADD CONSTRAINT `reject_request_attachments_ibfk_1` FOREIGN KEY (`reject_request_id`) REFERENCES `reject_requests` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `request_feedback`
--
ALTER TABLE `request_feedback`
  ADD CONSTRAINT `request_feedback_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_feedback_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `resolutions`
--
ALTER TABLE `resolutions`
  ADD CONSTRAINT `resolutions_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resolutions_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `service_requests_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `support_requests`
--
ALTER TABLE `support_requests`
  ADD CONSTRAINT `support_requests_ibfk_1` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `support_request_attachments`
--
ALTER TABLE `support_request_attachments`
  ADD CONSTRAINT `support_request_attachments_ibfk_1` FOREIGN KEY (`support_request_id`) REFERENCES `support_requests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
