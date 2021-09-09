INSERT INTO `route` (`idRoute`, `path`, `name`, `icon`, `component`, `layout`, `show`, `parent`, `parentId`)
VALUES (NULL, '/pendientes-caja', 'Pendientes', 'fa fa-clock text-blue', '53', '/admin', '1', '0', '18')
ALTER TABLE `route` ADD `orderRoute` INT NULL AFTER `idRoute`;
UPDATE `route` SET `orderRoute` = '1' WHERE `route`.`idRoute` = 30;
UPDATE `route` SET `orderRoute` = '2' WHERE `route`.`idRoute` = 96;
UPDATE `route` SET `orderRoute` = '3' WHERE `route`.`idRoute` = 31;
UPDATE `route` SET `orderRoute` = '5' WHERE `route`.`idRoute` = 33;
UPDATE `route` SET `orderRoute` = '6' WHERE `route`.`idRoute` = 34;

ALTER TABLE `cuenta` CHANGE `total` `total` DECIMAL(10,2) NOT NULL;

