<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\KAPResults;
use Illuminate\Support\Facades\Mail;

class SendKAPResults implements ShouldQueue
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
        $sendFinal = Mail::to($this->details['email']);
        if(isset($this->details['emailAdicional'])){
            $sendFinal->cc($this->details['emailAdicional']);
        }
        $sendFinal->send(new KAPResults($this->details));
    }
}
