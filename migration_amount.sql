ALTER TABLE `requests`
ADD COLUMN `agreed_amount` decimal(10,2) DEFAULT NULL AFTER `status`;
