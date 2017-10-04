<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 17:55
 */

namespace Martin25699\Crud\Controllers;

use Illuminate\Support\Facades\Validator;
use Martin25699\Crud\Traits\CrudResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CrudController extends Controller
{
    use CrudResponse;

    /**
     * @var \Martin25699\Crud\ModelCrud | \Illuminate\Database\Eloquent\Model
     */
    protected $crudModel;

    public function __construct(Request $request)
    {
        $model = $request->route()->parameters['model'];
        $this->createModel($model);
    }

    /**
     * Возвращает список элементов модели
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return $this->setData($this->crudModel->get())
            ->response();
    }

    /**
     * Создает элемент в БД
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->crudModel->validatorStore);
        if ($validator->fails()) {
            $this->responseError(trans('crud::crud.create_item_error'),$validator->errors()->messages());
        }
        $item = $this->crudModel->create($request->all());
        return $this->setMessage(trans('crud::crud.create_item'))->setData($item)->response();
    }


    /**
     * Проверяет имя модели, если существует создает экземпляр модели
     * @param $model
     * @return $this
     */
    protected function createModel($model)
    {
        $modelname = 'App\\'.title_case($model);
        if (!class_exists($modelname)) {
            $this->responseError(trans('crud::crud.missing_model',['model'=>$modelname]), null,400);
        }
        $this->crudModel = new $modelname();
        return $this;
    }
}