-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.4:3306
-- Время создания: Окт 17 2026 г., 20:44
-- Версия сервера: 8.4.6
-- Версия PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cafe_orders`
--

-- --------------------------------------------------------

--
-- Структура таблицы `dishes`
--

CREATE TABLE `dishes` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cooking_time` int DEFAULT '10',
  `is_available` tinyint(1) DEFAULT '1'
) ;

--
-- Дамп данных таблицы `dishes`
--

INSERT INTO `dishes` (`id`, `name`, `price`, `cooking_time`, `is_available`) VALUES
(1, 'Капучино', 150.00, 3, 1),
(2, 'Латте', 170.00, 3, 1),
(3, 'Чай чёрный', 120.00, 2, 1),
(4, 'Чай зелёный', 120.00, 2, 1),
(5, 'Круассан', 90.00, 1, 1),
(6, 'Салат Цезарь', 320.00, 10, 1),
(7, 'Борщ', 250.00, 12, 1),
(8, 'Пельмени', 280.00, 8, 1),
(9, 'Бургер', 350.00, 15, 1),
(10, 'Картошка фри', 120.00, 7, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `table_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('draft','in_progress','ready','paid','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `table_id`, `user_id`, `status`, `total`, `created_at`, `closed_at`) VALUES
(1, 2, 2, 'paid', 620.00, '2026-10-17 15:11:08', '2026-10-17 15:11:56'),
(2, 3, 2, 'paid', 620.00, '2026-10-17 15:12:36', '2026-10-17 16:11:12'),
(3, 1, 2, 'paid', 720.00, '2026-10-17 15:14:09', '2026-10-17 17:26:44'),
(4, 1, 2, 'paid', 720.00, '2026-10-17 15:17:20', '2026-10-17 16:44:40'),
(5, 5, 2, 'paid', 430.00, '2026-10-17 16:50:47', '2026-10-17 17:26:42'),
(6, 2, 2, 'in_progress', 440.00, '2026-10-17 17:26:51', NULL),
(7, 4, 2, 'in_progress', 240.00, '2026-10-17 17:26:57', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `dish_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_at_moment` decimal(10,2) NOT NULL,
  `is_ready` tinyint(1) DEFAULT '0'
) ;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `dish_id`, `quantity`, `price_at_moment`, `is_ready`) VALUES
(1, 1, 10, 1, 120.00, 0),
(2, 1, 9, 1, 350.00, 0),
(3, 1, 1, 1, 150.00, 0),
(4, 2, 9, 1, 350.00, 1),
(5, 2, 1, 1, 150.00, 1),
(6, 2, 10, 1, 120.00, 1),
(7, 3, 8, 1, 280.00, 1),
(8, 3, 3, 1, 120.00, 1),
(9, 3, 6, 1, 320.00, 1),
(10, 4, 8, 1, 280.00, 0),
(11, 4, 3, 1, 120.00, 0),
(12, 4, 6, 1, 320.00, 0),
(13, 5, 8, 1, 280.00, 1),
(14, 5, 1, 1, 150.00, 1),
(15, 6, 10, 1, 120.00, 1),
(16, 6, 6, 1, 320.00, 0),
(17, 7, 4, 1, 120.00, 1),
(18, 7, 10, 1, 120.00, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('cash','card') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `type`, `status`, `created_at`) VALUES
(1, 1, 620.00, 'cash', 'completed', '2026-10-17 15:11:56'),
(2, 2, 620.00, 'cash', 'completed', '2026-10-17 16:11:12'),
(3, 4, 720.00, 'cash', 'completed', '2026-10-17 16:44:40'),
(4, 5, 430.00, 'cash', 'completed', '2026-10-17 17:26:42'),
(5, 3, 720.00, 'cash', 'completed', '2026-10-17 17:26:44');

-- --------------------------------------------------------

--
-- Структура таблицы `tables`
--

CREATE TABLE `tables` (
  `id` int NOT NULL,
  `number` int NOT NULL,
  `capacity` int NOT NULL DEFAULT '2',
  `status` enum('free','occupied','reserved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tables`
--

INSERT INTO `tables` (`id`, `number`, `capacity`, `status`) VALUES
(1, 1, 2, 'free'),
(2, 2, 2, 'occupied'),
(3, 3, 4, 'free'),
(4, 4, 4, 'occupied'),
(5, 5, 6, 'free'),
(6, 6, 2, 'free');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('waiter','cook','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$oAMqA5nom9y1zqvSYQfnLOdDKTeq7mbiEgtGHMvvzTP2PrE7FQMWq', 'Главный администратор', 'admin', '2026-10-17 14:01:43'),
(2, 'waiter1', '$2y$12$0gAdgzIm6bhenaXnRy1ZGekZMUCWTBeqW31ERGQpyZ3h2hWbsbunO', 'Иванов Иван', 'waiter', '2026-10-17 14:01:43'),
(3, 'waiter2', '$2y$12$QZ7XA1pSQlDr5OhoqgRkSuKGUKanBCMVVJrt2z7njNYv8qj1QRhAa', 'Петрова Мария', 'waiter', '2026-10-17 14:01:43'),
(4, 'cook1', '$2y$12$lt37w5b4lSrBdQmlSm.S7eSzdGyIXct547nPhrc5yehSNlb/hLXh.', 'Сидоров Петр', 'cook', '2026-10-17 14:01:43');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `dishes`
--
ALTER TABLE `dishes`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dish_id` (`dish_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_order_id` (`order_id`);

--
-- Индексы таблицы `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `dishes`
--
ALTER TABLE `dishes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`);

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
