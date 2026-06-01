-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 09:44 PM
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
-- Database: `mirae_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `order_line_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `customer_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_date`, `status`, `customer_id`, `total`, `tax`) VALUES
(3, '2026-05-27 17:21:51', 'delivered', 6, 12980.00, 1980.00),
(4, '2026-05-29 13:12:00', 'being_prepared', 6, 82600.00, 12600.00),
(5, '2026-05-29 13:13:19', 'shipped', 6, 82600.00, 12600.00),
(6, '2026-05-30 10:32:22', 'pending', 6, 12980.00, 1980.00),
(8, '2026-05-30 21:53:27', 'pending', 6, 12980.00, 1980.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_line`
--

CREATE TABLE `order_line` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_line`
--

INSERT INTO `order_line` (`id`, `product_id`, `order_id`, `amount`, `total_price`) VALUES
(3, 7, 3, 1, 11000.00),
(4, 6, 4, 1, 70000.00),
(5, 6, 5, 1, 70000.00),
(6, 7, 6, 1, 11000.00),
(8, 7, 8, 1, 11000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pictures`
--

CREATE TABLE `pictures` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pictures`
--

INSERT INTO `pictures` (`id`, `product_id`, `path`) VALUES
(1, 6, '../manager/uploads/1779869797_0_görsel_2026-05-27_111636089.png'),
(2, 6, '../manager/uploads/1779889436_0_görsel_2026-05-27_164353578.png'),
(4, 7, '../manager/uploads/1779891532_0_görsel_2026-05-27_171851212.png'),
(5, 7, '../manager/uploads/1779891614_0_görsel_2026-05-27_172012340.png'),
(9, 10, '../manager/uploads/görsel_2026-05-30_220237518.png'),
(10, 11, '../manager/uploads/görsel_2026-05-30_221006255.png'),
(11, 12, '../manager/uploads/görsel_2026-05-30_221955291.png');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `shop_id` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `quantity`, `shop_id`, `rating`) VALUES
(6, 'iphone 17 pro max', 'phone', 70000.00, 10, 3, 0.0),
(7, 'iphone 11', 'phone', 11000.00, 10, 3, 0.0),
(10, 'americano', 'coffee', 100.00, 10, 4, 0.0),
(11, 'Şeftali kebabı', '', 400.00, 3, 5, 0.0),
(12, 'Baby dress', '', 540.00, 12, 6, 0.0);

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `name`, `type`, `location`, `manager_id`, `rating`, `status`) VALUES
(3, 'telefon hastanesi', 'electronics', 'salamis', 13, 5.0, 'active'),
(4, 'coffee shop', 'coffeeshop', 'manisa', 14, 0.0, 'active'),
(5, 'my restaurant', 'restaurant', 'Daü kampüs', 15, 0.0, 'active'),
(6, 'My Clothes', 'general clothing', 'Akhisar', 16, 0.0, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('admin','manager','customer') NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `address`, `role`) VALUES
(1, 'Admin', 'admin@mirae.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mirae HQ', 'admin'),
(2, 'Mert Baskan', 'mert@mirae.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Istanbul', 'customer'),
(3, 'Ayse Kaya', 'ayse@mirae.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL57Al4C', 'Kadikoy, Istanbul', 'customer'),
(6, 'Nazlı Temiz', 'nazlitemiz45@gmail.com', '$2y$10$W7irohJ.8RqRsvhyy8LW1OHWZIGG69UULMUsqoM6VV3BjViSJAO0q', 'ŞEHZADELER', 'customer'),
(13, 'Esra Temiz', 'esraozyakatemiz@gmail.com', '$2y$10$ZlBCu2934Lg1TjXYZSWnWu76qtvnJOWAKGb8Fo.WGiN76yWrN7B6i', 'salamis', 'manager'),
(14, 'Özgür Temiz', 'm.ozgurtemiz@gmail.com', '$2y$10$xVCrlSt9ZdDhJN/gQIoSUeIudJKJAOWxg6gIY7CWff3ci7BDFr1li', 'manisa', 'manager'),
(15, 'Umut Tuna', 'umuttuna03@gmail.com', '$2y$10$dCHbvmv55NRcPWCsU/73Oesr3CtjywkkKzhXq/E.akKYjngHHuNx6', 'Daü Kampüs', 'manager'),
(16, 'Cansu Ozyaka', 'cansuozyaka@gmail.com', '$2y$10$hxpp9qKZeu1Y7Gi7UDB2SOS/DOx5d2ljMRIE9l/g8tyvJXRkpg2De', 'Akhisar', 'manager');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_line_id` (`order_line_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_line`
--
ALTER TABLE `order_line`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pictures`
--
ALTER TABLE `pictures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_line`
--
ALTER TABLE `order_line`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pictures`
--
ALTER TABLE `pictures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`order_line_id`) REFERENCES `order_line` (`id`),
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_line`
--
ALTER TABLE `order_line`
  ADD CONSTRAINT `order_line_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_line_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `pictures`
--
ALTER TABLE `pictures`
  ADD CONSTRAINT `pictures_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`);

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
