ALTER TABLE `smed_diagnostic_order_rad` CHANGE `is_deleted` `is_deleted` TINYINT(1) DEFAULT 0 NULL; 
ALTER TABLE `smed_diagnostic_order_rad` ADD COLUMN `charge_type` VARCHAR(100) NULL AFTER `is_cash`; 
ALTER TABLE `smed_diagnostic_order_rad` ADD COLUMN `charge` TINYINT(1) NULL AFTER `charge_type`; 