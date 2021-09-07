ALTER TABLE `Colaborador` ADD `presencial` BOOLEAN NULL DEFAULT TRUE AFTER `estado`;
ALTER TABLE `Pago` ADD `metadata` TEXT NULL AFTER `estado`;