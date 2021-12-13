<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Pedido;
use App\Models\Concepto;
use App\Models\Descuento;
use App\Mail\PedidoPaid;

use Mail;

class PayPedidoJob implements ShouldQueue
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
        $Pedido = Pedido::where('id',$this->details['idPedido'])->first();
        $Concepto = Concepto::where('idConcepto',$Pedido->idConcepto)->first();
        $Descuento=null;
        if($Pedido->idDescuento){
            $Descuento = Descuento::where('id',$Pedido->idDescuento)->first();
        }
        $CorreoData = new \stdClass();
        $CorreoData->receiver = $Pedido->correo;
        $CorreoData->nro_pedido = $Pedido->id;
        $CorreoData->fecha_pedido = $Pedido->created_at;
        $CorreoData->documento = $Pedido->documento;
        $CorreoData->tipo_doc = $Pedido->tipoDoc;
        $CorreoData->cliente = $Pedido->adquiriente;
        $CorreoData->telefono = $Pedido->telefono;
        $CorreoData->direccion = $Pedido->direccion;
        $CorreoData->concepto = $Concepto->descripcion;
        $CorreoData->monto_concepto = $Pedido->monto;
        $CorreoData->descuento = $Descuento ? $Descuento->motivo : null;
        $CorreoData->descuento_monto = $Pedido->montoDcto;
        $CorreoData->total = $Pedido->total;
        $CorreoData->estadoPago = $this->details['estadoPago'];
        $CorreoData->tarjeta = $this->details['tarjeta'];
        $CorreoData->montoPago = "S/.".number_format($this->details['montoPago'], 2);
        
        Mail::to($Pedido->correo)->send(new PedidoPaid($CorreoData));
    }
}
