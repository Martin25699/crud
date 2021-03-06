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

    public function getValidatorStore()
    {
        if(isset($this->validatorStore))
        {
            return $this->validatorStore;
        }
        return $this->getValidatorDefault();
    }

    public function getValidatorUpdate()
    {
        if(isset($this->validatorUpdate))
        {
            return $this->validatorUpdate;
        }
        return $this->getValidatorDefault();
    }

    public function getValidatorDefault()
    {
        if(isset($this->validator))
        {
            return $this->validator;
        }
        return [];
    }
}