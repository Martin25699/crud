<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 04.10.17
 * Time: 20:00
 */

trait CrudResponse
{
    public $status = 200;
    public $message = null;
    public function response(){
        return response()->json([
            'status' => $this->status,
            'message' => $this->message
        ]);
    }
}