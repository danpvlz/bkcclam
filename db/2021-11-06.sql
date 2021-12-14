ALTER TABLE `Concepto` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Concepto` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Concepto` ADD `inWeb` BOOLEAN NOT NULL DEFAULT FALSE AFTER `categoriaCuenta`;
ALTER TABLE `Concepto` ADD `descripcionInWeb` TEXT NULL AFTER `descripcion`;

CREATE TABLE Descuento ( `id` INT NOT NULL AUTO_INCREMENT , `codigo` VARCHAR(20) NOT NULL , `monto` DECIMAL(10,2) NOT NULL , `motivo` VARCHAR(25) NOT NULL , `active` BOOLEAN NOT NULL DEFAULT TRUE , PRIMARY KEY (`id`));
CREATE TABLE `Pedido` ( `id` INT NOT NULL AUTO_INCREMENT , `monto` DECIMAL(10,2) NOT NULL , `idConcepto` INT NOT NULL , `tipoDoc` VARCHAR(3) NOT NULL , `documento` VARCHAR(20) NOT NULL , `adquiriente` VARCHAR(150) NOT NULL , `direccion` VARCHAR(200) NOT NULL , `correo` VARCHAR(50) NOT NULL , `telefono` VARCHAR(25) NOT NULL , `idDescuento` INT NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`));
ALTER TABLE `Pedido` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Pedido` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Pedido` ADD `montoDcto` DECIMAL(10,2) NOT NULL AFTER `idDescuento`;
ALTER TABLE `Pedido` CHANGE `montoDcto` `montoDcto` DECIMAL(10,2) NULL;
ALTER TABLE `Pedido` CHANGE `direccion` `direccion` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `Pedido` ADD `origen` INT NOT NULL DEFAULT '1' COMMENT '1: Pedido, 2: Pago online' AFTER `montoDcto`;
ALTER TABLE `Pedido` ADD `paid` BOOLEAN NULL DEFAULT FALSE AFTER `origen`;
ALTER TABLE `Pedido` ADD `idCuenta` INT NULL AFTER `paid`;
UPDATE `Concepto` SET `inWeb` = '1' WHERE `Concepto`.`idConcepto` = 15;
UPDATE `Concepto` SET `inWeb` = '1' WHERE `Concepto`.`idConcepto` = 16;
ALTER TABLE `Concepto` CHANGE `valorConIGV` `valorConIGV` DECIMAL(10,2) NULL DEFAULT NULL;
ALTER TABLE `Pedido` ADD `izimetada` TEXT NULL AFTER `idCuenta`;
ALTER TABLE `Pedido` ADD `total` DECIMAL(10,2) NOT NULL AFTER `montoDcto`;