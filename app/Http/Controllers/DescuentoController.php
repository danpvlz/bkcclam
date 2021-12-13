<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Descuento;

class DescuentoController extends Controller
{
    public function checkIfDescuento(Request $request)
    {
        $objDescuento = 
            Descuento::
            select(
                'Descuento.id',
                'Descuento.codigo',
                'Descuento.monto',
                'Descuento.motivo'
                )
            ->where('active',1)
            ->where('codigo',$request->codigo);
        return $objDescuento->first();

    }
}
