<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 19.10.17
 * Time: 15:10
 */
namespace Martin25699\Crud\Traits;

trait CrudModel
{
    public function scopeAccessCrud($query)
    {
        if(method_exists ( $this , 'accessCrudQuery' ))
        {
            return $this->accessCrudQuery($query);
        }
        return $query;
    }
}