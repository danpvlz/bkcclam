<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Caja as CajaResourse;
use App\Models\Caja;
use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Concepto;
use App\Models\Colaborador;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

use App\Helpers\Helper;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CajaExport;
use App\Exports\CajaDetalleExport;
use App\Http\Controllers\CuentaController;

class CajaController extends Controller
{

    public function export(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idCliente' => 'integer',
            'idArea' => 'integer'
        ]);

        return Excel::download(
            new CajaExport(
                $request->since,
                $request->until,
                $request->status,
                $request->number,
                $request->idCliente,
                $request->idArea
            ), 'Caja.xlsx');
    }

    public function exportDetailBills(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idCliente' => 'integer',
            'idArea' => 'integer'
        ]);

        return Excel::download(
            new CajaDetalleExport(
                $request->since,
                $request->until,
                $request->status,
                $request->number,
                $request->idCliente,
                $request->idArea
            ), 'DetalleCaja.xlsx');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    
    public function showIndicators(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idCliente' => 'integer'
        ]);

        $where_since= $request->since ?' && Cuenta.fechaEmision>="'.$request->since.'"' : '';
        $where_until=$request->until ? ' && Cuenta.fechaEmision<="'.$request->until.'"' : '';

        $cuentas=Cuenta::join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->select(
            \DB::raw('SUM(IF(Cuenta.estado=1 && Cuenta.tipoDocumento!=3, total, 0)) AS pendientes'),
            \DB::raw('SUM(IF(Cuenta.estado=3 , total, 0))+SUM(IF(Cuenta.estado=2 && Cuenta.tipoDocumento=3, total, 0)) AS anulado'),
            \DB::raw('SUM(IF(Cuenta.estado!=3 && Cuenta.tipoDocumento!=3,Cuenta.total,0)) AS emitidos')
        )->where('Cuenta.serie','like','%108');

        $cobrado=Cuenta::join('Cliente', 'Cuenta.idAdquiriente', '=', 'Cliente.idCliente')
        ->select(
            \DB::raw('SUM(Cuenta.total) as cobrado')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.estado','=','2')
        ->whereIn('Cuenta.tipoDocumento', [1, 2]);

        if($request->since){
            if($request->status==4){
                $cuentas->where('Cuenta.fechaEmision','>=',$request->since);
                $cobrado->where('Cuenta.fechaEmision','>=',$request->since);
            }else{
                $cuentas->where('Cuenta.fechaEmision','>=',$request->since);
                $cobrado->where('fechaFinPago','>=',$request->since);
            }
        }

        if($request->until){
            if($request->status==4){
                $cuentas->where('Cuenta.fechaEmision','<=',$request->until);
                $cobrado->where('Cuenta.fechaEmision','<=',$request->until);
            }else{
                $cuentas->where('Cuenta.fechaEmision','<=',$request->until);
                $cobrado->where('fechaFinPago','<=',$request->until);
            }
        }

        if($request->status && $request->status<4){
            $cuentas->where('Cuenta.estado','=',$request->status);
        }

        if($request->number){
            $cuentas->where('Cuenta.numero','like',$request->number);
        }

        if($request->idCliente){
            $cuentas->where('Cuenta.idAdquiriente','=',$request->idCliente);
        }

        $cuentas=$cuentas->get();

        return response()->json([
            'pendientes' => ROUND($cuentas[0]->pendientes,2),
            'cobrado' => ROUND($cobrado->get()[0]->cobrado,2),
            'emitidos' => ROUND($cuentas[0]->emitidos,2),
            'anulado' => ROUND($cuentas[0]->anulado,2),
        ], 200);
    }

    public function listPendings(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idCliente' => 'integer'
        ]);

        $first= Cuenta::join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->select(
            'Cuenta.idCuenta', 
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cliente.denominacion', 
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.fechaVencimiento',
            'Cuenta.observaciones'
        )->where('Cuenta.serie','like','%108')->where('Cuenta.estado','=','1')->whereIn('Cuenta.tipoDocumento',[1,2])->whereNotNull('Cuenta.fechaVencimiento')->orderBy('Cuenta.fechaVencimiento','asc');

        return CajaResourse::collection(
            $first->orderBy('idCuenta', 'desc')->paginate(10)
        );
    }
    
    public function listBills(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idCliente' => 'integer',
            'idArea' => 'integer'
        ]);

        $first= Cuenta::
        select(
            'Cuenta.idCuenta', 
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            'Cliente.denominacion', 
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.fechaFinPago',
            'Cuenta.fechaAnulacion',
            'Cuenta.observaciones',
            \DB::raw('
            GROUP_CONCAT(DISTINCT Area.nombre
            ORDER BY Area.nombre DESC SEPARATOR ", ")
            as areas')
        )
        ->join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->join('CategoriaCuenta', 'CategoriaCuenta.idCategoria', '=', 'Concepto.categoriaCuenta')
        ->join('Area', 'Area.idArea', '=', 'CategoriaCuenta.idArea')
        ->groupBy('Cuenta.idCuenta')
        ->groupBy('Cuenta.fechaEmision')
        ->groupBy('Cuenta.tipoDocumento')
        ->groupBy('Cuenta.serie')
        ->groupBy('Cuenta.numero')
        ->groupBy('Cliente.denominacion')
        ->groupBy('Cuenta.total')
        ->groupBy('Cuenta.estado')
        ->groupBy('Cuenta.fechaFinPago')
        ->groupBy('Cuenta.fechaAnulacion')
        ->groupBy('Cuenta.observaciones')
        ->where('Cuenta.serie','like','%108');

        if($request->status && $request->status<4){
            $first->where('Cuenta.estado','=',$request->status);
            if($request->status==2){
                $first->where('Cuenta.tipoDocumento','<',3);
            }
        }

        if($request->number){
            $first->where('Cuenta.numero','like',$request->number);
        }

        if($request->idCliente){
            $first->where('Cuenta.idAdquiriente','=',$request->idCliente);
        }

        $CurrentColaborador = Colaborador::find(auth()->user()->idColaborador);
        if(auth()->user()->rol === 1 && $CurrentColaborador){
            $first->where('CategoriaCuenta.idArea','=',$CurrentColaborador->idArea);
        }

        if($request->idArea){
            $first->where('CategoriaCuenta.idArea','=',$request->idArea);
        }

        if($request->since || $request->until){
            $since = $request->since;
            $until = $request->until;
            $status = $request->status;

            $first->where(function($query) use ($since,$until,$status) {
                if($status){
                    $query->orWhere(function($query2) use ($since,$until) {
                        if($since){
                            $query2->where('Cuenta.fechaEmision','>=',$since);
                        }
                        if($until){
                            $query2->where('Cuenta.fechaEmision','<=',$until);
                        }
                    });
                }else{
                    $query->orWhere(function($query2) use ($since,$until) {
                        if($since){
                            $query2->where('Cuenta.fechaEmision','>=',$since);
                        }
                        if($until){
                            $query2->where('Cuenta.fechaEmision','<=',$until);
                        }
                    })
                    ->orWhere(function($query3) use ($since,$until) {
                        if($since){
                            $query3->where('Cuenta.fechaFinPago','>=',$since);
                        }
                        if($until){
                            $query3->where('Cuenta.fechaFinPago','<=',$until);
                        }
                    })->orWhere(function($query3) use ($since,$until,$status) {
                        if($status==3){
                            if($since){
                                $query3->where('Cuenta.fechaAnulacion','>=',$since);
                            }
                            if($until){
                                $query3->where('Cuenta.fechaAnulacion','<=',$until);
                            }
                        }
                    });
                }
            });
        }

        return CajaResourse::collection(
            $first->orderBy('idCuenta', 'desc')->paginate(10)
        );
    }

    public function generateNC(Request $request)
    {
        $request->validate([
            'idCuenta' => 'integer',
            'tiponc' => 'required',
        ]);
        $foundCuenta= Cuenta::join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->select(
            'Cliente.idCliente',
            'Cuenta.tipoDocumento',
            \DB::raw('Cliente.tipoDocumento as tipoDoc'),
            'Cliente.documento',
            'Cuenta.fechaEmision', 
            'Cuenta.fechaVencimiento', 
            'Cuenta.idCuenta', 
            'Cuenta.serie', 
            'Cuenta.numero', 
            'Cliente.denominacion',
            'Cliente.direccion',
            'Cuenta.total', 
            'Cuenta.estado',
            \DB::raw('IFNULL(Cuenta.observaciones, "-") as observaciones')
        )->where('Cuenta.idCuenta',$request->idCuenta)->first();

        $numeroComprobante=Cuenta::where('serie','like','%108')->where('tipoDocumento',3)->max('numero')+1;

        $comprobante_param = [
            "operacion"				            => "generar_comprobante",
            "tipo_de_comprobante"               => 3, //2=BOLETA/1=FACTURA/3=NC
            "serie"                             => 'F108',
            "numero"				            => $numeroComprobante,
            "sunat_transaction"			        => "1",
            "cliente_tipo_de_documento"			=> $foundCuenta->tipoDoc,
            "cliente_numero_de_documento"		=> $foundCuenta->documento,
            "cliente_email"=> "",
            "cliente_email_1"=> "",
            "cliente_email_2"=> "",
            "fecha_de_emision"=> date('d-m-Y'),
            "moneda"=> "1",
            "tipo_de_cambio"=> "",
            "porcentaje_de_igv"=> "18.00",
            "descuento_global"=> "",
            "total_descuento"=> "",
            "total_anticipo"=> "",
            "total_inafecta"=> "",
            "total_gratuita"=> "",
            "total_otros_cargos"=> "",
            "percepcion_tipo"=> "",
            "percepcion_base_imponible"=> "",
            "total_percepcion"=> "",
            "total_incluido_percepcion"=> "",
            "detraccion"=> "false",
            "observaciones"=> $foundCuenta->observaciones,
            "documento_que_se_modifica_tipo"=> $foundCuenta->tipoDocumento,
            "documento_que_se_modifica_serie"=> $foundCuenta->serie,
            "documento_que_se_modifica_numero"=> $foundCuenta->numero,
            "tipo_de_nota_de_debito"=> "",
            "enviar_automaticamente_a_la_sunat"=> "true",
            "enviar_automaticamente_al_cliente"=> "false",
            "codigo_unico"=> "",
            "condiciones_de_pago"=> "",
            "medio_de_pago"=> "",
            "placa_vehiculo"=> "",
            "orden_compra_servicio"=> "",
            "tabla_personalizada_codigo"=> "",
            "formato_de_pdf"=> "",
        ];

        $denominacion=$foundCuenta->denominacion;
        $direccion=$foundCuenta->direccion;
        /*CHECK REAL DIRECTION*/
            $clientedit= Cliente::where('idCliente',$foundCuenta->idCliente)->first();
            if($clientedit && $clientedit->tipoDocumento===6){
                $helperDoc = new Helper;
                $rpta=$helperDoc::searchPremium('ruc',$clientedit->documento);
                $clientedit->direccion=$rpta->direccion_completa;
                $denominacion=$rpta->nombre_o_razon_social;
                $direccion=$rpta->direccion_completa;
                $clientedit->save();
            }
        /*CHECK REAL DIRECTION*/
        $comprobante_param['cliente_denominacion']=$denominacion;
        $comprobante_param['cliente_direccion']=$direccion;

        if($request->tiponc===1){
            $comprobante_param['fecha_de_vencimiento']=date('d-m-Y');
            $comprobante_param['tipo_de_nota_de_credito']=1;
            $items=CuentaDetalle::
            select(
                \DB::raw('Concepto.idConcepto as identificador_interno'),
                \DB::raw('"ZZ" as unidad_de_medida'),
                \DB::raw('Concepto.codigo'),
                'Concepto.descripcion',
                'CuentaDetalle.cantidad',
                \DB::raw('(CuentaDetalle.subtotal+CuentaDetalle.descuento)/CuentaDetalle.cantidad as valor_unitario'),
                \DB::raw('CuentaDetalle.precioUnit as precio_unitario'),
                'CuentaDetalle.descuento',
                \DB::raw('CuentaDetalle.subtotal as subtotal'),
                \DB::raw('IF(CuentaDetalle.tipoIGV=0,1,CuentaDetalle.tipoIGV) as tipo_de_igv'),
                \DB::raw('CuentaDetalle.IGV as igv'),
                \DB::raw('CuentaDetalle.total as total'),
                \DB::raw('"false" as anticipo_regularizacion')
            )
            ->join('Concepto','Concepto.idConcepto','CuentaDetalle.idConcepto')
            ->where('idCuenta',$request->idCuenta)->get();

            // ANULAR CUENTA
                $anular=Cuenta::find($foundCuenta->idCuenta);
                $anular->estado=3;
                $anular->fechaAnulacion=date('Y-m-d');
                $anular->save();

                Pago::where('idCuenta', $foundCuenta->idCuenta)
                ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);

                if($foundCuenta->fechaVencimiento && $foundCuenta->estado==1){
                    $venta_al_credito = [
                        "cuota"=> 1,
                        "fecha_de_pago"=> $foundCuenta->fechaVencimiento,
                        "importe"=> $foundCuenta->total
                    ];
                    $comprobante_param['venta_al_credito']=$venta_al_credito;
                }
            // ANULAR CUENTA

        }else{
            $ndate=date_create($request->newFecha);
            $newFechaformat=date_format($ndate,"d-m-Y");
            $comprobante_param['fecha_de_vencimiento']=$newFechaformat;
            $comprobante_param['tipo_de_nota_de_credito']=13;
            $items=CuentaDetalle::
            select(
                \DB::raw('Concepto.idConcepto as identificador_interno'),
                \DB::raw('"ZZ" as unidad_de_medida'),
                \DB::raw('Concepto.codigo'),
                'Concepto.descripcion',
                'CuentaDetalle.cantidad',
                \DB::raw('"0" as valor_unitario'),
                \DB::raw('"0" as precio_unitario'),
                \DB::raw('"0" as subtotal'),
                \DB::raw('IF(CuentaDetalle.tipoIGV=0,1,CuentaDetalle.tipoIGV) as tipo_de_igv'),
                \DB::raw('"0" as igv'),
                \DB::raw('"0" as total'),
                \DB::raw('"false" as anticipo_regularizacion')
            )
            ->join('Concepto','Concepto.idConcepto','CuentaDetalle.idConcepto')
            ->where('idCuenta',$request->idCuenta)->get();

            $venta_al_credito = [
                "cuota"=> 1,
                "fecha_de_pago"=> $newFechaformat,
                "importe"=> $foundCuenta->total
            ];
            $comprobante_param['venta_al_credito']=$venta_al_credito;
            /*CHANGE FECHA VENC*/
                $changeFecha=Cuenta::find($foundCuenta->idCuenta);
                $changeFecha->fechaVencimiento=$request->newFecha;
                $changeFecha->save();
            /*CHANGE FECHA VENC*/
        }
        $comprobante_param['items']=$items;
        $totalGravada=0;
        $totalExonerada=0;
        $totalIgv=0;
        $totalDescuento=0;
        /*SAVE IN BD*/
            $Cuenta = new Cuenta();
            $Cuenta->fechaEmision = date('Y-m-d');
            $Cuenta->fechaVencimiento = $request->newFecha ? $request->newFecha : date('Y-m-d');
            $Cuenta->tipoDocumento = 3; 
            $Cuenta->serie = 'F108';
            $Cuenta->numero = $numeroComprobante; 
            $Cuenta->idAdquiriente = $foundCuenta->idCliente;
            $Cuenta->estado = 2; 
            $Cuenta->user_create =  auth()->user()->idUsuario; 
            $Cuenta->user_update =  auth()->user()->idUsuario; 
            $Cuenta->IGV =$totalIgv; 
            $Cuenta->subtotal = $totalGravada+$totalExonerada; 
            $Cuenta->total = $totalGravada+$totalExonerada; 
            $Cuenta->save();
            foreach ($items as $key => $item) {
                $totalDescuento+=$item['descuento'];
                $totalIgv+=$item['igv'];
                if($item['tipo_de_igv']===1){
                    $totalGravada+=$item['total'];
                }else{
                    $totalExonerada+=$item['total'];
                }
                $Detalle = new CuentaDetalle();
                $Detalle->idCuenta = $Cuenta->idCuenta; 
                $Detalle->idConcepto = $item['identificador_interno']; 
                $Detalle->tipoIGV = $item['tipo_de_igv'];
                $Detalle->precioUnit =$item['precio_unitario'];
                $Detalle->cantidad = $item['cantidad'];
                $Detalle->subtotal = $item['subtotal'];
                $Detalle->IGV =  $item['igv'];
                $Detalle->total = $item['total'];
                $Detalle->user_create =  auth()->user()->idUsuario; 
                $Detalle->user_update =  auth()->user()->idUsuario; 
                $Detalle->save(); 
            }
            $Cuenta->IGV =$totalIgv; 
            $Cuenta->subtotal = $totalGravada+$totalExonerada; 
            $Cuenta->total = $totalGravada+$totalExonerada; 
            $Cuenta->save();
            $comprobante_param['total_gravada']=$totalGravada===0 && $request->tiponc===2?"":$totalGravada;
            $comprobante_param['total_exonerada']=$totalExonerada===0?"":$totalExonerada;
            $comprobante_param['total_igv']=$totalIgv;
            $comprobante_param['total_descuento']=$totalDescuento===0?"":$totalDescuento;
            $comprobante_param['total']=$totalExonerada+$totalGravada;
        /*SAVE IN BD*/
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
                'headers' => [
                    'Content-type' => 'application/json; charset=utf-8',
                    'Authorization'     => env('APP_NUBEFACT_KEY_108')
                ],
                \GuzzleHttp\RequestOptions::JSON   => $comprobante_param
            ]);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            \DB::rollback();
            $response = $e->getResponse();
            $responseBodyAsString = json_decode($response->getBody()->getContents());
            if($responseBodyAsString->errors){
                return response()->json([
                    'message' => $responseBodyAsString->errors,
                ], 401);
            }
        }

        return response()->json([
            'message' => 'Nota de crédito generada!',
            'data' => $comprobante_param
        ], 200);
    }


    public static function saveCajaCuenta($request) : string{
        
        try {
            /*CHECK REAL DIRECTION*/
            $clientedit= Cliente::where('idCliente',$request->idCliente)->first();
            if($clientedit && $clientedit->tipoDocumento===6){
                $helperDoc = new Helper;
                $rpta=$helperDoc::searchPremium('ruc',$clientedit->documento);
                $clientedit->direccion=$rpta->direccion_completa;
                $clientedit->save();
            }
            /*CHECK REAL DIRECTION*/

            /*SEARCH CLIENTE*/
                $ClienteSearched= Cliente::find($request->idCliente);
            /*SEARCH CLIENTE*/
            
            switch ($request->tipo_de_comprobante) {
                case 1:
                    $serie = "F108";
                    break;
                case 2:
                    $serie = "B108";
                    break;
                default:
                    $serie = $request->docModificar["serie"];
                    break;
            }
            $numeroComprobante=Cuenta::where('serie',$serie)->where('tipoDocumento',$request->tipo_de_comprobante)->max('numero')+1;

            if($request->tipo_de_comprobante==3){
                $CuentaAnular = Cuenta::where('serie',$request->docModificar["serie"])->where('tipoDocumento',$request->docModificar["tipo"])->where('numero',$request->docModificar["numero"])->first();
                if(is_null($CuentaAnular)){
                    \DB::rollback();
                    throw new Exception('Cuenta no encontrada');
                }
                $CuentaAnular->estado=3;
                $CuentaAnular->fechaAnulacion=date('Y-m-d');
                $CuentaAnular->save();
            }

            \DB::beginTransaction();
                
            //CUENTA
                $Cuenta = new Cuenta();
                $Cuenta->fechaEmision = $request->fechaEmision; 
                $Cuenta->fechaVencimiento = $request->fechaVencimiento ? $request->fechaVencimiento : date('Y-m-d');
                $Cuenta->tipoDocumento = $request->tipo_de_comprobante; 
                $Cuenta->serie = $serie; 
                $Cuenta->numero = $numeroComprobante; 
                $Cuenta->idAdquiriente = $request->idCliente; 
                $Cuenta->moneda = $request->typeChange;
                $Cuenta->observaciones = $request->observacion; 
                $Cuenta->estado = $request->tipo_de_comprobante==3 ? 2 : $request->pagado; 
                // if($request->pagado==2 || $request->tipo_de_comprobante==3){
                if($request->pagado==2){
                    $Cuenta->fechaFinPago = $request->fechaEmision; 
                }
                $Cuenta->subtotal = 0;
                $Cuenta->IGV = 0; 
                $Cuenta->total = 0; 
                $Cuenta->user_create =  auth()->user()->idUsuario; 
                $Cuenta->user_update =  auth()->user()->idUsuario; 
                $Cuenta->save();
            //CUENTA
            
            //DETALLE
                $gravadaAcumulado=0;
                $gratuitaAcumulado=0;
                $igvAcumulado=0;
                $descuentoAcumulado=0;
                $totalAcumulado=0;
                $itemsNubefact=[];
                foreach ($request->items as $key => $item) {
                    $igv_calc = $item['igv']==1 ? 0.18 : 1;
                    $igv_calc_inv = $item['igv']==1 ? 100/118 : 1;
                    $descuento = array_key_exists('descuento',$item) ? $item['descuento'] : 0;
                    $total= $item['subtotal'];
                    $subtotal= $total*$igv_calc_inv;
                    $igv= ROUND($subtotal*$igv_calc,2);
                    $vunitario= ($subtotal+$descuento)/$item['ammount'];
                    $punitario=  ROUND($vunitario*$igv_calc+$vunitario,2);
                    $vunitario= ROUND($vunitario,2);
                    $subtotal= ROUND($total*$igv_calc_inv,2);

                    $Detalle = new CuentaDetalle();
                    $Detalle->idCuenta = $Cuenta->idCuenta; 
                    $Detalle->idConcepto = $item['idConcepto']; 
                    $Detalle->detalleAdicional = $item['detail']; 
                    $Detalle->tipoIGV = $item['igv'];
                    $Detalle->precioUnit =$vunitario; //43.14
                    $Detalle->cantidad = $item['ammount']; //2
                    $Detalle->descuento = $descuento; //10
                    $Detalle->subtotal = $subtotal; //76.27
                    $Detalle->IGV =   $igv; //13.73
                    $Detalle->total = $total; //90
                    $Detalle->user_create =  auth()->user()->idUsuario; 
                    $Detalle->user_update =  auth()->user()->idUsuario; 

                    $gravadaAcumulado = $gravadaAcumulado + ROUND($item['igv']==1 ? $subtotal : 0,2);
                    $gratuitaAcumulado = $gratuitaAcumulado + ROUND((($item['igv']==7 || $item['igv']==6) ? $subtotal : 0),2);
                    $igvAcumulado = $igvAcumulado + $igv;
                    $descuentoAcumulado = $descuentoAcumulado + $descuento;
                    $totalAcumulado = $totalAcumulado + ($item['igv']==1 ? $total : 0);
                    
                    $Detalle->save(); 
                
                    $Concepto = Concepto::find($item['idConcepto']);
                    
                    if(is_null($Concepto)){
                        \DB::rollback();
                        throw new Exception('Concepto "'.$Concepto->descripcion.'" no encontrado');
                    }

                    $descripcionDetalle = $Concepto->descripcion;
                    $descripcionDetalle .= $item['detail'] ? ' - '.$item['detail'] : '';
                    
                    $itemsNubefact[]=[
                        "unidad_de_medida"          => $Concepto->tipoConcepto==1 ? "ZZ" : "NIU",
                        "codigo"                    => $Concepto->codigo,
                        "descripcion"               => $descripcionDetalle,
                        "cantidad"                  => $item['ammount'],
                        "valor_unitario"            => $vunitario,
                        "precio_unitario"           => $punitario,
                        "descuento"                 => array_key_exists('descuento',$item) ? $item['descuento'] : "", //
                        "subtotal"                  => $subtotal,
                        "tipo_de_igv"               => $Detalle->tipoIGV,
                        "igv"                       => $igv,
                        "total"                     => $total,
                        "anticipo_regularizacion"   => "false"
                    ];
                }
            //DETALLE
            
            $Cuenta->subtotal = $gravadaAcumulado;
            $Cuenta->IGV = $igvAcumulado; 
            $Cuenta->total = $totalAcumulado; 
            $Cuenta->save();

            //PAGO
                if($request->pagado==2 && $totalAcumulado>0 && $request->tipo_de_comprobante!=3){
                    $Pago = new Pago();
                    $Pago->idCuenta = $Cuenta->idCuenta;
                    $Pago->monto = $totalAcumulado; 
                    $Pago->fecha = date('Y-m-d'); 
                    $Pago->banco = $request->opcion; 
                    $Pago->numoperacion = $request->numoperacion; 
                    $Pago->numsofdoc = $request->numsofdoc; 
                    $Pago->montoPaid = $request->montoPaid; 
                    $Pago->user_create =  auth()->user()->idUsuario; 
                    $Pago->user_update =  auth()->user()->idUsuario;
                    $Pago->save(); 
                }
            //PAGO

            $comprobante_param = [
                "operacion"				            => "generar_comprobante",
                "tipo_de_comprobante"               => $request->tipo_de_comprobante, //2=BOLETA/1=FACTURA
                "serie"                             => $serie,
                "numero"				            => $numeroComprobante,
                "sunat_transaction"			        => "1",
                "cliente_tipo_de_documento"		    => $ClienteSearched->tipoDocumento,
                "cliente_numero_de_documento"	    => $ClienteSearched->documento,
                "cliente_denominacion"              => $ClienteSearched->denominacion,
                "cliente_direccion"                 => $request->tipo_de_comprobante==2 ? "-" : ($ClienteSearched->direccion ? $ClienteSearched->direccion : "-"),
                "cliente_email"                     => $request->correo ? $request->correo : "",
                "fecha_de_emision"                  => date('d-m-Y'),
                "fecha_de_vencimiento"              => $request->fechaVencimiento ? $request->fechaVencimiento : date('Y-m-d'),
                "moneda"                            => $Cuenta->moneda,
                "porcentaje_de_igv"                 => "18.00",
                "total_gravada"                     => $gravadaAcumulado ? $gravadaAcumulado : "",
                "total_gratuita"                    => $gratuitaAcumulado ? $gratuitaAcumulado : "",
                "total_igv"                         => $igvAcumulado ? ROUND($igvAcumulado,2) : "",
                "total"                             => $totalAcumulado,
                "detraccion"                        => "false",
                "enviar_automaticamente_a_la_sunat" => "true",
                "enviar_automaticamente_al_cliente" => $request->correo=="" ? "false" : "true",
                "observaciones"                     => $request->observacion ? $request->observacion : '',
                "documento_que_se_modifica_tipo"    => $request->docModificar["tipo"] ? $request->docModificar["tipo"] : "",
                "documento_que_se_modifica_serie"   => $request->docModificar["serie"] ? $request->docModificar["serie"] : "",
                "documento_que_se_modifica_numero"  => $request->docModificar["numero"] ? $request->docModificar["numero"] : "",
                "tipo_de_nota_de_credito"           => $request->docModificar["tipo"] ? 1 : "",
                "items" => $itemsNubefact
            ];
            
            
            if( $request->tipo_de_comprobante!=3){
                if($request->pagado!=2){
                    $venta_al_credito[]=[
                        "cuota" => 1,
                        "fecha_de_pago" => $request->fechaVencimiento,
                        "importe" => $totalAcumulado,
                    ];
                    $comprobante_param["medio_de_pago"]="CREDITO";
                    $comprobante_param["condiciones_de_pago"]="CRÉDITO AL ".$request->fechaVencimiento;
                    $comprobante_param["venta_al_credito"]=$venta_al_credito;
                }else{
                    $comprobante_param["medio_de_pago"]="CONTADO";
                    $comprobante_param["condiciones_de_pago"]="CONTADO";
                }
            }
            
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
                    'headers' => [
                        'Content-type' => 'application/json; charset=utf-8',
                        'Authorization'     => env('APP_NUBEFACT_KEY_108')
                    ],
                    \GuzzleHttp\RequestOptions::JSON   => $comprobante_param
                ]);
            }
            catch (\GuzzleHttp\Exception\RequestException $e) {
                \DB::rollback();
                $response = $e->getResponse();
                $responseBodyAsString = json_decode($response->getBody()->getContents());
                if($responseBodyAsString->errors){
                    return response()->json([
                        'message' => $responseBodyAsString->errors,
                    ], 401);
                }
            }
            
            \DB::commit();
            
            if($request->opcion!=6){
                $helper = new Helper;
                $helper::checkPayInfo($request->numoperacion,$request->numsofdoc);
            }
            $rpta = new \stdClass();
            $rpta->message = 'Cuenta registrada!';
            $rpta->idCuenta = $Cuenta->idCuenta;

            return json_encode($rpta);

        } catch (Exception $e) {
            \DB::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $request->validate([
                'idCliente' => 'required|integer',
                'fechaEmision' => 'required',
                'correo' => 'nullable',
                'docModificar' => 'nullable',
                'items' => 'required',
                'observacion' => 'nullable',
                'pagado' => 'nullable',
                'opcion' => 'nullable',
                'tipo_de_comprobante' => 'required|integer',
                'typeChange' => 'required|integer'
            ]);
            
            $rpta = self::saveCajaCuenta($request);
            
            return response()->json([
                'message' => json_decode($rpta)->message
            ], 200);

        }catch (Exception $e) {
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
        $Cuenta= Cuenta::join('Cliente', 'Cliente.idCliente', '=', 'Cuenta.idAdquiriente')
        ->leftJoin('users', 'Cuenta.user_update', '=', 'users.idUsuario')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador')
        ->select(
            'Cuenta.fechaEmision', 
            'Cuenta.fechaVencimiento', 
            'Cuenta.idCuenta', 
            'Cuenta.serie', 
            'Cuenta.numero', 
            'Cliente.denominacion',
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.tipoDocumento',
            \DB::raw('IFNULL(Cuenta.observaciones, "-") as observaciones'),
            \DB::raw('Cuenta.updated_at as lastUpdate'),
            \DB::raw('IF(Cuenta.user_update!=0, CONCAT(Colaborador.nombres, " ", Colaborador.apellidoPaterno),"-") as userLastChanged')
        )->where('idCuenta',$id)->first();
        
        $CuentaDetalle = CuentaDetalle::
        join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->join('CategoriaCuenta', 'CategoriaCuenta.idCategoria', '=', 'Concepto.categoriaCuenta')
        ->select(
            \DB::raw('CONCAT(CategoriaCuenta.nombre," - ",Concepto.descripcion, " ", IFNULL(CuentaDetalle.detalleAdicional,"")) as descripcion'),
            'CuentaDetalle.cantidad',
            'CuentaDetalle.subtotal',
            \DB::raw('CuentaDetalle.IGV as totaligv'),
            'CuentaDetalle.tipoIGV',
            'CuentaDetalle.total'
        )
        ->where('idCuenta',$id)->get();

        $Pagos = Pago::where('idCuenta',$id)->get();

        //CONSULTA NUEBEFACT
        $nubefactrequest = new \stdClass();
        $nubefactrequest->tipo_de_comprobante=$Cuenta->tipoDocumento;
        $nubefactrequest->serie=$Cuenta->serie;
        $nubefactrequest->numero=$Cuenta->numero;
        $helper = new CuentaController;
        $nubefact = $helper::getNubefact($nubefactrequest);
        //CONSULTA NUEBEFACT

        return response()->json([
            'cuenta' => $Cuenta,
            'detalle' => $CuentaDetalle,
            'pagos' => $Pagos,
            'nubefact' => json_decode($nubefact)
        ], 200);
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

    public function pay(Request $request) { 
        try { 
            $request->validate([ 
            'idCuenta' => 'required|integer', 
            'monto' => 'required', 
            'fechaPago' => 'required', 
            "opcion" => 'integer', 
            "numoperacion" => 'nullable', 
            "numsofdoc" => 'nullable', ]); 

            \DB::beginTransaction(); 
            $Cuenta = Cuenta::find($request->idCuenta); 
            $pagadoPagos = Pago::select(\DB::raw('SUM(monto) as pagado'))->where('idCuenta',$request->idCuenta)->first()->pagado; 
            $checkp=ROUND($pagadoPagos+$request->monto,2);
            if(ROUND($Cuenta->total,2) == $checkp){ 
                $Cuenta->estado = 2; $Cuenta->fechaFinPago = $request->fechaPago; 
            } 
            $Cuenta->user_update = auth()->user()->idUsuario; $Cuenta->update();
            if(ROUND($Cuenta->total,2) < $checkp){ 
                return response()->json([
                    'message' => 'Monto excedente al cobrado.'.$checkp.'>'.$Cuenta->total,
                ],500);
            }else{ 
                $Pago = new Pago(); 
                $Pago->idCuenta = $Cuenta->idCuenta; 
                $Pago->monto = $request->monto; 
                $Pago->fecha = $request->fechaPago;
                $Pago->banco = $request->opcion;
                $Pago->numoperacion = $request->numoperacion;
                $Pago->numsofdoc = $request->numsofdoc;
                $Pago->montoPaid = $request->montoPaid; 
                $Pago->user_create = auth()->user()->idUsuario;
                $Pago->user_update = auth()->user()->idUsuario;
            } 
            $Pago->save();
            \DB::commit();
             
            if($request->opcion!=6){
                $helper = new Helper;
                $helper::checkPayInfo($request->numoperacion,$request->numsofdoc);
            }
            
            return response()->json([ 
                'message' => 'Pago registrado.', 
            ], 200);

        } catch (Exception $e) { \DB::rollback();
            return response()->json([ 
                'message' => $e->getMessage(),
            ], 500);
        } 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function anularCajaCuenta($id)
    {
        try {        
        \DB::beginTransaction();
            
        $Cuenta = Cuenta::find($id);
        $Cuenta->estado = 3; 
        $Cuenta->fechaAnulacion = date('Y-m-d'); 
        $Cuenta->user_update = auth()->user()->idUsuario;
        $Cuenta->update();
            
        Pago::where('idCuenta', $id)
        ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);

        $anulacion_param = [
            "operacion"				    => "generar_anulacion",
            "tipo_de_comprobante"       => $Cuenta->tipoDocumento,
            "serie"                     => $Cuenta->serie, //
            "numero"			       	=> $Cuenta->numero, //
            "motivo"			        => "error de generacion de comprobante",
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
            'headers' => [
                'Content-type' => 'application/json; charset=utf-8',
                'Authorization'     => env('APP_NUBEFACT_KEY_108')
            ],
            \GuzzleHttp\RequestOptions::JSON   => $anulacion_param
        ]);
        $nubefactRegister = $response->getBody()->getContents();

        \DB::commit();

        return response()->json([
            'message' => 'Cuenta anulada',
        ], 200);
    

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function loadDashboard(Request $request)
    {
        $mes = $request->mes ? $request->mes : date('m');
        $lineGraphCobrado = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('MONTH(fechaFinPago) as mes'),
            \DB::raw('ROUND(SUM(cd.total),2) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','=','2')
        ->whereYear('fechaFinPago',date('Y'))
        ->groupBy('mes');

        $lineGraphEmitido = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('MONTH(fechaEmision) as mes'),
            \DB::raw('ROUND(SUM(cd.total),2) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->groupBy('mes');

        $bars = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('a.idArea'),
            \DB::raw('a.nombre as area'),
            \DB::raw('sum(cd.total) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->whereIn('Cuenta.estado',[1,2])
        ->whereYear('Cuenta.fechaFinPago',date('Y'));

        $emitido = Cuenta::join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('a.idArea'),
            \DB::raw('a.nombre as area'),
            \DB::raw('sum(cd.total) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->whereIn('Cuenta.estado',[1,2])
        ->where('Cuenta.tipoDocumento','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->whereMonth('fechaEmision',$mes)->groupBy('idArea')->groupBy('area')->get();
        
        return response()->json([
            'lineEmitido' =>$lineGraphEmitido->orderBy('mes')->get(),
            'lineCobrado' =>$lineGraphCobrado->orderBy('mes')->get(),
            'bars' => $bars->groupBy('idArea')->groupBy('area')->get(),
            'currentPaidMonthBars' => $bars->whereMonth('fechaFinPago',$mes)->groupBy('idArea')->groupBy('area')->get(),
            'tableCurrent' => $emitido
        ], 200);
    }

    public function loadDashboardByArea(Request $request)
    {
        $mes = $request->mes ? $request->mes : date('m');
        $CurrentColaborador = Colaborador::find(auth()->user()->idColaborador);
        $area = $request->area ? $request->area : ($CurrentColaborador->idArea ? $CurrentColaborador->idArea : 5);

        $lineGraphCobrado = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('MONTH(fechaFinPago) as mes'),
            \DB::raw('ROUND(SUM(cd.total),2) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','=','2')
        ->whereYear('fechaFinPago',date('Y'))
        ->where('a.idArea','=',$area)
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        $lineGraphEmitido = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('MONTH(fechaEmision) as mes'),
            \DB::raw('ROUND(SUM(cd.total),2) as monto'),
            \DB::raw('COUNT(cd.total) as cantidad')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->where('a.idArea','=',$area)
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();

        $conceptos = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('co.idConcepto'),
            \DB::raw('co.descripcion as concepto'),
            \DB::raw('COUNT(cd.idConcepto) as cantidad'),
            \DB::raw('SUM(cd.total) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->whereMonth('fechaEmision',$mes)
        ->where('a.idArea','=',$area)
        ->groupBy('co.idConcepto','co.descripcion')
        ->orderBy($request->orderConceptos ? $request->orderConceptos : 'monto','desc')
        ->get();

        $clientes = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Cliente as cl', 'cl.idCliente', 'Cuenta.idAdquiriente')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('cl.idCliente'),
            \DB::raw('cl.denominacion as cliente'),
            \DB::raw('COUNT(cd.idConcepto) as cantidad'),
            \DB::raw('SUM(cd.total) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->whereMonth('fechaEmision',$mes)
        ->where('a.idArea','=',$area)
        ->groupBy('cl.idCliente','cl.denominacion')
        ->orderBy($request->orderClientes ? $request->orderClientes : 'monto','desc')
        ->get();

        $clientesPrev = Cuenta::
        join('CuentaDetalle as cd', 'cd.idCuenta', 'Cuenta.idCuenta')
        ->join('Cliente as cl', 'cl.idCliente', 'Cuenta.idAdquiriente')
        ->join('Concepto as co', 'co.idConcepto', 'cd.idConcepto')
        ->join('CategoriaCuenta as cc', 'cc.idCategoria', 'co.categoriaCuenta')
        ->join('Area as a', 'a.idArea', 'cc.idArea')
        ->select(
            \DB::raw('cl.idCliente'),
            \DB::raw('cl.denominacion as cliente'),
            \DB::raw('COUNT(cd.idConcepto) as cantidad'),
            \DB::raw('SUM(cd.total) as monto')
        )
        ->where('Cuenta.serie','like','%108')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('Cuenta.estado','!=','3')
        ->whereYear('fechaEmision',date('Y'))
        ->whereMonth('fechaEmision',$mes-1)
        ->where('a.idArea','=',$area)
        ->groupBy('cl.idCliente','cl.denominacion')
        ->orderBy($request->orderClientes ? $request->orderClientes : 'monto','desc')
        ->get();
        return response()->json([
            'clientes' =>$clientes,
            'clientesPrev' =>$clientesPrev,
            'conceptos' =>$conceptos,
            'lineEmitido' =>$lineGraphEmitido,
            'lineCobrado' =>$lineGraphCobrado,
        ], 200);
    }
    
}