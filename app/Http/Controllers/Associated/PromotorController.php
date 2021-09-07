<?php

namespace App\Http\Controllers\Associated;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Associated\Promotor;

class PromotorController extends Controller
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

    public function filterData(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        return 
        Promotor::where('estado',1)
            ->selectRaw('idPromotor as value, nombresCompletos as label')
            ->where('nombresCompletos','like', '%'.$request->search."%")
            ->get();
    }
}
