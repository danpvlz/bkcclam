ALTER TABLE `Asociado` ADD `user_create` INT NOT NULL AFTER `idSector`;

ALTER TABLE `Asociado`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `Asociado` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `Asociado` ADD updated_at TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Cuenta` ADD `user_create` INT NOT NULL AFTER `fechaAnulacion`;

ALTER TABLE `Cuenta`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `Cuenta` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `Cuenta` ADD updated_at TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `CuentaDetalle` ADD `user_create` INT NOT NULL AFTER `total`;

ALTER TABLE `CuentaDetalle`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `CuentaDetalle` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `CuentaDetalle` ADD updated_at TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Pago` ADD `user_create` INT NOT NULL AFTER `idCuenta`;

ALTER TABLE `Pago`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `Pago` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `Pago` ADD updated_at TIMESTAMP NOT NULL AFTER `created_at`;

ALTER TABLE `Membresia` ADD `user_create` INT NOT NULL AFTER `idSector`;

ALTER TABLE `Membresia`  ADD `user_update` INT NOT NULL  AFTER `user_create`;

ALTER TABLE `Membresia` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;

ALTER TABLE `Membresia` ADD updated_at TIMESTAMP NOT NULL AFTER `created_at`;


ALTER TABLE `Asociado` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Asociado` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Cuenta` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Cuenta` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `CuentaDetalle` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `CuentaDetalle` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Pago` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Pago` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Membresia` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Membresia` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Colaborador` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Colaborador` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Curso` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Curso` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;