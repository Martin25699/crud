<?php

// LaravelNews/CallRequest - общий неймспейс нашего пакета

Route::group([ 'prefix'=>'crud',
    //'middleware' => ['web'],
    'namespace' => 'Martin25699\Crud\Controllers'], function() {
    Route::get('{model}', 'CrudController@index')->name('{model}.index');
    Route::post('{model}', 'CrudController@store')->name('{model}.store');
    Route::get('{model}/{id}', 'CrudController@show')->name('{model}.show');
    Route::put('{model}/{id}', 'CrudController@update')->name('{model}.update');
    Route::delete('{model}/{id}', 'CrudController@destroy')->name('{model}.destroy');
});