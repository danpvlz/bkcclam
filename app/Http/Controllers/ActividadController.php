<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Actividad as ActividadResourse;
use App\Models\Actividad;

class ActividadController extends Controller
{
    public function list(Request $request)
    {
        $ActividadList = Actividad::orderBy('created_at','desc');
        
        if($request->desde){
            $ActividadList->where('created_at','>=',$request->desde);
        }
        if($request->hasta){
            $ActividadList->where('created_at','<=',$request->hasta);
        }
    
        if($request->titulo){
            $ActividadList->where('titulo',$request->titulo);
        }
        return ActividadResourse::collection($ActividadList->paginate(10));
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fechaInicio'   =>  'required',
                'fechaFin'      =>  'required',
                'imagen'        =>  'required',
                'titulo'        =>  'required'
            ]);

            \DB::beginTransaction();
            
            $Actividad                  =   new Actividad();
            $Actividad->fechaInicio     =   $request->fechaInicio; 
            $Actividad->fechaFin        =   $request->fechaFin; 
            $Actividad->titulo          =   $request->titulo; 
            
            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('actividads/'.date('Y').'/'.date('m'), 'public');
                $Actividad->imagen = 'actividads/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            $Actividad->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Actividad registrado',
            ], 200);
        

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
            if(!$id)
                return response()->json([
                    'message' => 'ID de Actividad inválido',
                ], 500);
                
            $request->validate([
                'fechaInicio'     =>  'required',
                'fechaFin'        =>  'required',
                'imagen'          =>  'required',
                'titulo'          =>  'required'
            ]);

            $Actividad = Actividad::find($id);
    
            if(is_null($Actividad))
                return response()->json([
                    'message' => 'Actividad no encontrado',
                ], 500);
            
            \DB::beginTransaction();
            
            $Actividad->fechaInicio     =   $request->fechaInicio; 
            $Actividad->fechaFin        =   $request->fechaFin; 
            $Actividad->titulo          =   $request->titulo; 
            
            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('actividads/'.date('Y').'/'.date('m'), 'public');
                $Actividad->imagen = 'actividads/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            $Actividad->save();

            \DB::commit();

            return response()->json([
                'message' => 'Actividad actualizado'
            ], 200);

        } catch (Exception $e) {
            \DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'idActividad'     =>  'required'
            ]);

            $ActividadFound=Actividad::where('id',$request->idActividad)->first();
            if($ActividadFound){
                $ActividadFound->delete();
                return response()->json([
                    'message' => 'Actividad eliminado',
                ], 200);
            }

            return response()->json([
                'message' => 'No se encontró el actividad',
            ], 500);
        
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $request->validate([
                'idActividad'     =>  'required',
            ]);

            $ActividadFound=Actividad::where('id',$request->idActividad)->first();
            if($ActividadFound){
                return $ActividadFound;
            }

            return response()->json([
                'message' => 'No se encontró el actividad',
            ], 500);
        
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listForWeb(Request $request)
    {
        $Actividad = Actividad::selectRaw('id, titulo as title,created_at as date,fechaInicio,fechaFin,imagen as coverImage')->orderBy('created_at','desc');

        return $Actividad->get();
    }
}