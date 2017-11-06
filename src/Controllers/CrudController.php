<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 17:55
 */

namespace Martin25699\Crud\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Martin25699\Crud\Traits\CrudQueries;
use Martin25699\Crud\Traits\CrudResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CrudController extends Controller
{
    use CrudResponse,CrudQueries;

    const _MODEL = 'model';
    const _ID = 'id';

    /**
     * @var \Martin25699\Crud\ModelCrud | \Illuminate\Database\Eloquent\Model
     */
    protected $crudModel;
    protected $id;

    public function __construct(Request $request)
    {
        if(!!$request->route()) {
            $routeParams = $request->route()->parameters;

            $codeModel = config('crud.model',self::_MODEL);
            $model = (array_key_exists($codeModel, $routeParams)) ? $routeParams[$codeModel] : null;
            $this->setModel($model);

            $codeID = config('crud.id',self::_ID);
            $id = (array_key_exists($codeID, $routeParams)) ? $routeParams[$codeID] : null;
            $this->setID($id);
        }
    }

    /**
     * Возвращает список элементов модели
     * @param Request $request
     * Query example
     * Устанавливаем выборку полей
     * ? fields[]=field & fields[]=relation & fields[]=relation.field
     * Фильтруем записи текущей модели
     * & filters[]=field:val
     * Фильтруем записи в отношении
     * & filters[]=relation.field:val
     * Фильтруем записи текущей модели, отбираем только те что содежат в отношениях требуемое поле
     * & has[]=relation.field:val
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->getFieldsQuery($request->all(),$this->crudModel);
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
        $this->validateRequest($request, $this->crudModel->validatorStore);
        $item = $this->crudModel->create($request->all());
        return $this->setMessage(trans('crud::crud.messages.create_item'))->setData($item)->response();
    }

    /**
     * Получить элемент из БД
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $this->getFieldsQuery($request->all(),$this->crudModel);
        $item = $this->crudModel->find($this->id);
        return $this->setMessage(trans('crud::crud.messages.show_item'))->setData($item)->response();
    }

    /**
     * Обновляет элемент в БД
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $this->validateRequest($request, $this->crudModel->validatorUpdate);
        $item = $this->crudModel->find($this->id);
        $item->update($request->all());
        return $this->setMessage(trans('crud::crud.messages.update_item'))->setData($item)->response();
    }

    /**
     * Удаление элемента в БД
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        $item = $this->crudModel->find($this->id);
        $result = $item->delete();
        return $this->setMessage(trans('crud::crud.messages.delete_item'))->setData($result)->response();
    }


    /**
     * Проверяет имя модели, если существует создает экземпляр модели
     * @param $model
     * @return $this
     */
    private function setModel($model)
    {
        $modelName = 'App\\'.title_case($model);
        if (!class_exists($modelName)) {
            $this->responseError(trans('crud::crud.errors.missing_model',['model' => $modelName]), null,Response::HTTP_BAD_REQUEST);
        }
        $this->crudModel = new $modelName();
        return $this;
    }

    private function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    private function validateRequest($request, $params)
    {
        $validator = Validator::make($request->all(), $params);
        if ($validator->fails()) {
            $this->responseError(trans('crud::crud.errors.validate'), $validator->errors()->messages(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this;
    }
}