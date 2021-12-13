<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Pronunciamiento as PronunciamientoResourse;
use App\Models\Pronunciamiento;

class PronunciamientoController extends Controller
{
    public function list(Request $request)
    {
        $PronunciamientoList = Pronunciamiento::orderBy('created_at','desc');
        
        if($request->desde){
            $PronunciamientoList->where('created_at','>=',$request->desde);
        }
        if($request->hasta){
            $PronunciamientoList->where('created_at','<=',$request->hasta);
        }
    
        if($request->titulo){
            $PronunciamientoList->where('titulo',$request->titulo);
        }
        return PronunciamientoResourse::collection($PronunciamientoList->paginate(10));
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha'     =>  'nullable',
                'imagen'    =>  'required',
                'titulo'    =>  'required'
            ]);

            \DB::beginTransaction();
            
            $Pronunciamiento            =   new Pronunciamiento();
            $Pronunciamiento->fecha     =   $request->fecha; 
            $Pronunciamiento->titulo    =   $request->titulo; 
            
            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('pronunciamientos/'.date('Y').'/'.date('m'), 'public');
                $Pronunciamiento->imagen = 'pronunciamientos/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            $Pronunciamiento->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Pronunciamiento registrado',
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
                    'message' => 'ID de Pronunciamiento inválido',
                ], 500);
                
            $request->validate([
                'fecha'     =>  'nullable',
                'imagen'    =>  'required',
                'titulo'    =>  'required'
            ]);

            $Pronunciamiento = Pronunciamiento::find($id);
    
            if(is_null($Pronunciamiento))
                return response()->json([
                    'message' => 'Pronunciamiento no encontrado',
                ], 500);
            
            \DB::beginTransaction();
            
            $Pronunciamiento->fecha     =   $request->fecha; 
            $Pronunciamiento->titulo    =   $request->titulo; 
            
            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('pronunciamientos/'.date('Y').'/'.date('m'), 'public');
                $Pronunciamiento->imagen = 'pronunciamientos/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            $Pronunciamiento->save();

            \DB::commit();

            return response()->json([
                'message' => 'Pronunciamiento actualizado'
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
                'idPronunciamiento'     =>  'required'
            ]);

            $PronunciamientoFound=Pronunciamiento::where('id',$request->idPronunciamiento)->first();
            if($PronunciamientoFound){
                $PronunciamientoFound->delete();
                return response()->json([
                    'message' => 'Pronunciamiento eliminado',
                ], 200);
            }

            return response()->json([
                'message' => 'No se encontró el pronunciamiento',
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
                'idPronunciamiento'     =>  'required',
            ]);

            $PronunciamientoFound=Pronunciamiento::where('id',$request->idPronunciamiento)->first();
            if($PronunciamientoFound){
                return $PronunciamientoFound;
            }

            return response()->json([
                'message' => 'No se encontró el pronunciamiento',
            ], 500);
        
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listForWeb(Request $request)
    {
        $Pronunciamiento = Pronunciamiento::selectRaw('id, titulo as title,IFNULL(fecha,created_at) as date,imagen as coverImage')->orderBy('fecha','desc')->orderBy('created_at','desc');

        return $Pronunciamiento->get();
    }
}