<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\Vouchers;
use Mail;

class SendEmailJob implements ShouldQueue
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
        $CorreoData->comprobante = $this->details['comprobante'];
        $CorreoData->receiver = $this->details['correo'];
        $CorreoData->pdf = $this->details['enlace_del_pdf'];
        $CorreoData->xml = $this->details['enlace_del_xml'];
        $CorreoData->cdr = $this->details['enlace_del_cdr'];

        Mail::to($this->details['correo'])->send(new Vouchers($CorreoData));
    }
}
