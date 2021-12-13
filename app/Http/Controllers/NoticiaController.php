<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Noticia as NoticiaResourse;
use App\Models\Noticia;

class NoticiaController extends Controller
{
    public function list(Request $request)
    {
        $NoticiaList = Noticia::orderBy('created_at','desc');
        
        if($request->desde){
            $NoticiaList->where('created_at','>=',$request->desde);
        }
        if($request->hasta){
            $NoticiaList->where('created_at','<=',$request->hasta);
        }
    
        if($request->titulo){
            $NoticiaList->where('titulo',$request->titulo);
        }
        return NoticiaResourse::collection($NoticiaList->paginate(10));
    }
    
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha'     =>  'nullable',
                'url'       =>  'required',
                'miniatura' =>  'nullable',
                'titulo'    =>  'required',
                'content' =>  'required'
            ]);
            
            $noticiaFound=Noticia::where('url',$request->url)->first();
            if($noticiaFound){
                return response()->json([
                    'message' => 'Ya existe una noticia con este título.',
                ], 500);
            }

            \DB::beginTransaction();
            
            $Noticia            =   new Noticia();
            $Noticia->fecha     =   $request->fecha; 
            $Noticia->url	    =   $request->url; 
            $Noticia->titulo    =   $request->titulo; 
            $Noticia->contenido =   $request->content;
            
            if ($request->hasFile('miniatura')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->miniatura->store('noticias/'.date('Y').'/'.date('m'), 'public');
                $Noticia->miniatura = 'noticias/'.date('Y').'/'.date('m').'/'.$request->miniatura->hashName();
            }

            $Noticia->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Noticia registrada',
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
                    'message' => 'ID de noticia inválido',
                ], 500);
                
            $request->validate([
                'fecha'     =>  'nullable',
                'url'       =>  'required',
                'miniatura' =>  'nullable',
                'titulo'    =>  'required',
                'content' =>  'required'
            ]);

            $Noticia = Noticia::find($id);
    
            if(is_null($Noticia))
                return response()->json([
                    'message' => 'Noticia no encontrada',
                ], 500);
            
            \DB::beginTransaction();
            
            $Noticia->fecha     =   $request->fecha; 
            $Noticia->url	    =   $request->url; 
            $Noticia->titulo    =   $request->titulo; 
            $Noticia->contenido =   $request->content;
            
            if ($request->hasFile('miniatura')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->miniatura->store('noticias/'.date('Y').'/'.date('m'), 'public');
                $Noticia->miniatura = 'noticias/'.date('Y').'/'.date('m').'/'.$request->miniatura->hashName();
            }

            $Noticia->save();

            \DB::commit();

            return response()->json([
                'message' => 'Noticia actualizada'
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
                'idNoticia'     =>  'required',
            ]);

            $noticiaFound=Noticia::where('id',$request->idNoticia)->first();
            if($noticiaFound){
                $noticiaFound->delete();
                return response()->json([
                    'message' => 'Noticia eliminada',
                ], 200);
            }

            return response()->json([
                'message' => 'No se encontró la noticia',
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
                'idNoticia'     =>  'required',
            ]);

            $noticiaFound=Noticia::where('id',$request->idNoticia)->first();
            if($noticiaFound){
                return $noticiaFound;
            }

            return response()->json([
                'message' => 'No se encontró la noticia',
            ], 500);
        
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listForWeb(Request $request)
    {
        $Noticia = Noticia::selectRaw('id, titulo as title,url as slug,IFNULL(fecha,created_at) as date,miniatura as coverImage')->orderBy('fecha','desc')->orderBy('created_at','desc');

        return $Noticia->get();
    }

    public function getForWeb(Request $request)
    {
        $Noticia = Noticia::selectRaw('id, titulo as title,url as slug,IFNULL(fecha,created_at) as date,miniatura as coverImage,contenido as content')->where('url',$request->url)->orderBy('fecha','desc')->orderBy('created_at','desc');

        return $Noticia->first();
    }
}
