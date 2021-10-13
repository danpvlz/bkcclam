<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Associated\Associated;
use App\Models\Associated\Empresa;
use App\Models\Associated\Persona;
use App\Models\Cuenta;
use App\Http\Resources\Cuenta as CuentaResourse;
use App\Models\CuentaDetalle;
use App\Models\Pago;
use App\Models\Membresia;
use App\Models\Colaborador;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CuentaExport;
use App\Exports\CuentaDetalleExport;
use App\Exports\PendienteExport;
use App\Exports\MembresiaExport;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Helpers\Helper;

class CuentaController extends Controller
{

    public function export(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
            'tipocomprob' => 'integer'
        ]);

        return Excel::download(
            new CuentaExport(
                $request->since,
                $request->until,
                $request->status,
                $request->number,
                $request->idAsociado,
                $request->debCollector,
                $request->tipocomprob
            ), 'Cuentas.xlsx');
    }

    public function exportDetailBills(Request $request)
    {
        $request->validate([
            'since' => 'date',
            'until' => 'date',
            'status' => 'integer',
            'number' => 'string',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
            'tipocomprob' => 'integer'
        ]);

        return Excel::download(
            new CuentaDetalleExport(
                $request->since,
                $request->until,
                $request->status,
                $request->number,
                $request->idAsociado,
                $request->debCollector,
                $request->tipocomprob
            ), 'DetalleCuentas.xlsx');
    }

    public function exportPendings(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);

        return Excel::download(
            new PendienteExport(
                $request->fecha,
                $request->idAsociado,
                $request->debCollector
            ), 'Pendientes.xlsx');
    }

    public function exportMemberships(Request $request)
    {
        $request->validate([
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
            'status' => 'integer'
        ]);

        return Excel::download(
            new MembresiaExport(
                $request->status,
                $request->idAsociado,
                $request->debCollector
            ), 'membresias.xlsx');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return CuentaResourse::collection(
            Cuenta::paginate(10));
    }
    
    public function showIndicators(Request $request)
    {
        $request->validate([
            'since' => 'string',
            'until' => 'string',
            'status' => 'integer',
            'number' => 'string',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
            'typeDetail' => 'integer',
        ]);

        $cuentas=Cuenta::join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Asociado', 'Cuenta.idAdquiriente', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            \DB::raw('SUM(IF(Cuenta.estado=1 && Cuenta.tipoDocumento!=3, Cuenta.total, 0)) AS pendientes'),
            \DB::raw('SUM(IF(Cuenta.estado=3, CuentaDetalle.total, 0))+SUM(IF(Cuenta.estado=2 && Cuenta.tipoDocumento=3 , CuentaDetalle.total, 0)) AS anulado'),
            \DB::raw('SUM(IF(Cuenta.estado!=3 && Cuenta.tipoDocumento!=3,Cuenta.total,0)) AS emitidos')
        )->where('Cuenta.serie','like','%109');

        $anulado=Cuenta::join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Asociado', 'Cuenta.idAdquiriente', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            \DB::raw('SUM(CuentaDetalle.total) AS anulado')
        )
        ->where('Cuenta.serie','like','%109')
        ->where('Cuenta.estado','=','3');

        $cobrado=Pago::join('Cuenta', 'Cuenta.idCuenta', '=', 'Pago.idCuenta')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Asociado', 'Cuenta.idAdquiriente', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            \DB::raw('SUM(Pago.monto) as cobrado')
        )->where('Cuenta.serie','like','%109')
        ->whereIn('Cuenta.tipoDocumento', [1, 2])
        ->where('Pago.estado',1);

        if($request->since){
            if($request->status==4){
                $anulado->where('Cuenta.fechaEmision','>=',$request->since);
                $cobrado->where('Cuenta.fechaEmision','>=',$request->since);
                $cuentas->where('Cuenta.fechaEmision','>=',$request->since);
            }else{
                $anulado->where('Cuenta.fechaAnulacion','>=',$request->since);
                $cobrado->where('fecha','>=',$request->since);
                $cuentas->where('Cuenta.fechaEmision','>=',$request->since);
            }
        }

        if($request->until){
            if($request->status==4){
                $anulado->where('Cuenta.fechaEmision','<=',$request->until);
                $cobrado->where('Cuenta.fechaEmision','<=',$request->until);
                $cuentas->where('Cuenta.fechaEmision','<=',$request->until);
            }else{
                $anulado->where('Cuenta.fechaAnulacion','<=',$request->until);
                $cobrado->where('fecha','<=',$request->until);
                $cuentas->where('Cuenta.fechaEmision','<=',$request->until);
            }
        }

        if($request->status && $request->status<4){
            $cuentas->where('Cuenta.estado','=',$request->status);
            $cobrado->where('Cuenta.estado','=',$request->status);
            $anulado->where('Cuenta.estado','=',$request->status);
        }

        if($request->number){
            $cuentas->where('Cuenta.numero','like',$request->number);
            $anulado->where('Cuenta.numero','like',$request->number);
            $cobrado->where('Cuenta.numero','like',$request->number);
        }

        if($request->idAsociado){
            $cuentas->where('Cuenta.idAdquiriente','=',$request->idAsociado);
            $anulado->where('Cuenta.idAdquiriente','like',$request->idAsociado);
            $cobrado->where('Cuenta.idAdquiriente','like',$request->idAsociado);
        }

        if($request->debCollector){
            $cuentas->where('Sector.idSector',"=", $request->debCollector);
            $anulado->where('Sector.idSector',"=", $request->debCollector);
            $cobrado->where('Sector.idSector',"=", $request->debCollector);
        }
        
        if($request->typeDetail && $request->typeDetail!=0){
            $cuentas->where('CuentaDetalle.idConcepto', $request->typeDetail);
            $anulado->where('CuentaDetalle.idConcepto', $request->typeDetail);
            $cobrado->where('CuentaDetalle.idConcepto', $request->typeDetail);
        }else{
            $cobrado->where('CuentaDetalle.idConcepto', '!=' ,69);
            $cuentas->where('CuentaDetalle.idConcepto', '!=' ,69);
        }

        $cuentas=$cuentas->get();
        $anulado=$anulado->get();
        $cobrado=$cobrado->get();

        return response()->json([
            'pendientes' => ROUND($cuentas[0]->pendientes,2),
            'cobrado' => ROUND($cobrado[0]->cobrado,2),
            'emitidos' => ROUND($cuentas[0]->emitidos,2),
            'anulado' => ROUND($anulado[0]->anulado,2),
        ], 200);
    }

    public function listBills(Request $request)
    {
        $request->validate([
            'since' => 'string',
            'until' => 'string',
            'status' => 'integer',
            'tipocomprob' => 'integer',
            'number' => 'string',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
            'typeDetail' => 'integer',
        ]);

        $first= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->select(
            'Cuenta.idCuenta', 
            'Cuenta.fechaEmision', 
            \DB::raw('IF(Cuenta.tipoDocumento=1, "F",  IF(Cuenta.tipoDocumento=2, "B",  "NC")) as tipo'),
            \DB::raw('CONCAT(Cuenta.serie,"-",Cuenta.numero) as serieNumero'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Cuenta.total', 
            'Cuenta.estado',
            'Sector.descripcion',
            'Cuenta.fechaAnulacion',
            'Cuenta.fechaFinPago'
        )
        ->distinct()
        ->where('Cuenta.serie','like','%109');

        if($request->status && $request->status<4){
            $first->where('Cuenta.estado','=',$request->status);
            if($request->status==2){
                $first->where('Cuenta.tipoDocumento','<',3);
            }
        }
        if($request->tipocomprob){
            $first->where('Cuenta.tipoDocumento','=',$request->tipocomprob);
        }

        if($request->number){
            $first->where('Cuenta.numero','like',$request->number);
        }

        if($request->idAsociado){
            $first->where('Cuenta.idAdquiriente','=',$request->idAsociado);
        }
        
        if($request->debCollector){
            $first->where('Sector.idSector',"=", $request->debCollector);
        }
        
        if($request->typeDetail && $request->typeDetail!=0){
            $first->where('CuentaDetalle.idConcepto', $request->typeDetail);
        }

        if($request->since || $request->until){
            $since = $request->since;
            $until = $request->until;
            $status = $request->status;

            $first->where(function($query) use ($since,$until,$status) {
                if($status==4){
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
                    })->orWhere(function($query3) use ($since,$until) {
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

        return CuentaResourse::collection(
            $first->orderBy('Cuenta.idCuenta', 'desc')->paginate(10)
        );
    }

    public function pendingsIndicators(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);
        
        $where_clause="";
        $where_clause.=$request->idAsociado!=null ? (" and a.idAsociado = ".$request->idAsociado) : "";
        $where_clause.=$request->debCollector!=null ? (" and s.idSector = ".$request->debCollector) : "";

        $data = \DB::select("select SUM(IF(estado=1, total, 0)) AS pendientes, 
        SUM(IF(estado=2, total, 0)) AS cobrado,
        SUM(IF(estado=3, total, 0)) AS anulado,
        SUM(IF(estado!=3, total, 0)) AS emitidos from 
        ( select * from 
            (
            select c.*,e.razonSocial as asociado,s.descripcion FROM 
            Cuenta c inner join 
            Asociado a on c.idAdquiriente=a.idAsociado left join 
            Empresa e on e.idAsociado=a.idAsociado left join
            Persona p on p.idAsociado=a.idAsociado inner join 
            Sector s on s.idSector=a.idSector 
            where serie like '%109' and fechaEmision<'".$request->fecha."' 
            ". $where_clause ."
            ) 
            as a where fechaFinPago>'".$request->fecha."' or fechaFinPago='NULL' or fechaFinPago IS NULL 
        ) 
        as b where fechaAnulacion>'".$request->fecha."' or fechaAnulacion='NULL' or fechaAnulacion IS NULL
        order by b.fechaEmision desc
        ");
        return $data;
    }

    public function listPendientes(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);
        
        $where_clause="";
        $where_clause.=$request->idAsociado!=null ? (" and a.idAsociado = ".$request->idAsociado) : "";
        $where_clause.=$request->debCollector!=null ? (" and s.idSector = ".$request->debCollector) : "";

        $data = \DB::select("select * from 
        ( select * from 
            (
            select c.*,IF(a.tipoAsociado=1, e.razonSocial,p.nombresCompletos) as asociado,s.descripcion FROM 
            Cuenta c inner join 
            Asociado a on c.idAdquiriente=a.idAsociado left join 
            Empresa e on e.idAsociado=a.idAsociado left join
            Persona p on p.idAsociado=a.idAsociado inner join 
            Sector s on s.idSector=a.idSector 
            where serie like '%109' and fechaEmision<'".$request->fecha."' 
            ". $where_clause ."
            ) 
            as a where fechaFinPago>'".$request->fecha."' or fechaFinPago='NULL' or fechaFinPago IS NULL 
        ) 
        as b where fechaAnulacion>'".$request->fecha."' or fechaAnulacion='NULL' or fechaAnulacion IS NULL
        order by b.fechaEmision desc
        ");

        $collect = collect($data);
        $page = 1;
        $size = 10;
        $paginationData = new LengthAwarePaginator(
                                 $collect->forPage($page, $size),
                                 $collect->count(), 
                                 $size, 
                                 $page
                               );
        return $paginationData;
    }

    public function listMemberships(Request $request)
    {
        $request->validate([
            'status' => 'integer',
            'idAsociado' => 'integer',
            'debCollector' => 'integer',
        ]);

        $first= Membresia::join('Asociado', 'Asociado.idAsociado', '=', 'Membresia.idAsociado')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            'Membresia.idMembresia', 
            'Membresia.idCuenta', 
            'Membresia.idAsociado', 
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Membresia.mes', 
            'Membresia.year', 
            'Membresia.masdeuno', 
            'Membresia.estado', 
            'Membresia.cobrado', 
            'Membresia.pagado', 
            'Sector.idSector',
            'Sector.descripcion'
        );

        if($request->status){
            $first->where('Membresia.estado','=',$request->status);
        }

        if($request->idAsociado){
            $first->where('Asociado.idAsociado','=',$request->idAsociado);
        }

        if($request->debCollector){
            $first->where('Sector.idSector','=',$request->debCollector);
        }

        return CuentaResourse::collection(
            $first->orderBy('idMembresia', 'desc')->paginate(10)
        );
    }

    public function generateNC(Request $request)
    {
        $request->validate([
            'idCuenta' => 'integer',
            'tiponc' => 'required',
        ]);

        $foundCuenta= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('users', 'Cuenta.user_update', '=', 'users.idUsuario')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador')
        ->select(
            'Asociado.idAsociado', 
            'Asociado.tipoAsociado', 
            'Cuenta.fechaEmision', 
            'Cuenta.fechaVencimiento', 
            'Cuenta.idCuenta', 
            'Cuenta.tipoDocumento',
            'Cuenta.serie', 
            'Cuenta.numero', 
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as denominacion'),
            \DB::raw('IF(Asociado.tipoAsociado=1, 6,Persona.tipoDocumento) as tipoDoc'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.direccion,Persona.direccion) as direccion'),
            'Cuenta.total', 
            'Cuenta.estado',
            \DB::raw('IFNULL(Cuenta.observaciones, "-") as observaciones'),
            \DB::raw('Cuenta.updated_at as lastUpdate'),
            \DB::raw('IF(Cuenta.user_update!=0, CONCAT(Colaborador.nombres, " ", Colaborador.apellidoPaterno),"-") as userLastChanged')
        )->where('idCuenta',$request->idCuenta)->first();

        $numeroComprobante=Cuenta::where('serie','like','%109')->where('tipoDocumento',3)->max('numero')+1;

        $comprobante_param = [
            "operacion"				            => "generar_comprobante",
            "tipo_de_comprobante"               => 3, //2=BOLETA/1=FACTURA/3=NC
            "serie"                             => 'F109',
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
            if($foundCuenta && $foundCuenta->tipoDoc===6){
                $helperDoc = new Helper;
                if($foundCuenta->tipoAsociado===1){
                    $empedit= Empresa::where('idAsociado', '=', $foundCuenta->idAsociado)->first();
                    $rpta=$helperDoc::searchPremium('ruc',$empedit->ruc);
                    $empedit->direccion=$rpta->direccion_completa;
                    $denominacion=$rpta->nombre_o_razon_social;
                    $direccion=$rpta->direccion_completa;
                    $empedit->save();
                }else{
                    $peredit= Persona::where('idAsociado', '=', $foundCuenta->idAsociado)->first();
                    if($peredit->tipoDocumento===6){
                        $rpta=$helperDoc::searchPremium('ruc',$peredit->documento);
                        $peredit->direccion=$rpta->direccion_completa;
                        $denominacion=$rpta->nombre_o_razon_social;
                        $direccion=$rpta->direccion_completa;
                        $peredit->save();
                    }
                }
            }
        /*CHECK REAL DIRECTION*/
        $comprobante_param['cliente_denominacion']=$denominacion;
        $comprobante_param['cliente_direccion']=$direccion;

        if($request->tiponc===1){
            $comprobante_param['fecha_de_vencimiento']=date('d-m-Y');
            $comprobante_param['tipo_de_nota_de_credito']=1;
            $items=CuentaDetalle::
            select(
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
                    
                $Membresia = Membresia::where('idCuenta', $foundCuenta->idCuenta)
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
                \DB::raw('"ZZ" as unidad_de_medida'),
                \DB::raw('Concepto.idConcepto as codigo'),
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
            $Cuenta->serie = 'F109';
            $Cuenta->numero = $numeroComprobante; 
            $Cuenta->idAdquiriente = $foundCuenta->idAsociado;
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
                $Detalle->idConcepto = $item['codigo']; 
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
                    'Authorization'     => env('APP_NUBEFACT_KEY_109')
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

    public function detailBill(Request $request)
    {
        $request->validate([
            'idCuenta' => 'integer',
        ]);

        $Cuenta= Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('users', 'Cuenta.user_update', '=', 'users.idUsuario')
        ->leftJoin('Colaborador', 'Colaborador.idColaborador', '=', 'users.idColaborador')
        ->select(
            'Cuenta.fechaEmision', 
            'Cuenta.fechaVencimiento', 
            'Cuenta.idCuenta', 
            'Cuenta.serie', 
            'Cuenta.numero', 
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as denominacion'),
            'Cuenta.total', 
            'Cuenta.estado',
            'Cuenta.tipoDocumento',
            \DB::raw('IFNULL(Cuenta.observaciones, "-") as observaciones'),
            \DB::raw('Cuenta.updated_at as lastUpdate'),
            \DB::raw('IF(Cuenta.user_update!=0, CONCAT(Colaborador.nombres, " ", Colaborador.apellidoPaterno),"-") as userLastChanged')
        )->where('idCuenta',$request->idCuenta)->first();

        $CuentaDetalle = CuentaDetalle::select(
            'Concepto.descripcion', 
            'CuentaDetalle.cantidad',
            'CuentaDetalle.subtotal',
            \DB::raw('CuentaDetalle.IGV as totaligv'),
            'CuentaDetalle.tipoIGV',
            'CuentaDetalle.total'
        )->join('Concepto', 'Concepto.idConcepto', '=', 'CuentaDetalle.idConcepto')
        ->where('idCuenta',$request->idCuenta)->get();

        $Membresia = Membresia::where('idCuenta',$request->idCuenta)->get();

        $Pagos = Pago::where('idCuenta',$request->idCuenta)->get();


        //CONSULTA NUEBEFACT
        $nubefactrequest = new \stdClass();
        $nubefactrequest->tipo_de_comprobante=$Cuenta->tipoDocumento;
        $nubefactrequest->serie=$Cuenta->serie;
        $nubefactrequest->numero=$Cuenta->numero;
        $nubefact = self::getNubefact($nubefactrequest);
        //CONSULTA NUEBEFACT
        
        return response()->json([
            'cuenta' => $Cuenta,
            'detalle' => $CuentaDetalle,
            'membresia' => $Membresia,
            'pagos' => $Pagos,
            'nubefact' => json_decode($nubefact)
        ], 200);
    }
    
    public function listBySector(Request $request)
    {
        $since = $request->since ? $request->since : date('Y-m').'-01';
        $until = $request->until ? $request->until : date('Y-m').'-31';
        
        $cuentas=Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            'Sector.idSector',            
            'Sector.descripcion',            
            \DB::raw('
            SUM(IF(Cuenta.estado=2 && Cuenta.tipoDocumento!=3 && Cuenta.fechaFinPago>="'.$since.'" && Cuenta.fechaFinPago<="'.$until.'", CuentaDetalle.total, 0))
            -SUM(IF(Cuenta.estado=2 && CuentaDetalle.idConcepto=69 && Cuenta.fechaFinPago>="'.$since.'" && Cuenta.fechaFinPago<="'.$until.'", Cuenta.total, 0)) 
            AS cobrado'),
            \DB::raw('SUM(IF(Cuenta.estado!=3 && Cuenta.tipoDocumento!=3  && CuentaDetalle.idConcepto!=69  && Cuenta.fechaEmision>="'.$since.'" && Cuenta.fechaEmision<="'.$until.'",Cuenta.total,0))
            -SUM(IF(Cuenta.estado!=3 && Cuenta.tipoDocumento!=3 && CuentaDetalle.idConcepto=69 && Cuenta.fechaEmision>="'.$since.'" && Cuenta.fechaEmision<="'.$until.'", Cuenta.total, 0)) 
            AS emitidos'),
            \DB::raw('(Select IFNULL(sum(importeMensual),0) from Asociado aa where aa.idSector=Sector.idSector and aa.estado=1 AND aa.idAsociado not in ( SELECT idAsociado FROM `Membresia` where estado=2 and year=YEAR(NOW()) and mes=MONTH(NOW()) and TIMESTAMPDIFF(MONTH,updated_at,CURRENT_DATE())>0 )) as meta'),
            \DB::raw('(Select IFNULL(count(aso.idAsociado),0) from Asociado aso where aso.idSector=Sector.idSector and aso.estado=1 AND aso.idAsociado not in ( SELECT idAsociado FROM `Membresia` where estado=2 and year=YEAR(NOW()) and mes=MONTH(NOW()) and TIMESTAMPDIFF(MONTH,updated_at,CURRENT_DATE())>0 )) as asociados')
        )->where('Cuenta.serie','like','%109')
        ->where('Sector.idSector','<>','6')
        ->groupBy('Sector.idSector')
        ->groupBy('Sector.descripcion')
        ->groupBy('meta');
        
        $cobertura=Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            'Sector.descripcion',
            \DB::raw('COUNT(DISTINCT(Cuenta.idAdquiriente)) as cobertura')
        )
        ->where('Cuenta.serie','like','%109')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->where('CuentaDetalle.idConcepto','!=','69')
        ->whereBetween('Cuenta.fechaFinPago',[$since,$until])
        ->groupBy('Sector.idSector')
        ->groupBy('Sector.descripcion');
        
        $cuentasActualMes=Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            'Sector.descripcion',
            \DB::raw('
            SUM(IF(Cuenta.estado!=3 && CuentaDetalle.idConcepto!=69, Cuenta.total, 0))
            -SUM(IF(Cuenta.estado!=3 && CuentaDetalle.idConcepto=69, Cuenta.total, 0)) 
            AS emitidoMesActual'),
            \DB::raw('
            SUM(IF(Cuenta.estado=2 && CuentaDetalle.idConcepto!=69 && Cuenta.fechaFinPago>="'.$since.'" && Cuenta.fechaFinPago<="'.$until.'", Cuenta.total, 0))
            -SUM(IF(Cuenta.estado=2 && CuentaDetalle.idConcepto=69 && Cuenta.fechaFinPago>="'.$since.'" && Cuenta.fechaFinPago<="'.$until.'", Cuenta.total, 0)) 
            AS cobradoMesActual')
        )
        ->where('Cuenta.serie','like','%109')
        ->where('Cuenta.tipoDocumento','!=','3')
        ->whereBetween('Cuenta.fechaEmision',[$since,$until])
        ->groupBy('Sector.idSector')
        ->groupBy('Sector.descripcion');
        
        $afiliaciones=Cuenta::join('Asociado', 'Asociado.idAsociado', '=', 'Cuenta.idAdquiriente')
        ->join('CuentaDetalle', 'CuentaDetalle.idCuenta', '=', 'Cuenta.idCuenta')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->select(
            \DB::raw('SUM(IF(Cuenta.estado=2 && Cuenta.tipoDocumento!=3 && Cuenta.fechaFinPago>="'.$since.'" && Cuenta.fechaFinPago<="'.$until.'", Cuenta.total, 0)) AS cobrado'),
            \DB::raw('SUM(IF(Cuenta.estado!=3 && Cuenta.tipoDocumento!=3 && Cuenta.fechaEmision>="'.$since.'" && Cuenta.fechaEmision<="'.$until.'",Cuenta.total,0)) AS emitidos')
        )->where('Cuenta.serie','like','%109')
        ->where('CuentaDetalle.idConcepto','69');
        
        return response()->json([
            'cuentasActualMes' => $cuentasActualMes->get(),
            'afiliaciones' => $afiliaciones->get()[0],
            'cuentas' => $cuentas->get(),
            'cobertura' => $cobertura->get()
        ], 200);
    }
    
    public function store(Request $request)
    {
        //
    }
    
    public function show($id)
    {
        //
    }
    
    public function updatePay(Request $request, $id)
    {
        try {
            $request->validate([
                'fecha' => 'required',
                'monto' => 'required',
                'banco' => 'nullable',
                'numoperacion' => 'nullable',
                'numsofdoc' => 'nullable',
            ]);

            $Pago = Pago::find($id);
        /*
            if($request->numoperacion){
                $indicanciasOp = Pago::where('numoperacion',$request->numoperacion)->where('idPago',$Pago->idPago)->count();
                
                
            }
    
            if($request->numsofdoc){
                $indicanciasSD = Pago::where('numsofdoc',$request->numsofdoc)->where('idPago',$Pago->idPago)->count();
                if($indicanciasSD>0){
                    return response()->json([
                        'message' => 'Ya existe este número de sofydoc en otra cuenta.',
                    ],400);
                }
            }
        */
            $Cuenta = Cuenta::find($Pago->idCuenta);
    
            if(is_null($Pago)){
                return response()->json([
                    'message' => 'Pago no encontrado.'
                ], 400);
            }

            \DB::beginTransaction();
            
            $Pago->fecha =  $request->fecha;
            $Pago->monto =  $request->monto;
            $Pago->banco =  $request->banco;
            $Pago->numoperacion = $request->numoperacion; 
            $Pago->numsofdoc = $request->numsofdoc; 
            $Pago->montoPaid = $request->montoPaid; 
            $Pago->user_update = auth()->user()->idUsuario;
            $Pago->update(); 
            
            $Cuenta->fechaFinPago =  $request->fecha;
            $Cuenta->user_update = auth()->user()->idUsuario;
            $Cuenta->update(); 

            \DB::commit();

            return response()->json([
                'message' => 'Pago actualizado.',
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        //
    }

    public static function getNubefact($request) : string{
        try {
            $client = new \GuzzleHttp\Client();
            $request_param = [
                "operacion" => "consultar_comprobante",
                "tipo_de_comprobante" => $request->tipo_de_comprobante,
                "serie" => $request->serie,
                "numero" => $request->numero
            ];
            $request_data = $request_param;

            $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
                'headers' => [
                    'Content-type' => 'application/json; charset=utf-8',
                    'Authorization'     => env('APP_NUBEFACT_KEY_109')
                ],
                \GuzzleHttp\RequestOptions::JSON   => $request_data
            ]);
            return $response->getBody()->getContents();
        } catch (Exception $e) {
            \DB::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function showComprobante(Request $request)
    {
        $request->validate([
            'tipo_de_comprobante' => 'required|integer',
            'serie' => 'required|string',
            'numero' => 'required|integer',
        ]);
        $rpta = self::getNubefact($request);
        return $rpta;
    }


    public function saveComprobante(Request $request)
    {
        try {
            $request->validate([
                'tipo_de_comprobante' => 'required|integer',
                'idAsociado' => 'required|integer',
                'conafiliacion' => 'required',
                'pagado' => 'nullable',
                'docModificar' => 'nullable',
                "numoperacion" => 'nullable',
                "numsofdoc" => 'nullable',
                "montoPaid" => 'nullable',
                "importe" => 'nullable',
            ]);    

            \DB::beginTransaction();

            $items=[];
            $acumuladoFinal=0;

            /*CHECK REAL DIRECTION*/
            $asoedit= Associated::where('idAsociado',$request->idAsociado)->first();
            if($asoedit){
                $helperDoc = new Helper;
                if($asoedit->tipoAsociado===1){
                    $empedit= Empresa::where('idAsociado', '=', $asoedit->idAsociado)->first();
                    $rpta=$helperDoc::searchPremium('ruc',$empedit->ruc);
                    $empedit->direccion=$rpta->direccion_completa;
                    $empedit->save();
                }else{
                    $peredit= Persona::where('idAsociado', '=', $asoedit->idAsociado)->first();
                    if($peredit->tipoDocumento===6){
                        $rpta=$helperDoc::searchPremium('ruc',$peredit->documento);
                        $peredit->direccion=$rpta->direccion_completa;
                        $peredit->save();
                    }
                }
            }
            /*CHECK REAL DIRECTION*/

            /*SEARCH ASOCIADO*/
                $AssociatedSearched= Associated::
                select(
                    \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
                    \DB::raw('IF(Asociado.tipoAsociado=1, 6,Persona.tipoDocumento) as tipoDocumento'),
                    \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
                    \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.direccion,Persona.direccion) as direccion'),
                    'Asociado.tipoAsociado',
                    'Asociado.estado',
                    \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.actividad,Persona.actividad) as actividad'),
                    'Asociado.importeMensual',
                    'Asociado.idSector',
                    'Asociado.fechaIngreso'
                )
                ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
                ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
                ->where('Asociado.idAsociado',$request->idAsociado)->first();
            /*SEARCH ASOCIADO*/
            
            $importeMensual=$AssociatedSearched->importeMensual;

            /*SI ES NOTA DE CRÉDITO O DÉBITO*/
                if($request->tipo_de_comprobante==3){
                    $CuentaAnular = Cuenta::where('serie',$request->docModificar["serie"])->where('tipoDocumento',$request->docModificar["tipo"])->where('numero',$request->docModificar["numero"])->first();
                    if(is_null($CuentaAnular)){
                        \DB::rollback();
                        return response()->json([
                            'message' => 'Cuenta no encontrada',
                        ], 400);
                    }
                    $CuentaAnular->estado=3;
                    $CuentaAnular->fechaAnulacion=date('Y-m-d');
                    $CuentaAnular->save();

                    Pago::where('idCuenta', $CuentaAnular->idCuenta)
                    ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);
                        
                    $Membresia = Membresia::where('idCuenta', $CuentaAnular->idCuenta)
                    ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);
                }
            /*SI ES NOTA DE CRÉDITO O DÉBITO*/

            /*INFO DE CUENTA*/
                switch ($request->tipo_de_comprobante) {
                    case 1:
                        $serie = "F109";
                        break;
                    case 2:
                        $serie = "B109";
                        break;
                    default:
                        $serie = $request->docModificar["serie"];
                        break;
                }
                $numeroComprobante=Cuenta::where('serie',$serie)->where('tipoDocumento',$request->tipo_de_comprobante)->max('numero')+1;
            /*INFO DE CUENTA*/
                
            $Cuenta = new Cuenta();
            $Cuenta->fechaEmision = $request->fechaEmision;
            $Cuenta->fechaVencimiento = $request->fechaVencimiento ? $request->fechaVencimiento : date('Y-m-d');
            $Cuenta->tipoDocumento = $request->tipo_de_comprobante; 
            $Cuenta->serie = $serie; 
            $Cuenta->numero = $numeroComprobante; 
            $Cuenta->idAdquiriente = $request->idAsociado;
            $Cuenta->observaciones = $request->observacion; 
            $Cuenta->IGV = 0; 
            $Cuenta->estado = $request->tipo_de_comprobante==3 ? 2 : $request->pagado; 
            // if($request->pagado==2 || $request->tipo_de_comprobante==3){
            if($request->pagado==2){
                $Cuenta->fechaFinPago = $request->fechaPago; 
            }
            $Cuenta->user_create =  auth()->user()->idUsuario; 
            $Cuenta->user_update =  auth()->user()->idUsuario; 
            
            $Cuenta->subtotal = $acumuladoFinal;
            $Cuenta->total = $acumuladoFinal; 
            $Cuenta->save(); 
            
            if($request->conafiliacion){
                $afiliacion=[
                    "unidad_de_medida"          => "ZZ",
                    "codigo"                    => "69", 
                    "descripcion"               => "PAGO AFILIACION ASOCIADO", 
                    "cantidad"                  => 1, 
                    "valor_unitario"            => $request->importe, //
                    "precio_unitario"           => $request->importe, //
                    "subtotal"                  => $request->importe, // valor_unitario * cantidad
                    "tipo_de_igv"               => 8,
                    "igv"                       => 0,
                    "total"                     => $request->importe, //
                    "anticipo_regularizacion"   => "false"
                ];
                $items[]=$afiliacion;
                /*Cambiar importe mensual*/
                    $AsociadoEdit = Associated::find($request->idAsociado);
                    $AsociadoEdit->importeMensual=$request->newImporte;
                    $AsociadoEdit->save();
                    $importeMensual=$request->newImporte;
                /*Cambiar importe mensual*/
                $acumuladoFinal=$acumuladoFinal+$request->importe;

                //Cuenta detalle de afiliación
                    $Afiliacion = new CuentaDetalle();
                    $Afiliacion->idCuenta = $Cuenta->idCuenta; 
                    $Afiliacion->idConcepto = 69;
                    $Afiliacion->cantidad = 1;
                    $Afiliacion->tipoIGV = 8;
                    $Afiliacion->precioUnit = 70;
                    $Afiliacion->subtotal = $request->importe; 
                    $Afiliacion->total = $request->importe; 
                    $Afiliacion->user_create =  auth()->user()->idUsuario; 
                    $Afiliacion->user_update =  auth()->user()->idUsuario; 
                    $Afiliacion->save();
                //Cuenta detalle de afiliación
            }
            
            foreach ($request->items as $key => $item) {
                $importeMensual_temp=$importeMensual;
                if($item['descuento']>0){
                    $importeMensual_temp=$importeMensual-($importeMensual*$item['descuento']/100);
                }
                $total=$importeMensual_temp*$item['cantidad'];
                $acumuladoFinal=$acumuladoFinal+$total;
            
                //CUENTA DETALLE
                    $MembresiaCuenta = new CuentaDetalle();
                    $MembresiaCuenta->idCuenta = $Cuenta->idCuenta; 
                    $MembresiaCuenta->idConcepto = 70; 
                    $MembresiaCuenta->cantidad = $item['cantidad']; 
                    $MembresiaCuenta->tipoIGV = 8; 
                    $MembresiaCuenta->precioUnit = $importeMensual; 
                    $MembresiaCuenta->subtotal = $importeMensual*$item['cantidad']; 
                    $MembresiaCuenta->descuento = $item['descuento']; 
                    $MembresiaCuenta->total = $total;
                    $MembresiaCuenta->user_create =  auth()->user()->idUsuario; 
                    $MembresiaCuenta->user_update =  auth()->user()->idUsuario; 
                    $MembresiaCuenta->save(); 
                //CUENTA DETALLE

                //MEMBRESÍA
                for ($i=0; $i < $item['cantidad'] ; $i++) {
                    $add=strtotime("+". $i ." months",strtotime($item['desde']."-01"));
                    $Membresia = new Membresia();
                    $Membresia->year = date("Y",$add);

                    if($request->tipo_de_comprobante!=3){
                        $Membresia->mes = date("m",$add);
                        $Membresia->estado = $request->pagado;
                        $Membresia->cobrado = $importeMensual_temp;
                        $Membresia->pagado = $request->pagado == 2 ? $importeMensual_temp : 0;
                        $Membresia->idAsociado = $request->idAsociado; 
                        $Membresia->idCuenta = $Cuenta->idCuenta; 
                        $Membresia->idSector = $AssociatedSearched->idSector; 
                        $Membresia->user_create =  auth()->user()->idUsuario; 
                        $Membresia->user_update =  auth()->user()->idUsuario;
                        $Membresia->save();
                    }
                }

                $membresia=[
                    "unidad_de_medida"          => "ZZ",
                    "codigo"                    => "70",
                    "descripcion"               => "PAGO ORDINARIO DE ASOCIADOS " . mb_strtoupper($item['comprobanteLabel']), //
                    "cantidad"                  => $item['cantidad'], //
                    "valor_unitario"            => $importeMensual, //
                    "precio_unitario"           => $importeMensual, //
                    "descuento"                 => $importeMensual*$item['cantidad']*$item['descuento']/100, //
                    "subtotal"                  => $importeMensual_temp*$item['cantidad'], // valor_unitario * cantidad - dcto
                    "tipo_de_igv"               => 8,
                    "igv"                       => 0,
                    "total"                     => $importeMensual_temp*$item['cantidad'], //
                    "anticipo_regularizacion"   => "false"
                ];

                $items[]=$membresia;
                
            }
            
            $Cuenta->subtotal = $acumuladoFinal;
            $Cuenta->total = $acumuladoFinal; 
            $Cuenta->save(); 

            //PAGO
                if($request->pagado==2 && $request->tipo_de_comprobante!=3){
                    $Pago = new Pago();
                    $Pago->idCuenta = $Cuenta->idCuenta;
                    $Pago->monto = $acumuladoFinal; 
                    $Pago->fecha = $request->fechaPago; 
                    $Pago->banco = $request->banco; 
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
                "tipo_de_comprobante"               => $request->tipo_de_comprobante, //2=BOLETA/1=FACTURA/3=NC
                "serie"                             => $serie,
                "numero"				            => $numeroComprobante,
                "sunat_transaction"			        => "1",
                "cliente_tipo_de_documento"		    => $AssociatedSearched->tipoDocumento,
                "cliente_numero_de_documento"	    => $AssociatedSearched->documento,
                "cliente_denominacion"              => $AssociatedSearched->asociado,
                "cliente_direccion"                 => $AssociatedSearched->direccion,
                "cliente_email"                     => $request->correo=="" ? "" : $request->correo,
                "cliente_email_1"                   => 'marthasifuentes@cclam.org.pe',
                "fecha_de_emision"                  => $request->fechaEmision, //date('d-m-Y'),
                "fecha_de_vencimiento"              => $request->fechaVencimiento ? $request->fechaVencimiento : date('Y-m-d'), //date('d-m-Y'),
                "moneda"                            => "1",
                "porcentaje_de_igv"                 => "18.00",
                "total_gravada"                     => 0,
                "total_igv"                         => 0,
                "total_exonerada"                   => $acumuladoFinal,
                "total"                             => $acumuladoFinal,
                "detraccion"                        => "false",
                "enviar_automaticamente_a_la_sunat" => "true",
                "enviar_automaticamente_al_cliente" => $request->correo=="" ? "false" : "true",
                "observaciones" => $request->observacion ? $request->observacion : '',
                "documento_que_se_modifica_tipo"    => $request->docModificar["tipo"] ? $request->docModificar["tipo"] : "",
                "documento_que_se_modifica_serie"   => $request->docModificar["serie"] ? $request->docModificar["serie"] : "",
                "documento_que_se_modifica_numero"  => $request->docModificar["numero"] ? $request->docModificar["numero"] : "",
                "tipo_de_nota_de_credito"           => $request->docModificar["tipo"] ? 1 : "",
                "items" => $items,
            ];
            
            if( $request->tipo_de_comprobante!=3){
                if($request->pagado!=2){
                    $venta_al_credito[]=[
                        "cuota" => 1,
                        "fecha_de_pago" => $request->fechaVencimiento,
                        "importe" => $acumuladoFinal,
                    ];
                    $comprobante_param["medio_de_pago"]="CREDITO";
                    $comprobante_param["condiciones_de_pago"]="CRÉDITO AL ".$request->fechaVencimiento;
                    $comprobante_param["venta_al_credito"]=$venta_al_credito;
                }else{
                    $comprobante_param["medio_de_pago"]="CONTADO";
                    $comprobante_param["condiciones_de_pago"]="CONTADO";
                }
            }

            /*Nubefact*/
            
                try {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
                        'headers' => [
                            'Content-type' => 'application/json; charset=utf-8',
                            'Authorization'     => env('APP_NUBEFACT_KEY_109')
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
                
            /*Nubefact*/

            \DB::commit();
            
            //MEMBRESÍA

            $helper = new Helper;
            $helper::checkPayInfo($request->numoperacion,$request->numsofdoc);
            
            return response()->json([
                'message' => "Cuenta registrada!"
            ], 200);
            
        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function annulmentComprobante(Request $request)
    {
        try {
        $request->validate([
            'idCuenta' => 'required|integer',
        ]);    
        
        \DB::beginTransaction();
            
        $Cuenta = Cuenta::find($request->idCuenta);
        $Cuenta->estado = 3; 
        $Cuenta->fechaAnulacion = date('Y-m-d'); 
        $Cuenta->user_update = auth()->user()->idUsuario;
        $Cuenta->update();
            
        Pago::where('idCuenta', $request->idCuenta)
        ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);
            
        $Membresia = Membresia::where('idCuenta', $request->idCuenta)
        ->update(['estado' => 0,'user_update'=> auth()->user()->idUsuario]);

        $anulacion_param = [
            "operacion"				    => "generar_anulacion",
            "tipo_de_comprobante"       => $Cuenta->tipoDocumento, //2=BOLETA/1=FACTURA
            "serie"                     => $Cuenta->serie, //
            "numero"			       	=> $Cuenta->numero, //
            "motivo"			        => "error de generacion de comprobante",
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', env('APP_NUBEFACT_ROUTE'), [
            'headers' => [
                'Content-type' => 'application/json; charset=utf-8',
                'Authorization'     => env('APP_NUBEFACT_KEY_109')
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

    public function payComprobante(Request $request)
    {
        try {
        $request->validate([
            'idCuenta' => 'required|integer',
            'monto' => 'required',
            'fechaPago' => 'required',
            "banco" => 'integer',
            "numoperacion" => 'nullable',
            "numsofdoc" => 'nullable',
        ]);    
        
        \DB::beginTransaction();
            
        $Cuenta = Cuenta::find($request->idCuenta);
            
        $pagadoPagos = Pago::select(\DB::raw('IFNULL(SUM(monto),0) as pagado'))->where('idCuenta',$request->idCuenta)->first()->pagado;
        if($Cuenta->total == $pagadoPagos+$request->monto){
            $Cuenta->estado = 2; 
            $Cuenta->fechaFinPago = $request->fechaPago; 
        }
        
        $Cuenta->user_update = auth()->user()->idUsuario;
        $Cuenta->update();

        $Pago = new Pago();
        $Pago->idCuenta = $Cuenta->idCuenta;
        $Pago->monto = $request->monto; 
        $Pago->fecha = $request->fechaPago; 
        $Pago->banco = $request->banco; 
        $Pago->numoperacion = $request->numoperacion; 
        $Pago->numsofdoc = $request->numsofdoc; 
        $Pago->montoPaid = $request->montoPaid; 
        $Pago->user_create =  auth()->user()->idUsuario; 
        $Pago->user_update =  auth()->user()->idUsuario;
        $Pago->save(); 

        $remaining =  $request->monto;
        $AfiliacionPendiente = CuentaDetalle::where('idCuenta',$Cuenta->idCuenta)->where('idConcepto',69)->first();
        if($AfiliacionPendiente){
            $remaining =  $remaining-$AfiliacionPendiente->total;
        }

        while ($remaining > 0) {
            $Membresia = Membresia::where('idCuenta', $request->idCuenta)->where('estado', 1)->first();
            if($Membresia==null && $remaining>0){
                return response()->json([
                    'message' => 'Monto excedente al cobrado.',
                ],500);
            }
            if(($Membresia->cobrado-$Membresia->pagado)>=$remaining){
                $Membresia->pagado=$Membresia->pagado+$remaining;
                $remaining=0;
            }else{
                $remaining=$remaining-($Membresia->cobrado-$Membresia->pagado);
                $Membresia->pagado=$Membresia->cobrado;
            }            
            $Membresia->estado = $Membresia->pagado == $Membresia->cobrado ? 2 : 1; 
            $Membresia->user_update =  auth()->user()->idUsuario; 
            $Membresia->update();
        }

        \DB::commit();

        $helper = new Helper;
        $helper::checkPayInfo($request->numoperacion,$request->numsofdoc);

        return response()->json([
            'message' => 'Pago registrado.',
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

        $lineGraphCobrado = Cuenta::join('Pago', 'Pago.idCuenta', 'Cuenta.idCuenta')
        ->select(
            \DB::raw('MONTH(fecha) as mes'),
            \DB::raw('ROUND(SUM(monto),2) as monto')
        )->where('Cuenta.serie','like','%109')
        ->whereIn('Cuenta.tipoDocumento', [1, 2])
        ->whereYear('fecha',date('Y'))
        ->where('Pago.estado',1)
        ->groupBy('mes');

        $lineGraphEmitido = Cuenta::select(
            \DB::raw('MONTH(fechaEmision) as mes'),
            \DB::raw('SUM(total) as monto')
        )
        ->where('Cuenta.serie','like','%109')
        ->where('Cuenta.estado','!=','3')
        ->whereIn('Cuenta.tipoDocumento', [1, 2])
        ->whereYear('fechaEmision',date('Y'))
        ->groupBy('mes');

        $bars = Cuenta::join('Pago as p', 'p.idCuenta', 'Cuenta.idCuenta')
        ->join('Asociado as a', 'a.idAsociado', 'Cuenta.idAdquiriente')
        ->join('ComiteGremial as cg', 'cg.idComite', 'a.idComiteGremial')
        ->select(
            \DB::raw('cg.nombre as comite'),
            \DB::raw('ROUND(sum(p.monto),2) as monto')
        )->where('Cuenta.serie','like','%109')
        ->whereIn('Cuenta.tipoDocumento', [1, 2])
        ->where('p.estado','1')
        ->whereYear('fecha',date('Y'))
        ->groupBy('comite')
        ->orderBy('monto','desc');
        
        if($request->mes){
            $bars->whereMonth('fecha',$request->mes +1);
        }else{
            $bars->whereMonth('fecha',date('m'));
        }

        return response()->json([
            'lineEmitido' =>$lineGraphEmitido->orderBy('mes')->get(),
            'lineCobrado' =>$lineGraphCobrado->orderBy('mes')->get(),
            'bars' => $bars->get(),
        ], 200);
    }

    function probandoPay(){
        $helper = new Helper;
        $helper::checkPayInfo(5457,null);
    }

    public function restoreToPending($idCuenta){
        $CuentaAnular = Cuenta::find($idCuenta);
        if(is_null($CuentaAnular)){
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 400);
        }

        $CuentaAnular->estado=1;
        $CuentaAnular->fechaFinPago=null;
        $CuentaAnular->user_update=auth()->user()->idUsuario;
        $CuentaAnular->save();

        Pago::where('idCuenta', $CuentaAnular->idCuenta)->delete();
            
        $Membresia = Membresia::where('idCuenta', $CuentaAnular->idCuenta)
        ->update(['estado' => 1,'pagado' => 0,'user_update'=> auth()->user()->idUsuario]);

        return response()->json([
            'message' => 'Cuenta cambiada a pendiente.',
        ], 200);
    }

    public function getNumComprobante(Request $request){
        $numeroComprobante=Cuenta::where('serie','like','%'.$request->serie)->where('tipoDocumento',$request->typedoc)->max('numero')+1;
        return $numeroComprobante;
    }
}