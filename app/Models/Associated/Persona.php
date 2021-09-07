<?php

namespace App\Models\Associated;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'Persona'; 
    protected $primaryKey = 'idPersona';
    public $timestamps = false;
}
