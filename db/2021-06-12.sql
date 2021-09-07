
CREATE TABLE `ReservaConcepto` (
  `idReserva` int(11) NOT NULL,
  `motivo` VARCHAR(150) NULL,
  `fecha` date NOT NULL,
  `desde` time NOT NULL,
  `hasta` time NOT NULL,
  `horas` smallint(6) NOT NULL,
  `tipo` smallint(6) NOT NULL DEFAULT 1 COMMENT '1: Interna, 2: Externa',
  `estado` smallint(6) NOT NULL DEFAULT 1 COMMENT '1: Reserva, 2: Ocupado',
  `idResponsable` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `ReservaConcepto`
  ADD PRIMARY KEY (`idReserva`);

ALTER TABLE `ReservaConcepto`
  MODIFY `idReserva` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ReservaConcepto` ADD `user_create` INT NOT NULL AFTER `idResponsable`;
ALTER TABLE `ReservaConcepto`  ADD `user_update` INT NOT NULL  AFTER `user_create`;
ALTER TABLE `ReservaConcepto` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `user_update`;
ALTER TABLE `ReservaConcepto` ADD `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_at`;

CREATE TABLE `cclamor_asociadosrelacional`.`ReservaConceptoDetalle` ( `idReservaDetalle` INT NOT NULL AUTO_INCREMENT , `idReserva` INT NOT NULL , `idConcepto` INT NOT NULL , `gratuito` BOOLEAN NULL , PRIMARY KEY (`idReservaDetalle`)) ENGINE = InnoDB;
ALTER TABLE `ReservaConceptoDetalle` CHANGE `gratuito` `gratuito` BOOLEAN NULL DEFAULT FALSE;
ALTER TABLE `ReservaConceptoDetalle` ADD `cantidad` SMALLINT NOT NULL AFTER `gratuito`;
ALTER TABLE `ReservaConceptoDetalle` ADD `price` FLOAT NOT NULL AFTER `cantidad`, ADD `descuento` FLOAT NULL AFTER `price`, ADD `total` FLOAT NOT NULL AFTER `descuento`;

ALTER TABLE `ReservaConcepto` ADD `idCuenta` INT NULL AFTER `idResponsable`;
ALTER TABLE `ReservaConcepto` ADD `total` DECIMAL(5,2) NULL AFTER `horas`;


INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '', 'Ambientes', 'ni ni-building text-blue', '0', '/admin', '0', '1', '0');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '6', '87');
INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/disponibilidad', 'Disponilidad', 'fa fa-calendar-alt text-blue', '51', '/admin', '1', '0', '87');
INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/reservas', 'Reservas', 'fa fa-table text-blue', '52', '/admin', '1', '0', '87');

INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '', 'Ambientes', 'ni ni-building text-blue', '0', '/admin', '0', '1', '0');
INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/disponibilidad', 'Disponilidad', 'fa fa-calendar-alt text-blue', '51', '/admin', '1', '0', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '1', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '2', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '3', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '4', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '5', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '7', '90');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '8', '90');

INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '', 'Ambientes', 'ni ni-building text-blue', '0', '/admin', '0', '1', '0');
INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/reservas', 'Reservas', 'fa fa-table text-blue', '52', '/admin', '1', '0', '92');
INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/reserva', 'Reserva', 'fa fa-table text-blue', '50', '/admin', '0', '0', '92');
INSERT INTO `RolRoute` (`idRolRoute`, `idRol`, `idRoute`) VALUES (NULL, '9', '92');

INSERT INTO `Concepto` (`idConcepto`, `codigo`, `descripcion`, `tipoConcepto`, `tipoIGV`, `valorSinIGV`, `valorConIGV`, `priceInmutable`, `categoriaCuenta`, `user_create`, `user_update`) VALUES (NULL, '165', 'AUDITORIO 1', '1', '1', NULL, NULL, '0', '6', '0', '0');
INSERT INTO `Concepto` (`idConcepto`, `codigo`, `descripcion`, `tipoConcepto`, `tipoIGV`, `valorSinIGV`, `valorConIGV`, `priceInmutable`, `categoriaCuenta`, `user_create`, `user_update`) VALUES (NULL, '166', 'AUDITORIO 2', '1', '1', NULL, NULL, '0', '6', '0', '0');
INSERT INTO `Concepto` (`idConcepto`, `codigo`, `descripcion`, `tipoConcepto`, `tipoIGV`, `valorSinIGV`, `valorConIGV`, `priceInmutable`, `categoriaCuenta`, `user_create`, `user_update`) VALUES (NULL, '167', 'ZOOM 100 PERSONAS', '1', '1', NULL, NULL, '0', '6', '0', '0');
INSERT INTO `Concepto` (`idConcepto`, `codigo`, `descripcion`, `tipoConcepto`, `tipoIGV`, `valorSinIGV`, `valorConIGV`, `priceInmutable`, `categoriaCuenta`, `user_create`, `user_update`) VALUES (NULL, '168', 'ZOOM 500 PERSONAS', '1', '1', NULL, NULL, '0', '6', '0', '0');

INSERT INTO `Route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`) VALUES (NULL, '/ambientes/reserva', 'Reserva', 'fa fa-table text-blue', '50', '/admin', '0', '0', '87');