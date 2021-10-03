ALTER TABLE `Area` CHANGE `nombre` `nombre` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE `Area` SET `nombre` = 'CENTRO DE FORMACIÓN Y DESARROLLO EMPRESARIAL' WHERE `Area`.`idArea` = 2;
INSERT INTO `Area` (`idArea`, `nombre`, `estado`) VALUES (NULL, 'FORMALIZACIÓN DE EMPRESAS', '1');
UPDATE `CategoriaCuenta` SET `idArea` = '7' WHERE `CategoriaCuenta`.`idCategoria` = 5;
UPDATE `CategoriaCuenta` SET `nombre` = 'CAPACITACIÓN' WHERE `CategoriaCuenta`.`idCategoria` = 2;
ALTER TABLE `Concepto` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Concepto` CHANGE `created_at` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `Concepto` ADD `vigente` BOOLEAN NOT NULL DEFAULT TRUE AFTER `categoriaCuenta`;

ALTER TABLE `Cuenta` CHANGE `total` `total` DECIMAL(10,2) NOT NULL;
ALTER TABLE `Cuenta` CHANGE `subtotal` `subtotal` DECIMAL(10,2) NOT NULL;
ALTER TABLE `CuentaDetalle` CHANGE `precioUnit` `precioUnit` DECIMAL(10,2) NOT NULL;
ALTER TABLE `CuentaDetalle` CHANGE `subtotal` `subtotal` DECIMAL(10,2) NOT NULL;
ALTER TABLE `CuentaDetalle` CHANGE `total` `total` DECIMAL(10,2) NOT NULL;
