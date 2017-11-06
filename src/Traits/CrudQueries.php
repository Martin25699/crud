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
     * @param $request
     * @param $model
     */
    private function getFieldsQuery($request, &$model)
    {
        $queryParams = [];
        if(isset($request['fields'])){
            foreach ($request['fields'] as $field)
            {
                $this->refactoringFields($field,$queryParams,$model);
            }
        }
        $this->checkQueryParams($queryParams,$model);
        if(isset($request['filters'])) {
            $this->refactoringFilters($request['filters'], $queryParams);
        }
        if(isset($request['has'])) {
            $this->refactoringHas($request['has'], $queryParams);
        }
        $this->buildQuery($queryParams,$model);
    }

    /**
     * Формируем данные для выборки полей (SELECT `field`)
     * @param $field = [field, relation.field, relation.relation.field ]
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
     * Тут мы просто проверяем поля, и если есть зависимые таблицы в выборке,
     * то проверяем есть ли там ключ который нужен для вывода данных этой таблицы
     * @param $params = [fields => [ field,... ], pivots=> [ relation => [ fields => [field,field...] ]]]
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
     * Тут мы обрабатываем поля фильтрации (WHERE foo=2 and bar=2), устанавливаем споб выборки по умолчанию '='
     * Допустим 'relation.field:val' -> поле relation.field, значение val, способ '='
     * Или допустим 'relation.field:>$val' -> поле relation.field, значение val, способ '>'
     * @param $filters = ['field:val','relation.field:val']
     * @param $queryParams
     */
    private function refactoringFilters($filters, &$queryParams)
    {
        foreach ($filters as $filterStr) {
            $filter = explode(':',$filterStr);
            $pathFilter = $filter[0];
            if((count($valWithOp = explode('$',$filter[1],2))) === 2)
            {
                $operator = strlen($valWithOp[0])!==0 ? $valWithOp[0] : '=';
                $val = $valWithOp[1];
            } else {
                $operator = '=';
                $val = $filter[1];
            }
            $pathFieldArr = explode('.',$pathFilter);
            /**
             * Далее уже готовим сами фильтры, отдельная функция потому что надо обрабатывать отношения (relation)
             * а как сделать их в этой функция до меня не дошло
             */
            $this->setQueryFilter($pathFieldArr,$val,$operator,$queryParams);
        }
    }

    /**
     * @param $pathFieldArr = Путь до фильтруемого поля [relation,relation,field] ||  [relation,field] ||  [field]
     * Если отношение не было указано в fields параметре запроса, оно будет добавлено автоматически
     * @param $val
     * @param $operator
     * @param $queryParams
     */
    private function setQueryFilter($pathFieldArr, $val, $operator, &$queryParams)
    {
        $total = count($pathFieldArr);
        $item = array_shift($pathFieldArr);
        if($total !== 1){
            if(!isset($queryParams['pivots'][$item])) {
                if(isset($queryParams['pivots']) && ($_k = array_search($item,$queryParams['pivots'])) !== false)
                {
                    unset($queryParams['pivots'][$_k]);
                }
                $queryParams['pivots'][$item] = [];
            }
            $this->setQueryFilter($pathFieldArr,$val,$operator,$queryParams['pivots'][$item]);
        } else {
            $queryParams['filters'][] = [$item,$operator,$val];
        }
    }

    /**
     * Формируем данные для фильтрации (WHERE foo=2 and bar=2)
     * @param $filters = ['foo:2','bar:2']
     * @param $queryParams
     */
    private function refactoringHas($filters, &$queryParams)
    {
        foreach ($filters as $filterStr) {
            $filter = explode(':',$filterStr);
            $pathFilter = $filter[0];
            if((count($valWithOp = explode('$',$filter[1],2))) === 2)
            {
                $operator = strlen($valWithOp[0])!==0 ? $valWithOp[0] : '=';
                $val = $valWithOp[1];
            } else {
                $operator = '=';
                $val = $filter[1];
            }
            $pathFieldArr = explode('.',$pathFilter);
            /**
             * Далее уже готовим сами фильтры, отдельная функция потому что надо обрабатывать отношения (relation)
             * а как сделать их в этой функция до меня не дошло
             */
            $this->setQueryHas($pathFieldArr,$val,$operator,$queryParams);
        }
    }

    /**
     * @param $pathField = Path has field [relation,relation,field] ||  [relation,field]
     * Если отношение не было указано в fields параметре запроса, оно будет добавлено автоматически
     * @param $val
     * @param $operator
     * @param $queryParams
     */
    private function setQueryHas($pathField, $val, $operator, &$queryParams)
    {
        $total = count($pathField);
        /**
         * Если массив из двух элементов то глубже уже не надо, записываем данные в конфиг выборки
         * если иначе, значит поле по которому отбирать находится глубже следующей таблице отношений (relation)
         */
        if($total !== 2){
            $item = array_shift($pathField);
            if(!isset($queryParams['pivots'][$item])) {
                if(isset($queryParams['pivots']) && ($_k = array_search($item,$queryParams['pivots'])) !== false)
                {
                    unset($queryParams['pivots'][$_k]);
                }
                $queryParams['pivots'][$item] = [];
            }
            $this->setQueryHas($pathField,$val,$operator,$queryParams['pivots'][$item]);
        } else {
            $queryParams['has'][$pathField[0]] = [$pathField[1],$operator,$val];
        }
    }

    /**
     * @param $params  = [
            "fields" => [ "foo", "bar" ]
            "filters" => [
                [ column, operator, value], ...
            ],
            "has" => [
                relation => [ column, operator, value  ]
            ]
            "pivots" => [
                "relation",
                "relation" => [
                    "filters" => [
                        [ column, operator, value], ...
                    ],
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
                case 'filters':
                    $query = $query->where($param);
                    break;
                case 'has':
                    foreach ($param as $i => $has)
                    {
                        $query->whereHas($i,function ($query) use ($has){
                            $table = $query->getModel()->getTable();
                            $query->where($table.'.'.$has[0],$has[1],$has[2]);
                        });
                    }
                    break;
                case 'pivots':
                    $with = [];
                    foreach ($param as $i => $pivot)
                    {
                        if (is_string($pivot))
                            $with[$pivot] = function ($_query) {
                                $_query->accessCrud();
                            };
                        else
                            $with[$i] = function ($_query) use ($pivot){
                                $this->buildQuery($pivot,$_query);
                            };
                    }
                    $query = $query->with($with);
                    break;
            }
        }
        $query = $query->accessCrud();
    }
}