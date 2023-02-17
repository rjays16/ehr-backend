ALTER TABLE `smed_radio_service`   
  ADD COLUMN `price_cash` DECIMAL(10,2) DEFAULT 0.0  NULL AFTER `create_dt`,
  ADD COLUMN `price_charge` DECIMAL(10,2) DEFAULT 0.0  NULL AFTER `price_cash`,
  ADD COLUMN `is_socialized` TINYINT(1) DEFAULT 0  NULL AFTER `price_charge`,
  ADD COLUMN `is_er` TINYINT(1) NULL AFTER `is_socialized`,
  ADD COLUMN `only_in_clinic` TINYINT(1) DEFAULT 1  NULL AFTER `is_er`;
