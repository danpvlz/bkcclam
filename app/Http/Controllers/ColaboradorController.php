<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Http\Resources\Colaborador as ColaboradorResourse;
use App\Models\Colaborador;
use App\Models\User;
use App\Models\Folder;
use App\Models\FolderContenido;
use File;

class ColaboradorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ColaboradorResourse::collection(
            Colaborador::join('users','users.idColaborador','=','Colaborador.idColaborador')->where('Colaborador.active',1)
            ->selectRaw('Colaborador.idColaborador,dni,nombres,apellidoPaterno,apellidoMaterno,foto,fechaIngreso,usuario,Colaborador.estado')
            ->orderBy('Colaborador.nombres')
            ->paginate(10));
    }

    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        return 
            Colaborador::where('active',1)
            ->selectRaw('idColaborador as value, CONCAT(nombres, " ", apellidoPaterno, " ", apellidoMaterno, " [",dni,"]") as label')
            ->where('dni','like', '%'.$request->search."%")
            ->orWhere('nombres','like', '%'.$request->search."%")
            ->orWhere('apellidoPaterno','like', '%'.$request->search."%")
            ->orWhere('apellidoMaterno','like', '%'.$request->search."%")
            ->get();
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
                'dni' => 'required',
                'nombres' => 'required',
                'apellidoPaterno' => 'required',
                'apellidoMaterno' => 'required',
                'fechaNacimiento' => 'required',
                'fechaIngreso' => 'required',
            ]);
            
            $Colaborador = new Colaborador();
            $Colaborador->dni =  $request->get('dni'); 
            $Colaborador->nombres =  $request->get('nombres'); 
            $Colaborador->apellidoPaterno =  $request->get('apellidoPaterno'); 
            $Colaborador->apellidoMaterno =  $request->get('apellidoMaterno'); 
            $Colaborador->fechaNacimiento =  $request->get('fechaNacimiento'); 
            $Colaborador->fechaIngreso =  $request->get('fechaIngreso'); 
        
            if ($request->hasFile('foto')) {
                $request->validate([
                    'image' => 'mimes:jpg,jpeg,bmp,png'
                ]);
                
                $request->foto->store('colaborador', 'public');
                $Colaborador->foto = $request->foto->hashName();
            }

            $Colaborador->user_create = Auth::user()->idUsuario;
            $Colaborador->user_update = Auth::user()->idUsuario;
            $Colaborador->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Colaborador registrado',
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
                'message' => 'ID de colaborador inválido',
            ], 400);

        $Colaborador = Colaborador::find($id);
    
        if(is_null($Colaborador) || $Colaborador->active==0)
            return response()->json([
                'message' => 'Colaborador no encontrado',
            ], 400);

            $currentUser = Colaborador::selectRaw('Colaborador.idColaborador,usuario,users.estado,nombres, apellidoPaterno paterno, apellidoMaterno materno, fechaNacimiento fNac, foto')
            ->join('users','users.idColaborador', 'Colaborador.idColaborador')
            ->where('Colaborador.idColaborador', $Colaborador->idColaborador)
            ->get();

        return response()->json($currentUser[0]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateworker(Request $request, $id)
    {
        try {

            $request->validate([
                'nombres' => 'required',
                'apellidoPaterno' => 'required',
                'apellidoMaterno' => 'required',
                'fechaNacimiento' => 'required',
                'password' => 'max:50',
            ]);

            if(!$id){
                return response()->json([
                    'message' => 'ID de participante inválido',
                ], 400);
            }

            $Colaborador = Colaborador::find($id);
    
            if(is_null($Colaborador))
                return response()->json([
                    'message' => 'Colaborador no encontrado',
                ], 400);

            \DB::beginTransaction();
            
            $Colaborador->nombres =  $request->nombres; 
            $Colaborador->apellidoPaterno =  $request->apellidoPaterno; 
            $Colaborador->apellidoMaterno =  $request->apellidoMaterno; 
            $Colaborador->fechaNacimiento =  $request->fechaNacimiento; 

            if($request->password){
                $User = User::where('idColaborador', $Colaborador->idColaborador)->first();
                $User->password =  bcrypt($request->password); 
            }

            if ($request->hasFile('foto')) {
                $image_path="storage/colaborador/".$Colaborador->foto;
                if(file_exists($image_path)) {
                    File::delete($image_path);
                }

                $request->validate([
                    'image' => 'mimes:jpeg,bmp,png'
                ]);
                
                $request->foto->store('colaborador', 'public');
                $Colaborador->foto = $request->foto->hashName();
            }
            
            $Colaborador->user_update = Auth::user()->idUsuario;
            $Colaborador->save(); 

            \DB::commit();

            return response()->json([
                'message' => 'Colaborador actualizado',
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
    public function status($id)
    {
        try {
        if(!$id)
            return response()->json([
                'message' => 'ID de colaborador inválido',
            ], 400);

        $Colaborador=Colaborador::find($id);
    
        if(is_null($Colaborador) || $Colaborador->active==0)
            return response()->json([
                'message' => 'Colaborador no encontrado',
            ], 400);
            
        $User = User::where('idColaborador', $Colaborador->idColaborador)->first();
        $message=$Colaborador->estado==0 ? "reincorporado" : "dado de baja";

        \DB::beginTransaction();
        $User->estado =  $Colaborador->estado==0 ? 1 : 0; 
        $Colaborador->estado = $Colaborador->estado==0 ? 1 : 0;
        $Colaborador->save();
        $User->save();

        \DB::commit();
        return response()->json([
            'message' => 'Colaborador ' . $message,
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
    public function vacations($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de colaborador inválido',
            ], 400);

        $Colaborador=Colaborador::find($id);
    
        if(is_null($Colaborador) || $Colaborador->active==0)
            return response()->json([
                'message' => 'Colaborador no encontrado',
            ], 400);
            
        $User = User::where('idColaborador', $Colaborador->idColaborador)->first();
        $message=$Colaborador->estado==1 ? "a vaciones" : "reincorporado";
        $User->estado =  $Colaborador->estado==1 ? 2 : 1;
        $Colaborador->estado = $Colaborador->estado==1 ? 2 : 1;
        $Colaborador->save();
        $User->save();
        return response()->json([
            'message' => 'Colaborador ' . $message,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de colaborador inválido',
            ], 400);

        $Colaborador=Colaborador::find($id);
    
        if(is_null($Colaborador) || $Colaborador->active==0)
            return response()->json([
                'message' => 'Colaborador no encontrado',
            ], 400);

        $Colaborador->active=0;
        $Colaborador->save();
        return response()->json([
            'message' => 'Colaborador eliminado',
        ], 200);
    }

    public function resetPassword($id)
    {
        if(!$id)
            return response()->json([
                'message' => 'ID de colaborador inválido',
            ], 400);

        $Colaborador=Colaborador::find($id);
    
        if(is_null($Colaborador) || $Colaborador->active==0)
            return response()->json([
                'message' => 'Colaborador no encontrado',
            ], 400);
        
        $User = User::where('idColaborador', $Colaborador->idColaborador)->first();
        $User->password =  bcrypt("1234"); 
        $User->save();

        \DB::table('oauth_access_tokens')->where('user_id',$User->idUsuario)
        ->update([
            'revoked'=>true
        ]);

        return response()->json([
            'message' => 'Contraseña reseteada',
        ], 200);
    }

    public function saveFolder(Request $request){
        $folder = new Folder();
        $folder->idColaborador = Auth::user()->idColaborador;
        $folder->nombre = $request->nombre;
        $folder->color = $request->color;
        $folder->save();
        return response()->json([
            'message' => 'Folder guardado.',
        ], 200);
    }

    public function getFolders(){
        return 
            Colaborador::join('Folder','Folder.idColaborador','=','Colaborador.idColaborador')
            ->leftJoin('FolderContenido','FolderContenido.idFolder','=','Folder.idFolder')
            ->selectRaw('Folder.idFolder,Folder.nombre as folder,Folder.color,FolderContenido.contenido,FolderContenido.fecha')
            ->where('Colaborador.idColaborador',Auth::user()->idColaborador)
            ->orderBy('FolderContenido.fecha')->get();
        
    }

    public function saveContentOfFolder(Request $request){
        $folder = new FolderContenido();
        $folder->idFolder = $request->idFolder;
        $folder->contenido = $request->content;
        $folder->save();
        return response()->json([
            'message' => 'Notificación guardada.',
        ], 200);
    }
}