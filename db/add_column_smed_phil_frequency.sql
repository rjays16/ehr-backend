ALTER TABLE `ehrv2`.`smed_phil_frequency` ADD COLUMN `is_diagnostic` TINYINT DEFAULT 0 NULL AFTER `frequency_disc`, ADD COLUMN `is_med` TINYINT DEFAULT 0 NULL AFTER `is_diagnostic`;
