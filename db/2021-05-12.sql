ALTER TABLE `Inscripcion` ADD `user_create` INT NOT NULL AFTER `active`;
ALTER TABLE `Inscripcion`  ADD `user_update` INT NOT NULL  AFTER `user_create`;
ALTER TABLE `Inscripcion` ADD `created_at` TIMESTAMP NOT NULL AFTER `user_update`;
ALTER TABLE `Inscripcion` ADD `updated_at` TIMESTAMP NOT NULL AFTER `created_at`;
update Inscripcion set created_at=fecha, updated_at=fecha;
update Inscripcion set user_create=1,user_update=1;