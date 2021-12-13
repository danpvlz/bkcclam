<?php

namespace App\Http\Controllers\FormacionYDesarrollo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Resources\FormacionYDesarrollo\Curso as CursoResourse;
use App\Models\FormacionYDesarrollo\Curso;
use App\Models\Concepto;
use File;


class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $CursoList = Curso::where('active',1)->orderBy('Curso.idCurso','desc');
        
        if($request->searchCurso){
            $CursoList->where('descripcion','like','%'.$request->searchCurso.'%');
        }

        return CursoResourse::collection($CursoList->paginate(10));
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
                'descripcion' => 'required',
            ]);

            $existCourse = Curso::where('active',1)->where('descripcion', $request->descripcion)->first();
            if($existCourse != null)
                return response()->json([
                    'message' => 'Ya existe un curso con ese nombre',
                ], 400);
            
            \DB::beginTransaction();

            $Course = new Curso();
            $Course->descripcion = $request->descripcion;
            $Course->inWeb = $request->inWeb ? 1 : 0;
            $Course->user_create = Auth::user()->idUsuario;
            $Course->user_update = Auth::user()->idUsuario;
            
            $Concepto                   =   new Concepto();
            $Concepto->codigo           =   Concepto::max('idConcepto')+1;
            $Concepto->descripcion      =   $request->descripcion; 
            $Concepto->tipoConcepto	    =   1; 
            $Concepto->tipoIGV          =   1; 
            $Concepto->valorConIGV      =   $request->valor; 
            $Concepto->priceInmutable   =   0; 
            $Concepto->categoriaCuenta  =   2; 

            $Concepto->user_create = auth()->user()->idUsuario;
            $Concepto->user_update = auth()->user()->idUsuario;
            $Concepto->save(); 

            
            $Course->idConcepto = $Concepto->idConcepto;
        
            if ($request->hasFile('foto')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->foto->store('courses/'.date('Y').'/'.date('m'), 'public');
                $Course->foto = 'courses/'.date('Y').'/'.date('m').'/'.$request->foto->hashName();
            }
            
            $Course->save();

            \DB::commit();
            return response()->json([
                'message' => 'Curso registrado',
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
                'message' => 'ID de curso inválido',
            ], 400);
        
        if(is_numeric($id)){
            $id = $id;
        }else{
            $id = base64_decode($id);
        }

        $Course = Curso::find($id);
        $Course['inscripcion']=env('APP_GS_LINK')."inscripcion/".base64_encode($id);
        if(is_null($Course) || $Course->active==0)
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 400);

        return $Course;
    }

    public function getCourseHtml($id){
        $Course = Curso::find(base64_decode($id));

        if(is_null($Course) || $Course->active==0)
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 400);

        return view('course',[
            "link"=> env('APP_GS_LINK')."inscripcion/".$id,
            "image"=>$Course->foto,
            "title"=>$Course->descripcion,
        ]);
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
            if(!$id)
                return response()->json([
                    'message' => 'ID de curso inválido',
                ], 400);

            $validateData = $request->validate([
                'descripcion' => 'required'
            ]);

            $Course = Curso::find($id);
    
            if(is_null($Course) || $Course->active==0)
                return response()->json([
                    'message' => 'Curso no encontrado',
                ], 400);
            
            \DB::beginTransaction();
            
            $Course->descripcion = $request->descripcion;
            $Course->inWeb = $request->inWeb ? 1 : 0;
        
            if ($request->hasFile('foto')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                $request->foto->store('courses/'.date('Y').'/'.date('m'), 'public');
                $Course->foto = 'courses/'.date('Y').'/'.date('m').'/'.$request->foto->hashName();
            }

            $Course->user_update = Auth::user()->idUsuario;

            $Concepto = Concepto::find($Course->idConcepto);
            
            if($Concepto){
                $Concepto->descripcion = $request->descripcion;
                $Concepto->user_update = Auth::user()->idUsuario;
                $Concepto->save();
            }

            $Course->save();

            \DB::commit();

            return response()->json([
                'message' => 'Curso actualizado'
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
    public function changeState($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de curso inválido',
            ], 400);

        $Course=Curso::find($id);
    
        if(is_null($Course) || $Course->active==0)
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 400);

        $Course->estado=$Course->estado==0 ? 1 : 0;
        $Course->save();
        return response()->json([
            'message' => 'Curso '.$Course->estado==1 ? 'activado!' : 'desactivado!',
        ], 200);
    }

    public function destroy($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de curso inválido',
            ], 400);

        $Course=Curso::find($id);
    
        if(is_null($Course) || $Course->active==0)
            return response()->json([
                'message' => 'Curso no encontrado',
            ], 400);

        $Course->active=0;
        $Course->save();
        return response()->json([
            'message' => 'Curso eliminado',
        ], 200);
    }
    
    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        $cursoFilter = Curso::
            selectRaw('idCurso as value, descripcion as label');
            
        if($request->isId){
            $cursoFilter->where('idCurso',$request->search);
        }else{
            $cursoFilter
            ->where('descripcion','like', '%'.$request->search."%");
        }

        return $cursoFilter->get();
    }

    
    public function listForWeb(Request $request)
    {
        $CursoList = Curso::select(
            
            \DB::raw('CONCAT("'.env('APP_GS_LINK').'","inscripcion/",TO_BASE64(idCurso)) as inscripcionLink'),
            \DB::raw('foto as coverImage'),
            'descripcion','inWeb')->where('active',1)->where('inWeb',1)->orderBy('Curso.idCurso','desc');

        return $CursoList->get();
    }
}
