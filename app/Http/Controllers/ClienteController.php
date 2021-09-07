<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Cliente as ClienteResourse;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        return 
            Cliente::
            selectRaw('idCliente as value, CONCAT(denominacion, " [",documento,"]") as label, documento')
            ->where('documento','like', '%'.$request->search."%")
            ->orWhere('denominacion','like', '%'.$request->search."%")
            ->get();
    }

    public function list(Request $request)
    {
        $ClienteList = Cliente::orderBy('Cliente.idCliente','desc');
        
        if($request->idCliente){
            $ClienteList->where('idCliente',$request->idCliente);
        }

        return ClienteResourse::collection($ClienteList->paginate(10));
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
                'tipoDocumento' => 'required',
                'documento' => 'required',
                'denominacion' => 'required',
                'direccion' => 'nullable',
                'email' => 'nullable',
                'telefono' => 'nullable',
            ]);

            $ClienteFind = Cliente::where('documento',$request->documento)->first();
    
            if($ClienteFind){
                return response()->json([
                    'message' => 'Este cliente ya está registrado.'
                ], 400);
            }

            \DB::beginTransaction();
            
            $Cliente = new Cliente();
            $Cliente->tipoDocumento =  $request->tipoDocumento;
            $Cliente->documento =  $request->documento;
            $Cliente->denominacion =  $request->denominacion;
            $Cliente->direccion =  $request->direccion;
            $Cliente->email =  $request->email;
            $Cliente->telefono =  $request->telefono;
            $Cliente->user_create = auth()->user()->idUsuario;
            $Cliente->user_update = auth()->user()->idUsuario;
            $Cliente->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Cliente registrado',
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
                'message' => 'ID de cliente inválido',
            ], 400);

        $Cliente = Cliente::select(\DB::raw('Cliente.*, IF(tipoDocumento=1,"DNI",IF(tipoDocumento=4,"CARNET DE EXTRANJ.",IF(tipoDocumento=6,"RUC",IF(tipoDocumento=7,"PASAPORTE","-")))) as tipo'))->find($id);
    
        if(is_null($Cliente))
            return response()->json([
                'message' => 'Cliente no encontrado',
            ], 400);
            
        return $Cliente;
    }

    public function searchRuc($ruc)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://consulta.api-peru.com/api/ruc/'.$ruc);
        return $response->getBody()->getContents();
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
                'tipoDocumento' => 'required',
                'documento' => 'required',
                'denominacion' => 'required',
                'direccion' => 'nullable',
                'email' => 'nullable',
                'telefono' => 'nullable',
            ]);

            $Cliente = Cliente::find($id);
    
            if(is_null($Cliente)){
                return response()->json([
                    'message' => 'Cliente no encontrado.'
                ], 400);
            }

            \DB::beginTransaction();
            
            $Cliente->tipoDocumento =  $request->tipoDocumento;
            $Cliente->documento =  $request->documento;
            $Cliente->denominacion =  $request->denominacion;
            $Cliente->direccion =  $request->direccion;
            $Cliente->email =  $request->email;
            $Cliente->telefono =  $request->telefono;
            $Cliente->user_update = auth()->user()->idUsuario;
            $Cliente->update(); 

            \DB::commit();

            return response()->json([
                'message' => 'Cliente actualizado.',
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
