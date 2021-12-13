<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Pedido extends Mailable
{
    use Queueable, SerializesModels;

    public $params;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $client = new \GuzzleHttp\Client();
        
        $demoTmp = $this->params;
        
        $pdf = \PDF::loadView('pdfs.pedido',json_decode(json_encode($demoTmp), true));

        $finalMail=$this->subject("CONFIRMACIÓN DE PEDIDO N° ".$demoTmp->nro_pedido)->view('mails.pedido');

        $finalMail->attachData($pdf->output(),"PEDIDO".$demoTmp->nro_pedido."_".date("Y-m-d").".pdf");

        return $demoTmp;
    }
}
