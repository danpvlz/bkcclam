<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'Servicio'; 
    protected $primaryKey = 'idServicio';
    public $timestamps = false;
}
