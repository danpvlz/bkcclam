<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Concepto as ConceptoResourse;
use App\Models\Concepto;

class ConceptoController extends Controller
{
    

    public function filterDataAmbientes(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        return 
            Concepto::
            selectRaw('idConcepto as value, CONCAT(descripcion) as label,priceInmutable as inmutable,valorConIGV as price')
            ->where('categoriaCuenta','=', "6")
            ->where('descripcion','like', '%'.$request->search."%")
            ->orWhere('idConcepto','like', '%'.$request->search."%")
            ->get();
    }
    

    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        return 
            Concepto::
            selectRaw('idConcepto as value, CONCAT(descripcion) as label,priceInmutable as inmutable,valorConIGV as price')
            ->where('descripcion','like', '%'.$request->search."%")
            ->orWhere('idConcepto','like', '%'.$request->search."%")
            ->get();
    }

    public function filterAreas(Request $request)
    {
        return 
            \DB::table('Area')
            ->selectRaw('idArea as value, nombre as label')
            ->get();
    }

    public function filterCategoriaCuenta($idArea)
    {
        return 
            \DB::table('CategoriaCuenta')
            ->selectRaw('idCategoria as value, nombre as label')
            ->where('idArea',$idArea)
            ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $ConceptList = 
            Concepto::join('CategoriaCuenta','Concepto.categoriaCuenta','=','CategoriaCuenta.idCategoria')
            ->join('Area','Area.idArea','=','CategoriaCuenta.idArea')
            ->selectRaw('Concepto.*,CategoriaCuenta.nombre as categoriaNombre,Area.nombre as area,updated_at')
            ->orderBy('Concepto.idConcepto','desc');
        
            if($request->idArea){
                $ConceptList->where('Area.idArea',$request->idArea);
            }
        
            if($request->idConcepto){
                $ConceptList->where('idConcepto',$request->idConcepto);
            }
        return ConceptoResourse::collection($ConceptList->paginate(10));
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
                'descripcion' => 'required',
                'tipoConcepto' => 'required',
                'igv' => 'required',
                'inmutable' => 'nullable',
                'valor' => 'nullable',
                'idCategoria' => 'required|integer',
            ]);
            
            $nuevoCodigo=Concepto::max('idConcepto')+1;
            $Concepto                   =   new Concepto();
            $Concepto->codigo           =   $nuevoCodigo; 
            $Concepto->descripcion      =   $request->descripcion; 
            $Concepto->tipoConcepto	    =   $request->tipoConcepto; 
            $Concepto->tipoIGV          =   $request->igv; 
            $Concepto->valorConIGV      =   $request->valor; 
            $Concepto->priceInmutable   =   $request->inmutable ? $request->inmutable : 0; 
            $Concepto->categoriaCuenta  =   $request->idCategoria; 

            $Concepto->user_create = auth()->user()->idUsuario;
            $Concepto->user_update = auth()->user()->idUsuario;
            $Concepto->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Concepto registrado',
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
        if(!$id)
            return response()->json([
                'message' => 'ID de concepto inválido',
            ], 400);

        $Concepto = Concepto::join('CategoriaCuenta','Concepto.categoriaCuenta','=','CategoriaCuenta.idCategoria')
        ->join('Area','Area.idArea','=','CategoriaCuenta.idArea')
        ->selectRaw('Concepto.*,CategoriaCuenta.nombre as categoriaNombre,Area.*')
        ->find($id);
    
        if(is_null($Concepto))
            return response()->json([
                'message' => 'Concepto no encontrado',
            ], 400);

        return $Concepto;
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
        try {
            $request->validate([
                'descripcion' => 'required',
                'tipoConcepto' => 'required',
                'igv' => 'required',
                'valor' => 'required',
                'inmutable' => 'nullable',
                'idCategoria' => 'required|integer',
            ]);
            
            $Concepto                   =   Concepto::find($id);
            if(is_null($Concepto))
                return response()->json([
                    'message' => 'Concepto no encontrado',
                ], 400);

            \DB::beginTransaction();
            $Concepto->descripcion      =   $request->descripcion; 
            $Concepto->tipoConcepto	    =   $request->tipoConcepto; 
            $Concepto->tipoIGV          =   $request->igv; 
            $Concepto->valorConIGV      =   $request->valor; 
            $Concepto->priceInmutable   =   $request->inmutable;
            $Concepto->categoriaCuenta  =   $request->idCategoria; 
            $Concepto->user_update      =   auth()->user()->idUsuario;
            $Concepto->update(); 

            \DB::commit();

            return response()->json([
                'message' => 'Concepto registrado',
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
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
    public function destroy($id)
    {
        //
    }
}
