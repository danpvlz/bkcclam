<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Asistencia as AsistenciaResourse;
use App\Models\Asistencia;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query=  Asistencia::join('users','users.idUsuario','=','Asistencia.idUsuario')
        ->join('Colaborador','Colaborador.idColaborador','=','users.idColaborador')        
        ->where('active',1);
        return AsistenciaResourse::collection(
            $query
            ->paginate(10));
    }
    
    public function showAllIndicators(Request $request)
    {
        $request->validate([
            'month' => 'date',
            'idColaborador' => 'integer',
        ]);
        
        $tardanzas=Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->selectRaw('sum(calc) as acum')->where('Asistencia.estado',2);

        if($request->month){
            $tardanzas->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $tardanzas->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        if($request->idColaborador){
            $tardanzas->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        $tardanzas=$tardanzas->first();

        $faltas=Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->selectRaw('fecha')->where('Asistencia.estado',3)->where('Asistencia.tipo',1);
        if($request->month){
            $faltas->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $faltas->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        if($request->idColaborador){
            $faltas->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        $faltas=$faltas->count();
        
        $salioTemprano=Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->selectRaw('sum(calc) as acum')->where('Asistencia.estado',"=",4);
        if($request->month){
            $salioTemprano->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $salioTemprano->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        if($request->idColaborador){
            $salioTemprano->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        $salioTemprano=$salioTemprano->first();

        $compensar=Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->selectRaw('sum(calc) as acum')->where('Asistencia.estado',"=",5);
        if($request->month){
            $compensar->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $compensar->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        if($request->idColaborador){
            $compensar->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        $compensar=$compensar->first();

        $tardanzasShow = abs($tardanzas->acum+$salioTemprano->acum);
        
        $hRealizadasShow = $tardanzas->acum+$salioTemprano->acum+$compensar->acum;

        $hCompensarShow = abs($compensar->acum);
        return response()->json([
            'tardanzas' => $tardanzasShow,
            'faltas' => $faltas,
            'hRealizadas' => $hRealizadasShow,
            'hCompensar' => $hCompensarShow,
        ], 200);
    }

    public function listAssistanceByWorker(Request $request)
    {
        $request->validate([
            'month' => 'date',
            'idColaborador' => 'integer',
        ]);

        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador');
        
        if($request->month){
            $assistance->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $assistance->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }

        if($request->idColaborador){
            $assistance->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        
        $assistance
        ->select(
            'Asistencia.idUsuario',
            \DB::raw('CONCAT(Colaborador.nombres, " ",Colaborador.apellidoPaterno, " ",Colaborador.apellidoMaterno) as colaborador'),
            'Colaborador.foto',
            \DB::raw('SUM(case when Asistencia.estado = 5 then calc else 0 end) as compensar'),
            \DB::raw('SUM(case when Asistencia.estado = 2 or Asistencia.estado = 4 then calc else 0 end) as debe'),
            \DB::raw('COUNT(IF(Asistencia.estado = 3 and tipo=1, 1, NULL)) as faltas'),
            \DB::raw('SUM(case when Asistencia.estado = 6 then calc else 0 end) as vacaciones')
        )
        ->groupBy('Asistencia.idUsuario')
        ->groupBy('Colaborador.nombres')
        ->groupBy('Colaborador.apellidoPaterno')
        ->groupBy('Colaborador.apellidoMaterno')
        ->groupBy('Colaborador.foto')
        ->orderBy('Colaborador.nombres', 'asc');            

        return AsistenciaResourse::collection(
            $assistance->paginate(10)
        );
    }

    public function listAssistance(Request $request)
    {
        $request->validate([
            'month' => 'date',
            'idColaborador' => 'integer',
        ]);

        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->where('Asistencia.estado','!=',6);
        
        if($request->month){
            $assistance->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $assistance->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }

        if($request->idColaborador){
            $assistance->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        
        $assistance
        ->select(
            'Asistencia.idUsuario',
            \DB::raw('CONCAT(Colaborador.nombres, " ",Colaborador.apellidoPaterno, " ",Colaborador.apellidoMaterno) as colaborador'),
            'Colaborador.foto',
            'Asistencia.fecha',
            \DB::raw('CONCAT("[",
            GROUP_CONCAT(DISTINCT CONCAT("{""hora"":""",Asistencia.hora,""", ""tipo"":",Asistencia.tipo,", ""estado"":",Asistencia.estado,"}")
            ORDER BY Asistencia.hora,Asistencia.tipo DESC SEPARATOR ",")
            ,"]") as asistencia'),
            \DB::raw('SUM(case when Asistencia.estado = 5 then calc else 0 end) as compensado'),
            \DB::raw('SUM(case when Asistencia.estado = 2 then calc else 0 end) as tardanza'),
            \DB::raw('SUM(case when Asistencia.estado = 3 then calc else 0 end) as falta'),
            \DB::raw('SUM(case when Asistencia.estado = 4 then calc else 0 end) as temp')
        )
        ->groupBy('Asistencia.idUsuario')
        ->groupBy('Asistencia.fecha')
        ->groupBy('Colaborador.nombres')
        ->groupBy('Colaborador.apellidoPaterno')
        ->groupBy('Colaborador.apellidoMaterno')
        ->groupBy('Colaborador.foto')
        ->orderBy('Asistencia.fecha', 'desc');            

        return AsistenciaResourse::collection(
            $assistance->paginate(10)
        );
    }

    public function listDetail(Request $request)
    {
        $request->validate([
            'month' => 'date',
            'idColaborador' => 'integer'
        ]);

        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->where('Asistencia.estado','!=',6);
        
        if($request->month){
            $assistance->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $assistance->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }

        if($request->idColaborador){
            $assistance->where('Colaborador.idColaborador',"=", $request->idColaborador );
        }
        

        $assistance
        ->selectRaw('nombres,apellidoPaterno,apellidoMaterno,foto,Asistencia.fecha,Asistencia.tipo,Asistencia.estado,Asistencia.calc,Asistencia.hora,Asistencia.observacion,Asistencia.justificacion')
        ->orderBy('fecha', 'desc')         
        ->orderBy('hora', 'desc');            

        return AsistenciaResourse::collection(
            $assistance->paginate(10)
        );
    }

    public function listMyTodayAssistance(Request $request)
    {
        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->where('Colaborador.idColaborador',"=", $request->user()->idColaborador )
        ->where('Asistencia.estado','!=',6)
        ->where('Asistencia.fecha',"=",date('Y-m-d'));
        
        $assistance
        ->select(
            'Asistencia.fecha',
            \DB::raw('CONCAT("[",
            GROUP_CONCAT(DISTINCT CONCAT("{""hora"":""",Asistencia.hora,""", ""idAsistencia"":",Asistencia.idAsistencia,", ""estado"":",Asistencia.estado,", ""justificacion"": """,IFNULL(Asistencia.justificacion,""), """}")
            ORDER BY Asistencia.hora,Asistencia.tipo DESC SEPARATOR ",")
            ,"]") as asistencia')
        )
        ->groupBy('Asistencia.fecha')
        ->orderBy('Asistencia.fecha', 'desc');            

        $response = $assistance->get();
        return count($response)==0 ?  [] : $response[0];
    }

    public function listMyAssistance(Request $request)
    {
        $request->validate([
            'month' => 'date',
        ]);

        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->where('Colaborador.idColaborador',"=", $request->user()->idColaborador )
        ->where('Asistencia.estado','!=',6);
        
        if($request->month){
            $assistance->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $assistance->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        
        $assistance
        ->select(
            'Asistencia.fecha',
            \DB::raw('CONCAT("[",
            GROUP_CONCAT(DISTINCT CONCAT("{""hora"":""",Asistencia.hora,""", ""tipo"":",Asistencia.tipo,", ""estado"":",Asistencia.estado,"}")
            ORDER BY Asistencia.hora,Asistencia.tipo DESC SEPARATOR ",")
            ,"]") as asistencia'),
            \DB::raw('SUM(case when Asistencia.estado = 5 then calc else 0 end) as compensado'),
            \DB::raw('SUM(case when Asistencia.estado = 2 then calc else 0 end) as tardanza'),
            \DB::raw('SUM(case when Asistencia.estado = 3 then calc else 0 end) as falta'),
            \DB::raw('SUM(case when Asistencia.estado = 4 then calc else 0 end) as temp')
        )
        ->groupBy('Asistencia.fecha')
        ->orderBy('Asistencia.fecha', 'desc');            

        return AsistenciaResourse::collection(
            $assistance->paginate(10)
        );
    }

    public function listMyAssistanceDetail(Request $request)
    {
        $request->validate([
            'month' => 'date',
        ]);

        $assistance= Asistencia::join('users', 'users.idUsuario', '=', 'Asistencia.idUsuario')
        ->join('Colaborador', 'users.idColaborador', '=', 'Colaborador.idColaborador')
        ->where('Colaborador.idColaborador',"=", $request->user()->idColaborador )
        ->where('Asistencia.estado','!=',6);
        
        if($request->month){
            $assistance->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $assistance->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }

        $assistance
        ->selectRaw('Asistencia.idAsistencia,Asistencia.fecha,Asistencia.tipo,Asistencia.estado,Asistencia.hora,Asistencia.observacion,Asistencia.calc,Asistencia.justificacion')
        ->orderBy('fecha', 'desc')         
        ->orderBy('hora', 'desc');            

        return AsistenciaResourse::collection(
            $assistance->paginate(10)
        );
    }
    
    public function showMyIndicators(Request $request)
    {
        $request->validate([
            'month' => 'date',
        ]);
        
        $tardanzas=Asistencia::selectRaw('sum(calc) as acum')->where('estado',2)->where('idUsuario',$request->user()->idUsuario);
        if($request->month){
            $tardanzas->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $tardanzas->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        $tardanzas=$tardanzas->first();

        $faltas=Asistencia::selectRaw('fecha')->where('estado',3)->where('tipo',1)->where('idUsuario',$request->user()->idUsuario);
        if($request->month){
            $faltas->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $faltas->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        $faltas=$faltas->count();
        
        $salioTemprano=Asistencia::selectRaw('sum(calc) as acum')->where('estado',"=",4)->where('idUsuario',$request->user()->idUsuario);
        if($request->month){
            $salioTemprano->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $salioTemprano->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        $salioTemprano=$salioTemprano->first();

        $compensar=Asistencia::selectRaw('sum(calc) as acum')->where('estado',"=",5)->where('idUsuario',$request->user()->idUsuario);
        if($request->month){
            $compensar->whereMonth('Asistencia.fecha','=',intval(date('m',strtotime($request->month))));
            $compensar->whereYear('Asistencia.fecha','=',intval(date('Y',strtotime($request->month))));
        }
        $compensar=$compensar->first();

        $tardanzasShow = abs($tardanzas->acum+$salioTemprano->acum);
        
        $hRealizadasShow = $tardanzas->acum+$salioTemprano->acum+$compensar->acum;

        $hCompensarShow = abs($compensar->acum);
        return response()->json([
            'tardanzas' => $tardanzasShow,
            'faltas' => $faltas,
            'hRealizadas' => $hRealizadasShow,
            'hCompensar' => $hCompensarShow,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'hora' => 'required',
                'tipo' => 'required',
                'observacion' => 'max:100',
            ]);
            
            \DB::beginTransaction();

            $Asistencia = new Asistencia();
            $Asistencia->idUsuario = Auth::user()->idUsuario;
            $Asistencia->fecha = date("Y-m-d");
            $Asistencia->hora = $request->hora;
            $Asistencia->tipo = $request->tipo;
            $Asistencia->observacion = $request->observacion;

            $dateNumberDay = date('N');

            $estado=1;
            if($dateNumberDay>=1 && $dateNumberDay<=5){
                /*LUNES A VIERNES*/
                if($request->tipo == 1){
                /*ENTRADA*/
                    if($request->hora<="13:00:00"){
                        /*PRIMERA*/
                        if($request->hora>="08:10:00" && $request->hora<="08:35:00"){
                            $minutesDiference = 0;
                            $estado=1;
                        }else{
                            if($request->hora<="08:10:00"){
                                $time1 = new \DateTime('08:10:00');
                            }else{
                                $time1 = new \DateTime('08:35:00');
                            }
                            $time2 = new \DateTime($request->hora);
                            $interval = $time2->diff($time1);
                            $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                            $estado=$minutesDiference>0 ? 5 : 2;
                        }
                        /*PRIMERA*/
                    }else{
                        /*SEGUNDA*/
                        if($request->hora>="15:10:00" && $request->hora<="15:35:00"){
                            $minutesDiference = 0;
                            $estado=1;
                        }else{
                            if($request->hora<="15:10:00"){
                                $time1 = new \DateTime('15:10:00');
                            }else{
                                $time1 = new \DateTime('15:35:00');
                            }
                            $time2 = new \DateTime($request->hora);
                            $interval = $time2->diff($time1);
                            $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                            $estado=$minutesDiference>0 ? 5 : 2;
                        }
                        /*SEGUNDA*/
                    }
                /*ENTRADA*/
                }else{
                    /*SALIDA*/
                    $time1 = new \DateTime('13:05:00');
                    $time2 = new \DateTime($request->hora);
                    $interval = $time1->diff($time2);
                    $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'));

                    if($request->hora<="15:35:00"){
                        /*PRIMERA*/
                        if($request->hora>="13:00:00" && $request->hora<="13:20:00"){
                            $minutesDiference = 0;
                            $estado=1;
                        }else{
                            if($request->hora<="13:00:00"){
                                $time1 = new \DateTime('13:00:00');
                            }else{
                                $time1 = new \DateTime('13:20:00');
                            }
                            $time2 = new \DateTime($request->hora);
                            $interval = $time1->diff($time2);
                            $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                            $estado=$minutesDiference>0 ? 5 : 4;
                        }
                        /*PRIMERA*/
                    }else{
                        /*SEGUNDA*/
                        if($request->hora>="19:00:00" && $request->hora<="19:20:00"){
                            $minutesDiference = 0;
                            $estado=1;
                        }else{
                            if($request->hora<="19:00:00"){
                                $time1 = new \DateTime('19:00:00');
                            }else{
                                $time1 = new \DateTime('19:20:00');
                            }
                            $time2 = new \DateTime($request->hora);
                            $interval = $time1->diff($time2);
                            $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                            $estado=$minutesDiference>0 ? 5 : 4;
                        }
                        /*SEGUNDA*/
                    }
                    /*SALIDA*/
                }  
                /*LUNES A VIERNES*/
            }else{
                /*SÁBADO*/
                if($request->tipo == 1){
                    if($request->hora>="08:40:00" && $request->hora<="09:05:00"){
                        $minutesDiference = 0;
                        $estado=1;
                    }else{
                        if($request->hora<="08:40:00"){
                            $time1 = new \DateTime('08:40:00');
                        }else{
                            $time1 = new \DateTime('09:05:00');
                        }
                        $time2 = new \DateTime($request->hora);
                        $interval = $time2->diff($time1);
                        $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                        $estado=$minutesDiference>0 ? 5 : 2;
                    }
                }else{
                    
                    if($request->hora>="13:00:00" && $request->hora<="13:20:00"){
                        $minutesDiference = 0;
                        $estado=1;
                    }else{
                        if($request->hora<="13:00:00"){
                            $time1 = new \DateTime('13:00:00');
                        }else{
                            $time1 = new \DateTime('13:20:00');
                        }
                        $time2 = new \DateTime($request->hora);
                        $interval = $time1->diff($time2);
                        $minutesDiference = intval($interval->format('%R%h'))*60+intval($interval->format('%R%i'))+round(intval($interval->format('%R%s'))/60,2);
                        $estado=$minutesDiference>0 ? 5 : 4;
                    }
                }
                /*SÁBADO*/
            }

            $Asistencia->estado = $estado;
            $Asistencia->calc = $minutesDiference;

            $Asistencia->save();

            \DB::commit();

            return response()->json([
                'message' =>  'Asistencia rgistrada.',
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function justify(Request $request)
    {
        try {
            $request->validate([
                'idAsistencia' => 'required|integer',
                'justification' => 'required|string',
            ]);
            
            \DB::beginTransaction();
            $assistance=Asistencia::find($request->idAsistencia);
    
            if(is_null($assistance))
                return response()->json([
                    'message' => 'Asistencia inválida',
                ], 400);
                
            $assistance->justificacion=$request->justification;
            if($assistance->estado==3){
                $assistance->calc=0;
                if($assistance->hora=="08:30:00" || $assistance->hora=="09:00:00"){
                    $assistanceOutTime="13:00:00";
                }else{
                    $assistanceOutTime="19:00:00";
                }
                $assistanceOut=Asistencia::where('fecha','=',$assistance->fecha)->where('hora','=',$assistanceOutTime)->where('tipo','=',2)->where('estado','=',3)->where('idUsuario','=',Auth::user()->idUsuario)->first();
                $assistanceOut->estado=1;
                $assistanceOut->save();
            }else{
                $time = new \DateTime($assistance->hora);
                if($assistance->estado == 2){
                    $time->sub(new \DateInterval('PT' . Round($assistance->calc*-1) . 'M'));
                }else{
                    $time->add(new \DateInterval('PT' . Round($assistance->calc*-1) . 'M'));
                }
                
                $assistance->hora = $time->format('H:i:s');
                $assistance->calc=0;
            }
            $assistance->estado=1;
            $assistance->save();
            \DB::commit();

            return response()->json([
                'message' => "Asistencia justificada.",
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
