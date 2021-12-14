<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Mail\KAPResults;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendKAPResults;

class KapController extends Controller
{
    public function sendMailResultsKap(Request $request)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://cclam.org.pe/kap_api/results.php', [
            'form_params' => [
                'user' =>  $request->user,
            ],
        ]);
        $content= $response->getBody()->getContents();
        $rpta = json_decode($content,true);
        $rpta['criteria'] = json_decode(($rpta['criteria']), true);
        $JSONcomments = json_decode(($rpta['comments']), true);
        foreach ($JSONcomments as &$comment) {
            $comment['comments'] = explode('|',$comment['comments']);
        }
        $rpta['comments'] = $JSONcomments;

        SendKAPResults::dispatch($rpta);

        return response()->json([
            'message' => 'Resultados enviados',
            'enviado' => true
        ], 200);
    }
}
