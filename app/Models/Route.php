<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'Route'; 
    protected $primaryKey = 'idRoute';
    public $timestamps = false;
}
