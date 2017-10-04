<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 20:00
 */
namespace Martin25699\Crud\Traits;

trait CrudResponse
{
    protected $status = 200;
    protected $data = null;
    protected $message = null;

    /**
     * @param int $code
     * @return $this
     */
    private function setStatus($code){
        $this->status = $code;
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    private function setMessage($message){
        $this->message = $message;
        return $this;
    }

    /**
     * @param array | object $data
     * @return $this
     */
    private function setData($data){
        $this->data = $data;
        return $this;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function response(){
        $data = [
            'status' => $this->status
        ];

        if ($this->message) $data['message'] = $this->message;

        if ($this->data) $data['data'] = $this->data;

        return response()->json($data, $this->status);
    }

    /**
     * @param string $message
     * @param array $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    private function responseError($message = null, $data = null, $status = 500){

        if ($message === null) $message = trans('crud::crud.error');

        $this->setStatus($status);

        $this->setMessage($message);

        $this->setData($data);

        return $this->response()->send();
    }
}