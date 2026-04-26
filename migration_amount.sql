ALTER TABLE `requests`
ADD COLUMN IF NOT EXISTS `agreed_amount` decimal(10,2) DEFAULT NULL AFTER `status`;

ALTER TABLE `requests`
ADD COLUMN IF NOT EXISTS `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid' AFTER `agreed_amount`;
