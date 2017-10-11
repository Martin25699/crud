<?php

// LaravelNews/CallRequest - общий неймспейс нашего пакета

Route::group([ 'prefix'=>'crud',
    //'middleware' => ['web'],
    'namespace' => 'Martin25699\Crud\Controllers'], function() {
    Route::get('{model}', 'CrudController@index')->name('crud.index');
    Route::post('{model}', 'CrudController@store')->name('crud.store');
    Route::get('{model}/{id}', 'CrudController@show')->name('crud.show');
    Route::put('{model}/{id}', 'CrudController@update')->name('crud.update');
    Route::delete('{model}/{id}', 'CrudController@destroy')->name('crud.destroy');
});