<?php


namespace App\Http\Controllers\Firebase;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseController extends Controller
{
    
    public function index(){
        $factory = (new Factory)->withServiceAccount(__DIR__.'/firebase.json');
        $database = $factory->createDatabase();

        $ref = $database->getReference('notifications/7');
        
        $key = $ref->push()->getKey();

        $ref->getChild($key)->set([
            'title' => 'Actividad irregular',
            'description' => 'DANIELA PAIVA ha registrado el mismo número de sofydoc en más de un pago.',
            'seen' => 0,
            'clicked' => false,
            'numsofdoc' => 123,
            'detail' => [],
            'timestamp' => date('Y-m-d G:i:s')
        ]);

        return date('Y-m-d G:i:s');

    }
}