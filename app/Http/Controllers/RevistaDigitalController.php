<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\RevistaDigital as RevistaDigitalResourse;
use App\Models\RevistaDigital;

class RevistaDigitalController extends Controller
{
    public function list(Request $request)
    {
        $RevistaDigitalList = RevistaDigital::orderBy('created_at','desc');
        
        if($request->desde){
            $RevistaDigitalList->where('created_at','>=',$request->desde);
        }
        if($request->hasta){
            $RevistaDigitalList->where('created_at','<=',$request->hasta);
        }
        
        return RevistaDigitalResourse::collection($RevistaDigitalList->paginate(10));
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha'     =>  'nullable',
                'imagen'    =>  'required',
                'url'       =>  'nullable',
                'active'    =>  'required'
            ]);

            \DB::beginTransaction();
            
            $RevistaDigital            =   new RevistaDigital();

            if($request->active){
                RevistaDigital::where('active', 1)->update(['active' => 0]);
            }

            $RevistaDigital->active     =   $request->active;
            $RevistaDigital->fecha     =   $request->fecha;
            $RevistaDigital->url       =   $request->url;

            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('revistadigital/'.date('Y').'/'.date('m'), 'public');
                $RevistaDigital->imagen = 'revistadigital/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            if ($request->hasFile('revista')) {
                $request->validate([
                    'revista' => 'mimes:pdf'
                ]);
                $request->revista->store('revistadigital/pdfs/'.date('Y').'/'.date('m'), 'public');
                $RevistaDigital->revista = 'revistadigital/pdfs/'.date('Y').'/'.date('m').'/'.$request->revista->hashName();
            }

            $RevistaDigital->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Revista digital registrada',
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
                    'message' => 'ID de revista digital inválido',
                ], 500);
                
            $request->validate([
                'fecha'     =>  'nullable',
                'imagen'    =>  'required',
                'url'       =>  'nullable',
                'active'    =>  'required'
            ]);

            $RevistaDigital = RevistaDigital::find($id);
    
            if(is_null($RevistaDigital))
                return response()->json([
                    'message' => 'Revista digital no encontrado',
                ], 500);
            
            \DB::beginTransaction();
            
            if($request->active==1){
                RevistaDigital::where('active', 1)->where('id','<>', $id)->update(['active' => 0]);
            }
            
            $RevistaDigital->active     = $request->active;
            $RevistaDigital->fecha     =   $request->fecha; 
            $RevistaDigital->url       =   $request->url;
            
            if ($request->hasFile('imagen')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->imagen->store('revistadigital/'.date('Y').'/'.date('m'), 'public');
                $RevistaDigital->imagen = 'revistadigital/'.date('Y').'/'.date('m').'/'.$request->imagen->hashName();
            }

            if ($request->hasFile('revista')) {
                $request->validate([
                    'revista' => 'mimes:pdf'
                ]);
                $request->revista->store('revistadigital/pdfs/'.date('Y').'/'.date('m'), 'public');
                $RevistaDigital->revista = 'revistadigital/pdfs/'.date('Y').'/'.date('m').'/'.$request->revista->hashName();
            }

            $RevistaDigital->save();

            \DB::commit();

            return response()->json([
                'message' => 'Revista digital actualizada'
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
                'idRevistaDigital'     =>  'required'
            ]);

            $RevistaDigitalFound=RevistaDigital::where('id',$request->idRevistaDigital)->first();
            if($RevistaDigitalFound){
                $RevistaDigitalFound->delete();
                return response()->json([
                    'message' => 'Revista digital eliminada',
                ], 200);
            }

            return response()->json([
                'message' => 'No se encontró la revista digital',
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
                'idRevistaDigital'     =>  'required',
            ]);

            $RevistaDigitalFound=RevistaDigital::where('id',$request->idRevistaDigital)->first();
            if($RevistaDigitalFound){
                return $RevistaDigitalFound;
            }

            return response()->json([
                'message' => 'No se encontró el revista digital',
            ], 500);
        
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listForWeb(Request $request)
    {
        $RevistaDigital = RevistaDigital::selectRaw('id, IFNULL(fecha,created_at) as date,imagen as coverImage, url,revista')->orderBy('active','desc')->orderBy('fecha','desc')->orderBy('created_at','desc');

        return $RevistaDigital->get();
    }

    public function getForWeb(Request $request)
    {
        $Noticia = RevistaDigital::selectRaw('id, url as slug,IFNULL(fecha,created_at) as date,imagen as coverImage,revista')->where('active',1)->orderBy('fecha','desc')->orderBy('created_at','desc');

        return $Noticia->first();
    }
}