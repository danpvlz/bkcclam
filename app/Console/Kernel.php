<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //ASISTENCIA
            //LUNES A VIERNES
                //FALTAS ENTRADA
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario)
                    select CURRENT_DATE,"08:30:00",1,3,-270,idUsuario from 
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador where u.estado=1 and c.presencial=0 and idUsuario in 
                    (
                        select idUsuario from users where idUsuario not in 
                        (select idUsuario from Asistencia where fecha=CURRENT_DATE)
                    );');
                    
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario)
                    select CURRENT_DATE,"13:00:00",2,3, idUsuario from
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador
                    where presencial=0 and idUsuario in 
                    (
                        select idUsuario from Asistencia where 
                        fecha=CURRENT_DATE and estado=3 and idUsuario not in 
                        (
                            select idUsuario from Asistencia where 
                            fecha=CURRENT_DATE and 
                            tipo=2 and 
                            estado=3
                        )
                    );');
                })
                ->weekdays()->at('09:30')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) 
                    select CURRENT_DATE,"13:00:00",2,1,idUsuario from
                    users where estado=1 and idUsuario not in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=2 and
                        hora>"10:00:00"
                    )
                    and idUsuario in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=1 and
                        hora<"10:00:00"
                    );');
                })
                ->weekdays()->at('15:35')
                ->runInBackground();
            
                //Entrada FALTAS 
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario)
                    select CURRENT_DATE,"15:30:00",1,3,-210,idUsuario from
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador where u.estado=1 and c.presencial=0 and idUsuario not in 
                    (
                        select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=1 and hora>"10:00:00"
                    );');
                    
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario)
                    select CURRENT_DATE,"19:00:00",2,3,idUsuario from
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador
                    where presencial=0 and idUsuario in 
                    (
                        select idUsuario from Asistencia where fecha=CURRENT_DATE and estado=3 and idUsuario not in 
                        (
                            select idUsuario from Asistencia where 
                            fecha=CURRENT_DATE and
                            tipo=2 and
                            estado=3
                            and hora>"17:00:00"
                        )
                    );');
                })
                ->weekdays()->at('16:30')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) 
                    select CURRENT_DATE,"19:00:00",2,1,idUsuario from users
                    where estado=1 and idUsuario not in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=2 and
                        hora>"17:00:00"
                    )
                    and idUsuario in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=1 and
                        hora<"17:00:00"
                    );');
                })
                ->weekdays()->at('23:55')
                ->runInBackground();
            //LUNES A VIERNES
            
            //SÁBADOS
                //Entrada
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario) 
                    select CURRENT_DATE,"09:00:00",1,3,-240,idUsuario from 
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador where u.estado=1 and c.presencial=0 and idUsuario in 
                    (
                        select idUsuario from users where idUsuario not in 
                        (select idUsuario from Asistencia where fecha=CURRENT_DATE)
                    );');
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) 
                    select CURRENT_DATE,"13:00:00",2,3,idUsuario from 
                    users u inner join Colaborador c on u.idColaborador=c.idColaborador
                    where presencial=0 and idUsuario in 
                    (
                        select idUsuario from Asistencia where 
                        fecha=CURRENT_DATE and estado=3 and idUsuario not in 
                        (
                            select idUsuario from Asistencia where 
                            fecha=CURRENT_DATE and 
                            tipo=2 and 
                            estado=3
                        )
                    );');
                })
                ->saturdays()->at('10:30')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('
                    INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) 
                    select CURRENT_DATE,"13:00:00",2,1,idUsuario from
                    users where estado=1 and idUsuario not in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=2 and
                        hora>"11:00:00"
                    )
                    and idUsuario in
                    (
                        select idUsuario from Asistencia where
                        fecha=CURRENT_DATE and
                        tipo=1 and
                        hora<"11:00:00"
                    );');
                })
                ->saturdays()->at('23:55')
                ->runInBackground();
            //SÁBADOS
        //ASISTENCIA
        
        //VACACIONES
            //LUNES A VIERNES
                //Entrada
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario) select CURRENT_DATE,"08:30:00",1,6,270,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=1 and estado=6 and hora="08:30"));');
                })
                ->weekdays()->at('08:30')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) select CURRENT_DATE,"13:00:00",2,6,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=2 and estado=6 and hora="13:00:00"));');
                })
                ->weekdays()->at('13:00')
                ->runInBackground();
            
                //Entrada
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario) select CURRENT_DATE,"15:30:00",1,6,210,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=1 and estado=6 and hora="15:30:00"));');
                })
                ->weekdays()->at('15:30')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) select CURRENT_DATE,"19:00:00",2,6,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=2 and estado=6 and hora="19:00:00"));');
                })
                ->weekdays()->at('19:00')
                ->runInBackground();
            //LUNES A VIERNES
        
            //SABADO
                //Entrada
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,calc,idUsuario) select CURRENT_DATE,"09:00:00",1,6,240,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=1 and estado=6 and hora="09:00"));');
                })
                ->saturdays()->at('09:00')
                ->runInBackground();
            
                //Salida
                $schedule->call(function () {
                    \DB::statement('INSERT INTO Asistencia (fecha,hora,tipo,estado,idUsuario) select CURRENT_DATE,"13:00:00",2,6,idUsuario from users where idUsuario in (select idUsuario from users where estado=2 and idUsuario not in (select idUsuario from Asistencia where fecha=CURRENT_DATE and tipo=2 and estado=6 and hora="13:00:00"));');
                })
                ->saturdays()->at('13:00')
                ->runInBackground();
            //SABADO
        //VACACIONES

        //KPI VENCIMIENTO
            $schedule->call(function () {
                \DB::statement('Delete from `KPIPass` WHERE created_at < (NOW() - INTERVAL 24 HOUR);');
            })
            ->hourly()
            ->runInBackground();
        //KPI VENCIMIENTO
    }   

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
