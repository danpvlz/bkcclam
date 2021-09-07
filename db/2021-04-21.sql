ALTER TABLE `Cliente` ADD `user_create` INT NOT NULL AFTER `telefono`;
ALTER TABLE `Cliente`  ADD `user_update` INT NOT NULL  AFTER `user_create`;
ALTER TABLE `Cliente` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;
ALTER TABLE `Cliente` ADD `updated_at` TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Concepto` ADD `user_create` INT NOT NULL AFTER `categoriaCuenta`;
ALTER TABLE `Concepto`  ADD `user_update` INT NOT NULL  AFTER `user_create`;
ALTER TABLE `Concepto` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;
ALTER TABLE `Concepto` ADD `updated_at` TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Colaborador` ADD `isDirectivo` TINYINT NOT NULL DEFAULT '0' AFTER `fechaIngreso`;
