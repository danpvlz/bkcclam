<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\Pedido;
use Mail;

class SendPedidoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $CorreoData = new \stdClass();
        $CorreoData->receiver = $this->details['correo'];
        $CorreoData->nro_pedido = $this->details['nro_pedido'];
        $CorreoData->fecha_pedido = $this->details['fecha_pedido'];
        $CorreoData->documento = $this->details['documento'];
        $CorreoData->tipo_doc = $this->details['tipo_doc'];
        $CorreoData->cliente = $this->details['cliente'];
        $CorreoData->telefono = $this->details['telefono'];
        $CorreoData->direccion = $this->details['direccion'];
        $CorreoData->concepto = $this->details['concepto'];
        $CorreoData->monto_concepto = $this->details['monto_concepto'];
        $CorreoData->descuento = $this->details['descuento'];
        $CorreoData->descuento_monto = $this->details['descuento_monto'];
        $CorreoData->total = $this->details['total'];
        
        Mail::to($this->details['correo'])->send(new Pedido($CorreoData));
    }
}
