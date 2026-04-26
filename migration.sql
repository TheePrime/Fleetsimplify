ALTER TABLE `requests`
ADD COLUMN `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid' AFTER `status`;

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Success','Failed') NOT NULL DEFAULT 'Pending',
  `reference` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
