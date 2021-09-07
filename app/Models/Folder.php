<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $table = 'Folder'; 
    protected $primaryKey = 'idFolder';
    public $timestamps = false;
}
