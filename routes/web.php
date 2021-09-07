<?php

Route::get('/', function () {
    return view('welcome');
});

Route::get('/capacitacion-cclam/{id}', 'FormacionYDesarrollo\CursoController@getCourseHtml');