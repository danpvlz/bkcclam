<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Vouchers extends Mailable
{
    use Queueable, SerializesModels;

    public $demo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($demo)
    {
        $this->demo = $demo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $demoTmp = $this->demo;
        $finalMail=$this->subject($demoTmp->comprobante.' COMPROBANTES')->text('mails.vouchers');
        if(property_exists($demoTmp,'archivo')){
            $finalMail->attach($demoTmp->archivo);
        }
        if(property_exists($demoTmp,'pdf')){
            $finalMail->attach($demoTmp->pdf);
        }
        if(property_exists($demoTmp,'xml')){
            $finalMail->attach($demoTmp->xml);
        }
        if(property_exists($demoTmp,'cdr')){
            $finalMail->attach($demoTmp->cdr);
        }
        return $demoTmp;
    }
}
