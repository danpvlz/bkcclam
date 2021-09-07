<?php

namespace App\Models\Associated;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $table = 'Contacto'; 
    protected $primaryKey = 'idContacto';
    public $timestamps = false;
}
