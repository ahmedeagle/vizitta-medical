<?php


#### Start Authenticated Routes
Route::group(['middleware' => ['CheckManagerToken:manager-api']], function () {

    Route::prefix('{locale}')->middleware(['setAPILocale'])->where(['locale' => '[a-zA-Z]{2}'])->group(function () {




});

