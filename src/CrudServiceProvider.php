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

        $this->loadTranslationsFrom(__DIR__ . '/../lang','crud');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    public function register()
    {

    }

}