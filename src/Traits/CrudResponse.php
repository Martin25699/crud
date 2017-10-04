<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 20:00
 */
namespace Martin25699\Crud\Traits;

use Symfony\Component\HttpFoundation\Response;

trait CrudResponse
{
    protected $status = Response::HTTP_OK;
    protected $data = null;
    protected $errors = null;
    protected $message = null;

    /**
     * @param null $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
        return $this;
    }

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

        if ($this->errors) $data['errors'] = $this->errors;

        return response()->json($data, $this->status);
    }

    /**
     * @param string $message
     * @param array $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    private function responseError($message = null, $data = null, $status = Response::HTTP_INTERNAL_SERVER_ERROR){

        if ($message === null) $message = trans('crud::crud.errors.default');

        $this->setStatus($status);

        $this->setMessage($message);

        $this->setErrors($data);

        return $this->response()->send();
    }
}