<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;    
use App\Models\User;
use Carbon\Carbon;
use App\Models\Colaborador;
use App\Models\KPIPass;

use App\Mail\PowerBiPass as PowerBiPassEmail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     */
    public function signUp(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
            'idColaborador' => 'required|integer'
        ]);

        User::create([
            'usuario' => $request->usuario,
            'password' => bcrypt($request->password),
            'idColaborador' => $request->idColaborador
        ]);

        return response()->json([
            'message' => 'Usuario creado!'
        ], 201);
    }

    /**
     * Inicio de sesión y creación de token
     */
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string'
        ]);

        $credentials = request(['usuario', 'password']);

        if (!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Credenciales incorrectas.'
            ], 401);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;
        $token->save();

        
        $currentUser = Colaborador::selectRaw('Colaborador.idColaborador,usuario,users.rol,users.estado,nombres, apellidoPaterno paterno, apellidoMaterno materno, fechaNacimiento fNac, foto')
            ->join('users','users.idColaborador', 'Colaborador.idColaborador')
            ->where('users.idUsuario', $request->user()->idUsuario)
            ->get();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString(),
            'user'=>$currentUser[0]
        ]);
    }

    /**
     * Cierre de sesión (anular el token)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        User::find(auth()->user()->idUsuario)->update(['password'=> bcrypt($request->password)]);

        \DB::table('oauth_access_tokens')->where('user_id',auth()->user()->idUsuario)
        ->update([
            'revoked'=>true
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada!'
        ]);
    }

    /**
     * Obtener el objeto User como json
     */
    public function user(Request $request)
    {
        
        $currentUser = Colaborador::selectRaw('Colaborador.idColaborador,usuario,users.rol,users.estado,nombres, apellidoPaterno paterno, apellidoMaterno materno, fechaNacimiento fNac, foto')
            ->join('users','users.idColaborador', 'Colaborador.idColaborador')
            ->where('users.idUsuario', $request->user()->idUsuario)
            ->get();
        return response()->json($currentUser[0]);
    }

    public function powerBiPass(){
        $Pass= KPIPass::where('idUsuario',auth()->user()->idUsuario)->first();
        if(is_null($Pass)){
            return response()->json([
                'autorization' => null,
                'message' => 'No autorizado.'
            ]);
        }
        return response()->json([
            'autorization' => $Pass->status,
        ]);
    }

    public function powerBiAskPermission(){
        $Colaborador = Colaborador::find(auth()->user()->idColaborador);
        $KPIPass = new KPIPass();
        $KPIPass->idUsuario=auth()->user()->idUsuario;
        $KPIPass->save();
        $mailInfo = new \stdClass();
        $mailInfo->solicitante = $Colaborador->nombres.' '.$Colaborador->apellidoPaterno.' '.$Colaborador->appelidoMaterno;
        $mailInfo->linkGrant = 'api/grantpermission/'.$KPIPass->idPass;
        $mailInfo->linkDeny = 'api/denypermission/'.$KPIPass->idPass;

        $emails = ['secretariagerencia@cclam.org.pe'];
        
        foreach ($emails as $email) {
            $mailInfo->receiver = $email;
            Mail::to($email)->send(new PowerBiPassEmail($mailInfo));
        }

        return response()->json([
            'message' => 'Se ha enviado la solicitud al administrador.'
        ]);
    }

    public function powerBiGrantPermission($pass){
        $KPIPass = KPIPass::find($pass);
        if(is_null($KPIPass)){
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ]);
        }
        $User=User::find($KPIPass->idUsuario);
        $Colaborador=Colaborador::find($User->idColaborador);
        $KPIPass->status = 1;
        $KPIPass->save();

        return view('grantpowerbi', ['solicitante' => $Colaborador->nombres.' '.$Colaborador->apellidoPaterno.' '.$Colaborador->appelidoMaterno]);
    }

    public function powerBiDenyPermission($pass){
        $KPIPass = KPIPass::find($pass);
        if(is_null($KPIPass)){
            return response()->json([
                'message' => 'Solicitud no encontrada.'
            ]);
        }
        $KPIPass->delete();
        
        return response()->json([
            'message' => 'Permiso no concedido.'
        ]);
    }
}