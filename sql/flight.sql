-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2024 at 09:46 PM
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
-- Database: `flight`
--

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fees` decimal(10,2) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `capacity` int(11) NOT NULL DEFAULT 100,
  `company_id` int(11) NOT NULL,
  `max_passengers` int(11) NOT NULL,
  `flight_time` datetime NOT NULL,
  `from_location` varchar(255) NOT NULL,
  `to_location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `name`, `fees`, `start_time`, `end_time`, `completed`, `capacity`, `company_id`, `max_passengers`, `flight_time`, `from_location`, `to_location`) VALUES
(1, 'Flight A', 150.00, '2024-12-15 08:00:00', '2024-12-15 12:00:00', 0, 100, 0, 0, '2024-12-15 08:00:00', 'New York', 'Los Angeles'),
(2, 'Flight B', 200.00, '2024-12-16 09:30:00', '2024-12-16 13:30:00', 0, 100, 0, 0, '2024-12-16 09:30:00', 'Chicago', 'Miami'),
(3, 'Flight C', 120.00, '2024-12-17 07:45:00', '2024-12-17 10:45:00', 0, 100, 0, 0, '2024-12-17 07:45:00', 'New York', 'Chicago'),
(4, 'Flight D', 180.00, '2024-12-18 14:00:00', '2024-12-18 22:00:00', 0, 100, 0, 0, '2024-12-18 14:00:00', 'Los Angeles', 'New York'),
(5, 'Flight E', 210.00, '2024-12-19 11:15:00', '2024-12-19 15:15:00', 0, 100, 0, 0, '2024-12-19 11:15:00', 'Miami', 'Chicago'),
(6, 'test', 12.00, '2024-12-09 11:50:00', '2024-12-10 11:50:00', 0, 33, 12, 33, '2024-12-09 11:50:00', 'test', 'totest'),
(7, 'test 2', 111.00, '2024-12-09 11:55:00', '2024-12-10 11:55:00', 0, 11, 12, 11, '2024-12-09 11:55:00', 'egypt', 'nyc'),
(8, 'test 3', 500.00, '2024-12-11 12:47:00', '2024-12-13 12:47:00', 0, 25, 12, 25, '2024-12-11 12:47:00', 'eg', 'cdg');

-- --------------------------------------------------------

--
-- Table structure for table `flight_passengers`
--

CREATE TABLE `flight_passengers` (
  `flight_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Registered','Pending') NOT NULL,
  `payment_method` enum('balance','cash') NOT NULL DEFAULT 'balance'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_passengers`
--

INSERT INTO `flight_passengers` (`flight_id`, `user_id`, `status`, `payment_method`) VALUES
(1, 10, 'Registered', 'balance'),
(1, 11, 'Registered', 'balance'),
(2, 10, 'Registered', 'balance'),
(3, 10, 'Registered', 'balance'),
(4, 10, 'Registered', 'balance'),
(4, 13, 'Registered', 'balance'),
(5, 10, 'Registered', 'balance'),
(6, 13, 'Registered', 'balance');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_content` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Sent','Delivered','Read') NOT NULL DEFAULT 'Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message_content`, `timestamp`, `status`) VALUES
(1, 13, 6, 'test message', '2024-12-09 10:19:26', 'Sent'),
(2, 13, 6, 'test', '2024-12-09 10:24:47', 'Sent'),
(3, 13, 8, 'test asd', '2024-12-09 10:34:00', 'Sent');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `user_type` enum('Company','Passenger') NOT NULL,
  `account_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `telephone`, `user_type`, `account_balance`) VALUES
(1, 'yehia mohamed', 'yehiashaikhoun2002@gmail.com', '234234', '01203994663', 'Passenger', 0.00),
(3, 'yehia@gmail.com', 'yehia@gmail.com', 'yehia@gmail.com', '01203994663', 'Passenger', 0.00),
(4, 'yehia2@gmail.com', 'yehia2@gmail.com', 'yehia2@gmail.com', '01203994663', 'Passenger', 0.00),
(6, 'yehia23@gmail.com', 'yehia23@gmail.com', 'yehia2@gmail.com', 'yehia2@gmail.com', 'Company', 0.00),
(7, 'yehia233@gmail.com', 'yehia233@gmail.com', 'yehia233@gmail.com', '1234123123', 'Company', 0.00),
(8, 'asd@gmail.com', 'asd@gmail.com', 'asd@gmail.com', 'as', 'Company', 500000.00),
(9, 't2@gmail.com', 't2@gmail.com', '$2y$10$UKffBfrA6pXQp8Dxt96cIOAcmbDnFhtYQ6zvtbWGHaE8Q6xgFPJeq', '01203994663', 'Company', 500000.00),
(10, 'aftertest@gmail.com', 'aftertest@gmail.com', '$2y$10$pYVdsB5EzE6e8NRzVpwnfeif3AAqQcu1DWh15zevqa3lVo0wZ7R2i', 'aftertest@gmail.com', 'Passenger', 499140.00),
(11, 'test8@gmail.com', 'test8@gmail.com', '$2y$10$3K7n8YBVzi/uUmrF9pEIqOcLONO8rveS01Qb/Q5mCeVET4EgRy8rK', 'test8@gmail.com', 'Passenger', 999849.00),
(12, 'company@gmail.com', 'company@gmail.com', '$2y$10$9ZmMhodRhD5Ozw5i0969c.n5N47xqfyrtrBxLgyiYlgMMc5ymdtXm', 'company@gmail.com', 'Company', 50000.00),
(13, 'user2@gmail.com', 'user2@gmail.com', '$2y$10$LYHTjhW9OHhHkPdPUyByueHqQa4NjEBlRhdp2Iyn4Nnasr2RMfFcW', '1234', 'Passenger', 499808.00),
(14, 'yehiashaikhoun20302@gmail.com', 'yehiashaikhoun20032@gmail.com', '$2y$10$gQFN2ZtmGyfH5XoEgm1UI.yv6dpQbDH5dmZz.09WkPm5Uw24.ODra', 'yehiashaikhoun2002@g', 'Passenger', 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`);

--
-- Indexes for table `flight_passengers`
--
ALTER TABLE `flight_passengers`
  ADD PRIMARY KEY (`flight_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flight_passengers`
--
ALTER TABLE `flight_passengers`
  ADD CONSTRAINT `flight_passengers_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flight_passengers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
