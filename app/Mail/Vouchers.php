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
        $client = new \GuzzleHttp\Client();
        
        $demoTmp = $this->demo;
        
        $subjectTmp=$demoTmp->comprobante.' COMPROBANTES';
        if($demoTmp->subject){
            $subjectTmp=$demoTmp->subject;
        }

        $finalMail=$this->subject($subjectTmp)->text('mails.vouchers');

        if(property_exists($demoTmp,'pdf') && $demoTmp->pdf){
            $res = $client->get($demoTmp->pdf);
            $content = (string) $res->getBody();
            $finalMail->attachData($content,$demoTmp->pdf);
        }
        if(property_exists($demoTmp,'xml') && $demoTmp->xml){
            $res = $client->get($demoTmp->xml);
            $content = (string) $res->getBody();
            $finalMail->attachData($content,$demoTmp->xml);
        }
        if(property_exists($demoTmp,'cdr') && $demoTmp->cdr){
            $res = $client->get($demoTmp->cdr);
            $content = (string) $res->getBody();
            $finalMail->attachData($content,$demoTmp->cdr);
        }
        return $demoTmp;
    }
}
