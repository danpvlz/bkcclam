<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('grantpermission/{id}', 'AuthController@powerBiGrantPermission');
Route::get('denypermission/{id}', 'AuthController@powerBiDenyPermission');
Route::get('/searchRuc/{ruc}', 'ClienteController@searchRuc');
Route::get('/course/{id}', 'FormacionYDesarrollo\CursoController@show');
Route::post('/externalInscription', 'FormacionYDesarrollo\IncripcionController@externalInscription');
Route::post('/iziini', 'IziPayController@init');
Route::post('/ipn', 'IziPayController@ipn');

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signUp');

    Route::post('/sectorFilterData', 'Associated\SectorController@filterData');
    Route::post('/comiteFilterData', 'Associated\ComiteGremialController@filterData');
    Route::post('/promotorFilterData', 'Associated\PromotorController@filterData');
    Route::post('/newafiliation', 'Associated\AssociatedController@store');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'AuthController@logout');
        Route::get('user', 'AuthController@user');
        Route::post('updatepassword', 'AuthController@updatePassword');
    });
});



Route::group([
    'middleware' => 'auth:api'
], function () {
    Route::get('routes', 'RouteController@index');
    Route::apiResource('/course', 'FormacionYDesarrollo\CursoController');
    Route::post('/courseList', 'FormacionYDesarrollo\CursoController@list');
    Route::get('/courseStatus/{id}', 'FormacionYDesarrollo\CursoController@changeState');
    Route::post('/courseUpdate/{id}', 'FormacionYDesarrollo\CursoController@update');
    Route::post('/courseDelete/{id}', 'FormacionYDesarrollo\CursoController@destroy');
    Route::apiResource('/participant', 'FormacionYDesarrollo\ParticipanteController');
    Route::post('/participantList', 'FormacionYDesarrollo\ParticipanteController@list');
    Route::post('/participantUpdate/{id}', 'FormacionYDesarrollo\ParticipanteController@update');
    Route::post('/participantDelete/{id}', 'FormacionYDesarrollo\ParticipanteController@destroy');
    Route::apiResource('/inscription', 'FormacionYDesarrollo\IncripcionController');
    Route::post('/inscriptionList', 'FormacionYDesarrollo\IncripcionController@list');
    Route::post('/inscriptionUpdate/{id}', 'FormacionYDesarrollo\IncripcionController@update');
    Route::post('/inscriptionDelete/{id}', 'FormacionYDesarrollo\IncripcionController@destroy');
    Route::post('/inscriptionsExport', 'FormacionYDesarrollo\IncripcionController@export');
    
    Route::apiResource('/worker', 'ColaboradorController');
    Route::post('/worker/{id}', 'ColaboradorController@updateworker');
    Route::get('/worker/status/{id}', 'ColaboradorController@status');
    Route::get('/worker/resetPassword/{id}', 'ColaboradorController@resetPassword');
    Route::get('/worker/vacations/{id}', 'ColaboradorController@vacations');
    Route::get('/getMyFolders', 'ColaboradorController@getFolders');
    Route::post('/saveFolder', 'ColaboradorController@saveFolder');
    Route::post('/saveContentOfFolder', 'ColaboradorController@saveContentOfFolder');
    
    Route::apiResource('/assistance', 'AsistenciaController');
    Route::post('/assistance/store', 'AsistenciaController@store');
    Route::post('/assistance/justify', 'AsistenciaController@justify');
    Route::post('/assistance/detail', 'AsistenciaController@listDetail');
    Route::post('/assistance/assistance', 'AsistenciaController@listAssistance');
    Route::post('/assistance/assistancebyworker', 'AsistenciaController@listAssistanceByWorker');
    Route::post('/assistance/mytodayassistance', 'AsistenciaController@listMyTodayAssistance');
    Route::post('/assistance/myassistance', 'AsistenciaController@listMyAssistance');
    Route::post('/assistance/myassistanceDetail', 'AsistenciaController@listMyAssistanceDetail');
    Route::post('/assistance/indicators', 'AsistenciaController@showMyIndicators');
    Route::post('/assistance/indicatorsall', 'AsistenciaController@showAllIndicators');

    Route::apiResource('/associated', 'Associated\AssociatedController');
    Route::get('/associateddelete/{id}', 'Associated\AssociatedController@destroy');
    Route::post('/associatedupdate/{id}', 'Associated\AssociatedController@askForUpdate');
    Route::post('/associatedAcceptUpdate/{id}', 'Associated\AssociatedController@update');
    Route::get('/associatedstatus/{id}', 'Associated\AssociatedController@status');
    Route::get('/associatedpreactive/{id}', 'Associated\AssociatedController@preactive');
    Route::get('/associatededit/{id}', 'Associated\AssociatedController@edit');
    Route::post('/associated/listafiliations', 'Associated\AssociatedController@listAfiliations');
    Route::post('/associated/list', 'Associated\AssociatedController@listAssociated');
    Route::post('/associatedassigncode', 'Associated\AssociatedController@setCodigo');
    Route::post('/associated/indicators', 'Associated\AssociatedController@showIndicators');
    Route::post('/associated/export', 'Associated\AssociatedController@export');
    Route::post('/associatedWeekCalendar', 'Associated\AssociatedController@listWeekCalendar');
    Route::post('/associatedMonthCalendar', 'Associated\AssociatedController@listMonthCalendar');
    
    Route::apiResource('/phonecalls', 'PhoneCallsController');
    Route::post('/phonecalls/list', 'PhoneCallsController@listPhoneCalls');
    Route::post('/phonecalls/export', 'PhoneCallsController@export');
    
    Route::apiResource('/service', 'ServiceController');
    Route::post('/service/list', 'ServiceController@list');
    
    Route::apiResource('/bill', 'CuentaController');
    Route::post('/billUpdatePay/{id}', 'CuentaController@updatePay');
    Route::post('/bill/list', 'CuentaController@listBills');
    Route::post('/listbysector', 'CuentaController@listBySector');
    Route::post('/listpendings', 'CuentaController@listPendientes');
    Route::post('/listmemberships', 'CuentaController@listMemberships');
    Route::post('/pendingsindicators', 'CuentaController@pendingsIndicators');
    Route::post('/billsindicators', 'CuentaController@showIndicators');
    Route::post('/billsexport', 'CuentaController@export');
    Route::post('/billsdetailexport', 'CuentaController@exportDetailBills');
    Route::post('/pendingsexport', 'CuentaController@exportPendings');
    Route::post('/membershipsexport', 'CuentaController@exportMemberships');
    Route::post('/showcomprobante', 'CuentaController@showComprobante');
    Route::post('/savecomprobante', 'CuentaController@saveComprobante');
    Route::post('/annulment', 'CuentaController@annulmentComprobante');
    Route::post('/pay', 'CuentaController@payComprobante');
    Route::post('/detailbill', 'CuentaController@detailBill');
    Route::post('/billslistrepeated', 'CuentaController@listRepeated');
    Route::post('/billDashboard', 'CuentaController@loadDashboard');
    Route::get('/toPending/{id}', 'CuentaController@restoreToPending');
    Route::get('/probandoPay', 'CuentaController@probandoPay');
    
    
    Route::apiResource('/cliente', 'ClienteController');
    Route::post('/clienteUpdate/{id}', 'ClienteController@update');
    Route::apiResource('/concepto', 'ConceptoController');
    Route::post('/conceptoUpdate/{id}', 'ConceptoController@update');
    Route::post('/listconcepto', 'ConceptoController@list');

    Route::apiResource('/caja', 'CajaController');
    Route::post('/caja/list', 'CajaController@listBills');
    Route::get('/cajaAnul/{id}', 'CajaController@anularCajaCuenta');
    Route::post('/cajaPay', 'CajaController@pay');
    Route::post('/cajaIndicators', 'CajaController@showIndicators');
    Route::post('/cajaDashboard', 'CajaController@loadDashboard');
    Route::post('/byAreaDashboard', 'CajaController@loadDashboardByArea');
    Route::post('/cliente/list', 'ClienteController@list');
    Route::post('/billsexport108', 'CajaController@export');
    Route::post('/billsdetailexport108', 'CajaController@exportDetailBills');

    Route::post('/rcList', 'ReservaConceptoController@list');
    Route::post('/rcListWeek', 'ReservaConceptoController@listWeek');
    Route::post('/rcListMonth', 'ReservaConceptoController@listMonth');
    Route::post('/rcStore', 'ReservaConceptoController@store');
    Route::post('/rcCheckIn', 'ReservaConceptoController@generarComprobante');
    Route::post('/confirmCheckIn', 'ReservaConceptoController@confirmarCheckIn');
    

    
    //Filters
    Route::post('/workerFilterData', 'ColaboradorController@filterData');
    Route::post('/associatedFilterData', 'Associated\AssociatedController@filterData');
    Route::post('/clienteFilterData', 'ClienteController@filterData');
    Route::post('/conceptoFilterData', 'ConceptoController@filterData');
    Route::post('/filterAreas', 'ConceptoController@filterAreas');
    Route::get('/filterCategoriaCuenta/{idArea}', 'ConceptoController@filterCategoriaCuenta');
    Route::post('/filterParticipants', 'FormacionYDesarrollo\ParticipanteController@filterData');
    Route::post('/filterCursos', 'FormacionYDesarrollo\CursoController@filterData');
    Route::post('/filterAmbientes', 'ConceptoController@filterDataAmbientes');
    

    Route::get('kpicheck', 'AuthController@powerBiPass');
    Route::get('askpermission', 'AuthController@powerBiAskPermission');
    Route::get('firebase', 'Firebase\FirebaseController@index');

    
    Route::post('/pagoList', 'PagoController@list');
    Route::post('/pagosExport', 'PagoController@export');
    
    //Searchs
    Route::post('/probando', 'Associated\AssociatedController@probando');
    
    
});
