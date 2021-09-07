update Asistencia set calc=-270 where estado=3 and tipo=1 and hora<"13:00";
update Asistencia set calc=-210 where estado=3 and tipo=1 and hora>"10:00";

update Asistencia set calc=270 where estado=6 and tipo=1 and hora<"13:00";
update Asistencia set calc=210 where estado=6 and tipo=1 and hora>"10:00";