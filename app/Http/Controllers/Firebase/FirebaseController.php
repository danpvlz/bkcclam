<?php


namespace App\Http\Controllers\Firebase;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Models\Folder;
use App\Models\FolderContenido;

class FirebaseController extends Controller
{
    public function index(){
        //
    }
    public static function checkOldNotifications() : string{
        $factory = (new Factory)->withServiceAccount(__DIR__.'/firebase.json');
        $database = $factory->createDatabase();
        $ref = $database->getReference('notifications');
        $content = $ref->getValue();
        foreach($content as $key=>$value){
            $folder=Folder::where('idColaborador', $key)->where('nombre', "Autoarchivados")->first();
            //CREAR FOLDER DE AUTOARCHIVADOS SI NO EXISTE
                if(!$folder){
                    $folder = new Folder();
                    $folder->idColaborador=$key;
                    $folder->nombre="Autoarchivados";
                    $folder->color="#1570CB";
                    $folder->save();
                }
            //CREAR FOLDER DE AUTOARCHIVADOS SI NO EXISTE
            foreach($value as $key2=>$subvalue){
                $time1 = new \DateTime($subvalue['timestamp']);
                $now = new \DateTime(date('Y-m-d H:i:s'));
                $interval=$now->diff($time1);
                //GUARDAR NOTIFICACIOENS DE MÁS DE UN MES
                    if($interval->m>0){
                        $subvalue['key']=$key2;
                        $folderContenido = new FolderContenido();
                        $folderContenido->idFolder = $folder->idFolder;
                        $folderContenido->contenido = json_encode($subvalue);
                        $folderContenido->save();
                        //ELIMINAR DE FIREBASE
                            $database->getReference('notifications/'.$key.'/'.$key2)->remove();
                        //ELIMINAR DE FIREBASE
                    }
                //GUARDAR NOTIFICACIOENS DE MÁS DE UN MES
            }
        }
        return "Done";
    }
}