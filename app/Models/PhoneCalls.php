<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneCalls extends Model
{
    protected $table = 'Llamada'; 
    protected $primaryKey = 'idLlamada';
    public $timestamps = false;
}
