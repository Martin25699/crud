<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 18:44
 */
namespace Martin25699\Crud\Traits;

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
    public $validator = [];

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
        if (!isset($this->validatorStore)) $this->validatorStore = $this->validator;
        if (!isset($this->validatorStore)) $this->validatorUpdate = $this->validator;
    }
}
