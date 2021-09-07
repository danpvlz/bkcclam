CREATE TABLE `cclamor_asociadosrelacional`.`Cliente` ( `idCliente` INT NOT NULL AUTO_INCREMENT , `tipoDocumento` TINYINT(1) NOT NULL , `documento` VARCHAR(13) NOT NULL , `denominacion` VARCHAR(100) NOT NULL , `direccion` VARCHAR(200) NULL , `email` VARCHAR(150) NULL , `telefono` VARCHAR(150) NULL , PRIMARY KEY (`idCliente`));
ALTER TABLE `Cuenta` CHANGE `idAsociado` `idAdquiriente` INT(11) NOT NULL;
ALTER TABLE Cuenta add moneda TINYINT not null DEFAULT '1' AFTER idAdquiriente;
ALTER TABLE `CuentaDetalle` ADD `IGV` FLOAT NOT NULL DEFAULT '0' AFTER `descuento`;
