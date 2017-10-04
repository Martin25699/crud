<?php

// LaravelNews/CallRequest - общий неймспейс нашего пакета

Route::group(array('prefix'=>'crud', 'middleware' => ['web']), function() {
    Route::get('', function (){
        dd('curd');
    });
});