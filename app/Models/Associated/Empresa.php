<?php

namespace App\Models\Associated;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'Empresa'; 
    protected $primaryKey = 'idEmpresa';
    public $timestamps = false;
}
