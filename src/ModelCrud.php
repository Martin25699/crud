<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 18:44
 */
namespace Martin25699\Crud;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelCrud
 * @package Martin25699\Crud
 */
class ModelCrud extends Model
{
    /**
     * @var array
     */
    protected $validator = [];

    /**
     * @var array
     */
    public $validatorStore = false;

    /**
     * @var array
     */
    public $validatorUpdate = false;

    public function __construct(array $attributes = [])
    {
        if (!$this->validatorStore) $this->validatorStore = $this->validator;
        if (!$this->validatorUpdate) $this->validatorUpdate = $this->validator;
        parent::__construct($attributes);
    }
}
