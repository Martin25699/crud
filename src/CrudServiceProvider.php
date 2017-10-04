<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 18:14
 */
namespace Martin25699\Crud;

use Illuminate\Support\ServiceProvider as ServiceProvider;

class CrudServiceProvider extends ServiceProvider {

    public function boot()
    {

        //Указываем что пакет должен опубликовать при установке
        $this->publishes([__DIR__ . '/../config/' => config_path() . "/"], 'config');

        // Routing
        if (! $this->app->routesAreCached()) {
            include __DIR__ . 'routes/.php';
        }

        //Указывам где искать вью и какой неймспейс им задать
        $this->loadViewsFrom(__DIR__.'/../views', 'call-request');

    }

    public function register()
    {

    }

}