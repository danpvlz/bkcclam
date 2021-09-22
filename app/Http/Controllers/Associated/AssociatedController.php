<?php

namespace App\Http\Controllers\Associated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Associated\Associated as AssociatedResourse;
use App\Models\Associated\Associated;
use App\Models\Associated\Persona;
use App\Models\Associated\Empresa;
use App\Models\Associated\Contacto;
use App\Models\Associated\Promotor;
use App\Models\Associated\ComiteGremial;
use App\Models\Associated\Sector;
use App\Models\Colaborador;
use App\Models\Associated\HistoriaAsociado;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssociatedExport;
use App\Exports\CoberturaExport;

use App\Mail\Afiliacion;
use App\Mail\Welcome;

use App\Helpers\Helper;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AssociatedController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'idAsociado' => 'integer',
            'state' => 'integer',
            'debCollector' => 'integer',    
            'comite' => 'integer',
            'promotor' => 'integer',
            'month' => 'string'
        ]);   
        return Excel::download(
            new AssociatedExport(
                $request->idAsociado,
                $request->state,
                $request->debCollector,
                $request->comite,
                $request->promotor,
                $request->month
            ), 'associateds.xlsx');
    }

    public function exportCobertura(Request $request)
    {
        $request->validate([
            'debCollector' => 'integer',
        ]);   
        return Excel::download(
            new CoberturaExport(
                $request->debCollector,
            ), 'Meta_Cobertura.xlsx');
    }
    
    public function index()
    {
        $first= Associated::
        select(
            'Empresa.razonSocial as asociado', 
            'Empresa.ruc as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Empresa.actividad',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.descripcion',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso',
            'Asociado.codigo'
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial');

        $final= Associated::
        select(
            'Persona.nombresCompletos as asociado', 
            'Persona.documento as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Persona.actividad',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.descripcion',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso',
            'Asociado.codigo'
        )
        ->join('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->union($first);

        return AssociatedResourse::collection(
            $final
            ->orderBy('fechaIngreso', 'desc')
            ->paginate(10));
    }
    
    public function showIndicators(Request $request)
    {
        $request->validate([
            'search' => 'string',
            'state' => 'integer',
            'debCollector' => 'string',
        ]);
        
        $associateds=Associated::whereIn('estado',[1,3])->count();
        $afiliations=Associated::whereYear('fechaIngreso', '=', date('Y'))->whereMonth('fechaIngreso', '=', date('m'))->where('estado',1)->where('idPromotor','!=',94)->count();
        $retreatsActualMonth=Associated::where('estado',0)->whereYear('fechaRetiro', '=', date('Y'))->whereMonth('fechaRetiro', '=', date('m'))->count();
        $retreats=Associated::where('estado',0)->count();
        $inProcess=Associated::where('estado',2)->count();

        return response()->json([
            'associateds' => $associateds,
            'afiliations' => $afiliations,
            'retreatsActualMonth' => $retreatsActualMonth,
            'retreats' => $retreats,
            'inProcess' => $inProcess,
            'associateds' => $associateds,
        ], 200);
    }

    public function listAssociated(Request $request)
    {
        $request->validate([
            'idAsociado' => 'integer',
            'state' => 'integer',
            'debCollector' => 'integer',
            'month' => 'string',
            'promotor' => 'integer',
            'comite' => 'integer',
        ]);

        $first= Associated::
        select(
            'Asociado.idAsociado', 
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Empresa.actividad',
            'Empresa.actividadSecundaria',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.descripcion as cobrador',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso',
            'Asociado.fechaRetiro',
            'Promotor.nombresCompletos as promotor',
            'Asociado.codigo',
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.fundacion,Persona.fechaNacimiento) as onomastico'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.correos,Persona.correos) as correos'),
            \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.telefonos,Persona.telefonos) as telefonos')
        )
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado');

        if($request->idAsociado){
            $first->where('Asociado.idAsociado','=',$request->idAsociado);
        }

        if($request->state){
            $first->where('Asociado.estado',"=",$request->state == 4 ? 0 : $request->state);
        }

        if($request->debCollector){
            $first->where('Sector.idSector',$request->debCollector);
        }

        if($request->promotor){
            $first->where('Asociado.idPromotor',$request->promotor);
        }

        if($request->comite){
            $first->where('ComiteGremial.idComite',$request->comite);
        }

        if($request->month){
            $first->whereBetween('Asociado.fechaIngreso',[$request->month."-01",$request->month.'-31']);
        }

        $first
        ->orderBy('idAsociado', 'desc');            

        return AssociatedResourse::collection(
            $first->paginate(10)
        );
    }

    public function listAfiliations(Request $request)
    {
        $request->validate([
            'idAsociado' => 'integer',
            'state' => 'integer',
            'debCollector' => 'integer',
        ]);

        $first= Associated::
        select(
            'Asociado.idAsociado', 
            \DB::raw('IFNULL(Empresa.ruc,Persona.documento) as documento'),
            \DB::raw('IFNULL(Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Empresa.actividad',
            'Empresa.actividadSecundaria',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.descripcion as cobrador',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso',
            'Promotor.nombresCompletos as promotor',
            'Asociado.codigo'
        )
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor')
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado');
        
        if($request->idAsociado){
            $first->where('Asociado.idAsociado','=',$request->idAsociado);
        }

        if($request->state){
            $first->where('Asociado.estado',"=",$request->state == 4 ? 0 : $request->state);
        }

        if($request->debCollector){
            $first->where('Sector.idSector',$request->debCollector);
        }

        $first
        ->orderBy('idAsociado', 'desc');            

        return AssociatedResourse::collection(
            $first->paginate(10)
        );
    }

    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        $first= Associated::
        select(
            \DB::raw('CONCAT(Empresa.razonSocial, " [",Empresa.ruc, "]") as label'), 'Asociado.idAsociado as value'
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->where('Empresa.razonSocial','like', '%'.$request->search."%")
        ->orWhere('Empresa.ruc','like', '%'.$request->search."%");

        $final= Associated::
        select(
            \DB::raw('CONCAT(Persona.nombresCompletos, " [", Persona.documento, "]") as label'), 'Asociado.idAsociado as value'
        )
        ->join('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->where('Persona.nombresCompletos','like', '%'.$request->search."%")
        ->orWhere('Persona.documento','like', '%'.$request->search."%")
        ->union($first);

        return $final->get();
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                //Asociado
                    'comitegremial' => 'required|integer',
                    'tipoasociado' => 'required|integer',
                    'direccionsocial' => 'required|max:250',
                    'idPromotor' => 'nullable',
                    'promotornombre' => 'nullable|string|max:250',
                //Asociado

                //Empresa
                    'ruc' => 'required_if:tipoasociado,==,1|max:11|min:11',
                    'razonsocial' => 'required_if:tipoasociado,==,1|string',
                    'direccionfiscal' => 'required_if:tipoasociado,==,1|max:250',
                    'fundacion' => 'nullable|date',
                    'actividad' => 'nullable|max:255',
                    'telefono_asociado' => 'nullable|max:255',
                    'correo_asociado' => 'nullable|max:255',

                    //Representante
                        'tipodocumento_representante' => 'required_if:tipoasociado,==,1',
                        'documento_representante' => 'required_if:tipoasociado,==,1',
                        'nombres_representante' => 'required_if:tipoasociado,==,1|string',
                        'paterno_representante' => 'required_if:tipoasociado,==,1|string',
                        'materno_representante' => 'required_if:tipoasociado,==,1|string',
                        'fechanacimiento_representante' => 'nullable|date',
                        'cargo_representante' => 'required_if:tipoasociado,==,1|string',
                        'telefono_representante' => 'nullable|string',
                        'correo_representante' => 'nullable|string',
                    //Representante

                    //Contacto adicional
                        'tipodocumento_adicional' => 'nullable',
                        'documento_adicional' => 'nullable',
                        'nombres_adicional' => 'nullable|string',
                        'paterno_adicional' => 'nullable|string',
                        'materno_adicional' => 'nullable|string',
                        'fechanacimiento_adicional' => 'nullable|date',
                        'cargo_adicional' => 'nullable|string',
                        'telefono_adicional' => 'nullable|string',
                        'correo_adicional' => 'nullable|string',
                    //Contacto adicional
                //Empresa

                //Persona
                    'tipodocumento_persona' => 'required_if:tipoasociado,==,2',
                    'documento_persona' => 'required_if:tipoasociado,==,2',
                    'nombres_persona' => 'required_if:tipoasociado,==,2|string',
                    'paterno_persona' => 'required_if:tipoasociado,==,2|string',
                    'materno_persona' => 'required_if:tipoasociado,==,2|string',
                    'sexo' => 'required_if:tipoasociado,==,2',
                    'fechanacimiento_persona' => 'nullable|date',
                    'direccionfiscal_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'actividad_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'telefono_persona' => 'nullable|max:250',
                    'correo_persona' => 'nullable|max:250'
                //Persona
            ]);

            $afiliacion = new \stdClass();

            \DB::beginTransaction();

            //Asociado
                $Asociado = new Associated();
                $Asociado->codigo = $request->tipoasociado == 1 ? $request->ruc : $request->documento_persona;
                $Asociado->documento = $Asociado->codigo;
                $Asociado->fechaIngreso = date("Y-m-d");
                $Asociado->importeMensual = 75;
                $Asociado->direccionSocial = strtoupper($request->direccionsocial);
                $Asociado->tipoAsociado = $request->tipoasociado;
                $Asociado->estado = 2;
                if($request->idPromotor){
                    $Promotor = Promotor::find($request->idPromotor);
                    if(is_null($Promotor)){
                        $Promotor = new Promotor();
                        $Promotor->nombresCompletos = $request->promotornombre;
                        $Promotor->save();
                    }
                    $Asociado->idPromotor = $request->idPromotor;
                    $afiliacion->promotor = $Promotor->nombresCompletos;
                }else{
                    $Asociado->idPromotor = 99;
                    $afiliacion->promotor = 'NO PRESENTA';
                }
                $Asociado->idComiteGremial = $request->comitegremial;
                $Asociado->idCategoria = 18;
                $Asociado->idSector = 4;
                $Asociado->user_create =  0; 
                $Asociado->user_update =  0; 
                $Asociado->save();
            //Asociado

            if($request->tipoasociado==1){
                $Empresa = new Empresa();
                $Empresa->ruc = $request->ruc;
                $Empresa->razonSocial = strtoupper($request->razonsocial);
                $Empresa->direccion = strtoupper($request->direccionfiscal);
                $Empresa->fundacion = $request->fundacion;
                $Empresa->actividad = $request->actividad;
                $Empresa->correos = $request->correo_asociado;
                $Empresa->telefonos = $request->telefono_asociado;
                $Empresa->idAsociado = $Asociado->idAsociado;
                
                $afiliacion->afiliado = strtoupper($request->razonsocial);
                $afiliacion->documento = $request->ruc;

                $Representante = new Contacto();
                $Representante->nombres = strtoupper($request->nombres_representante);
                $Representante->apellidoPaterno = strtoupper($request->paterno_representante);
                $Representante->apellidoMaterno = strtoupper($request->materno_representante);
                $Representante->nombreCompleto = strtoupper($request->nombres_representante . " " . $request->paterno_representante . " " . $request->materno_representante);
                $Representante->fechaNacimiento = $request->fechanacimiento_representante;
                $Representante->tipoDoc = $request->tipodocumento_representante;
                $Representante->documento = $request->documento_representante;
                $Representante->cargo = $request->cargo_representante;
                $Representante->email = $request->correo_representante;
                $Representante->telefonos = $request->telefono_representante;
                $Representante->save();
                
                $Empresa->idRepresentante = $Representante->idContacto;

                if($request->documento_adicional){
                    $ContactoAdicional = new Contacto();
                    $ContactoAdicional->nombres = strtoupper($request->nombres_adicional);
                    $ContactoAdicional->apellidoPaterno = strtoupper($request->paterno_adicional);
                    $ContactoAdicional->apellidoMaterno = strtoupper($request->materno_adicional);
                    $ContactoAdicional->nombreCompleto = strtoupper($request->nombres_adicional . " " . $request->paterno_adicional . " " . $request->materno_adicional);
                    $ContactoAdicional->fechaNacimiento = $request->fechanacimiento_adicional;
                    $ContactoAdicional->tipoDoc = $request->tipodocumento_adicional;
                    $ContactoAdicional->documento = $request->documento_adicional;
                    $ContactoAdicional->cargo = $request->cargo_adicional;
                    $ContactoAdicional->email = $request->correo_adicional;
                    $ContactoAdicional->telefonos = $request->telefono_adicional;
                    $ContactoAdicional->save();
                    $Empresa->idContactoAdicional = $ContactoAdicional->idContacto;
                }
                $Empresa->save();
            }else{
                $Persona = new Persona();
                $Persona->tipoDocumento = $request->tipodocumento_persona;
                $Persona->documento = $request->documento_persona;
                $Persona->nombresCompletos = strtoupper($request->nombres_persona." ".$request->paterno_persona." ".$request->materno_persona);
                $Persona->nombres = strtoupper($request->nombres_persona);
                $Persona->apellidoPaterno = strtoupper($request->paterno_persona);
                $Persona->apellidoMaterno = strtoupper($request->materno_persona);
                $Persona->sexo = $request->sexo;
                $Persona->fechaNacimiento = $request->fechanacimiento_persona;
                $Persona->direccion = $request->direccionfiscal_persona;
                $Persona->actividad = $request->actividad_persona;
                $Persona->correos = $request->correo_persona;
                $Persona->telefonos = $request->telefono_persona;
                $Persona->idAsociado = $Asociado->idAsociado;
                $Persona->save();

                $afiliacion->afiliado = $Persona->nombresCompletos;
                $afiliacion->documento = $Persona->documento;
            }

            \DB::commit();
            $emails = ['secretariagerencia@cclam.org.pe'];
            
            foreach ($emails as $email) {
                $afiliacion->receiver = $email;
                Mail::to($email)->send(new Afiliacion($afiliacion));
            }

            $this->notify($Asociado->idAsociado,'Se ha registrado una nueva afiliación.','success',null,2,7);
            
            $this->notify($Asociado->idAsociado,'Se ha registrado una nueva afiliación.','success',null,2,3);
            $this->notify($Asociado->idAsociado,'Se ha registrado una nueva afiliación.','success',null,2,16);
            $this->notify($Asociado->idAsociado,'Se ha registrado una nueva afiliación.','success',null,2,17);

            $this->saveUpdates( $Asociado->idAsociado, 'Nuevo asociado', 'Afiliación' );

            return response()->json([
                'message' => 'Afiliación registrada.',
            ], 200);
    
        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function probando(Request $request){
                
        $welcome = new \stdClass();
            
        $emails = ['secretariagerencia@cclam.org.pe'];
            
        foreach ($emails as $email) {
            $welcome->receiver = $email;
            Mail::to($email)->send(new Welcome($welcome));
        }

        return response()->json([
            'message' => 'Probando bien.'
        ], 200);

    }

    public function edit($id)
    {
        $Associated = Associated::find($id);
        $Promotor = Promotor::where('idPromotor','=',$Associated->idPromotor)->first();
        $ComiteGremial = ComiteGremial::where('idComite','=',$Associated->idComiteGremial)->first();
        $Sector = Sector::where('idSector','=',$Associated->idSector)->first();
        $Persona=null;
        $Empresa=null;
        $Representante=null;
        $Adicional=null;
        if($Associated->tipoAsociado == 1){
            $Empresa = Empresa::where('idAsociado','=',$Associated->idAsociado)->first();
            $Representante = Contacto::where('idContacto','=',$Empresa->idRepresentante)->first();
            $Adicional = Contacto::where('idContacto','=',$Empresa->idContactoAdicional)->first();
        }else{
            $Persona = Persona::where('idAsociado','=',$Associated->idAsociado)->first();
        }
        
        return response()->json([
            'asociado' => $Associated,
            'promotor' => $Promotor,
            'comite' => $ComiteGremial,
            'sector' => $Sector,
            'persona' => $Persona,
            'empresa' => $Empresa,
            'representante' => $Representante,
            'adicional' => $Adicional,
        ], 200);
    }
    
    public function show($id)
    {
        $first= Associated::
        select(
            'Empresa.razonSocial as asociado', 
            \DB::raw('6 as tipoDocumento'),
            'Empresa.ruc as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Empresa.actividad',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.codigo',
            'Sector.descripcion',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso'
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->where('Asociado.idAsociado',$id);

        $final= Associated::
        select(
            'Persona.nombresCompletos as asociado', 
            'Persona.tipoDocumento',
            'Persona.documento as documento',
            'Asociado.tipoAsociado',
            'Asociado.estado',
            'Persona.actividad',
            'ComiteGremial.nombre as comitegremial',
            'Asociado.importeMensual',
            'Sector.codigo',
            'Sector.descripcion',
            'Asociado.direccionSocial',
            'Asociado.fechaIngreso'
        )
        ->join('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
        ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
        ->where('Asociado.idAsociado',$id)
        ->union($first);

        return 
            $final
            ->get();
    }
    
    public function askForUpdate(Request $request, $id)
    {
        try {
            $request->validate([
                //Asociado
                    'comitegremial' => 'required|integer',
                    'tipoasociado' => 'required|integer',
                    'direccionsocial' => 'required|max:250',
                    'idPromotor' => 'nullable',
                    'promotornombre' => 'nullable|string|max:250',
                    'importemensual' => 'required',
                //Asociado

                //Empresa
                    'ruc' => 'required_if:tipoasociado,==,1|max:11|min:11',
                    'razonsocial' => 'required_if:tipoasociado,==,1|string',
                    'direccionfiscal' => 'required_if:tipoasociado,==,1|max:250',
                    'fundacion' => 'nullable|date',
                    'actividad' => 'nullable|max:255',
                    'telefono_asociado' => 'nullable|max:255',
                    'correo_asociado' => 'nullable|max:255',

                    //Representante
                        'tipodocumento_representante' => 'required_if:tipoasociado,==,1',
                        'documento_representante' => 'required_if:tipoasociado,==,1',
                        'nombres_representante' => 'required_if:tipoasociado,==,1|string',
                        'paterno_representante' => 'required_if:tipoasociado,==,1|string',
                        'materno_representante' => 'required_if:tipoasociado,==,1|string',
                        'fechanacimiento_representante' => 'nullable|date',
                        'cargo_representante' => 'required_if:tipoasociado,==,1|string',
                        'telefono_representante' => 'nullable|string',
                        'correo_representante' => 'nullable|string',
                    //Representante

                    //Contacto adicional
                        'tipodocumento_adicional' => 'nullable',
                        'documento_adicional' => 'nullable',
                        'nombres_adicional' => 'nullable|string',
                        'paterno_adicional' => 'nullable|string',
                        'materno_adicional' => 'nullable|string',
                        'fechanacimiento_adicional' => 'nullable|date',
                        'cargo_adicional' => 'nullable|string',
                        'telefono_adicional' => 'nullable|string',
                        'correo_adicional' => 'nullable|string',
                    //Contacto adicional
                //Empresa

                //Persona
                    'tipodocumento_persona' => 'required_if:tipoasociado,==,2',
                    'documento_persona' => 'required_if:tipoasociado,==,2',
                    'nombres_persona' => 'required_if:tipoasociado,==,2|string',
                    'paterno_persona' => 'required_if:tipoasociado,==,2|string',
                    'materno_persona' => 'required_if:tipoasociado,==,2|string',
                    'sexo' => 'required_if:tipoasociado,==,2',
                    'fechanacimiento_persona' => 'nullable|date',
                    'direccionfiscal_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'actividad_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'telefono_persona' => 'nullable|string|max:250',
                    'correo_persona' => 'nullable|string|max:250'
                //Persona
            ]);
            
            DB::beginTransaction();

            $changes = [];
            //Asociado
                $Asociado = Associated::find($id);
                $Asociado->importeMensual = $request->importemensual;
                $Asociado->direccionSocial = strtoupper($request->direccionsocial);
                $Asociado->tipoAsociado = $request->tipoasociado;
                if($request->idPromotor){
                    $Promotor = Promotor::find($request->idPromotor);
                    if(is_null($Promotor)){
                        $Promotor = new Promotor();
                        $Promotor->nombresCompletos = $request->promotornombre;
                        $Promotor->save();
                    }
                    $Asociado->idPromotor = $request->idPromotor;
                }else{
                    $Asociado->idPromotor = $Asociado->idPromotor;
                }
                $Asociado->idComiteGremial = $request->comitegremial;
                $Asociado->idSector = $request->idSector;
                if($Asociado->isDirty()){
                    $changes['Asociado']=$Asociado->getDirty();
                }
            //Asociado

            $asociado = "";
            if($request->tipoasociado==1){
                $Empresa = Empresa::where('idAsociado',$id)->first();
                $asociado=$Empresa->razonSocial.' - '.$request->ruc;

                $Empresa->ruc = $request->ruc;
                $Empresa->razonSocial = strtoupper($request->razonsocial);
                $Empresa->direccion = strtoupper($request->direccionfiscal);
                $Empresa->fundacion = $request->fundacion;
                $Empresa->actividad = $request->actividad;
                $Empresa->actividadSecundaria = $request->actividad_secundaria;
                $Empresa->correos = $request->correo_asociado;
                $Empresa->telefonos = $request->telefono_asociado;

                $Representante = Contacto::where('idContacto',$Empresa->idRepresentante)->first();
                $Representante->nombres = strtoupper($request->nombres_representante);
                $Representante->apellidoPaterno = strtoupper($request->paterno_representante);
                $Representante->apellidoMaterno = strtoupper($request->materno_representante);
                $Representante->nombreCompleto = strtoupper($request->nombres_representante . " " . $request->paterno_representante . " " . $request->materno_representante);
                $Representante->fechaNacimiento = $request->fechanacimiento_representante;
                $Representante->tipoDoc = $request->tipodocumento_representante;
                $Representante->documento = $request->documento_representante;
                $Representante->cargo = $request->cargo_representante;
                $Representante->email = $request->correo_representante;
                $Representante->telefonos = $request->telefono_representante;
                if($Representante->isDirty()){
                    $changes['Representante']=$Representante->getDirty();
                }

                if($request->documento_adicional){
                    $checkIfExistsAdicional = Contacto::where('idContacto',$Empresa->idContactoAdicional)->first();
                    if($checkIfExistsAdicional){
                        $ContactoAdicional = $checkIfExistsAdicional;
                    }else{
                        $ContactoAdicional = new Contacto();
                    }
                    $ContactoAdicional->nombres = $request->nombres_adicional;
                    $ContactoAdicional->apellidoPaterno = $request->paterno_adicional;
                    $ContactoAdicional->apellidoMaterno = $request->materno_adicional;
                    $ContactoAdicional->nombreCompleto = $request->nombres_adicional . " " . $request->paterno_adicional . " " . $request->materno_adicional;
                    $ContactoAdicional->fechaNacimiento = $request->fechanacimiento_adicional;
                    $ContactoAdicional->tipoDoc = $request->tipodocumento_adicional;
                    $ContactoAdicional->documento = $request->documento_adicional;
                    $ContactoAdicional->cargo = $request->cargo_adicional;
                    $ContactoAdicional->email = $request->correo_adicional;
                    $ContactoAdicional->telefonos = $request->telefono_adicional;
                    if($ContactoAdicional->isDirty()){
                        $changes['ContactoAdicional']=$ContactoAdicional->getDirty();
                    }
                }
                if($Empresa->isDirty()){
                    $changes['Empresa']=$Empresa->getDirty();
                }
            }else{
                $Persona = Persona::where('idAsociado',$Asociado->idAsociado)->first();
                $asociado=$Persona->nombresCompletos.' - '.$request->documento_persona;

                $Persona->tipoDocumento = $request->tipodocumento_persona;
                $Persona->documento = $request->documento_persona;
                $Persona->nombresCompletos = $request->nombres_persona." ".$request->paterno_persona." ".$request->materno_persona;
                $Persona->nombres = $request->nombres_persona;
                $Persona->apellidoPaterno = $request->paterno_persona;
                $Persona->apellidoMaterno = $request->materno_persona;
                $Persona->sexo = $request->sexo;
                $Persona->fechaNacimiento = $request->fechanacimiento_persona;
                $Persona->direccion = $request->direccionfiscal_persona;
                $Persona->actividad = $request->actividad_persona;
                $Persona->actividadSecundaria = $request->actividad_secundaria_persona;
                $Persona->correos = $request->correo_persona;
                $Persona->telefonos = $request->telefono_persona;
                if($Persona->isDirty()){
                    $changes['Persona']=$Persona->getDirty();
                }
            }

            if(empty((array) $changes)){
                return response()->json([
                    'message' => 'No ha realizado ningún cambio',
                ], 200);
            }else{
                $detail = new \stdClass();
                $detail->id=$id;
                $detail->asociado=$asociado;
                $detail->changes=(array) $changes;
                $detail->request=json_decode($request->getContent());
                $this->notify(null,' solicita un cambio en los datos de asociado.','primary',$detail,3,17);
                
                return response()->json([
                    'message' => 'Solicitud de actualización enviada',
                ], 200);
            }
    
        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                //Asociado
                    'comitegremial' => 'required|integer',
                    'tipoasociado' => 'required|integer',
                    'direccionsocial' => 'required|max:250',
                    'idPromotor' => 'nullable',
                    'promotornombre' => 'nullable|string|max:250',
                    'importemensual' => 'required',
                //Asociado

                //Empresa
                    'ruc' => 'required_if:tipoasociado,==,1|max:11|min:11',
                    'razonsocial' => 'required_if:tipoasociado,==,1|string',
                    'direccionfiscal' => 'required_if:tipoasociado,==,1|max:250',
                    'fundacion' => 'nullable|date',
                    'actividad' => 'nullable|max:255',
                    'telefono_asociado' => 'nullable|max:255',
                    'correo_asociado' => 'nullable|max:255',

                    //Representante
                        'tipodocumento_representante' => 'required_if:tipoasociado,==,1',
                        'documento_representante' => 'required_if:tipoasociado,==,1',
                        'nombres_representante' => 'required_if:tipoasociado,==,1|string',
                        'paterno_representante' => 'required_if:tipoasociado,==,1|string',
                        'materno_representante' => 'required_if:tipoasociado,==,1|string',
                        'fechanacimiento_representante' => 'nullable|date',
                        'cargo_representante' => 'required_if:tipoasociado,==,1|string',
                        'telefono_representante' => 'nullable|string',
                        'correo_representante' => 'nullable|string',
                    //Representante

                    //Contacto adicional
                        'tipodocumento_adicional' => 'nullable',
                        'documento_adicional' => 'nullable',
                        'nombres_adicional' => 'nullable|string',
                        'paterno_adicional' => 'nullable|string',
                        'materno_adicional' => 'nullable|string',
                        'fechanacimiento_adicional' => 'nullable|date',
                        'cargo_adicional' => 'nullable|string',
                        'telefono_adicional' => 'nullable|string',
                        'correo_adicional' => 'nullable|string',
                    //Contacto adicional
                //Empresa

                //Persona
                    'tipodocumento_persona' => 'required_if:tipoasociado,==,2',
                    'documento_persona' => 'required_if:tipoasociado,==,2',
                    'nombres_persona' => 'required_if:tipoasociado,==,2|string',
                    'paterno_persona' => 'required_if:tipoasociado,==,2|string',
                    'materno_persona' => 'required_if:tipoasociado,==,2|string',
                    'sexo' => 'required_if:tipoasociado,==,2',
                    'fechanacimiento_persona' => 'nullable|date',
                    'direccionfiscal_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'actividad_persona' => 'required_if:tipoasociado,==,2|string|max:250',
                    'telefono_persona' => 'nullable|string|max:250',
                    'correo_persona' => 'nullable|string|max:250'
                //Persona
            ]);
        
            \DB::beginTransaction();

            //Asociado
                $Asociado = Associated::find($id);
                $Asociado->importeMensual = $request->importemensual;
                $Asociado->direccionSocial = $request->direccionsocial;
                $Asociado->tipoAsociado = $request->tipoasociado;
                if($request->idPromotor){
                    $Promotor = Promotor::find($request->idPromotor);
                    if(is_null($Promotor)){
                        $Promotor = new Promotor();
                        $Promotor->nombresCompletos = $request->promotornombre;
                        $Promotor->save();
                    }
                    $Asociado->idPromotor = $request->idPromotor;
                }else{
                    $Asociado->idPromotor = $Asociado->idPromotor;
                }
                $Asociado->idComiteGremial = $request->comitegremial;
                $Asociado->idSector = $request->idSector;
                $Asociado->update();
            //Asociado

            if($request->tipoasociado==1){
                $Empresa = Empresa::where('idAsociado',$id)->first();
                $Empresa->ruc = $request->ruc;
                $Empresa->razonSocial = $request->razonsocial;
                $Empresa->direccion = $request->direccionfiscal;
                $Empresa->fundacion = $request->fundacion;
                $Empresa->actividad = $request->actividad;
                $Empresa->actividadSecundaria = $request->actividad_secundaria;
                $Empresa->correos = $request->correo_asociado;
                $Empresa->telefonos = $request->telefono_asociado;

                $Representante = Contacto::where('idContacto',$Empresa->idRepresentante)->first();
                $Representante->nombres = $request->nombres_representante;
                $Representante->apellidoPaterno = $request->paterno_representante;
                $Representante->apellidoMaterno = $request->materno_representante;
                $Representante->nombreCompleto = $request->nombres_representante . " " . $request->paterno_representante . " " . $request->materno_representante;
                $Representante->fechaNacimiento = $request->fechanacimiento_representante;
                $Representante->tipoDoc = $request->tipodocumento_representante;
                $Representante->documento = $request->documento_representante;
                $Representante->cargo = $request->cargo_representante;
                $Representante->email = $request->correo_representante;
                $Representante->telefonos = $request->telefono_representante;
                $Representante->save();

                if($request->documento_adicional){
                    $checkIfExistsAdicional = Contacto::where('idContacto',$Empresa->idContactoAdicional)->first();
                    if($checkIfExistsAdicional){
                        $ContactoAdicional = $checkIfExistsAdicional;
                    }else{
                        $ContactoAdicional = new Contacto();
                    }
                    $ContactoAdicional->nombres = $request->nombres_adicional;
                    $ContactoAdicional->apellidoPaterno = $request->paterno_adicional;
                    $ContactoAdicional->apellidoMaterno = $request->materno_adicional;
                    $ContactoAdicional->nombreCompleto = $request->nombres_adicional . " " . $request->paterno_adicional . " " . $request->materno_adicional;
                    $ContactoAdicional->fechaNacimiento = $request->fechanacimiento_adicional;
                    $ContactoAdicional->tipoDoc = $request->tipodocumento_adicional;
                    $ContactoAdicional->documento = $request->documento_adicional;
                    $ContactoAdicional->cargo = $request->cargo_adicional;
                    $ContactoAdicional->email = $request->correo_adicional;
                    $ContactoAdicional->telefonos = $request->telefono_adicional;
                    $ContactoAdicional->save();
                    $Empresa->idContactoAdicional = $ContactoAdicional->idContacto;
                }
                $Empresa->save();
            }else{
                $Persona = Persona::where('idAsociado',$Asociado->idAsociado)->first();
                $Persona->tipoDocumento = $request->tipodocumento_persona;
                $Persona->documento = $request->documento_persona;
                $Persona->nombresCompletos = $request->nombres_persona." ".$request->paterno_persona." ".$request->materno_persona;
                $Persona->nombres = $request->nombres_persona;
                $Persona->apellidoPaterno = $request->paterno_persona;
                $Persona->apellidoMaterno = $request->materno_persona;
                $Persona->sexo = $request->sexo;
                $Persona->fechaNacimiento = $request->fechanacimiento_persona;
                $Persona->direccion = $request->direccionfiscal_persona;
                $Persona->actividad = $request->actividad_persona;
                $Persona->actividadSecundaria = $request->actividad_secundaria_persona;
                $Persona->correos = $request->correo_persona;
                $Persona->telefonos = $request->telefono_persona;
                $Persona->save();
            }

            \DB::commit();
        
            return response()->json([
                'message' => 'Asociado actualizado',
            ], 200);
    
        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function status($id)
    {
        try {
        if(!$id)
            return response()->json([
                'message' => 'ID de asociado inválido',
            ], 400);

        $Associated=Associated::find($id);
    
        if(is_null($Associated))
            return response()->json([
                'message' => 'Asociado no encontrado',
            ], 400);

        if($Associated->estado==2){
            $Associated->fechaIngreso=date("Y-m-d");
            $welcome = new \stdClass();

            $emails = [];
            if($Associated->tipoAsociado==1){
                $empresa = Empresa::where('idAsociado',$Associated->idAsociado)->first();
                if($empresa->correos){
                    $emails[]=$empresa->correos;
                }
                $representante = Contacto::where('idContacto',$empresa->idRepresentante)->first();
                if($representante){
                    $representante->email && $emails[]=$representante->email;
                }
                $adicional = Contacto::where('idContacto',$empresa->idContactoAdicional)->first();
                if($adicional){
                    $adicional->email && $emails[]=$adicional->email;
                }
            }else{
                $persona = Persona::where('idAsociado',$Associated->idAsociado)->first();
                if($persona->correos){
                    $emails[]=$persona->correos;
                }

            }
            if(count($emails)>0){
                foreach ($emails as $email) {
                    $welcome->receiver = $email;
                    Mail::to($email)->send(new Welcome($welcome));
                }
            }
        }

        $message=$Associated->estado==0 ? "reincorporado" : ($Associated->estado==2 ? "activado" : "retirado");
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,7);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,3);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,16);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,17);

        \DB::beginTransaction();
        $Associated->estado =  $Associated->estado==0 ? 1 :  ($Associated->estado==2 ? 1 : 0);

        if($Associated->estado==0){
            $Associated->fechaRetiro = date("Y-m-d");
        }else{
            $Associated->fechaRetiro = null;
        }

        $Associated->save();
        $this->saveUpdates( $Associated->idAsociado, 'Cambio de estado', ucfirst($message) );
        \DB::commit();
        return response()->json([
            'message' => 'Asociado ' . $message,
        ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function preactive($id)
    {
        try {
        if(!$id)
            return response()->json([
                'message' => 'ID de asociado inválido',
            ], 400);

        $Associated=Associated::find($id);
    
        if(is_null($Associated))
            return response()->json([
                'message' => 'Asociado no encontrado',
            ], 400);

        $message=$Associated->estado==3 ? "activado" : "cambiado a preactivo";
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,7);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,3);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,16);
        $this->notify($id,' ha '.$message.' un asociado.','warning',null,2,17);

        $this->saveUpdates( $Associated->idAsociado, 'Cambio de estado', ucfirst($message) );

        \DB::beginTransaction();
        $Associated->estado =  $Associated->estado==3 ? 1 : 3;
        $Associated->save();

        \DB::commit();
        return response()->json([
            'message' => 'Asociado '.$message,
        ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function setCodigo(Request $request)
    {
        try {
            $request->validate([
                //Asociado
                    'idAsociado' => 'required|integer',
                    'codigo' => 'required']);
                    
        if(!$request->idAsociado)
            return response()->json([
                'message' => 'ID de asociado inválido',
            ], 400);

        $Associated=Associated::find($request->idAsociado);
    
        if(is_null($Associated))
            return response()->json([
                'message' => 'Asociado no encontrado',
            ], 400);

        \DB::beginTransaction();
        $Associated->codigo =  $request->codigo; 
        $Associated->save();

        \DB::commit();
        return response()->json([
            'message' => 'Código asignado.'
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
        try {
        if(!$id)
            return response()->json([
                'message' => 'ID de asociado inválido',
            ], 400);

        $Associated=Associated::find($id);
    
        if(is_null($Associated))
            return response()->json([
                'message' => 'Asociado no encontrado',
            ], 400);

        if($Associated->estado!=2)
            return response()->json([
                'message' => 'Solo puede eliminar asociados en proceso',
            ], 400);

        $this->notify($id,' ha eliminado un asociado en proceso.',null,null,2,7);
        $this->notify($id,' ha eliminado un asociado en proceso.',null,null,2,17);

        \DB::beginTransaction();
        if($Associated->tipoAsociado==1){
            $Empresa = Empresa::where('idAsociado',$Associated->idAsociado)->first();
            $idRepresentante = $Empresa->idRepresentante;
            $idAdicional = $Empresa->idContactoAdicional;
            $Empresa->delete();

            $Representate = Contacto::find($idRepresentante)->delete();
            if($idAdicional){
                $Adicional = Contacto::find($idAdicional)->delete();
            }
        }else{
            $Persona = Persona::where('idAsociado',$Associated->idAsociado)->delete();
        }

        $Associated->delete();
        \DB::commit();

        return response()->json([
            'message' => 'Asociado en proceso elimininado.'
        ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listWeekCalendar(Request $request){
        $start = $request->start;
        $end = $request->end;
        $asociation= Associated::
        select(
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.razonSocial,
                Persona.nombresCompletos
            ) as description'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.correos,
                Persona.correos
            ) as correo'),
            'Asociado.fechaIngreso as fecha',
            \DB::raw('"Afiliación" as tipo'),
            \DB::raw('2 as codTipo')
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($start,$end) {
            $firsstWhere->orWhere(function($query)  use ($start,$end){
                $query->whereRaw(
                    "DATE_FORMAT( Asociado.fechaIngreso, '2021-%m-%d') BETWEEN ? AND ?",
                    [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
                );
            });
        });
        
        $aniversaries= Associated::
        select(
            \DB::raw('
            CONCAT(
                IF(
                    Asociado.tipoAsociado=1, 
                    Empresa.razonSocial,
                    Persona.nombresCompletos
                ),
                " - Asociado"
            ) as description'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.correos,
                Persona.correos
            ) as correo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.fundacion,
                Persona.fechaNacimiento
            ) as fecha'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                "Aniversario",
                "Onomástico"
            ) as tipo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                1,
                3
            ) as codTipo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
            Empresa.razonSocial,
                "-"
            ) as empresa')
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($start,$end) {
            $firsstWhere->orWhere(function($query)  use ($start,$end){
                $query->whereRaw(
                    "DATE_FORMAT( Empresa.fundacion, '2021-%m-%d') BETWEEN ? AND ?",
                    [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
                );
            })
            ->orWhere(function($query)  use ($start,$end){
                $query->whereRaw(
                    "DATE_FORMAT( Persona.fechaNacimiento, '2021-%m-%d') BETWEEN ? AND ?",
                    [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
                );
            });
        });

        $representantes= Associated::
        select(
            \DB::raw('CONCAT(cr.nombreCompleto," - Representante") as description'),
            \DB::raw('cr.email as correo'),
            \DB::raw('cr.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('3 as codTipo'),
            \DB::raw('Empresa.razonSocial empresa')
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Contacto as cr', 'cr.idContacto', '=', 'Empresa.idRepresentante')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($start,$end) {
            $firsstWhere->where(function($query)  use ($start,$end){
                $query->whereRaw(
                    "DATE_FORMAT( cr.fechaNacimiento, '2021-%m-%d') BETWEEN ? AND ? ",
                    [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
                );
            });
        });

        $adicionales= Associated::
        select(
            \DB::raw('CONCAT(cr.nombreCompleto," - Contacto adicional") as description'),
            \DB::raw('cr.email as correo'),
            \DB::raw('cr.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('3 as codTipo'),
            \DB::raw('Empresa.razonSocial empresa')
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Contacto as cr', 'cr.idContacto', '=', 'Empresa.idContactoAdicional')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($start,$end) {
            $firsstWhere->where(function($query)  use ($start,$end){
                $query->whereRaw(
                    "DATE_FORMAT( cr.fechaNacimiento, '2021-%m-%d') BETWEEN ? AND ? ",
                    [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
                );
            });
        });

        $colaboradores = Colaborador::
        select(
            \DB::raw('
            CONCAT(
                Colaborador.nombres," ",Colaborador.apellidoPaterno," ",Colaborador.apellidoMaterno,
                IF(Colaborador.isDirectivo=1," - Directivo"," - Colaborador")
                ) as description'),
            \DB::raw('"" as correo'),
            \DB::raw('Colaborador.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('2 as codTipo'),
            \DB::raw('"CCLAM" empresa')
        )
        ->where( 'Colaborador.estado','=',1)
        ->whereRaw(
            "DATE_FORMAT( Colaborador.fechaNacimiento, '2021-%m-%d') BETWEEN ? AND ? ",
            [date('2021-m-d',strtotime($start)),date('2021-m-d',strtotime($end))]
        );
        

        return $aniversaries->union($representantes)->union($adicionales)->union($colaboradores)->get();
    }
    
    public function listMonthCalendar(Request $request){
        $month = $request->month;
        
        $afiliations= Associated::
        select(
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.razonSocial,
                Persona.nombresCompletos
            ) as description'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.correos,
                Persona.correos
            ) as correo'),
            'Asociado.fechaIngreso as fecha',
            \DB::raw('"Afiliación" as tipo'),
            \DB::raw('2 as codTipo')
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($month) {
            $firsstWhere->orWhere(function($query)  use ($month){
                $query->whereMonth( 'Asociado.fechaIngreso',$month);
            });
        });

        $aniversaries= Associated::
        select(
            \DB::raw('
            CONCAT(
                IF(
                    Asociado.tipoAsociado=1, 
                    Empresa.razonSocial,
                    Persona.nombresCompletos
                ),
                " - Asociado"
            ) as description'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.correos,
                Persona.correos
            ) as correo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.fundacion,
                Persona.fechaNacimiento
            ) as fecha'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                "Aniversario",
                "Onomástico"
            ) as tipo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                1,
                3
            ) as codTipo'),
            \DB::raw('
            IF(Asociado.tipoAsociado=1, 
                Empresa.razonSocial,
                "-"
            ) as empresa')
        )
        ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
        ->whereIn( 'Asociado.estado',[1,3])
        ->where(function($firsstWhere) use ($month) {
            $firsstWhere->orWhere(function($query)  use ($month){
                $query->whereMonth( 'Empresa.fundacion',$month)
                    ->whereMonth( 'Empresa.fundacion',$month);
            })
            ->orWhere(function($query)  use ($month){
                $query->whereMonth( 'Persona.fechaNacimiento',$month);
            }); 
        });

        $representantes= Associated::
        select(
            \DB::raw('CONCAT(cr.nombreCompleto," - Representante") as description'),
            \DB::raw('cr.email as correo'),
            \DB::raw('cr.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('3 as codTipo'),
            \DB::raw('Empresa.razonSocial empresa')
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Contacto as cr', 'cr.idContacto', '=', 'Empresa.idRepresentante')
        ->whereIn( 'Asociado.estado',[1,3])
        ->whereMonth( 'cr.fechaNacimiento',$month);

        $adicionales= Associated::
        select(
            \DB::raw('CONCAT(cr.nombreCompleto," - Representante") as description'),
            \DB::raw('cr.email as correo'),
            \DB::raw('cr.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('3 as codTipo'),
            \DB::raw('Empresa.razonSocial empresa')
        )
        ->join('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
        ->join('Contacto as cr', 'cr.idContacto', '=', 'Empresa.idContactoAdicional')
        ->whereIn( 'Asociado.estado',[1,3])
        ->whereMonth( 'cr.fechaNacimiento',$month);

        $colaboradores = Colaborador::
        select(
            \DB::raw('
            CONCAT(
                Colaborador.nombres," ",Colaborador.apellidoPaterno," ",Colaborador.apellidoMaterno,
                IF(Colaborador.isDirectivo=1," - Directivo"," - Colaborador")
                ) as description'),
            \DB::raw('"" as correo'),
            \DB::raw('Colaborador.fechaNacimiento as fecha'),
            \DB::raw('"Onomástico" as tipo'),
            \DB::raw('2 as codTipo'),
            \DB::raw('"CCLAM" empresa')
        )
        ->where( 'Colaborador.estado','=',1)
        ->whereMonth( 'Colaborador.fechaNacimiento',$month);
        
        return $aniversaries->union($representantes)->union($adicionales)->union($colaboradores)->get();
    }

    public function notify($id,$description,$color='danger',$detail=null,$type=2,$receiver){
        if(!is_null($id)){
            $detail = Associated::
            select(
                'Asociado.idAsociado', 
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.ruc,Persona.documento) as documento'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.razonSocial,Persona.nombresCompletos) as asociado'),
                'Asociado.tipoAsociado',
                'Asociado.estado',
                'Empresa.actividad',
                'Empresa.actividadSecundaria',
                'ComiteGremial.nombre as comitegremial',
                'Asociado.importeMensual',
                'Sector.descripcion as cobrador',
                'Asociado.direccionSocial',
                'Asociado.fechaIngreso',
                'Promotor.nombresCompletos as promotor',
                'Asociado.codigo',
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.fundacion,Persona.fechaNacimiento) as onomastico'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.correos,Persona.correos) as correos'),
                \DB::raw('IF(Asociado.tipoAsociado=1, Empresa.telefonos,Persona.telefonos) as telefonos')
            )
            ->join('Sector', 'Sector.idSector', '=', 'Asociado.idSector')
            ->join('ComiteGremial', 'ComiteGremial.idComite', '=', 'Asociado.idComiteGremial')
            ->join('Promotor', 'Promotor.idPromotor', '=', 'Asociado.idPromotor')
            ->leftJoin('Empresa', 'Empresa.idAsociado', '=', 'Asociado.idAsociado')
            ->leftJoin('Persona', 'Persona.idAsociado', '=', 'Asociado.idAsociado')
            ->where('Asociado.idAsociado', '=', $id)
            ->first();
        }
    
        $helper = new Helper;
        $helper::firebaseSender(
            'Actividad en asociados',
            $type,
            $description,
            $detail,
            $receiver,
            null,
            null,
            $color
        );
    }

    public function saveUpdates($idAsociado,$title,$description){
        $HistoriaAsociado= new HistoriaAsociado();
        $HistoriaAsociado->idAsociado=$idAsociado;
        $HistoriaAsociado->idUsuario=auth()->user() ? auth()->user()->idUsuario : 0;
        $HistoriaAsociado->title=$title;
        $HistoriaAsociado->description=$description;
        $HistoriaAsociado->save();
    }
}