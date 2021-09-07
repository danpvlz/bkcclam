<?php

namespace App\Http\Controllers\Associated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Associated\Sector as SectorResourse;
use App\Models\Associated\Sector;

class SectorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        $first= Sector::
        select(
            \DB::raw('CONCAT(Sector.descripcion, " (",Sector.codigo, ")") as label'), 'Sector.idSector as value'
        )
        ->where('Sector.descripcion','like', '%'.$request->search."%")
        ->orWhere('Sector.codigo','=', $request->search);

        return $first->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
