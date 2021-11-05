<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReservaConcepto;
use App\Models\ReservaConceptoDetalle as RCDetalle;
use App\Http\Resources\ReservaConcepto as RCResourse;
use App\Models\Cliente;

use App\Helpers\Helper;
use App\Http\Controllers\CajaController;

class ReservaConceptoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $query = ReservaConcepto::
        select(
            'ReservaConcepto.idReserva',
            \DB::raw('IF(ReservaConcepto.tipo=1, CONCAT(Colaborador.nombres," ",Colaborador.apellidoPaterno," ",Colaborador.apellidoMaterno),Cliente.denominacion) as persona'),
            'ReservaConcepto.motivo',
            'ReservaConcepto.fecha',
            'ReservaConcepto.desde',
            'ReservaConcepto.hasta',
            'ReservaConcepto.tipo',
            'ReservaConcepto.total',
            'ReservaConcepto.idCuenta',
            'cu.estado',
            \DB::raw('CONCAT("[",
            GROUP_CONCAT(DISTINCT CONCAT(
                "{ ""concepto"": "" " ,c.descripcion," "" ",
                ", ""gratuito"": " ,IFNULL(rcd.gratuito,0),
                ", ""cantidad"": " ,rcd.cantidad,
                ", ""price"": " ,rcd.price,
                ", ""descuento"": " ,IFNULL(rcd.descuento,0),
                ", ""total"": " ,rcd.total,
                "}"
                )
            ORDER BY rcd.idConcepto DESC SEPARATOR ",")
            ,"]") as detalle')
        )
        ->leftJoin('Cliente', 'Cliente.idCliente', '=', 'ReservaConcepto.idResponsable')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'ReservaConcepto.idResponsable')
        ->join('ReservaConceptoDetalle as rcd', 'rcd.idReserva', '=', 'ReservaConcepto.idReserva')
        ->join('Concepto as c', 'c.idConcepto', '=', 'rcd.idConcepto')
        ->leftJoin('Cuenta as cu', 'cu.idCuenta', '=', 'ReservaConcepto.idCuenta');

        if($request->searchReserva){
            $query
            ->where('Colaborador.nombres','like','%'.$request->searchReserva.'%')
            ->orWhere('Colaborador.apellidoPaterno','like','%'.$request->searchReserva.'%')
            ->orWhere('Colaborador.apellidoMaterno','like','%'.$request->searchReserva.'%')
            ->orWhere('Cliente.denominacion','like','%'.$request->searchReserva.'%')
            ->orWhere('ReservaConcepto.motivo','like','%'.$request->searchReserva.'%');
        }

        $query
        ->groupBy('ReservaConcepto.idReserva')
        ->groupBy('Colaborador.nombres')
        ->groupBy('Colaborador.apellidoPaterno')
        ->groupBy('Colaborador.apellidoMaterno')
        ->groupBy('Cliente.denominacion')
        ->groupBy('motivo')
        ->groupBy('fecha')
        ->groupBy('desde')
        ->groupBy('hasta')
        ->groupBy('tipo')
        ->groupBy('ReservaConcepto.total')
        ->groupBy('ReservaConcepto.idCuenta')
        ->groupBy('cu.estado');

        return RCResourse::collection(
            $query
            ->orderBy('ReservaConcepto.fecha', 'desc')
            ->orderBy('ReservaConcepto.desde', 'asc')
            ->paginate(10));
    }

    public function listWeek(Request $request)
    {
        $start = $request->start ? $request->start : (date('m').'-01');
        $end = $request->end ? $request->end : (date('m').'-31');
        $query = ReservaConcepto::
        select(
            'ReservaConcepto.fecha',
            'Concepto.descripcion as description',
            'ReservaConcepto.tipo as codTipo',
            \DB::raw('IF(ReservaConcepto.tipo=1,"Interna","Externa") as tipo'),
            \DB::raw('LCase(CONCAT(TIME_FORMAT(ReservaConcepto.desde,"%l:%i%p"),"-",TIME_FORMAT(ReservaConcepto.hasta,"%l:%i%p"))) as small'),
            \DB::raw('IF(ReservaConcepto.tipo=1, Colaborador.nombres,Cliente.denominacion) as person'),
            \DB::raw('IFNULL(ReservaConcepto.motivo, "") as motivo'),
            \DB::raw('ReservaConcepto.desde as desde'),
            \DB::raw('ReservaConcepto.hasta as hasta'),
            \DB::raw('Concepto.idConcepto as idc')
        )
        ->join('ReservaConceptoDetalle', 'ReservaConceptoDetalle.idReserva', '=', 'ReservaConcepto.idReserva')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'ReservaConceptoDetalle.idConcepto')
        ->leftJoin('Cliente', 'Cliente.idCliente', '=', 'ReservaConcepto.idResponsable')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'ReservaConcepto.idResponsable')
        ->whereRaw(
            "DATE_FORMAT( ReservaConcepto.fecha, '2021-%m-%d') BETWEEN ? AND ? ",
            [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
        );

        if($request->idConcepto){
            $query->where('Concepto.idConcepto', '=',$request->idConcepto);
        }
        
        return $query
            ->orderBy('ReservaConcepto.desde', 'asc')
            ->get();
    }

    public function listMonth(Request $request)
    {
        $month = $request->month ? $request->month : date('m');
        $query = ReservaConcepto::
        select(
            'ReservaConcepto.fecha',
            'Concepto.descripcion as description',
            'ReservaConcepto.tipo as codTipo',
            \DB::raw('IF(ReservaConcepto.tipo=1,"Interna","Externa") as tipo'),
            \DB::raw('LCase(CONCAT(TIME_FORMAT(ReservaConcepto.desde,"%l:%i%p"),"-",TIME_FORMAT(ReservaConcepto.hasta,"%l:%i%p"))) as small'),
            \DB::raw('IF(ReservaConcepto.tipo=1, Colaborador.nombres,Cliente.denominacion) as person'),
            \DB::raw('ReservaConcepto.motivo'),
            \DB::raw('ReservaConcepto.desde as desde'),
            \DB::raw('ReservaConcepto.hasta as hasta'),
            \DB::raw('Concepto.idConcepto as idc')
        )
        ->join('ReservaConceptoDetalle', 'ReservaConceptoDetalle.idReserva', '=', 'ReservaConcepto.idReserva')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'ReservaConceptoDetalle.idConcepto')
        ->leftJoin('Cliente', 'Cliente.idCliente', '=', 'ReservaConcepto.idResponsable')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'ReservaConcepto.idResponsable')
        ->whereMonth( 'ReservaConcepto.fecha',$month);

        if($request->idConcepto){
            $query->where('Concepto.idConcepto', '=',$request->idConcepto);
        }
            
        return $query
            ->orderBy('ReservaConcepto.desde', 'asc')
            ->get();
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
            $request->validate([
                'date' => 'required',
                'motivo' => 'nullable',
                'idResponsable' => 'required',
                'since' => 'required|string',
                'until' => 'required|string',
                'typeReservation' => 'required|string',
                'items' => 'required'
            ]);
            
            \DB::beginTransaction();
            
            $newRC = new ReservaConcepto();
            $newRC->motivo =  $request->motivo; 
            $newRC->fecha =  $request->date; 
            $newRC->desde =  $request->since; 
            $newRC->hasta =  $request->until; 
            $newRC->horas =  $request->dif; 
            $newRC->tipo =  $request->typeReservation === "interna" ? 1 : 2; 
            $newRC->idResponsable =  $request->idResponsable; 
            $newRC->user_create =  auth()->user()->idUsuario; 
            $newRC->user_update =  auth()->user()->idUsuario; 
            $newRC->save(); 
            $total_acum=0;
            foreach ($request->items as $key => $item) {
                $newRCD = new RCDetalle();
                $newRCD->idReserva = $newRC->idReserva; 
                $newRCD->idConcepto = $item['idAmbiente'];
                $newRCD->gratuito = array_key_exists('gratuito',$item) ? $item['gratuito'] : null;
                $newRCD->cantidad = $request->dif;
                $newRCD->price = $item['price'];
                $newRCD->descuento = array_key_exists('descuento',$item) ? $item['descuento'] : null;
                $newRCD->total = $request->dif*$item['price']-(array_key_exists('descuento',$item) ? $item['descuento'] : 0);
                $newRCD->save(); 
                $total_acum+=$request->dif*$item['price']-(array_key_exists('descuento',$item) ? $item['descuento'] : 0);
            }
            $newRC->total =  $total_acum;
            $newRC->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Reserva registrada.',
            ], 200);
        

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generarComprobante(Request $request){
        try {
            $reserva = ReservaConcepto::find($request->idRC);

            $metadata = new \stdClass();
            
            $cliente = Cliente::find($reserva->idResponsable);
            
            $metadata->idRC = $request->idRC;
            $metadata->observacion = "BANCOS AMBIENTES";
            $metadata->idCliente = $cliente->idCliente;
            $metadata->cliente_documento = $cliente->documento;
            $metadata->cliente_documento = $cliente->documento;
            $metadata->cliente = $cliente->denominacion;
            $metadata->cliente_direccion = $cliente->direccion;
            $metadata->total = $reserva->total;

            //ITEMS
                $detalle = RCDetalle::select(
                    \DB::raw('"" as detail'),
                    'c.idConcepto',
                    'c.descripcion as labelConcepto',
                    'ReservaConceptoDetalle.cantidad as ammount',
                    'ReservaConceptoDetalle.gratuito',
                    \DB::raw('IF(ReservaConceptoDetalle.gratuito is not null,IFNULL(c.valorConIGV,0),ReservaConceptoDetalle.price) as price'),
                    \DB::raw('IF(ReservaConceptoDetalle.gratuito is not null,6,1) as igv'),
                    \DB::raw('IFNULL(ReservaConceptoDetalle.descuento,0) as descuento'),
                    \DB::raw('IF(ReservaConceptoDetalle.gratuito is not null,IFNULL(c.valorConIGV,0)*ReservaConceptoDetalle.cantidad,ReservaConceptoDetalle.total) as subtotal')
                    )
                ->join('Concepto as c','c.idConcepto','=','ReservaConceptoDetalle.idConcepto')->where('idReserva',$request->idRC)->get();
                $items=[];
                $metadata->items = $detalle;
                $metadata->pagado = $request->pagado ? 2 : 1;
                $metadata->opcion = $request->banco;
                $metadata->numsofdoc = $request->sofdoc;
                $metadata->montoPaid = $request->monto;
            //ITEMS

            //INFO 

            $reserva->idCuenta = 0;
            $reserva->save();
            
            $helper = new Helper;
            $helper::firebaseSender(
                'Solicitud de comprobante',
                5, //tipo
                ' solicita generar un comprobante'. ($request->pagado ? " PAGADO." : " NO PAGADO."),
                $metadata,
                4, //receiver
                null,
                null,
                'primary'
            );

            return response()->json([
                'message' => "Solicitud enviada.",
            ], 200);
        

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmarCheckIn(Request $request){
        try {

            $helper = new CajaController;
            $rpta = $helper::saveCajaCuenta($request);
            $rptaJSON = json_decode($rpta);
            if($rptaJSON->error){
                return response()->json([
                    'message' => $rptaJSON->message,
                ],500);
            }
            
            $reserva = ReservaConcepto::find($request->idRC);
            $reserva->idCuenta = $rptaJSON->idCuenta;
            $reserva->save();

            return response()->json([
                'message' => $rptaJSON->message
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
