<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 06.10.17
 * Time: 14:42
 */
namespace Martin25699\Crud\Traits;

trait CrudQueries
{
    /**
     * @param $requestFields
     * @param $model
     */
    private function getFieldsQuery($requestFields, &$model)
    {
        $queryParams = [];
        if($requestFields){
            foreach ($requestFields as $field)
            {
                $this->refactoringFields($field,$queryParams,$model);
            }
        }
        $this->checkQueryParams($queryParams,$model);
        $this->buildQuery($queryParams,$model);
    }

    /**
     * @param $field
     * @param $params
     * @param $model
     */
    private function refactoringFields($field, &$params, $model)
    {
        $_field = explode('.',$field,2);
        $column = $_field[0];
        /**
         * Если такой метод есть то по умолчанию он является сводной таблицей
         */
        if (method_exists($model, $column))
        {
            if(isset($_field[1]))
            {
                !isset($params['pivots'][$column]) ? $params['pivots'][$column] = [] : null;
                /**
                 * Если ранее был уже добавлен этот пивот без дочерних элементов, удаляем его
                 */
                if(($k = array_search($column,$params['pivots'])) !== false)
                {
                    unset($params['pivots'][$k]);
                }
                $this->refactoringFields($_field[1],$params['pivots'][$column],$model->$column()->getRelated());
            } else if(!isset($params['pivots'][$column])) {
                /**
                 * Добавляем пивот в запрос, при условии что у него нет дочек
                 */
                $params['pivots'][] = $column;
            }
        }
        else
        {
            $params['fields'][] = $column;
        }
    }

    /**
     * @param $params
     * @param $model - Текущая модель
     */
    private function checkQueryParams(&$params,$model)
    {
        /**
         * Если запрашиваемых поелй нет, то ставим метку о выводе всех полей
         */
        if(!isset($params['fields']))
        {
            $params['fields'] = '*';
        }
        /**
         * Если есть сводные таблицы
         * Надо проверить добавлены ли в запрос поля необходимые для вывода данных сводных таблиц
         */
        if(isset($params['pivots']))
        {
            /**
             * Перебираем все сводные поля
             */
            foreach ($params['pivots'] as $key => &$pivot)
            {
                /**
                 * Определяем что является моделью ключ или содержимое
                 * если содержимое значит пивот содержит дочерние элементы или информацию о определенных полях
                 */
                $_pivot_modelN = is_int($key) ? $pivot : $key;
                /**
                 * Сводная таблица
                 */
                $_pivot_model = $model->$_pivot_modelN();
                /**
                 * Если поля текущей модели определенны вручную
                 * Надо проверить содержатся ли там поля необходимые
                 * для вывода данных зависимой таблицы
                 */
                if(is_array($params['fields']) and method_exists($_pivot_model, 'getQualifiedParentKeyName'))
                {
                    $parentKey = explode('.',$_pivot_model->getQualifiedParentKeyName())[1];
                    if (!in_array($parentKey,$params['fields']))
                        $params['fields'][] = $parentKey;
                }
                /**
                 * Если текущая зависимой таблица в текущей модели содержит определенные поля
                 * То надо проверит что бы сводной таблице было выбранно поле которое является связанным с моделью
                 */
                if(isset($pivot['fields']) and method_exists($_pivot_model, 'getQualifiedForeignKeyName'))
                {
                    $foreignKey = explode('.',$_pivot_model->getQualifiedForeignKeyName())[1];
                    if (!in_array($foreignKey,$pivot['fields']))
                        $pivot['fields'][] = $foreignKey;
                }
                /**
                 * Если текущая зависимой таблица содержит дочерние зависимые таблицы до запускаем данный скрипт для нее
                 */
                if(is_array($pivot))
                    $this->checkQueryParams($pivot,$_pivot_model->getRelated());
            }
        }
    }

    /**
     * @param $params  = [
            "fields" => [ "foo", "bar" ]
            "pivots" => [
                "table",
                "table" => [
                    "fields" => [ "foo","bar"],
                        "pivots" => [
                            "table",
                            "table" => ["fields" => [ "foo","bar"]
                        ]
                    ]
                ]
            ]
        ]
     * @param $query
     */
    private function buildQuery($params, &$query){
        foreach ($params as $key => $param) {
            switch ($key)
            {
                case 'fields':
                    $query = $query->select($param);
                    break;
                case 'pivots':
                    $with = [];
                    foreach ($param as $i => $pivot)
                    {
                        if (is_string($pivot))
                            $with[] = $pivot;
                        else
                            $with[$i] = function ($_query) use ($pivot){
                                $this->buildQuery($pivot,$_query);
                            };
                    }
                    $query = $query->with($with);
                    break;
            }
        }
    }
}