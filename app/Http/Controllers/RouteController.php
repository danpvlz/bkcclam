<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\Route as RouteResourse;
use App\Models\Route;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rol = auth()->user()->rol;
        return \DB::table('Route as r')->leftJoin('Route as rc','r.idRoute','=','rc.parentId')
            ->join('RolRoute as rr','rr.idRoute','=','r.idRoute')
            ->where('rr.idRol',$rol)
            ->select(
                'r.path',
                'r.name',
                'r.icon',
                'r.component',
                'r.layout',
                'r.show',
            \DB::raw(
                "
                IF(r.parent=0,'',
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                            JSON_OBJECT(
                                'path',rc.path,
                                'name',rc.name,
                                'icon',rc.icon,
                                'component',rc.component,
                                'layout',rc.layout,
                                'show',rc.show
                            )
                            order by rc.idRoute
                        )
                        ,
                        ']'
                    )
                )
                as routes")
            )
            ->groupBy('r.parent')
            ->groupBy('r.path')
            ->groupBy('r.name')
            ->groupBy('r.icon')
            ->groupBy('r.component')
            ->groupBy('r.layout')
            ->groupBy('r.show')
            ->orderBy('rr.idRolRoute')
            ->get();
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
