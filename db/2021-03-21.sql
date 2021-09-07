ALTER TABLE `Colaborador` ADD `active` BOOLEAN NOT NULL DEFAULT TRUE AFTER `estado`;

ALTER TABLE `Colaborador` ADD `foto` VARCHAR(255) NOT NULL AFTER `apellidoMaterno`;

ALTER TABLE `Colaborador` ADD `user_create` INT NOT NULL AFTER `active`;

ALTER TABLE `Colaborador`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `Colaborador` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `Colaborador` ADD `updated_at` TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Asistencia` CHANGE `fecha` `fecha` DATE NOT NULL;