<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('grantpermission/{id}', 'AuthController@powerBiGrantPermission');
Route::get('denypermission/{id}', 'AuthController@powerBiDenyPermission');
Route::get('/searchRuc/{ruc}', 'ClienteController@searchRuc');
Route::get('/sruckap/{ruc}', 'ClienteController@searchRucKAP');
Route::post('/pendingmemberships', 'MembresiaController@membresiasPendientes');
Route::get('/course/{id}', 'FormacionYDesarrollo\CursoController@show');
Route::get('/externalcourselist', 'FormacionYDesarrollo\CursoController@listForWeb');
Route::get('/externalnewslist', 'NoticiaController@listForWeb');
Route::post('/externalnewget', 'NoticiaController@getForWeb');
Route::get('/externalpronouncementlist', 'PronunciamientoController@listForWeb');
Route::get('/externaldigitalmagazinelist', 'RevistaDigitalController@listForWeb');
Route::post('/externaldigitalmagazineget', 'RevistaDigitalController@getForWeb');
Route::get('/externalactivitylist', 'ActividadController@listForWeb');
Route::post('/externalInscription', 'FormacionYDesarrollo\IncripcionController@externalInscription');
Route::post('/iziiniweb', 'IziPayController@initweb');
Route::post('/iziini', 'IziPayController@init');
Route::post('/ipn', 'IziPayController@ipn');
Route::post('/listconceptos', 'ConceptoController@listConceptosPublicWeb');
Route::get('/getConceptoPay/{idC}', 'ConceptoController@getConceptoPublicWeb');
Route::post('/checkDcto', 'DescuentoController@checkIfDescuento');
Route::post('/saveorderonline', 'PedidoController@savePedidoWeb');
Route::post('/paypedidoweb', 'PedidoController@payPedido');
Route::post('/sendMailResultsKap', 'KapController@sendMailResultsKap');

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
    Route::post('/associated/exportcobertura', 'Associated\AssociatedController@exportCobertura');
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
    Route::post('/generateNCC', 'CuentaController@generateNC');
    Route::post('/billslistrepeated', 'CuentaController@listRepeated');
    Route::post('/billDashboard', 'CuentaController@loadDashboard');
    Route::get('/toPending/{id}', 'CuentaController@restoreToPending');
    Route::get('/probandoPay', 'CuentaController@probandoPay');
    Route::post('/listPendings109', 'CuentaController@listPendings');
    Route::post('/sendmailcuenta', 'CuentaController@sendMail');
    
    
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
    Route::post('/listPendings108', 'CajaController@listPendings');
    Route::post('/generateNCCj', 'CajaController@generateNC');
    Route::post('/sendmailcaja', 'CajaController@sendMail');
    

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

    
    Route::post('/pagoList', 'PagoController@list');
    Route::post('/pagosExport', 'PagoController@export');
    
    Route::post('/noticialist', 'NoticiaController@list');
    Route::post('/noticiastore', 'NoticiaController@store');
    Route::post('/noticiadelete', 'NoticiaController@delete');
    Route::post('/noticiashow', 'NoticiaController@show');
    Route::post('/noticiaupdate/{id}', 'NoticiaController@update');
    
    Route::post('/pronunciamientolist', 'PronunciamientoController@list');
    Route::post('/pronunciamientostore', 'PronunciamientoController@store');
    Route::post('/pronunciamientodelete', 'PronunciamientoController@delete');
    Route::post('/pronunciamientoshow', 'PronunciamientoController@show');
    Route::post('/pronunciamientoupdate/{id}', 'PronunciamientoController@update');
    
    Route::post('/revistadigitallist', 'RevistaDigitalController@list');
    Route::post('/revistadigitalstore', 'RevistaDigitalController@store');
    Route::post('/revistadigitaldelete', 'RevistaDigitalController@delete');
    Route::post('/revistadigitalshow', 'RevistaDigitalController@show');
    Route::post('/revistadigitalupdate/{id}', 'RevistaDigitalController@update');
    
    Route::post('/actividadlist', 'ActividadController@list');
    Route::post('/actividadstore', 'ActividadController@store');
    Route::post('/actividaddelete', 'ActividadController@delete');
    Route::post('/actividadshow', 'ActividadController@show');
    Route::post('/actividadupdate/{id}', 'ActividadController@update');
    
    //Searchs
    Route::get('/sruc/{ruc}', 'ClienteController@searchRucIntern');
    Route::get('/sdni/{dni}', 'ClienteController@searchDniIntern');
    Route::post('/probando', 'Associated\AssociatedController@probando');
    Route::post('/getNextNum', 'CuentaController@getNumComprobante');
    
});
